<?php

namespace Illuminate\Session;

use SessionHandlerInterface;

/**
 * Illuminate session storage with PHP 8.6 ID methods backported by Evolution CMS.
 *
 * The compatibility lifecycle maintains these methods while retaining Laravel 12 storage behavior.
 */
class NullSessionHandler implements SessionHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function read($sessionId): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function gc($lifetime): int
    {
        return 0;
    }

    /**
     * Generate a session ID (Laravel 13 backport for PHP 8.6).
     *
     * @return string
     * @since 13.0
     */
    public function create_sid(): string
    {
        return session_create_id() ?: throw new \RuntimeException('Unable to create a session ID.');
    }

    /**
     * Check an existing session ID (Laravel 13 backport for PHP 8.6).
     *
     * @param string $id
     * @return bool
     * @since 13.0
     */
    public function validateId($id): bool
    {
        return true;
    }

}
