<?php

namespace Voopite\KeycloakGuard;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

/**
 * Class KeycloakGuardServiceProvider
 * @package Voopite\KeycloakGuard
 * @author Ibson Machado <ibson.machado@voopite.com>
 *
 * @TODO
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class KeycloakGuardServiceProvider extends ServiceProvider
{
    /**
     *
     * @TODO
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/keycloak.php', 'keycloak');
    }

    /**
     *
     * @TODO
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        Auth::extend('keycloak', function ($app, $name, array $config) {
            return new KeycloakGuard(Auth::createUserProvider($config['provider']), $app->request);
        });
    }
}