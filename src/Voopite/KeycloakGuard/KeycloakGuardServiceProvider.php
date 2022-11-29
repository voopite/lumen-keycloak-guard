<?php

namespace Voopite\KeycloakGuard;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Voopite\EnvironmentEditor\EnvironmentEditor;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
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
    protected $editor = null;
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
        $packageConfigPath = __DIR__ . '/config/keycloak.php';
        $path = 'keycloak.php';
        $appConfigPath=  $this->app->basePath() . '/config' . ($path ? '/' . $path : $path);

        $this->mergeConfigFrom($appConfigPath, 'keycloak');

        $this->editor = $this->app->make(EnvironmentEditor::class);


        /**
         * @TODO Isolar a chamada com a lib de trace se tiver presente...
         */
        try {
            $this->editor->getKey("KEYCLOAK_REALM_PUBLIC_KEY");
        } catch (\Jackiedo\DotenvEditor\Exceptions\KeyNotFoundException $exception) {

            $url = $this->getIssuerUrl();

            $json = $this->getIssuerDetails($url);

            $publicKey = $json['public_key'];

            $this->saveRealmPublicKey($publicKey);
        }




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

    /**
     * @return string
     */
    public function getIssuerUrl(): string
    {
        $config = config('keycloak');
        $url = $config['keycloak_url'] . '/auth/realms/' . $config['realm_name'];
        return $url;
    }

    /**
     * @param string $url
     * @return mixed
     * @throws \JsonException
     */
    public function getIssuerDetails(string $url): mixed
    {
        $client = new Client();
        $request = new Request('GET', $url);
        $res = $client->sendAsync($request)->wait();

        $data = (string)$res->getBody();
        $json = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        return $json;
    }

    /**
     * @param mixed $publicKey
     * @return void
     */
    public function saveRealmPublicKey(mixed $publicKey): void
    {
        $this->editor->addEmpty();
        $this->editor->setKey('KEYCLOAK_REALM_PUBLIC_KEY', $publicKey, null, false);
        $this->editor->save();
    }
}
