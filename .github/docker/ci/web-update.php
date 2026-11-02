<?php

/*
 * Drives the web side of an update the way an administrator's browser does.
 *
 *   php web-update.php grant  BASE_URL ADMIN PASSWORD COOKIE_JAR
 *       Logs into the Manager and keeps the installer grant it hands an administrator
 *       while install/ exists: what a browser holds when the manager's updater sends it on.
 *   php web-update.php update BASE_URL COOKIE_JAR
 *       Opens install/index.php?action=mode as the updater's redirect does, then submits the
 *       installer's own forms (mode, connection, options, summary, install) as a browser would,
 *       choosing "update" and accepting the licence, until the results page. Exits 1 unless
 *       the results page reports a complete update.
 */

if ($argc < 4) {
    fwrite(STDERR, "usage: web-update.php grant BASE ADMIN PASSWORD JAR | update BASE JAR\n");
    exit(2);
}

[$command, $base] = [$argv[1], rtrim($argv[2], '/')];
$jar = $command === 'grant' ? $argv[5] : $argv[3];

/** @return array{status: int, url: string, body: string, redirects: list<array{int, string}>} */
function request(string $url, string $jar, ?array $post = null): array
{
    $redirects = [];
    for ($hop = 0; $hop < 10; $hop++) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $jar,
            CURLOPT_COOKIEFILE => $jar,
            CURLOPT_HTTPHEADER => ['Accept-Language: en'],
            // Same-origin, as a browser on the site sends it: the Manager refuses a login without one.
            CURLOPT_REFERER => $url,
            CURLOPT_TIMEOUT => 900,
            CURLOPT_HEADER => true,
        ]);
        if ($post !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $response = (string) curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $location = (string) curl_getinfo($curl, CURLINFO_REDIRECT_URL);
        curl_close($curl);
        if ($status < 300 || $status >= 400 || $location === '') {
            return ['status' => $status, 'url' => $url, 'body' => substr($response, $headerSize), 'redirects' => $redirects];
        }
        $redirects[] = [$status, $location];
        // A redirect after a form post is followed with a GET, as browsers do for 302 and 303.
        [$url, $post] = [$location, null];
    }
    throw new RuntimeException("too many redirects from $url");
}

/** The fields form#install_form submits as it stands, and where it posts them. */
function installForm(string $html, string $pageUrl): ?array
{
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $form = $document->getElementById('install_form');
    if (!$form instanceof DOMElement) {
        return null;
    }
    $fields = [];
    foreach ((new DOMXPath($document))->query('.//input|.//select|.//textarea', $form) as $field) {
        $name = $field->getAttribute('name');
        if ($name === '' || $field->hasAttribute('disabled')) {
            continue;
        }
        $type = strtolower($field->getAttribute('type'));
        if (in_array($type, ['checkbox', 'radio'], true) && !$field->hasAttribute('checked')) {
            continue;
        }
        if ($field->nodeName === 'select') {
            $value = '';
            foreach ($field->getElementsByTagName('option') as $index => $option) {
                if ($index === 0 || $option->hasAttribute('selected')) {
                    $value = $option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent;
                }
            }
        } else {
            $value = $field->nodeName === 'textarea' ? $field->textContent : $field->getAttribute('value');
        }
        if (str_ends_with($name, '[]')) {
            $fields[substr($name, 0, -2)][] = $value;
        } else {
            $fields[$name] = $value;
        }
    }
    $action = $form->getAttribute('action');
    $target = preg_match('~^https?://~', $action) ? $action : substr($pageUrl, 0, (int) strrpos(strtok($pageUrl, '?'), '/') + 1) . $action;

    return ['action' => $target, 'fields' => $fields];
}

function say(string $line): void
{
    fwrite(STDOUT, $line . "\n");
}

if ($command === 'grant') {
    [$admin, $password] = [$argv[3], $argv[4]];
    request("$base/manager/", $jar);
    $login = request("$base/manager/index.php?a=0", $jar, ['username' => $admin, 'password' => $password]);
    request("$base/manager/index.php?a=2", $jar);
    $granted = str_contains((string) @file_get_contents($jar), 'evo_installer_grant');
    say("manager login: {$login['status']}, installer grant: " . ($granted ? 'held' : 'missing'));
    exit($granted ? 0 : 1);
}

$page = request("$base/install/index.php?action=mode", $jar);
foreach ($page['redirects'] as [$status, $location]) {
    say("installer redirected ($status) to $location");
}
say("installer opened: {$page['status']} " . $page['url']);

// Each step posts to the next; "update" and the licence are what an administrator chooses.
$choices = ['installmode' => '1', 'chkagree' => '1', 'installdata' => '0', 'language' => 'en'];
for ($step = 0; $step < 8; $step++) {
    if ($page['status'] !== 200) {
        fwrite(STDERR, substr(strip_tags($page['body']), 0, 2000) . "\n");
        throw new RuntimeException("the installer answered {$page['status']} at {$page['url']}");
    }
    if (str_contains($page['body'], 'remove_installer.processor.php')) {
        break;
    }
    $form = installForm($page['body'], $page['url']);
    if ($form === null) {
        fwrite(STDERR, substr(strip_tags($page['body']), 0, 2000) . "\n");
        throw new RuntimeException("no installer form at {$page['url']}");
    }
    $fields = array_replace($form['fields'], array_intersect_key($choices, $form['fields']) + ['installmode' => '1']);
    if (str_contains($form['action'], 'action=summary') || str_contains($form['action'], 'action=install')) {
        $fields['chkagree'] = '1';
    }
    say('submitting ' . $form['action']);
    $page = request($form['action'], $jar, $fields);
}

$text = preg_replace('/\s+/', ' ', strip_tags($page['body']));
$incomplete = stripos($text, 'Update incomplete') !== false || stripos($text, 'FAILED') !== false;
say('results page: ' . ($incomplete ? 'INCOMPLETE' : 'complete'));
if ($incomplete || !str_contains($page['body'], 'remove_installer.processor.php')) {
    fwrite(STDERR, substr($text, 0, 3000) . "\n");
    exit(1);
}
