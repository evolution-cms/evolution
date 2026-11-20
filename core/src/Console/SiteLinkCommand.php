<?php namespace EvolutionCMS\Console;

use Illuminate\Console\Command;

/**
 * Link the entry points of a site into a public folder that lies outside it.
 *
 * Hosting accounts serve one folder (public_html) and keep files of their own in it. Installed
 * next to that folder, the CMS stays out of the web root, core/ included, and the public folder
 * only needs links to what a browser opens: the front controller, the manager, assets, and the
 * installer and update.php while they exist. The links stay valid when the code is replaced, so
 * updates through the installer, the manager or make:site keep working; EVO_BASE_PATH is the real
 * folder, whichever path the request came through. ng.inx lists what changes for nginx.
 *
 * @since 3.5.10
 */
class SiteLinkCommand extends Command
{
    protected $signature = 'site:link
                            {path : The public folder the web server serves, e.g. /home/user/public_html}
                            {--absolute : Point the links at absolute paths; relative ones survive a move of both folders}
                            {--force : Replace a link that points elsewhere (never a file or a folder)}
                            {--htaccess : Link .htaccess too, for Apache}';

    protected $description = 'Link the site\'s entry points into a public folder outside it (public_html next to the CMS)';

    public function handle(): int
    {
        $public = $this->resolvePublicPath((string) $this->argument('path'));
        $base = rtrim(str_replace('\\', '/', (string) realpath($this->basePath())), '/');
        if ($public === null) {
            $this->error('The public folder ' . $this->argument('path') . ' does not exist.');

            return self::FAILURE;
        }
        if ($public === $base || str_starts_with($public . '/', $base . '/')) {
            $this->error('The public folder is inside the site; links are for a folder next to it.');

            return self::FAILURE;
        }
        if (str_starts_with($base . '/', $public . '/')) {
            $this->error('The site is inside the public folder already, core/ included; serve it as it is, or move it next to that folder.');

            return self::FAILURE;
        }

        $failed = 0;
        $entries = $this->entries((bool) $this->option('htaccess'));
        foreach ($entries as $name => $target) {
            $failed += $this->linkEntry($public . '/' . $name, $target, $public) ? 0 : 1;
        }
        $this->removeStaleLinks($public, $base, array_keys($entries));

        if ($failed > 0) {
            $this->error($failed . ' links were not made, see above.');

            return self::FAILURE;
        }
        $this->line('Serve ' . $public . ' as the document root. nginx: ng.inx, with the lines for a site outside the web root.');

        return self::SUCCESS;
    }

    /**
     * Link name in the public folder => what it leads to.
     *
     * install/ and update.php are linked while absent: the installer and the updater create
     * them for a while, and a link to nothing answers like a missing file.
     *
     * @return array<string, string>
     */
    public function entries(bool $htaccess = false): array
    {
        $base = rtrim(str_replace('\\', '/', realpath($this->basePath()) ?: $this->basePath()), '/') . '/';
        $manager = rtrim(str_replace('\\', '/', realpath($this->managerPath()) ?: $this->managerPath()), '/');
        $entries = [
            'index.php' => $base . 'index.php',
            basename($manager) => $manager,
            'assets' => $base . 'assets',
            'install' => $base . 'install',
            'update.php' => $base . 'update.php',
        ];
        if ($htaccess) {
            $entries['.htaccess'] = $base . '.htaccess';
        }

        return $entries;
    }

    /**
     * Make one link, or say why not. A file or folder of that name is never touched.
     */
    protected function linkEntry(string $link, string $target, string $public): bool
    {
        $name = substr($link, strlen($public) + 1);
        $value = $this->option('absolute') ? $target : self::relativePath($public, $target);

        if (is_link($link)) {
            $current = (string) readlink($link);
            if ($current === $value || self::sameTarget($link, $current, $target)) {
                $this->line($name . ' -> ' . $current . ' (already linked)');

                return true;
            }
            if (!$this->option('force')) {
                $this->error($name . ' links to ' . $current . '; --force replaces the link.');

                return false;
            }
            if (!$this->replaceLink($value, $link)) {
                $this->error('Could not replace the link ' . $name . ' -> ' . $value . '; it still links to ' . $current . '.');

                return false;
            }
        } elseif (file_exists($link)) {
            $this->error($name . ' is a ' . (is_dir($link) ? 'folder' : 'file') . ' of the public folder; move it away first, nothing was changed.');

            return false;
        } elseif (!$this->createLink($value, $link)) {
            $this->error('Could not create the link ' . $name . ' -> ' . $value
                . (DIRECTORY_SEPARATOR === '\\' ? ' (Windows needs Developer Mode or administrator rights for links).' : '.'));

            return false;
        }
        $this->line($name . ' -> ' . $value . (file_exists($target) ? '' : ' (waits for ' . basename($target) . ')'));

        return true;
    }

    /**
     * Swap a link for a new one without a moment in which the entry point is missing.
     *
     * The new link is made beside the old one and renamed over it; when either step fails, the
     * old link is left as it was.
     */
    protected function replaceLink(string $value, string $link): bool
    {
        $staged = dirname($link) . '/.' . basename($link) . '.site-link-' . getmypid();
        if (is_link($staged)) {
            @unlink($staged);
        }
        if (!$this->createLink($value, $staged)) {
            return false;
        }
        if (!@rename($staged, $link)) {
            @unlink($staged);

            return false;
        }

        return true;
    }

    protected function createLink(string $value, string $link): bool
    {
        return @symlink($value, $link);
    }

    /**
     * The path from a folder to a target, or the target itself when they share no root.
     */
    public static function relativePath(string $from, string $to): string
    {
        $from = explode('/', trim(str_replace('\\', '/', $from), '/'));
        $to = explode('/', trim(str_replace('\\', '/', $to), '/'));
        if (strcasecmp($from[0], $to[0]) !== 0 && str_contains($from[0] . $to[0], ':')) {
            return implode('/', $to);
        }

        $common = 0;
        while (isset($from[$common], $to[$common]) && $from[$common] === $to[$common]) {
            $common++;
        }

        return str_repeat('../', count($from) - $common) . implode('/', array_slice($to, $common));
    }

    /**
     * Remove the links this command made to entry points the site no longer has.
     *
     * A manager renamed with MGR_DIR leaves its old link behind, leading to a folder of the site
     * that is gone. Only such a link goes: one that leads nowhere, into the top of the site, under
     * a name that is not an entry point now. Links elsewhere, and links that work, stay.
     *
     * @param array<int, string> $names Entry points linked now.
     */
    protected function removeStaleLinks(string $public, string $base, array $names): void
    {
        foreach (scandir($public) ?: [] as $name) {
            $link = $public . '/' . $name;
            if (in_array($name, $names, true) || !is_link($link) || file_exists($link)) {
                continue;
            }
            $current = (string) readlink($link);
            $absolute = str_starts_with($current, '/') || preg_match('~^[A-Za-z]:~', $current) === 1;
            if (dirname(self::collapsePath($absolute ? $current : $public . '/' . $current)) !== $base) {
                continue;
            }
            if (@unlink($link) || @rmdir($link)) {
                $this->line($name . ' -> ' . $current . ' removed: the site has no ' . basename($current) . ' any more');
            }
        }
    }

    /**
     * A path with its "." and ".." resolved by name, for a target that does not exist.
     */
    private static function collapsePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = preg_match('~^([A-Za-z]:)?/~', $path, $match) === 1 ? $match[0] : '';
        $parts = [];
        foreach (explode('/', substr($path, strlen($prefix))) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return $prefix . implode('/', $parts);
    }

    private static function sameTarget(string $link, string $current, string $target): bool
    {
        $resolved = realpath(str_starts_with($current, '/') || preg_match('~^[A-Za-z]:~', $current) === 1
            ? $current
            : dirname($link) . '/' . $current);

        return $resolved !== false && $resolved === realpath($target);
    }

    private function resolvePublicPath(string $path): ?string
    {
        $real = realpath($path);

        return $real === false || !is_dir($real) ? null : rtrim(str_replace('\\', '/', $real), '/');
    }

    protected function basePath(): string
    {
        return EVO_BASE_PATH;
    }

    protected function managerPath(): string
    {
        return EVO_MANAGER_PATH;
    }
}
