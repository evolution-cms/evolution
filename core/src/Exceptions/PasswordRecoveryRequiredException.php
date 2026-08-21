<?php
namespace EvolutionCMS\Exceptions;

/**
 * The stored password cannot be verified in any format the CMS knows, so the login
 * flow cannot continue and password recovery has been started instead.
 *
 * Extends ServiceActionException so that every existing login call site — which
 * already catches that type and shows its message — reports it correctly.
 */
class PasswordRecoveryRequiredException extends ServiceActionException { }
