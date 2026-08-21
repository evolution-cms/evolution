<?php namespace EvolutionCMS\Auth\Contracts;

use Closure;
use EvolutionCMS\Auth\LoginAttempt;

/**
 * One link of the login chain.
 *
 * Three things a pipe can do, and they are the whole point of the pipeline:
 *
 *  - **let through**: `return $next($attempt);`
 *  - **veto**: throw a ServiceActionException — the login stops and the message is
 *    shown by the existing error handling in LogInOut::simpleLogin(). This is what an
 *    OnManagerAuthentication plugin cannot do, because that event's result is an
 *    OR-gate: any `true` grants access and a `false` means nothing.
 *  - **authenticate on its own**: return a user model without calling `$next()`, e.g.
 *    `return $attempt->manager->loginByIdWithoutPipeline(['id' => $id]);` for a social
 *    or SSO login. Skipping `$next()` skips the password check that follows.
 *
 * A pipe may also post-process: `$user = $next($attempt); ...; return $user;`.
 *
 * Implementing this interface is not required — Illuminate\Pipeline only needs a
 * `handle()` method — but it documents the contract and lets static analysis see it.
 */
interface LoginPipe
{
    /**
     * @param LoginAttempt $attempt
     * @param Closure $next
     * @return mixed the authenticated user model
     */
    public function handle(LoginAttempt $attempt, Closure $next);
}
