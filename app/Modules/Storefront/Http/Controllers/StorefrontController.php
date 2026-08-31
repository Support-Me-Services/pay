<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Jobs\SendGatewayEvent;
use App\Modules\Storefront\Models\ShopItem;
use Inertia\Inertia;

class StorefrontController extends Controller
{
    /**
     * GET / — strona główna (landing SupportME wg Figmy).
     *
     * Sekcja „Kogo wspieramy?" to statyczne kafelki (ikona + etykieta, na sztywno
     * w Storefront/Home.jsx — system Kategorie usunięty, kafelki nie są już
     * edytowalne z panelu), wszystkie prowadzące do podstrony „Wspieramy" (/beneficiaries).
     */
    public function index()
    {
        // Logo mecenasa (LokalnyRolnik) do modala podziękowania — pierwszy istniejący format.
        $mecenasLogo = null;
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (is_file(public_path("img/mecenasi/lokalnyrolnik.$ext"))) {
                $mecenasLogo = asset("img/mecenasi/lokalnyrolnik.$ext");
                break;
            }
        }

        return Inertia::render('Storefront/Home', [
            'beneficiariesUrl' => route('beneficiaries'),
            // Modal „Dziękujemy" po powrocie z płatności (redirect ...?thank-you-page=1
            // dla ogólnego podziękowania, albo ...?thank-you-page={slug} dla konkretnego
            // produktu — sama obecność parametru pokazuje modal).
            'showThanks' => (bool) request('thank-you-page'),
            'mecenasLogo' => $mecenasLogo,
            'mecenasUrl' => route('mecenasi.lokalnyrolnik'),
            'mainUrl' => route('main'),
            // Wlasna tresc podziekowania danego produktu (Zbiorki), jesli
            // wartoscia ?thank-you-page= jest jego slug i ma cokolwiek
            // zdefiniowane — inaczej null (fallback na sztywny tekst w Home.jsx).
            'itemThanks' => $this->itemThanks(),
        ]);
    }

    /** Definiowalna treść podziękowania konkretnego produktu (Zbiórki), jeśli ustawiona. */
    private function itemThanks(): ?array
    {
        $slug = request('thank-you-page');
        if (! $slug || $slug === '1') {
            return null;
        }

        $item = ShopItem::with('mecenasOrganization')->where('slug', $slug)->first();
        if (! $item) {
            return null;
        }

        $mecenas = $item->mecenasOrganization;
        $hasCustom = $item->thank_you_heading || $item->thank_you_body || $item->thank_you_image || $mecenas;
        if (! $hasCustom) {
            return null;
        }

        return [
            'heading' => $item->thank_you_heading,
            // Akapity oddzielone pustą linią (jak w panelu).
            'body' => $item->thank_you_body
                ? array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $item->thank_you_body))))
                : null,
            'image' => $item->thank_you_image ? asset($item->thank_you_image) : null,
            // Mecenas = wybrana organizacja — nazwa/logo/URL pochodzą z jej profilu.
            'mecenasName' => $mecenas?->name,
            'mecenasUrl' => $mecenas ? route('user.shop', $mecenas->handle) : null,
            'mecenasLogo' => $mecenas?->logo ? asset($mecenas->logo) : null,
        ];
    }

    /**
     * GET /t/{tag_uid} — wejście z taga NFC: jeśli tag wskazuje produkt sklepu
     * donacyjnego (Zbiórki), kierujemy na stronę sklepu z auto-otwarciem
     * modala tego produktu; w przeciwnym razie 404.
     */
    public function tag(string $tagUid)
    {
        $item = ShopItem::where('tag_uid', $tagUid)->where('active', true)->first();

        if ($item) {
            SendGatewayEvent::dispatchAfterResponse('tag_open', $tagUid);

            return redirect()->route('home', ['produkt' => $item->slug], 302);
        }

        return Inertia::render('Storefront/TagNotFound', [
            // Strona kategorii usunięta — powrót prowadzi na podstronę „Wspieramy".
            'categoryUrl' => route('beneficiaries'),
        ])->toResponse(request())->setStatusCode(404);
    }
}
