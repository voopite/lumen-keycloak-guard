<?php

namespace Voopite\KeycloakGuard;

/**
 * Class Token
 * @package Voopite\KeycloakGuard
 * @author Ibson Machado <ibson.machado@voopite.com>
 *
 * @TODO
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Token
{
    /**
     * Decode a JWT token
     *
     * @param string|null $token
     * @param string|null $publicKey
     * @param int $leeway
     * @return mixed|null
     */
    public static function decode(string $token = null, string $publicKey = null, $leeway = 0)
    {
        JWT::$leeway = $leeway;
        $publicKey = self::buildPublicKey($publicKey);

        return $token ? JWT::decode($token, new Key($publicKey, 'RS256')) : null;
    }

    /**
     * Build a valid public key from a string
     *
     * @param  string  $key
     * @return mixed
     */
    private static function buildPublicKey(string $key)
    {
        return "-----BEGIN PUBLIC KEY-----\n".wordwrap($key, 64, "\n", true)."\n-----END PUBLIC KEY-----";
    }
}
