<?php

namespace Voopite\KeycloakClient;

use Exception;
use Illuminate\Support\ServiceProvider;
use Voopite\EnvironmentEditor\EnvironmentEditor;
use Voopite\EnvironmentEditor\Exceptions\KeyNotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Class KeycloakClientServiceProvider
 * @package Voopite\KeycloakClient
 * @author Ibson Machado <ibson.machado@voopite.com>
 *
 * @TODO
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class KeycloakClientServiceProvider extends ServiceProvider
{
    protected $editor = null;

    private $clientToken;
    private $publicKey;

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
        $clientTokenExp = null;

        try {
            $this->clientToken = $this->editor->getValue("__CLIENT_TOKEN");
            $clientTokenExp = $this->editor->getValue("__CLIENT_TOKEN_EXP");
            $leeway = 0;
            $timestamp = \time();
            if (isset($clientTokenExp) && ($timestamp - $leeway) >= $clientTokenExp) {
                throw new Exception('Expired token');
            }
        } catch (\Exception $e) {
            $this->clientToken = $this->getClientToken();
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
        $this->app->singleton("client-token", function () {
            return new ClientToken($this->clientToken);
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

    /**
     * @return mixed
     * @throws \JsonException
     */
    public function getAccessToken(): mixed
    {
        $TOKEN_URL = getenv("KEYCLOAK_URL") . getenv("KEYCLOAK_TOKEN_PATH");
        $CLIENT_ID = getenv("KEYCLOAK_CLIENT_ID");
        $CLIENT_SECRET = getenv("KEYCLOAK_CLIENT_SECRET");
        // Fetch a token
        $client = new Client();
        $response = $client->post(
            $TOKEN_URL, [
            'auth' => [$CLIENT_ID, $CLIENT_SECRET],
            'form_params' => ['grant_type' => 'client_credentials']
        ]);
        $data = (string)$response->getBody();
        $json = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        $accessToken = $json['access_token'];
        return $accessToken;
    }

    /**
     *
     * @FIXME First try to get from env.... If not found get from auth server
     *
     * @return mixed
     * @throws \JsonException
     */
    public function getPublicKey(): mixed
    {
        if($this->publicKey != null) {
            return $this->publicKey;
        }

        try {
            $this->publicKey = $this->editor->getKey("KEYCLOAK_REALM_PUBLIC_KEY");
        } catch (KeyNotFoundException $exception) {
            $url = $this->getIssuerUrl();
            $json = $this->getIssuerDetails($url);
            $this->publicKey = $json['public_key'];
        }
        return $this->publicKey;
    }

    /**
     * @param mixed $accessToken
     * @param mixed $publicKey
     * @return mixed
     */
    public function getDecodedToken(mixed $accessToken, mixed $publicKey, mixed $leeway = 0)
    {
        $decodedToken = null;
        try {
            JWT::$leeway = $leeway;
            $publicKey = self::buildPublicKey($publicKey);
            $decodedToken = $accessToken ? JWT::decode($accessToken, new Key($publicKey, 'RS256')) : null;
        } catch (\Exception $e) {
            throw new TokenException($e->getMessage());
        }
        return $decodedToken;
    }

    /**
     * Build a valid public key from a string
     *
     * @TODO
     *
     * @since 1.0.0
     *
     * @param  string  $key
     * @return mixed
     */
    private static function buildPublicKey(string $key)
    {
        return "-----BEGIN PUBLIC KEY-----\n".wordwrap($key, 64, "\n", true)."\n-----END PUBLIC KEY-----";
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function getClientToken(): mixed
    {
        $accessToken = $this->getAccessToken();
        $publicKey = $this->getPublicKey();
        $decodedToken = $this->getDecodedToken($accessToken, $publicKey);

        $this->editor->addEmpty();
        $this->editor->setKey('__CLIENT_TOKEN_EXP', $decodedToken->exp, null, false);
        $this->editor->setKey('__CLIENT_TOKEN', $accessToken, null, false);
        $this->editor->save();

        return $accessToken;
    }

}
