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
 */
class TenantUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true)
    {
        if (is_string($name)) {
            $host = $this->request?->getHost();

            if ($host !== null) {
                $match = null;

                foreach ($this->routes->getRoutes() as $route) {
                    if ($route->getName() === $name && $route->getDomain() === $host) {
                        $match = $route;
                        break;
                    }
                }

                if ($match !== null) {
                    return $this->toRoute($match, $parameters, $absolute);
                }
            }
        }

        return parent::route($name, $parameters, $absolute);
    }
}
