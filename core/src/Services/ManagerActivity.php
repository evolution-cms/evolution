<?php

namespace EvolutionCMS\Services;

use EvolutionCMS\Models\ActiveUser;
use EvolutionCMS\Models\ActiveUserLock;
use EvolutionCMS\Models\ActiveUserSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Per-request manager bookkeeping (active session, current action, element lock, manager log)
 * collected during the request and written in one transaction once the response is out.
 *
 * Each of these used to be its own autocommit statement inside the request - six or seven
 * fsyncs on every manager page, about 55 ms of the ~80 ms bootstrap on an fsync-per-commit
 * MySQL. Nothing in the same request reads its own bookkeeping back (the lock and action
 * queries exclude the current user), so nothing observable moves.
 * @since 3.5.9
 */
final class ManagerActivity
{
    private static ?self $instance = null;

    /** @var array<int, callable> */
    private array $jobs = [];

    private bool $flushed = false;

    private bool $shutdownRegistered = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Queues a write; the queue is flushed after the response or, when the request exits early, at shutdown.
     */
    public function defer(callable $job): void
    {
        $this->jobs[] = $job;
        $this->flushed = false;

        if (!$this->shutdownRegistered) {
            $this->shutdownRegistered = true;
            register_shutdown_function(function (): void {
                $this->flush();
            });
        }
    }

    /**
     * Replaces the active_user_sessions row of the user: one row per user, keyed by the current session id.
     */
    public function touchSession(int $userId, string $sid, int $time, string $ip): void
    {
        $this->defer(static function () use ($userId, $sid, $time, $ip): void {
            ActiveUserSession::query()
                ->where('internalKey', $userId)
                ->orWhere('sid', $sid)
                ->delete();
            ActiveUserSession::query()->insert([
                'internalKey' => $userId,
                'sid' => $sid,
                'lasthit' => $time,
                'ip' => $ip,
            ]);
        });
    }

    /**
     * Records which manager action (and item) the user is on: one row per user in active_users.
     */
    public function setAction(int $userId, string $sid, string $username, int $time, int $action, ?int $itemId): void
    {
        $this->defer(static function () use ($userId, $sid, $username, $time, $action, $itemId): void {
            ActiveUser::query()->where('internalKey', $userId)->delete();
            ActiveUser::query()->insert([
                'sid' => $sid,
                'internalKey' => $userId,
                'username' => $username,
                'lasthit' => $time,
                'action' => (string) $action,
                'id' => $itemId,
            ]);
        });
    }

    /**
     * Takes or refreshes the element lock, same row selection as before: the lock is keyed by element id.
     */
    public function lock(int $type, int $id, int $userId, string $sid, int $time): void
    {
        $this->defer(static function () use ($type, $id, $userId, $sid, $time): void {
            ActiveUserLock::query()->updateOrCreate(
                ['elementId' => $id],
                ['internalKey' => $userId, 'elementType' => $type, 'lasthit' => $time, 'sid' => $sid]
            );
        });
    }

    public function hasPending(): bool
    {
        return $this->jobs !== [];
    }

    /**
     * Runs the queued writes in one transaction. With $detach the response is flushed to the client first
     * (FPM / LiteSpeed), so the writes cost the request nothing; other SAPIs just run them at the end.
     * Idempotent: the second call is a no-op unless something was queued in between.
     */
    public function flush(bool $detach = false): void
    {
        if ($this->flushed || $this->jobs === []) {
            return;
        }
        $this->flushed = true;
        $jobs = $this->jobs;
        $this->jobs = [];

        if ($detach) {
            evo()->finishRequest();
        }

        try {
            DB::transaction(static function () use ($jobs): void {
                foreach ($jobs as $job) {
                    $job();
                }
            });
        } catch (\Throwable $e) {
            if (!$detach) {
                throw $e;
            }
            // the client is gone already, the log is all that is left to tell
            Log::error('Manager activity write failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
