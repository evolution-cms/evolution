<?php

namespace EvolutionCMS\Support;

/**
 * Keeps the integrity check of check_files_onlogin quiet about an update.
 *
 * sys_files_checksum holds the md5 of each listed file as it was last accepted, and the
 * dashboard warns about every file that no longer matches. An update replaces index.php and
 * manager/index.php with the release's copies, so every update used to report them as changed.
 * A file whose recorded hash and current hash are both release copies, the current one being the
 * copy of this code, was replaced by an update and nothing else: adopt() records its new hash.
 * A file anyone edited, before or after the update, matches neither and is still reported.
 * This holds for every way of updating (make:site, the web updater, an overlay, git).
 */
final class SystemFilesChecksum
{
    /**
     * The md5 of every copy of a file the CMS ships, oldest first, since 3.0: releases and the
     * commits of the 3.5 and 3.6 lines. The last entry is the copy of this code; whoever changes the file
     * appends its new md5 (SystemFilesChecksumTest says which).
     *
     * @var array<string, string[]>
     */
    public const RELEASED = [
        'index.php' => [
            '589eb44fffb0de5559311ada637bdfae', // 3.0RC
            '2bb924454f81f4a2f90e1124d73d0d12', // 3.0RC2
            '599511a98673763d066ea76b9e50fbb0', // 3.0RC3 - 3.2.5
            '4452ae4b9b9c9aff195991f46c90f79e', // 3.2.6 - 3.5.0
            '013001e96f9ac1031742413b7903641b',
            'f38ddec531ed48e89230818d2827ce26',
            'bb682f550e906c0b82d189e8f9ff70aa', // 3.5.1 - 3.5.2
            '1e9e549bf7a22145e27c2b733d26d025',
            '928f5b3af00fdf9847d1bc44754e9cd1',
            'e4eeeb5c1a78c2f03526360bfb0c3568',
            'ef67b3a006d8f9728c8d6a8eb7e7640c',
            '726d910bdd90f0caef4507a7d65102b8',
            '87c8b2cb7cbe7b98c831cd9c56531149', // 3.5.3
            '949f5a6f1ddaef3ce8049b5b99a5481d',
            '069f903824f213e84924cfe999e81ff1',
            '46c2370a6df13f6438390dba94e10266', // 3.5.4
            'cf45d727110f24e2af97fe71c9f5669f',
            '29bcd9deaa5255b271b06d8261ec5966', // 3.5.5
            'e845a31b74e249d433dc36badedfae29', // 3.5.6 - 3.5.7
            '11727c69a7f8c012bccd41524ca57d6d', // 3.5.8
            '73555b5a990998c9aa419a98150ca9f8', // 3.5.9
            '553c79eefae71611e57072b54a603a67',
        ],
        'manager/index.php' => [
            '3b1215f626722408dd6ffd7a8f16da9a', // 3.0RC
            '325d82eed384dcb95528d8ffb36d17a1', // 3.0RC2 - 3.1.3
            'e00ab1a8d239ad179954f5dbb0afb6e8',
            'b2e01536079e041f49b4faea638645e4',
            '3d47b1a1cd56ebbaf67ba2a88fda41bc',
            'c467fb824dc6e2df167e8d45d1919b0b',
            '19c60de055d5efeb3fdd9debde124cb7', // 3.1.4 - 3.2.7
            '42ef80570fd86e06b10f8425239ac375', // 3.3.0 - 3.5.0
            '45523481877a4160b742fd410f541903', // 3.5.1
            '976e13c4c0174d70e91626b890be0548', // 3.5.2
            'a7cae3062ec74f8261fd9ddf5ab8b0e5',
            'ba436e70af1f674813ee01a6ab9a6247',
            '343d20fc20f5f8e3abca27631242c588',
            'bccc8c37ddccf33590e125ec0bf67701', // 3.5.3
            'fa68033f07a081da54e32b16b89741dd',
            'ab14a72c71f97945e6d90792a950c4a5', // 3.5.4 - 3.5.8
            'c4da7b5495dc050db774a7f6171284c4',
            'a6be82138f1d5bf7ac980242a9d1b07f', // 3.5.9
            '35f96ba44e454449aeef6ed9cc13a786',
        ],
    ];

    /**
     * The stored checksum with the release copies of this code adopted, or null when nothing
     * changes (no stored checksum, or no listed file was replaced by an update).
     *
     * Entries are keyed as ManagerApi::getSystemChecksum() keys them: $basePath and the listed name.
     *
     * @param array<string, string[]>|null $released defaults to RELEASED
     */
    public static function adopt(string $checkFiles, string $stored, string $basePath, ?array $released = null): ?string
    {
        $released ??= self::RELEASED;
        $checksum = $stored === '' ? false : @unserialize($stored, ['allowed_classes' => false]);
        if (!is_array($checksum) || $checksum === []) {
            return null;
        }

        $changed = false;
        foreach (explode("\n", trim($checkFiles)) as $file) {
            $file = trim($file);
            $copies = $released[ltrim(str_replace('\\', '/', $file), '/')] ?? [];
            $path = $basePath . $file;
            if ($copies === [] || !is_file($path) || !isset($checksum[$path])) {
                continue;
            }
            $current = md5_file($path);
            if ($current !== $checksum[$path]
                && $current === end($copies)
                && in_array($checksum[$path], $copies, true)
            ) {
                $checksum[$path] = $current;
                $changed = true;
            }
        }

        return $changed ? serialize($checksum) : null;
    }
}
