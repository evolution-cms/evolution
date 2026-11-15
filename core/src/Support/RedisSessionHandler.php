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
 */
class RedisSessionHandler extends SessionHandler
{
    /** phpredis' key prefix when session.save_path names none */
    public const PREFIX = 'PHPREDIS_SESSION:';

    /**
     * KEYS: lock, session. ARGV: lock secret, SHA-1 of the session as read, lock TTL in ms.
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
            $this->script(self::RELEASE_LOCK, [self::PREFIX . $id . '_LOCK'], [self::lockSecret()]);
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

    private function keepLock(string $id): int
    {
        return (int)$this->script(self::KEEP_LOCK, [self::PREFIX . $id . '_LOCK', self::PREFIX . $id], [
            self::lockSecret(),
            $this->readHash,
            (string)($this->lockExpire * 1000),
        ]);
    }

    /**
     * @param list<string> $keys
     * @param list<string> $args
     */
    private function script(string $script, array $keys, array $args): mixed
    {
        try {
            $redis = ($this->connect)();
            if (!$redis instanceof Redis) {
                return false;
            }
            $result = $redis->eval($script, array_merge($keys, $args), count($keys));
            $redis->close();

            return $result;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
