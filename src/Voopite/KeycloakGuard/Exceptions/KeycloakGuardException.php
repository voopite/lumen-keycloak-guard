<?php

namespace Voopite\KeycloakGuard\Exceptions;

/**
 * @since 1.0.0
 * @package Voopite\KeycloakGuard\Exceptions
 * @author Ibson Machado <ibson.machado@voopite.com>
 * @version 1.0.0
 */
class KeycloakGuardException extends \UnexpectedValueException
{
    public function __construct(string $message)
    {
        $this->message = "[Keycloak Guard] {$message}";
    }
}
