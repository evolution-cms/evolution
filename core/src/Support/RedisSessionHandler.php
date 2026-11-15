<?php

namespace EvolutionCMS\Support;

use Redis;
use SessionHandler;
use Throwable;

/**
 * The ext-redis session handler, with a lock that a live request keeps past its expiry.
 *
 * Evolution gives the phpredis lock a TTL so that a killed request cannot lock its session for good.
 * phpredis then refuses the write of a request that kept the session open longer than that TTL
 * ("session lock not held"), even when no other request wanted the session. Before such a write this
 * handler extends the lock, or re-takes it under phpredis' own secret when it has expired, no other
 * request holds it and the stored session is still the one this request read. A session that another
 * request locked or changed in the meantime is left to phpredis, which refuses the write.
 *
 * With redis.session.compression phpredis stores the session compressed and reads it decompressed, so
 * "still the one this request read" is decided on the decompressed data, and the lock is then re-taken
 * only while the stored bytes are the ones that were decompressed.
 *
 * By design, a request whose lock expired while another request took the session and changed it loses
 * its own write: phpredis refuses it and the session keeps the other request's data. The two requests
 * were not serialized, the second ran while the first still held the data it read, so writing the first
 * request's copy afterwards would silently discard the second one's changes (a lost update); sessions
 * have no merge. A site with requests that hold their session longer than the TTL (max_execution_time,
 * or 30 s without one) sets redis.session.lock_expire above that; a value an operator set is kept.
 * RedisSessionStorageTest pins the case ("a request whose expired lock another request took does not
 * overwrite the session it changed").
 */
class RedisSessionHandler extends SessionHandler
{
    /** phpredis' key prefix when session.save_path names none */
    public const PREFIX = 'PHPREDIS_SESSION:';

    /**
     * KEYS: lock, session. ARGV: lock secret, SHA-1 of the stored session expected (empty: extend
     * only), lock TTL in ms.
     * Returns 1 when the lock is ours (again), 2 when it was taken anew, 0 when it is not.
     */
    private const KEEP_LOCK = <<<'LUA'
local owner = redis.call('GET', KEYS[1])
if owner == ARGV[1] then
    redis.call('PEXPIRE', KEYS[1], ARGV[3])
    return 1
end
if owner then
    return 0
end
local data = redis.call('GET', KEYS[2]) or ''
if redis.sha1hex(data) ~= ARGV[2] then
    return 0
end
redis.call('SET', KEYS[1], ARGV[1], 'PX', ARGV[3])
return 2
LUA;

    private const RELEASE_LOCK = <<<'LUA'
if redis.call('GET', KEYS[1]) == ARGV[1] then
    return redis.call('DEL', KEYS[1])
end
return 0
LUA;

    /** seconds before the expiry from which the lock is checked: the lock was set a moment before the read */
    private const MARGIN = 2.0;

    private ?float $readAt = null;
    private string $readHash = '';
    private int $compression = 0;

    /** @var callable(): ?Redis */
    private $connect;

    /**
     * @param int $lockExpire the lock TTL in seconds (redis.session.lock_expire)
     * @param callable(): ?Redis $connect opens a connection to the session database
     */
    public function __construct(private int $lockExpire, callable $connect)
    {
        $this->connect = $connect;
    }

    public function read(string $id): string|false
    {
        $data = parent::read($id);
        if ($data !== false) {
            $this->readAt = microtime(true);
            $this->readHash = sha1($data);
            $this->compression = self::compression();
        }

        return $data;
    }

    public function write(string $id, string $data): bool
    {
        $retaken = $this->readAt !== null && $this->lockExpire > 0
            && microtime(true) - $this->readAt >= $this->lockExpire - self::MARGIN
            && $this->keepLock($id) === 2;

        $written = parent::write($id, $data);
        if (!$written && $retaken) {
            // phpredis did not recognise the secret: do not leave a lock nobody releases
            $this->redis(static fn(Redis $redis) => $redis->eval(
                self::RELEASE_LOCK,
                [self::PREFIX . $id . '_LOCK', self::lockSecret()],
                1
            ));
        }

        return $written;
    }

    public function close(): bool
    {
        $this->readAt = null;

        return parent::close();
    }

    /**
     * The secret phpredis stores in the lock key (redis_session.c: "<hostname>|<pid>").
     */
    public static function lockSecret(): string
    {
        return gethostname() . '|' . getmypid();
    }

    /**
     * The compression phpredis applies to sessions: its reading of redis.session.compression
     * (redis_session.c, session_compression_type()), none for a method this ext-redis was built without.
     */
    public static function compression(): int
    {
        $setting = strtolower((string)ini_get('redis.session.compression'));
        foreach (['lzf' => 'LZF', 'zstd' => 'ZSTD', 'lz4' => 'LZ4'] as $prefix => $name) {
            if (str_starts_with($setting, $prefix) && defined(Redis::class . '::COMPRESSION_' . $name)) {
                return (int)constant(Redis::class . '::COMPRESSION_' . $name);
            }
        }

        return Redis::COMPRESSION_NONE;
    }

    /**
     * The session data phpredis reads from a stored value: decompressed, or the value itself when it
     * does not decompress (redis_session.c, session_uncompress_data()).
     */
    public static function uncompress(Redis $redis, string $stored, int $compression): string
    {
        if ($compression === Redis::COMPRESSION_NONE || $stored === '') {
            return $stored;
        }
        try {
            $redis->setOption(Redis::OPT_COMPRESSION, $compression);
            $data = $redis->_uncompress($stored);
        } catch (Throwable $exception) {
            $data = false;
        } finally {
            $redis->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_NONE);
        }

        return is_string($data) ? $data : $stored;
    }

    private function keepLock(string $id): int
    {
        $lock = self::PREFIX . $id . '_LOCK';
        $session = self::PREFIX . $id;

        return (int)$this->redis(function (Redis $redis) use ($lock, $session) {
            // The script compares stored bytes, this request read them decompressed: name the bytes
            // that hold what it read, or none. A write in between changes them, and the script refuses.
            $stored = $redis->get($session);
            $stored = is_string($stored) ? $stored : '';
            $unchanged = hash_equals($this->readHash, sha1(self::uncompress($redis, $stored, $this->compression)));

            return $redis->eval(self::KEEP_LOCK, [
                $lock,
                $session,
                self::lockSecret(),
                $unchanged ? sha1($stored) : '',
                (string)($this->lockExpire * 1000),
            ], 2);
        });
    }

    /**
     * @param callable(Redis): mixed $command
     * @return mixed the command's result, false without a connection
     */
    private function redis(callable $command): mixed
    {
        try {
            $redis = ($this->connect)();
            if (!$redis instanceof Redis) {
                return false;
            }
            try {
                return $command($redis);
            } finally {
                $redis->close();
            }
        } catch (Throwable $exception) {
            return false;
        }
    }
}
