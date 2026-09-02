<?php

namespace App\Modules\Init\Http\Controllers;

use App\Modules\Init\Models\InitCode;
use App\Modules\Storefront\Jobs\SendGatewayEvent;
use Inertia\Inertia;

class InitController extends Controller
{
    /**
     * GET /init/tag/{uuid} i GET /init/qr/{uuid} — inicjalizacja kontaktu po
     * zbliżeniu telefonu do tagu NFC albo zeskanowaniu kodu QR. Ten sam kod
     * (ten sam uuid) działa pod obydwoma adresami — kanał jest tylko
     * informacją dla analityki, nie osobnym bytem.
     *
     * Cel jest ZAWSZE dynamiczny: zwykłe pole w bazie, odczytywane na świeżo
     * przy każdym skanie — zmiana w panelu natychmiast zmienia, dokąd trafia
     * kolejna osoba skanująca ten sam fizyczny tag/QR.
     *   - kod organizacji (organization_id) -> konkretny produkt (shop_item_id).
     *   - kod osobisty użytkownika (owner_user_id) -> cała lista zbiórek
     *     wybranej przez niego organizacji (target_organization_id).
     */
    public function show(string $uuid)
    {
        $code = InitCode::where('uuid', $uuid)->where('active', true)->first();

        if ($code?->shop_item_id && $code->shopItem?->active) {
            SendGatewayEvent::dispatchAfterResponse('tag_open', $uuid);

            return redirect()->route('home', ['produkt' => $code->shopItem->slug], 302);
        }

        if ($code?->target_organization_id && $code->targetOrganization) {
            SendGatewayEvent::dispatchAfterResponse('tag_open', $uuid);

            return redirect()->route('user.shop', $code->targetOrganization->handle, 302);
        }

        return Inertia::render('Storefront/TagNotFound', [
            'categoryUrl' => route('beneficiaries'),
        ])->toResponse(request())->setStatusCode(404);
    }
}
