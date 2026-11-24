<?php

namespace EvolutionCMS\Support;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The response of an HTTP exception nothing else answered, abort() for one.
 */
final class HttpExceptionResponse
{
    /** The router's own message for these quotes the requested path. */
    private const FIXED_BODY = [404, 405];

    public static function make(HttpExceptionInterface $exception): Response
    {
        $status = $exception->getStatusCode();
        $message = in_array($status, self::FIXED_BODY, true) ? '' : $exception->getMessage();

        return new Response(
            $message !== '' ? $message : (Response::$statusTexts[$status] ?? 'HTTP ' . $status),
            $status,
            $exception->getHeaders() + ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
