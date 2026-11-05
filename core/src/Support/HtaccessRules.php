<?php declare(strict_types=1);

namespace EvolutionCMS\Support;

/**
 * The rules of ht.access that keep private files private.
 *
 * A site's .htaccess belongs to its operator and no update touches it, so a file laid by an older
 * release keeps working without the rules added since. They are only compared and reported.
 *
 * @since 3.5.10
 */
final class HtaccessRules
{
    public const REQUIRED = [
        'RewriteRule ^(core|views|vendor|tmp)(/|$) - [F,L]',
        'RewriteRule ^assets/(cache|backup|export|import)(/|$) - [F,L]',
        'RewriteRule ^(composer\.(json|lock)|phpstan\.neon|publiccode\.yml|AGENTS\.md|README\.md|ht\.access|ng\.inx|config\.php(\.example)?)$ - [F,L]',
        'RewriteRule \.(sql|sqlite|db|log|bak|old|orig|save|swp|swo|tpl|inc|ini|env|dist|example|yml|yaml|lock|tar)(\.(gz|bz2|xz|zip|tgz))?$ - [F,L,NC]',
        'RewriteRule ^assets/.*\.php$ - [F,L,NC]',
    ];

    /**
     * @return list<string> The required rules a .htaccess does not have, commented out ones included.
     */
    public static function missing(string $htaccess): array
    {
        $lines = array_map(
            static fn (string $line): string => (string) preg_replace('/\s+/', ' ', trim($line)),
            preg_split('/\R/', $htaccess) ?: []
        );

        return array_values(array_diff(self::REQUIRED, $lines));
    }
}
