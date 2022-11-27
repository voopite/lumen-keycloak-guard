<?php

namespace Voopite\KeycloakGuard\Exceptions;

/**
 * Class KeycloakGuardException
 * @package Voopite\KeycloakGuard\Exceptions
 * @author Ibson Machado <ibson.machado@voopite.com>
 *
 * @TODO
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class KeycloakGuardException extends \UnexpectedValueException
{
    /**
     *
     * @TODO
     *
     * @since 1.0.0
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = "[Keycloak Guard] {$message}";
    }
}
