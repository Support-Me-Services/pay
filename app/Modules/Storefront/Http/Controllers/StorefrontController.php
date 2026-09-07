<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
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

        // Faza 3 migracji: ShopItem żyje w org-svc — global lookup po slug
        // (jak dawniej, świadomie bez scope'owania po organizacji, patrz
        // historia tej metody) przez listAll(), bo org-svc nie ma dziś RPC
        // "get by slug".
        $item = collect(app(OrgSvcClient::class)->listShopItems())->firstWhere('slug', $slug);
        if (! $item) {
            return null;
        }

        $mecenas = $item['mecenasOrganizationId'] ? Organization::find($item['mecenasOrganizationId']) : null;
        $hasCustom = $item['thankYouHeading'] || $item['thankYouBody'] || $item['thankYouImage'] || $mecenas;
        if (! $hasCustom) {
            return null;
        }

        return [
            'heading' => $item['thankYouHeading'],
            // Akapity oddzielone pustą linią (jak w panelu).
            'body' => $item['thankYouBody']
                ? array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $item['thankYouBody']))))
                : null,
            'image' => $item['thankYouImage'] ? asset($item['thankYouImage']) : null,
            // Mecenas = wybrana organizacja — nazwa/logo/URL pochodzą z jej profilu.
            'mecenasName' => $mecenas?->name,
            'mecenasUrl' => $mecenas ? route('user.shop', $mecenas->handle) : null,
            'mecenasLogo' => $mecenas?->logo ? asset($mecenas->logo) : null,
        ];
    }
}
