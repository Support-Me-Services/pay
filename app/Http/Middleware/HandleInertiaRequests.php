<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            // Token CSRF — dla ręcznych żądań fetch (auto-zapis, notatki CRM,
            // upload obrazka w edytorze), które nie przechodzą przez router Inertia.
            'csrf_token' => fn () => csrf_token(),

            // Komunikaty flash (redirecty kontrolerów -> Inertia).
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],

            // Zalogowane konto (dla panelu).
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'name' => $request->user()->name,
                    'handle' => $request->user()->handle,
                ] : null,
            ],

            // SEO/OG dla publicznego storefrontu — obraz OG z hashem (cache-bust
            // crawlera) i bieżący URL liczone serwerowo (potrzebne do <Head> w SSR).
            'seo' => fn () => config('platform.role') === 'storefront' ? [
                'ogImage' => asset('img/og-supportme.png') . '?v=' . substr(md5_file(public_path('img/og-supportme.png')), 0, 8),
                'url' => url()->current(),
                'siteName' => 'SupportME',
                'shopName' => config('shop.name', 'SupportME'),
            ] : null,

            // Trasy publiczne storefrontu — używane przez StorefrontLayout i strony
            // (Inertia nie ma Ziggy; udostępniamy potrzebne URL-e bezparametrowe).
            'routes' => fn () => config('platform.role') === 'storefront' ? [
                'main' => route('main'),
                'home' => route('home'),
                'beneficiaries' => route('beneficiaries'),
                'careers' => route('careers'),
                'investors' => route('investors'),
                'regulamin' => route('regulamin'),
                'thanks' => route('thanks'),
            ] : null,

            // Nawigacja panelu + liczniki — tylko na trasach panelu (lazy).
            // Rola (storefront|gateway) rozstrzyga zestaw tras: na hoście bramki
            // trasy storefrontu NIE istnieją, więc nie wolno ich tu wołać.
            'panel' => fn () => $request->routeIs('panel.*')
                ? (config('platform.role') === 'gateway'
                    ? $this->gatewayPanel($request)
                    : $this->storefrontPanel($request))
                : null,
        ];
    }

    /** Nawigacja panelu sklepu (storefront) — pozycje filtrowane widocznością sekcji per-konto. */
    private function storefrontPanel(Request $request): array
    {
        $user = $request->user();

        // Trasy panelu dostępne dla gościa (login, register) też pasują do
        // 'panel.*' — bez zalogowanego konta nie ma czego filtrować.
        if (! $user) {
            return [
                'brand' => config('shop.name', 'SupportME'),
                'nav' => [],
                'logoutUrl' => route('panel.logout'),
            ];
        }

        $nav = [
            ['label' => 'Parafie', 'href' => route('panel.products.index'), 'active' => $request->routeIs('panel.products.*') || $request->routeIs('panel.parishes.*')],
        ];

        if ($user->canSee('beneficiaries')) {
            $nav[] = ['label' => 'O nas', 'href' => route('panel.beneficiaries.index'), 'active' => $request->routeIs('panel.beneficiaries.*')];
        }
        if ($user->canSee('shop-items')) {
            $nav[] = ['label' => 'Sklep', 'href' => route('panel.shop-items.index'), 'active' => $request->routeIs('panel.shop-items.*')];
        }
        if ($user->canSee('positions')) {
            $nav[] = ['label' => 'Praca', 'href' => route('panel.positions.index'), 'active' => $request->routeIs('panel.positions.*')];
        }
        if ($user->canSee('applications')) {
            $nav[] = ['label' => 'Aplikacje', 'href' => route('panel.applications.index'), 'active' => $request->routeIs('panel.applications.*') && ! $request->routeIs('panel.applications.consents'), 'badge' => \App\Modules\Storefront\Models\JobApplication::forUser($user->id)->where('is_read', false)->count() ?: null];
            $nav[] = ['label' => 'Baza kandydatów', 'href' => route('panel.applications.consents'), 'active' => $request->routeIs('panel.applications.consents')];
        }

        $nav[] = ['label' => 'Zmiana hasła', 'href' => route('panel.password.edit'), 'active' => $request->routeIs('panel.password.*')];

        if ($user->is_admin) {
            $nav[] = ['label' => 'Użytkownicy', 'href' => route('panel.users.index'), 'active' => $request->routeIs('panel.users.*')];
        }

        return [
            'brand' => config('shop.name', 'SupportME'),
            'nav' => $nav,
            'logoutUrl' => route('panel.logout'),
        ];
    }

    /** Nawigacja panelu bramki płatności (gateway). */
    private function gatewayPanel(Request $request): array
    {
        return [
            'brand' => 'SupportME',
            'nav' => [
                ['label' => 'Dashboard', 'href' => route('panel.dashboard'), 'active' => $request->routeIs('panel.dashboard')],
                ['label' => 'Sklepy', 'href' => route('panel.shops.index'), 'active' => $request->routeIs('panel.shops.*') || $request->routeIs('panel.tags.*')],
                ['label' => 'Statystyki', 'href' => route('panel.stats'), 'active' => $request->routeIs('panel.stats')],
                ['label' => 'Płatności', 'href' => route('panel.transactions'), 'active' => $request->routeIs('panel.transactions')],
                ['label' => 'Leady', 'href' => route('panel.leads'), 'active' => $request->routeIs('panel.leads')],
                ['label' => 'AntiTheft', 'href' => route('panel.antitheft'), 'active' => $request->routeIs('panel.antitheft')],
                ['label' => 'Zmiana hasła', 'href' => route('panel.password.edit'), 'active' => $request->routeIs('panel.password.*')],
            ],
            'logoutUrl' => route('panel.logout'),
        ];
    }
}
