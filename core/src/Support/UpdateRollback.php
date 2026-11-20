<?php

namespace EvolutionCMS\Support;

/**
 * What a core update changed on disk, so that a failure can put the site back as it was.
 *
 * The update lays the new files over the site, then Composer rebuilds core/vendor. A failure in
 * between (Composer without network, a full disk, a provider that no longer boots) used to leave
 * the new code next to the old vendor, and the site, artisan included, stopped at the first class
 * it could not find. Every file the update replaces or removes is moved into the journal first,
 * every file and folder it creates is noted, and whole folders Composer rewrites are copied, so
 * restore() undoes all of it. The journal keeps the site's own layout below its folder, and stays
 * on disk when a restore fails, for a manual one.
 *
 * @since 3.5.10
 */
final class UpdateRollback
{
    private string $root;

    private string $journal;

    /** @var array<string, string> Original path => its copy in the journal, in the order kept. */
    private array $kept = [];

    /** @var array<string, true> Files the update created. */
    private array $createdFiles = [];

    /** @var array<int, string> Folders the update created, in the order it created them. */
    private array $createdDirectories = [];

    /** @var array<string, int> Folders the update removed, parents first => their mode. */
    private array $removedDirectories = [];

    /** @var array<string, string|null> Snapshot folders: original path => copy (null when it did not exist). */
    private array $directories = [];

    /** @var array<string, string|null> Snapshot files: original path => previous contents (null when absent). */
    private array $files = [];

    /**
     * @param string $root Folder whose layout the journal mirrors (the site root).
     * @param string $journal Folder the journal is written to; created on first use, kept out of the web root.
     */
    public function __construct(string $root, string $journal)
    {
        $this->root = self::normalize($root);
        $this->journal = self::normalize($journal);
    }

    public function journalPath(): string
    {
        return $this->journal;
    }

    /**
     * Note that a file is about to be replaced: the current one moves into the journal.
     *
     * Call it right before the new file takes the path; a path that does not exist yet is noted
     * as created, so restore() removes it.
     */
    public function replace(string $path): void
    {
        $path = self::normalize($path);
        if (isset($this->createdFiles[$path]) || isset($this->kept[$path])) {
            return;
        }
        if (!file_exists($path) && !is_link($path)) {
            $this->createdFiles[$path] = true;

            return;
        }

        $copy = $this->journalFile($path);
        $this->makeDirectory(dirname($copy));
        if (!@rename($path, $copy)) {
            throw new \RuntimeException('Could not keep a copy of ' . $path . ' for the rollback.');
        }
        $this->kept[$path] = $copy;
    }

    /**
     * Remove a file the update no longer ships, keeping it in the journal.
     */
    public function remove(string $path): void
    {
        $path = self::normalize($path);
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
        if (is_dir($path) && !is_link($path)) {
            // Noted before its contents, so restore() makes it again even when it held nothing.
            $this->removedDirectories[$path] ??= fileperms($path) & 07777;
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $this->remove($path . '/' . $entry);
                }
            }
            @rmdir($path);

            return;
        }

        if (isset($this->createdFiles[$path]) || isset($this->kept[$path])) {
            // Already the update's own file: the original, if any, is in the journal.
            if (!@unlink($path)) {
                throw new \RuntimeException('Could not remove ' . $path . '.');
            }

            return;
        }
        $this->replace($path);
    }

    /**
     * Create a folder (and its missing parents) and note the ones that did not exist.
     */
    public function makeDirectory(string $path): void
    {
        $path = self::normalize($path);
        if (is_dir($path)) {
            return;
        }
        $this->makeDirectory(dirname($path));
        if (!@mkdir($path, 0777) && !is_dir($path)) {
            throw new \RuntimeException('Could not create the folder ' . $path . '.');
        }
        if (!str_starts_with($path . '/', $this->journal . '/')) {
            $this->createdDirectories[] = $path;
        }
    }

    /**
     * Copy a whole folder that something other than the overlay rewrites in place (core/vendor).
     *
     * Links inside are copied as links, so a path repository installed as a symlink stays one.
     * A folder that is itself a link (core/vendor on shared storage) is copied and restored at
     * its target, and the link stays as it is.
     */
    public function snapshotDirectory(string $path): void
    {
        $path = self::normalize($path);
        if (is_link($path) && is_dir($path)) {
            $path = self::normalize((string) realpath($path));
        }
        if (array_key_exists($path, $this->directories)) {
            return;
        }
        if (!is_dir($path)) {
            $this->directories[$path] = null;

            return;
        }

        $copy = $this->journal . '/.snapshots/' . count($this->directories);
        $this->copyTree($path, $copy);
        $this->directories[$path] = $copy;
    }

    /**
     * Remember the contents of a small file written in place (a lock, a manifest).
     */
    public function snapshotFile(string $path): void
    {
        $path = self::normalize($path);
        if (array_key_exists($path, $this->files)) {
            return;
        }
        $contents = is_file($path) ? file_get_contents($path) : false;
        $this->files[$path] = $contents === false ? null : $contents;
    }

    /**
     * Put back everything noted, newest change first.
     *
     * @return array<int, string> Paths that could not be restored; empty when the site is back.
     */
    public function restore(): array
    {
        $failed = [];

        foreach (array_reverse($this->directories, true) as $path => $copy) {
            if (!$this->restoreDirectory($path, $copy)) {
                $failed[] = $path;
            }
        }

        foreach (array_reverse($this->files, true) as $path => $contents) {
            if ($contents === null) {
                if (is_file($path) && !@unlink($path)) {
                    $failed[] = $path;
                }
            } elseif (@file_put_contents($path, $contents) === false) {
                $failed[] = $path;
            }
        }

        foreach (array_keys($this->createdFiles) as $path) {
            if ((file_exists($path) || is_link($path)) && !@unlink($path)) {
                $failed[] = $path;
            }
        }

        foreach ($this->removedDirectories as $path => $mode) {
            if (!is_dir($path) && !@mkdir($path, $mode) && !is_dir($path)) {
                $failed[] = $path;
                continue;
            }
            @chmod($path, $mode);
        }

        foreach (array_reverse($this->kept, true) as $path => $copy) {
            if (!file_exists($copy) && !is_link($copy)) {
                // The folder snapshot above already brought it back.
                continue;
            }
            if (file_exists($path) || is_link($path)) {
                @unlink($path);
            }
            if (!is_dir(dirname($path))) {
                @mkdir(dirname($path), 0777, true);
            }
            if (!@rename($copy, $path)) {
                $failed[] = $path;
            }
        }

        foreach (array_reverse($this->createdDirectories) as $path) {
            if (is_dir($path) && !is_link($path) && (scandir($path) ?: []) === ['.', '..']) {
                @rmdir($path);
            }
        }

        if ($failed === []) {
            $this->discard();
        }

        return $failed;
    }

    /**
     * The update went through: forget the journal and delete its copies.
     */
    public function discard(): void
    {
        self::removeTree($this->journal);
        $this->kept = [];
        $this->createdFiles = [];
        $this->createdDirectories = [];
        $this->removedDirectories = [];
        $this->directories = [];
        $this->files = [];
    }

    /**
     * Where a file is kept: its path below the site root, or below a hash of its folder outside it.
     */
    private function journalFile(string $path): string
    {
        if (str_starts_with($path, $this->root . '/')) {
            return $this->journal . '/files/' . substr($path, strlen($this->root) + 1);
        }

        return $this->journal . '/outside/' . md5(dirname($path)) . '/' . basename($path);
    }

    /**
     * Put a snapshot folder back: the live one is moved aside and the copy takes its place.
     */
    private function restoreDirectory(string $path, ?string $copy): bool
    {
        $broken = $path . '.update-failed-' . getmypid();
        if (is_dir($path) && !@rename($path, $broken)) {
            // A mount point (core/vendor as a volume of its own) cannot be moved.
            return $this->restoreInPlace($path, $copy);
        }
        if ($copy !== null && !@rename($copy, $path) && !$this->copyBack($copy, $path)) {
            self::removeTree($path);
            @rename($broken, $path);

            return false;
        }
        self::removeTree($broken);

        return true;
    }

    /**
     * Empty a folder that stays where it is and copy the snapshot into it.
     *
     * The snapshot is only deleted once it is all back, so a failure leaves it in the journal.
     */
    private function restoreInPlace(string $path, ?string $copy): bool
    {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::removeTree($path . '/' . $entry);
            }
        }
        if ((scandir($path) ?: []) !== ['.', '..']) {
            return false;
        }
        if ($copy === null) {
            @rmdir($path);

            return true;
        }
        try {
            $this->copyTree($copy, $path);
        } catch (\RuntimeException $exception) {
            return false;
        }
        self::removeTree($copy);

        return true;
    }

    /**
     * A folder on another device (a separate volume) cannot be renamed back: copy it.
     */
    private function copyBack(string $copy, string $path): bool
    {
        try {
            $this->copyTree($copy, $path);
        } catch (\RuntimeException $exception) {
            return false;
        }
        self::removeTree($copy);

        return true;
    }

    private function copyTree(string $source, string $target): void
    {
        if (!is_dir($target) && !@mkdir($target, 0777, true) && !is_dir($target)) {
            throw new \RuntimeException('Could not create the folder ' . $target . '.');
        }
        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $source . '/' . $entry;
            $to = $target . '/' . $entry;
            if (is_link($from)) {
                $link = readlink($from);
                if ($link === false || !@symlink($link, $to)) {
                    throw new \RuntimeException('Could not copy the link ' . $from . ' for the rollback.');
                }
            } elseif (is_dir($from)) {
                $this->copyTree($from, $to);
            } elseif (!@copy($from, $to)) {
                throw new \RuntimeException('Could not copy ' . $from . ' for the rollback.');
            } else {
                @chmod($to, fileperms($from) & 0777);
            }
        }
        // Last, so a folder without write permission still took its contents; setgid kept.
        @chmod($target, fileperms($source) & 07777);
    }

    private static function removeTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::removeTree($path . '/' . $entry);
            }
        }
        @rmdir($path);
    }

    private static function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
