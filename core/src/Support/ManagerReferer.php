<?php declare(strict_types=1);

namespace EvolutionCMS\Support;

/**
 * The referer check of the manager (`validate_referer`).
 *
 * A request passes when its Referer starts with the site URL. Without a Referer, a GET passes and
 * any other method is refused - unless the browser vouches for it with `Sec-Fetch-Site: same-origin`,
 * which no Referrer-Policy removes: a proxy or host that sends `Referrer-Policy: no-referrer` would
 * otherwise get every manager POST refused, the login form first. A foreign Referer is refused
 * whatever else the request says.
 *
 * The site URL takes its scheme from what PHP sees (HTTPS, HTTPS_PORT, X-Forwarded-Proto). Behind a
 * proxy that ends TLS without saying so, that is http while the browser's Referer says https; the
 * "Server type" setting (`server_protocol` = https) declares the site https, so the https form of the
 * site URL counts as well. It never makes http count for an https site.
 *
 * @since 3.5.9
 */
final class ManagerReferer
{
    /**
     * @param array<string, mixed> $server A `$_SERVER`-shaped array.
     */
    public static function allows(array $server, string $siteUrl, string $serverProtocol = 'http'): bool
    {
        $referer = $server['HTTP_REFERER'] ?? null;

        if (is_string($referer) && $referer !== '') {
            foreach (self::siteUrls($siteUrl, $serverProtocol) as $url) {
                if (stripos($referer, $url) === 0) {
                    return true;
                }
            }

            return false;
        }

        if (strtolower((string)($server['HTTP_SEC_FETCH_SITE'] ?? '')) === 'same-origin') {
            return true;
        }

        // an empty Referer header was refused even on GET; only an absent one lets a GET through
        return $referer === null && strtoupper((string)($server['REQUEST_METHOD'] ?? 'GET')) === 'GET';
    }

    /**
     * @return list<string>
     */
    public static function siteUrls(string $siteUrl, string $serverProtocol = 'http'): array
    {
        $urls = [$siteUrl];
        if (strtolower(trim($serverProtocol)) === 'https' && stripos($siteUrl, 'http://') === 0) {
            $urls[] = 'https://' . substr($siteUrl, 7);
        }

        return $urls;
    }
}
