<?php

namespace App\Socialite;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Keycloak\Provider as BaseKeycloakProvider;

/**
 * Faza 6 — dokładnie ten sam problem co jwk-set-uri vs issuer w
 * api-gateway/SecurityConfig.kt (Faza 3): "localhost:8180" to adres widoczny
 * z przeglądarki (zaszyty w tokenie jako issuer, MUSI zostać niezmieniony),
 * ale wewnątrz poda Laravela "localhost" to loopback TEGO poda, nie
 * kontener Keycloaka — token/userinfo (wywołania serwer-serwer, nie
 * przeglądarki) muszą iść przez wewnętrzny adres klastra. Pakiet bazowy
 * (socialiteproviders/keycloak) używa JEDNEGO base_url do wszystkiego, stąd
 * to rozszerzenie zamiast gotowego Provider::class.
 */
class KeycloakProvider extends BaseKeycloakProvider
{
    public static function additionalConfigKeys()
    {
        return [...parent::additionalConfigKeys(), 'internal_base_url'];
    }

    protected function getInternalBaseUrl(): string
    {
        $base = $this->getConfig('internal_base_url') ?: $this->getConfig('base_url');

        return rtrim(rtrim($base, '/').'/realms/'.$this->getConfig('realms', 'master'), '/');
    }

    protected function getTokenUrl()
    {
        return $this->getInternalBaseUrl().'/protocol/openid-connect/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getInternalBaseUrl().'/protocol/openid-connect/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
