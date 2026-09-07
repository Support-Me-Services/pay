<?php

namespace App\Modules\Init\Http\Controllers;

use App\Modules\Storefront\Jobs\SendGatewayEvent;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Inertia\Inertia;

/**
 * Faza 5 migracji: InitCode żyje w core-svc — rozwiązanie kodu (aktywność,
 * scope właściciela) robi core-svc.InitCodeService.Resolve; Laravel tylko
 * dopytuje o docelowy byt (ShopItem w org-svc / Organization lokalnie) i
 * weryfikuje, że TEN byt też jest wciąż aktywny — core-svc nie ma wglądu
 * w org-svc, więc ta ostatnia warstwa obrony zostaje tutaj.
 */
class InitController extends Controller
{
    /**
     * GET /init/tag/{uuid} i GET /init/qr/{uuid} — inicjalizacja kontaktu po
     * zbliżeniu telefonu do tagu NFC albo zeskanowaniu kodu QR. Ten sam kod
     * (ten sam uuid) działa pod obydwoma adresami — kanał jest tylko
     * informacją dla analityki, nie osobnym bytem.
     *
     * Cel jest ZAWSZE dynamiczny: zmiana w panelu natychmiast zmienia, dokąd
     * trafia kolejna osoba skanująca ten sam fizyczny tag/QR.
     *   - kod organizacji -> konkretny produkt (ShopItem, org-svc).
     *   - kod osobisty użytkownika -> cała lista zbiórek wybranej organizacji.
     */
    public function show(string $uuid)
    {
        $resolved = app(OrgSvcClient::class)->resolveInitCode($uuid);

        if ($resolved['found'] && $resolved['targetType'] === 'SHOP_ITEM') {
            $shopItem = $this->tryGetShopItem((int) $resolved['targetId']);
            if ($shopItem && $shopItem['active']) {
                SendGatewayEvent::dispatchAfterResponse('tag_open', $uuid);

                return redirect()->route('home', ['produkt' => $shopItem['slug']], 302);
            }
        }

        if ($resolved['found'] && $resolved['targetType'] === 'ORGANIZATION') {
            $org = Organization::find($resolved['targetId']);
            if ($org) {
                SendGatewayEvent::dispatchAfterResponse('tag_open', $uuid);

                return redirect()->route('user.shop', $org->handle, 302);
            }
        }

        return Inertia::render('Storefront/TagNotFound', [
            'categoryUrl' => route('beneficiaries'),
        ])->toResponse(request())->setStatusCode(404);
    }

    /** org-svc zwraca 404 dla nieistniejącego produktu — traktujemy jak brak trafienia. */
    private function tryGetShopItem(int $id): ?array
    {
        try {
            return app(OrgSvcClient::class)->getShopItem($id);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
