<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Interfaces\PasswordHashInterface;
//
// Portable PHP password hashing framework.
//
// Version 0.3 / genuine.
//
// Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
// the public domain.  Revised in subsequent years, still public domain.
//
// There's absolutely no warranty.
//
// The homepage URL for this framework is:
//
//    http://www.openwall.com/phpass/
//
// Please be sure to update the Version line if you edit this file in any way.
// It is suggested that you leave the main version number intact, but indicate
// your project name (after the slash) and add your own revision information.
//
// Please do not change the "private" password hashing method implemented in
// here, thereby making your hashes incompatible.  However, if you must, please
// change the hash type identifier (the "$P$") to something different.
//
// Obviously, since this code is in the public domain, the above are not
// requirements (there can be none), but merely suggestions.
//
class PasswordHash implements PasswordHashInterface
{
    /** Algorithm names as stored in the `pwd_hash_algo` system setting. */
    public const ALGO_BCRYPT = 'BCRYPT';
    public const ALGO_ARGON2ID = 'ARGON2ID';
    public const ALGO_ARGON2I = 'ARGON2I';

    /** Deliberately above the PHP 8.3 default of 10; matches the PHP 8.4 default. */
    public const BCRYPT_COST = 12;

    /** Storage formats recognised by identify(). */
    public const FORMAT_NATIVE = 'native';
    public const FORMAT_PORTABLE = 'portable';
    public const FORMAT_CRYPT = 'crypt';
    public const FORMAT_MD5 = 'md5';
    public const FORMAT_V1 = 'v1';
    public const FORMAT_UNKNOWN = 'unknown';

    public $itoa64;
    public $iteration_count_log2;
    public $portable_hashes;
    public $random_state;

    /**
     * PasswordHash constructor.
     *
     * @param int $iteration_count_log2
     * @param bool $portable_hashes
     */
    public function __construct($iteration_count_log2 = 8, $portable_hashes = true)
    {
        $this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31) {
            $iteration_count_log2 = 8;
        }
        $this->iteration_count_log2 = $iteration_count_log2;

        $this->portable_hashes = $portable_hashes;

        $this->random_state = microtime() . uniqid(mt_rand(), true);
    }

    /**
     * @param string $count
     * @return bool|string
     */
    public function get_random_bytes($count)
    {
        $output = '';

        if (is_callable('random_bytes')) {
            return random_bytes($count);
        }

        if (@is_readable('/dev/urandom') &&
            ($fh = @fopen('/dev/urandom', 'rb'))) {
            $output = fread($fh, $count);
            fclose($fh);
        }

        if (strlen($output) < $count) {
            $output = '';
            for ($i = 0; $i < $count; $i += 16) {
                $this->random_state =
                    md5(microtime() . $this->random_state);
                $output .=
                    pack('H*', md5($this->random_state));
            }
            $output = substr($output, 0, $count);
        }

        return $output;
    }

    /**
     * @param string $input
     * @param int $count
     * @return string
     */
    public function encode64($input, $count)
    {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $this->itoa64[$value & 0x3f];
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= $this->itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= $this->itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            $output .= $this->itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }

    /**
     * @param string $input
     * @return string
     */
    public function gensalt_private($input)
    {
        $output = '$P$';
        $output .= $this->itoa64[min($this->iteration_count_log2 + 5, 30)];
        $output .= $this->encode64($input, 6);

        return $output;
    }

    /**
     * @param string $password
     * @param string $setting
     * @return string
     */
    public function crypt_private($password, $setting)
    {
        $output = '*0';
        if (substr($setting, 0, 2) == $output) {
            $output = '*1';
        }

        $id = substr($setting, 0, 3);
        // We use "$P$", phpBB3 uses "$H$" for the same thing
        if ($id != '$P$' && $id != '$H$') {
            return $output;
        }

        $count_log2 = strpos($this->itoa64, $setting[3]);
        if ($count_log2 < 7 || $count_log2 > 30) {
            return $output;
        }

        $count = 1 << $count_log2;

        $salt = substr($setting, 4, 8);
        if (strlen($salt) != 8) {
            return $output;
        }

        // We're kind of forced to use MD5 here since it's the only
        // cryptographic primitive available in all versions of PHP
        // currently in use.  To implement our own low-level crypto
        // in PHP would result in much worse performance and
        // consequently in lower iteration counts and hashes that are
        // quicker to crack (by non-PHP code).

        $hash = md5($salt . $password, true);
        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        $output = substr($setting, 0, 12);
        $output .= $this->encode64($hash, 16);

        return $output;
    }

    /**
     * @param string $input
     * @return string
     */
    public function gensalt_extended($input)
    {
        $count_log2 = min($this->iteration_count_log2 + 8, 24);
        // This should be odd to not reveal weak DES keys, and the
        // maximum valid value is (2**24 - 1) which is odd anyway.
        $count = (1 << $count_log2) - 1;

        $output = '_';
        $output .= $this->itoa64[$count & 0x3f];
        $output .= $this->itoa64[($count >> 6) & 0x3f];
        $output .= $this->itoa64[($count >> 12) & 0x3f];
        $output .= $this->itoa64[($count >> 18) & 0x3f];

        $output .= $this->encode64($input, 3);

        return $output;
    }

    /**
     * @param string $input
     * @return string
     */
    public function gensalt_blowfish($input)
    {
        // This one needs to use a different order of characters and a
        // different encoding scheme from the one in encode64() above.
        // We care because the last character in our encoded string will
        // only represent 2 bits.  While two known implementations of
        // bcrypt will happily accept and correct a salt string which
        // has the 4 unused bits set to non-zero, we do not want to take
        // chances and we also do not want to waste an additional byte
        // of entropy.
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '$2a$';
        $output .= chr(ord('0') + $this->iteration_count_log2 / 10);
        $output .= chr(ord('0') + $this->iteration_count_log2 % 10);
        $output .= '$';

        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }

    /**
     * Hash a password with the algorithm selected in the system settings.
     *
     * Always produces a self-describing password_hash() string ($2y$ / $argon2id$).
     * The portable phpass format ($P$) is no longer generated — it is only still
     * verified, so hashes written by earlier Evolution versions keep working until
     * their owner logs in and the hash is upgraded in place.
     *
     * @param string $password
     * @return string '*' when hashing failed — never a usable hash
     */
    public function HashPassword($password)
    {
        if (strlen($password) > 4096) {
            return '*';
        }

        [$algorithm, $options] = $this->resolveAlgorithm();

        $hash = password_hash($password, $algorithm, $options);

        // password_hash() only returns a non-string on catastrophic failure, but a
        // truncated or empty result must never reach the database: '*' can never be
        // produced by any hashing algorithm, so it can never validate.
        if (!is_string($hash) || strlen($hash) < 20) {
            return '*';
        }

        return $hash;
    }

    /**
     * Resolve the configured algorithm into a password_hash() algorithm + options pair.
     *
     * Unknown and pre-3.5 values (BLOWFISH_Y, SHA512, UNCRYPT, ...) map to bcrypt:
     * those names described the legacy "v1" hashing scheme, which is verify-only now.
     *
     * @return array{0: string|int, 1: array}
     */
    public function resolveAlgorithm(): array
    {
        $configured = strtoupper((string) $this->configuredAlgorithm());

        if (($configured === self::ALGO_ARGON2ID || $configured === self::ALGO_ARGON2I)
            && defined('PASSWORD_' . $configured)) {
            return [
                constant('PASSWORD_' . $configured),
                [
                    'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                    'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
                    'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
                ],
            ];
        }

        return [PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]];
    }

    /**
     * @return string
     */
    protected function configuredAlgorithm(): string
    {
        // Reachable from the installer and from CLI seeding, where no CMS instance
        // exists yet. Any failure to read the setting simply means "use the default".
        try {
            if (!function_exists('evolutionCMS')) {
                return self::ALGO_BCRYPT;
            }

            $value = evolutionCMS()->getConfig('pwd_hash_algo');
        } catch (\Throwable $exception) {
            return self::ALGO_BCRYPT;
        }

        return is_scalar($value) ? (string) $value : self::ALGO_BCRYPT;
    }

    /**
     * Name the storage format of a hash already in the database.
     *
     * @param string $stored_hash
     * @return string native|portable|crypt|md5|v1|unknown
     */
    public function identify($stored_hash): string
    {
        $stored_hash = (string) $stored_hash;

        if ($stored_hash === '' || $stored_hash === '*') {
            return self::FORMAT_UNKNOWN;
        }

        if (str_starts_with($stored_hash, '$2') || str_starts_with($stored_hash, '$argon2')) {
            return self::FORMAT_NATIVE;
        }

        if (str_starts_with($stored_hash, '$P$') || str_starts_with($stored_hash, '$H$')) {
            return self::FORMAT_PORTABLE;
        }

        if (strlen($stored_hash) === 32 && ctype_xdigit($stored_hash)) {
            return self::FORMAT_MD5;
        }

        // "algorithm>hash" — the Evolution 1.x scheme, verified by ManagerApi::genV1Hash()
        if (str_contains($stored_hash, '>')) {
            return self::FORMAT_V1;
        }

        if (preg_match('/^\$[156]\$/', $stored_hash) === 1 || str_starts_with($stored_hash, '_')) {
            return self::FORMAT_CRYPT;
        }

        return self::FORMAT_UNKNOWN;
    }

    /**
     * Can this stored value be used to verify a password at all?
     *
     * False means the row is unusable — plaintext left behind by a buggy extra, a
     * truncated hash, an empty column. Such an account cannot log in by any password,
     * so the caller must start password recovery instead of reporting "wrong password".
     *
     * @param string $stored_hash
     * @return bool
     */
    public function isUsable($stored_hash): bool
    {
        return $this->identify($stored_hash) !== self::FORMAT_UNKNOWN;
    }

    /**
     * Must this hash be replaced after a successful login?
     *
     * True for every legacy format, and for current-format hashes whose algorithm or
     * cost no longer matches the system settings.
     *
     * @param string $stored_hash
     * @return bool
     */
    public function needsRehash($stored_hash): bool
    {
        $format = $this->identify($stored_hash);

        if ($format === self::FORMAT_UNKNOWN) {
            return false;
        }

        if ($format !== self::FORMAT_NATIVE) {
            return true;
        }

        [$algorithm, $options] = $this->resolveAlgorithm();

        return password_needs_rehash((string) $stored_hash, $algorithm, $options);
    }

    /**
     * Hash in the legacy portable phpass format ($P$).
     *
     * Kept for backwards compatibility and tests only — nothing in the CMS writes
     * this format any more.
     *
     * @param string $password
     * @return string
     */
    public function HashPasswordPortable($password)
    {
        if (strlen($password) > 4096) {
            return '*';
        }

        $random = '';

        if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
            $random = $this->get_random_bytes(16);
            $hash =
                crypt($password, $this->gensalt_blowfish($random));
            if (strlen($hash) == 60) {
                return $hash;
            }
        }

        if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
            if (strlen($random) < 3) {
                $random = $this->get_random_bytes(3);
            }
            $hash =
                crypt($password, $this->gensalt_extended($random));
            if (strlen($hash) == 20) {
                return $hash;
            }
        }

        if (strlen($random) < 6) {
            $random = $this->get_random_bytes(6);
        }
        $hash =
            $this->crypt_private($password,
                $this->gensalt_private($random));
        if (strlen($hash) == 34) {
            return $hash;
        }

        // Returning '*' on error is safe here, but would _not_ be safe
        // in a crypt(3)-like function used _both_ for generating new
        // hashes and for validating passwords against existing hashes.
        return '*';
    }

    /**
     * Verify a password against any hash format this CMS has ever written,
     * except the "v1" scheme, which needs the user id as its salt seed and is
     * handled by loginV1() / ManagerApi::genV1Hash().
     *
     * @param string $password
     * @param string $stored_hash
     * @return bool
     */
    public function CheckPassword($password, $stored_hash)
    {
        $password = (string) $password;
        $stored_hash = (string) $stored_hash;

        if (strlen($password) > 4096) {
            return false;
        }

        switch ($this->identify($stored_hash)) {
            case self::FORMAT_NATIVE:
                return password_verify($password, $stored_hash);

            case self::FORMAT_PORTABLE:
                return hash_equals($stored_hash, $this->crypt_private($password, $stored_hash));

            case self::FORMAT_CRYPT:
                return hash_equals($stored_hash, crypt($password, $stored_hash));

            case self::FORMAT_MD5:
                return hash_equals($stored_hash, md5($password));
        }

        // Unknown format, including plaintext: refuse rather than compare. A plaintext
        // column must never authenticate anybody — recovery is the only way out.
        return false;
    }
}
