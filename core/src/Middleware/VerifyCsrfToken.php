<?php namespace EvolutionCMS\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;

/**
 * Rejects Manager requests that do not carry a valid CSRF token.
 *
 * The check fails closed: a request that is subject to verification is rejected when the
 * token is absent, when the session holds no token, or when the two do not match. Earlier
 * releases only compared the token when the request happened to supply one, which meant a
 * cross-site request that simply omitted `_token` was never checked at all.
 *
 * @since 3.5.8
 */
class VerifyCsrfToken
{
    /**
     * Normally safe methods; legacy mutation actions still require a token.
     */
    protected const READ_ONLY_METHODS = ['HEAD', 'OPTIONS'];

    /**
     * Manager actions that change state even when they are reached through GET.
     *
     * The legacy dispatcher routes these to processors - and to page controllers, which are
     * easy to overlook because they carry no processor file - that delete, duplicate, publish,
     * execute or otherwise mutate content straight from the query string. Restricting
     * verification to POST would leave every one of them reachable from a plain `<img>` tag.
     */
    protected const MUTATING_GET_ACTIONS = [
        6,   // delete_content
        21,  // delete_template
        24,  // save_snippet (?disabled= toggle)
        25,  // delete_snippet
        26,  // RefreshSite - publishes/unpublishes pending documents and clears the cache
        52,  // MoveDocument - reparents from $_REQUEST; the UI posts, but GET reaches it too
        54,  // optimize_table
        55,  // empty_table
        61,  // publish_content
        62,  // unpublish_content
        63,  // undelete_content
        64,  // remove_content
        67,  // remove_locks
        79,  // save_htmlsnippet (?disabled= toggle)
        80,  // delete_htmlsnippet
        90,  // DeleteUser - deletes a manager user straight from $_GET
        92,  // web_access_groups - add/remove group couplings from $_REQUEST['operation']
        94,  // duplicate_content
        96,  // duplicate_template
        97,  // duplicate_htmlsnippet
        98,  // duplicate_snippet
        103, // save_plugin (?disabled= toggle)
        104, // delete_plugin
        105, // duplicate_plugin
        109, // save_module (?disabled= toggle)
        110, // delete_module
        111, // duplicate_module
        112, // execute_module
        116, // delete_eventlog
        119, // purge_plugin
        303, // delete_tmplvars
        304, // duplicate_tmplvars
        501, // delete_category
    ];

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->requiresVerification($request) && !$this->tokensMatch($request)) {
            return $this->reject($request);
        }

        return $next($request);
    }

    /**
     * Decides whether the request has to present a token.
     */
    protected function requiresVerification($request): bool
    {
        // Anonymous requests carry no privileges to abuse, and the login form is rendered
        // before a Manager session exists. Verifying them would break the login flow
        // without closing any cross-site vector.
        if (empty($_SESSION['mgrValidated'])) {
            return false;
        }

        $method = strtoupper((string)$request->method());

        if (!in_array($request->getRealMethod(), ['GET', 'HEAD', 'OPTIONS'], true)
            || ($method !== 'GET' && !in_array($method, self::READ_ONLY_METHODS, true))) {
            return true;
        }

        // Explicitly stale tokens must not be ignored even on read-only pages.
        return $request->input('_token', $request->header('X-CSRF-TOKEN')) !== null
            || in_array($this->getActionId($request), self::MUTATING_GET_ACTIONS, true);
    }

    /**
     * Compares the submitted token against the one held in the session.
     */
    protected function tokensMatch($request): bool
    {
        $sessionToken = $_SESSION['_token'] ?? null;
        $requestToken = $this->getRequestToken($request);

        if (!is_string($sessionToken) || $sessionToken === '' || $requestToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * Reads the token from the request body, the query string or the AJAX header.
     */
    protected function getRequestToken($request): string
    {
        // An explicitly empty/malformed field must not fall back to another credential.
        $token = $request->input('_token', $request->header('X-CSRF-TOKEN'));

        return is_string($token) ? $token : '';
    }

    /**
     * Resolves the requested action the same way the Manager dispatcher does.
     *
     * @param \Illuminate\Http\Request $request
     * @return int|null
     */
    protected function getActionId($request)
    {
        $value = $request->query('a', $request->input('a'));

        if (!is_scalar($value)) {
            return null;
        }

        $action = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 2000,
            ],
        ]);

        return $action === false ? null : (int)$action;
    }

    /**
     * Builds the rejection response. Legacy XHR callers require JSON even when
     * their Accept header prefers HTML; ordinary page loads receive plain text.
     */
    protected function reject($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return Response::json(['error' => 'CSRF token mismatch', 'code' => 'csrf_token_mismatch'], 403);
        }

        // Rejected operations, including mutating GET actions, must never be replayed.
        return Response::make(
            'The session token has changed or is missing. Reopen the manager, then review and retry your action. The action was not performed.',
            403,
            ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']
        );
    }
}
