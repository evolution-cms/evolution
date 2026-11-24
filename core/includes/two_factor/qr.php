<?php

/**
 * Returns a closure that draws a text as a QR code (byte mode, error correction M) in SVG.
 *
 * Kept out of the autoloader: only the set-up page of the authenticator app needs it, and the text
 * holds a secret, so no outside service may draw it either.
 *
 * @throws LengthException when the text is longer than 2331 bytes
 */
return static function (string $text): string {
    // per version: error correction codewords of a block, number of blocks (level M)
    $eccLength = [10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26,
        26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28];
    $blockCount = [1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16,
        17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49];

    // modules left for data once every function pattern is drawn
    $rawModules = static function (int $version): int {
        $result = (16 * $version + 128) * $version + 64;
        if ($version >= 2) {
            $align = intdiv($version, 7) + 2;
            $result -= (25 * $align - 10) * $align - 55;
            if ($version >= 7) {
                $result -= 36;
            }
        }

        return $result;
    };

    $length = strlen($text);
    for ($version = 1; ; $version++) {
        if ($version > 40) {
            throw new LengthException('The text is too long for a QR code.');
        }
        $total = intdiv($rawModules($version), 8);
        $dataLength = $total - $eccLength[$version - 1] * $blockCount[$version - 1];
        $countBits = $version < 10 ? 8 : 16;
        if (4 + $countBits + $length * 8 <= $dataLength * 8) {
            break;
        }
    }

    // data codewords: byte mode, length, text, terminator, padding
    $bits = '0100' . str_pad(decbin($length), $countBits, '0', STR_PAD_LEFT);
    for ($i = 0; $i < $length; $i++) {
        $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
    }
    $bits .= str_repeat('0', min(4, $dataLength * 8 - strlen($bits)));
    $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);
    $data = array_map('bindec', str_split($bits, 8));
    for ($pad = 0xEC; count($data) < $dataLength; $pad ^= 0xEC ^ 0x11) {
        $data[] = $pad;
    }

    // Reed-Solomon over GF(2^8 / 0x11D)
    $multiply = static function (int $x, int $y): int {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z;
    };
    $eccSize = $eccLength[$version - 1];
    $divisor = array_fill(0, $eccSize, 0);
    $divisor[$eccSize - 1] = 1;
    for ($i = 0, $root = 1; $i < $eccSize; $i++, $root = $multiply($root, 2)) {
        for ($j = 0; $j < $eccSize; $j++) {
            $divisor[$j] = $multiply($divisor[$j], $root) ^ ($divisor[$j + 1] ?? 0);
        }
    }

    $blocks = $blockCount[$version - 1];
    $shortBlocks = $blocks - $total % $blocks;
    $shortLength = intdiv($total, $blocks) - $eccSize;
    $dataBlocks = $eccBlocks = [];
    for ($i = 0, $offset = 0; $i < $blocks; $i++) {
        $size = $shortLength + ($i < $shortBlocks ? 0 : 1);
        $dataBlocks[$i] = array_slice($data, $offset, $size);
        $offset += $size;
        $remainder = array_fill(0, $eccSize, 0);
        foreach ($dataBlocks[$i] as $byte) {
            $factor = $byte ^ array_shift($remainder);
            $remainder[] = 0;
            foreach ($divisor as $j => $coefficient) {
                $remainder[$j] ^= $multiply($coefficient, $factor);
            }
        }
        $eccBlocks[$i] = $remainder;
    }
    $codewords = [];
    foreach ([[$dataBlocks, $shortLength + 1], [$eccBlocks, $eccSize]] as [$source, $size]) {
        for ($i = 0; $i < $size; $i++) {
            foreach ($source as $block) {
                if (isset($block[$i])) {
                    $codewords[] = $block[$i];
                }
            }
        }
    }

    // function patterns
    $size = $version * 4 + 17;
    $modules = $reserved = array_fill(0, $size, array_fill(0, $size, 0));
    $set = static function (int $x, int $y, int $dark) use (&$modules, &$reserved): void {
        $modules[$y][$x] = $dark;
        $reserved[$y][$x] = 1;
    };
    for ($i = 0; $i < $size; $i++) {
        $set(6, $i, 1 - $i % 2);
        $set($i, 6, 1 - $i % 2);
    }
    foreach ([[3, 3], [$size - 4, 3], [3, $size - 4]] as [$cx, $cy]) {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
                    $distance = max(abs($dx), abs($dy));
                    $set($x, $y, $distance !== 2 && $distance !== 4 ? 1 : 0);
                }
            }
        }
    }
    if ($version > 1) {
        $align = intdiv($version, 7) + 2;
        $step = intdiv($version * 8 + $align * 3 + 5, $align * 4 - 4) * 2;
        $positions = [6];
        for ($i = $align - 2; $i >= 0; $i--) {
            $positions[] = $size - 7 - $i * $step;
        }
        foreach ($positions as $i => $cx) {
            foreach ($positions as $j => $cy) {
                // the three corners that hold finder patterns
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $align - 1) || ($i === $align - 1 && $j === 0)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $set($cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) !== 1 ? 1 : 0);
                    }
                }
            }
        }
    }
    $drawFormat = static function (int $mask) use ($set, $size): void {
        $remainder = $mask; // level M is 00
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ (($remainder >> 9) * 0x537);
        }
        $format = (($mask << 10) | $remainder) ^ 0x5412;
        for ($i = 0; $i < 15; $i++) {
            $dark = ($format >> $i) & 1;
            // around the top left finder, then split between the other two
            if ($i < 6) {
                $set(8, $i, $dark);
            } elseif ($i < 8) {
                $set(8, $i + 1, $dark);
            } elseif ($i === 8) {
                $set(7, 8, $dark);
            } else {
                $set(14 - $i, 8, $dark);
            }
            if ($i < 8) {
                $set($size - 1 - $i, 8, $dark);
            } else {
                $set(8, $size - 15 + $i, $dark);
            }
        }
        $set(8, $size - 8, 1);
    };
    $drawFormat(0);
    if ($version >= 7) {
        $remainder = $version;
        for ($i = 0; $i < 12; $i++) {
            $remainder = ($remainder << 1) ^ (($remainder >> 11) * 0x1F25);
        }
        $info = ($version << 12) | $remainder;
        for ($i = 0; $i < 18; $i++) {
            $a = $size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $set($a, $b, ($info >> $i) & 1);
            $set($b, $a, ($info >> $i) & 1);
        }
    }

    // codewords, in a zigzag of two columns from the bottom right
    $bit = 0;
    $bitCount = count($codewords) * 8;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }
        for ($vertical = 0; $vertical < $size; $vertical++) {
            for ($j = 0; $j < 2; $j++) {
                $x = $right - $j;
                $y = (($right + 1) & 2) === 0 ? $size - 1 - $vertical : $vertical;
                if (!$reserved[$y][$x] && $bit < $bitCount) {
                    $modules[$y][$x] = ($codewords[$bit >> 3] >> (7 - ($bit & 7))) & 1;
                    $bit++;
                }
            }
        }
    }

    $masks = [
        static fn (int $x, int $y): bool => ($x + $y) % 2 === 0,
        static fn (int $x, int $y): bool => $y % 2 === 0,
        static fn (int $x, int $y): bool => $x % 3 === 0,
        static fn (int $x, int $y): bool => ($x + $y) % 3 === 0,
        static fn (int $x, int $y): bool => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
        static fn (int $x, int $y): bool => $x * $y % 2 + $x * $y % 3 === 0,
        static fn (int $x, int $y): bool => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
        static fn (int $x, int $y): bool => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
    ];
    $penalty = static function (array $grid) use ($size): int {
        $score = 0;
        $dark = 0;
        foreach ([$grid, array_map(null, ...$grid)] as $lines) {
            foreach ($lines as $line) {
                $row = implode('', $line);
                preg_match_all('/0{5,}|1{5,}/', $row, $runs);
                foreach ($runs[0] as $run) {
                    $score += strlen($run) - 2;
                }
                $padded = '0000' . $row . '0000';
                $score += 40 * (preg_match_all('/(?=10111010000)/', $padded) + preg_match_all('/(?=00001011101)/', $padded));
            }
        }
        for ($y = 0; $y < $size; $y++) {
            $dark += array_sum($grid[$y]);
            for ($x = 0; $x < $size - 1 && $y < $size - 1; $x++) {
                $sum = $grid[$y][$x] + $grid[$y][$x + 1] + $grid[$y + 1][$x] + $grid[$y + 1][$x + 1];
                $score += $sum === 0 || $sum === 4 ? 3 : 0;
            }
        }

        return $score + 10 * intdiv((int) abs($dark * 20 - $size * $size * 10), $size * $size);
    };

    $plain = $modules;
    $best = null;
    $bestScore = PHP_INT_MAX;
    foreach ($masks as $number => $mask) {
        $modules = $plain;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!$reserved[$y][$x] && $mask($x, $y)) {
                    $modules[$y][$x] ^= 1;
                }
            }
        }
        $drawFormat($number);
        $score = $penalty($modules);
        if ($score < $bestScore) {
            $bestScore = $score;
            $best = $modules;
        }
    }

    // four light modules all around belong to the code
    $path = '';
    foreach ($best as $y => $row) {
        foreach ($row as $x => $dark) {
            if ($dark) {
                $path .= 'M' . ($x + 4) . ' ' . ($y + 4) . 'h1v1h-1z';
            }
        }
    }
    $full = $size + 8;

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $full . ' ' . $full . '" width="200" height="200"'
        . ' shape-rendering="crispEdges" role="img" aria-label="QR"><path fill="#fff" d="M0 0h' . $full . 'v' . $full . 'H0z"/>'
        . '<path fill="#000" d="' . $path . '"/></svg>';
};
