<?php

namespace App\Routing;

use Illuminate\Routing\UrlGenerator;

/**
 * Generator URL świadomy hosta.
 *
 * Moduły bramki i sklepu rejestrują trasy o tych samych nazwach
 * (np. panel.login, panel.dashboard, home) scopowane przez Route::domain().
 * Standardowo route() zwraca pierwszą trasę o danej nazwie niezależnie od hosta,
 * co generuje linki do nieprawidłowej domeny. Tutaj — przy kolizji nazw —
 * wybieramy trasę, której domena pasuje do hosta bieżącego żądania.
 *
 * Dodatkowo: lokalny dev rejestruje fallback `Route::domain('{host}')` dla
 * dostępu z innego urządzenia w sieci (patrz StorefrontServiceProvider) —
 * ten wzorzec nigdy nie pasuje dosłownie (`getDomain() === $host`), więc bez
 * tego wszystkie linki spadałyby do PIERWSZEJ zarejestrowanej trasy o danej
 * nazwie (czyli zawsze na „localhost"). Gdy nie ma dokładnego dopasowania,
 * ale istnieje wariant `{host}`, generujemy go z hostem żądania jako
 * parametrem domeny — link zostaje zgodny z adresem, pod którym otwarto stronę.
 */
class TenantUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true)
    {
        if (is_string($name)) {
            $host = $this->request?->getHost();

            if ($host !== null) {
                $exactMatch = null;
                $wildcardMatch = null;

                foreach ($this->routes->getRoutes() as $route) {
                    if ($route->getName() !== $name) {
                        continue;
                    }
                    if ($route->getDomain() === $host) {
                        $exactMatch = $route;
                        break;
                    }
                    if ($route->getDomain() === '{host}') {
                        $wildcardMatch = $route;
                    }
                }

                if ($exactMatch !== null) {
                    return $this->toRoute($exactMatch, $parameters, $absolute);
                }

                if ($wildcardMatch !== null) {
                    $withHost = ['host' => $host] + (is_array($parameters) ? $parameters : [$parameters]);

                    return $this->toRoute($wildcardMatch, $withHost, $absolute);
                }
            }
        }

        return parent::route($name, $parameters, $absolute);
    }
}
