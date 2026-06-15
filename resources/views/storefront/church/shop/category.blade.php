@extends('layouts.landing')

@section('title', $category['label_text'] . ' — SupportME')

@section('content')
    <section class="lp-section lp-catpage lp-container">
        <a class="lp-back" href="{{ route('home') }}#kogo-wspieramy">← Wszystkie kategorie</a>
        <div class="lp-head">
            <h2>{{ $category['label_text'] }}</h2>
            <p>{{ $category['intro'] }}</p>
        </div>

        @if($products->isNotEmpty())
            @php
                /*
                 * 16 województw RP. Mapa wybranych miast → województwo służy do wyprowadzenia
                 * filtra, gdy kolumna products.voivodeship jest pusta (jest opcjonalna w CRM).
                 * Bez migracji — wyłącznie po stronie widoku. Klucze: city w lowercase (PL).
                 */
                $lpVoivodeships = [
                    'dolnośląskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
                    'łódzkie', 'małopolskie', 'mazowieckie', 'opolskie',
                    'podkarpackie', 'podlaskie', 'pomorskie', 'śląskie',
                    'świętokrzyskie', 'warmińsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie',
                ];
                $lpCityToVoiv = [
                    'kraków' => 'małopolskie', 'tarnów' => 'małopolskie', 'nowy sącz' => 'małopolskie',
                    'warszawa' => 'mazowieckie', 'radom' => 'mazowieckie', 'płock' => 'mazowieckie',
                    'wrocław' => 'dolnośląskie', 'świdnica' => 'dolnośląskie', 'wałbrzych' => 'dolnośląskie', 'legnica' => 'dolnośląskie', 'jelenia góra' => 'dolnośląskie',
                    'poznań' => 'wielkopolskie', 'kalisz' => 'wielkopolskie', 'konin' => 'wielkopolskie', 'licheń stary' => 'wielkopolskie', 'gniezno' => 'wielkopolskie',
                    'gdańsk' => 'pomorskie', 'gdynia' => 'pomorskie', 'sopot' => 'pomorskie', 'słupsk' => 'pomorskie',
                    'katowice' => 'śląskie', 'częstochowa' => 'śląskie', 'gliwice' => 'śląskie', 'sosnowiec' => 'śląskie', 'bielsko-biała' => 'śląskie',
                    'łódź' => 'łódzkie', 'piotrków trybunalski' => 'łódzkie',
                    'lublin' => 'lubelskie', 'zamość' => 'lubelskie', 'chełm' => 'lubelskie',
                    'szczecin' => 'zachodniopomorskie', 'koszalin' => 'zachodniopomorskie',
                    'bydgoszcz' => 'kujawsko-pomorskie', 'toruń' => 'kujawsko-pomorskie', 'włocławek' => 'kujawsko-pomorskie',
                    'białystok' => 'podlaskie', 'łomża' => 'podlaskie', 'suwałki' => 'podlaskie',
                    'rzeszów' => 'podkarpackie', 'przemyśl' => 'podkarpackie', 'krosno' => 'podkarpackie',
                    'kielce' => 'świętokrzyskie', 'ostrowiec świętokrzyski' => 'świętokrzyskie',
                    'olsztyn' => 'warmińsko-mazurskie', 'elbląg' => 'warmińsko-mazurskie',
                    'opole' => 'opolskie', 'nysa' => 'opolskie',
                    'gorzów wielkopolski' => 'lubuskie', 'zielona góra' => 'lubuskie',
                ];
                $lpVoivForCard = function ($product) use ($lpCityToVoiv) {
                    $v = trim((string) ($product->voivodeship ?? ''));
                    if ($v !== '') {
                        return mb_strtolower($v, 'UTF-8');
                    }
                    $city = mb_strtolower(trim((string) ($product->city ?? '')), 'UTF-8');
                    return $lpCityToVoiv[$city] ?? '';
                };

                // Lista istniejących miast (do suggestboxa) — tylko te, które realnie występują.
                $lpCities = $products->pluck('city')
                    ->filter()
                    ->map(fn ($c) => trim((string) $c))
                    ->unique()
                    ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                    ->values();
            @endphp

            <div class="lp-search" data-lp-search>
                <div class="lp-search__row">
                    <label class="lp-search__field lp-search__field--grow">
                        <input type="text" data-lp-q placeholder="Szukaj po nazwie…" autocomplete="off" aria-label="Szukaj po nazwie">
                    </label>
                    <label class="lp-search__field">
                        <input type="text" data-lp-city list="lp-city-list" placeholder="Miasto…" autocomplete="off" aria-label="Miasto">
                    </label>
                    <datalist id="lp-city-list">
                        @foreach($lpCities as $lpCity)
                            <option value="{{ $lpCity }}"></option>
                        @endforeach
                    </datalist>
                    <label class="lp-search__field">
                        <select data-lp-voiv aria-label="Województwo">
                            <option value="">Wszystkie województwa</option>
                            @foreach($lpVoivodeships as $voiv)
                                <option value="{{ $voiv }}">{{ \Illuminate\Support\Str::ucfirst($voiv) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="lp-search__btn" data-lp-go>Szukaj</button>
                </div>
            </div>

            <p class="lp-search__empty" data-lp-noresults hidden>Brak wyników dla podanych kryteriów.</p>

            <div class="lp-grid">
                @foreach($products as $product)
                    <a class="lp-pcard"
                       href="{{ route('product.show', $product->slug) }}"
                       data-name="{{ mb_strtolower($product->name, 'UTF-8') }}"
                       data-city="{{ mb_strtolower((string) $product->city, 'UTF-8') }}"
                       data-voiv="{{ $lpVoivForCard($product) }}">
                        <div class="lp-pcard__art">
                            @if($product->main_image)
                                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" loading="lazy">
                            @endif
                            @if($product->city)
                                <span class="lp-pcard__city">{{ $product->city }}</span>
                            @endif
                        </div>
                        <div class="lp-pcard__body">
                            <span class="lp-pcard__name">{{ $product->name }}</span>
                            @if($product->purpose)
                                <span class="lp-pcard__purpose">{{ $product->purpose }}</span>
                            @endif
                            <span class="lp-pcard__cta">
                                <span>już od <b>{{ $product->pricePln() }} zł</b></span>
                                <b>Wesprzyj →</b>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="lp-empty">
                W tej kategorii nie ma jeszcze organizacji.<br>
                Pracujemy nad tym — w międzyczasie zobacz
                <a href="{{ route('category', 'miejsca-kultu') }}">miejsca kultu i organizacje religijne</a>,
                które już wspieramy.
            </p>
        @endif
    </section>

    @if($products->isNotEmpty())
        <style>
            /* Pasek wyszukiwarki parafii — spójny z marką (tokeny --lp-*). */
            .lp-search{
                max-width:1142px; margin:34px auto 40px; padding:0 var(--lp-gutter);
            }
            .lp-search__row{
                display:flex; flex-wrap:wrap; gap:12px; align-items:stretch;
            }
            .lp-search__btn{
                flex:0 0 auto; display:inline-flex; align-items:center; gap:8px;
                border:0; cursor:pointer; border-radius:14px; padding:0 26px;
                font-family:var(--lp-ui); font-size:16px; font-weight:600; color:#fff;
                background:linear-gradient(117deg, var(--lp-blue-a), var(--lp-blue-b));
                box-shadow:0 8px 20px rgba(20,115,192,.22); transition:transform .15s ease, box-shadow .15s ease;
            }
            .lp-search__btn:hover{ transform:translateY(-1px); box-shadow:0 12px 26px rgba(20,115,192,.3); }
            .lp-search__btn-icon{ font-size:15px; }
            /* wyszukiwarka daje już odstęp do siatki — nie dublujemy marginesu */
            .lp-catpage .lp-grid{ margin-top:0; }
            .lp-search__field{
                position:relative; display:flex; align-items:center; min-width:200px;
                background:#fff; border:1px solid var(--lp-wave); border-radius:14px;
                padding:0 14px; transition:border-color .15s ease, box-shadow .15s ease;
            }
            .lp-search__field--grow{ flex:1 1 280px; }
            .lp-search__field:focus-within{
                border-color:var(--lp-blue-b); box-shadow:0 0 0 3px rgba(20,115,192,.14);
            }
            .lp-search__icon{ font-size:15px; margin-right:8px; opacity:.6; line-height:1; }
            .lp-search__field input,
            .lp-search__field select{
                flex:1; width:100%; border:0; outline:none; background:transparent;
                font-family:var(--lp-ui); font-size:16px; color:var(--lp-ink);
                padding:13px 0; line-height:1.3;
            }
            .lp-search__field select{ cursor:pointer; padding-right:6px; }
            .lp-search__field input::placeholder{ color:#8b95a5; }
            .lp-search__meta{
                margin-top:14px; font-family:var(--lp-ui); font-size:15px; color:var(--lp-blue-b);
                font-weight:600;
            }
            .lp-search__empty{
                max-width:1142px; margin:0 auto 40px; padding:18px var(--lp-gutter);
                text-align:center; font-family:var(--lp-ui); font-size:18px; color:var(--lp-ink);
            }
            @media (max-width:860px){
                .lp-search, .lp-search__empty{ padding-left:22px; padding-right:22px; }
                .lp-search__field--grow{ flex-basis:100%; }
                .lp-search__field{ flex:1 1 100%; }
                .lp-search__btn{ flex:1 1 100%; width:100%; justify-content:center; padding-top:14px; padding-bottom:14px; }
            }
        </style>

        <script>
        (function () {
            var root = document.querySelector('[data-lp-search]');
            if (!root) { return; }

            var qInput     = root.querySelector('[data-lp-q]');
            var cityInput  = root.querySelector('[data-lp-city]');
            var voivSelect = root.querySelector('[data-lp-voiv]');
            var goBtn      = root.querySelector('[data-lp-go]');
            var grid       = document.querySelector('.lp-grid');
            var noResults  = document.querySelector('[data-lp-noresults]');
            var cards      = grid ? Array.prototype.slice.call(grid.querySelectorAll('.lp-pcard')) : [];

            // Normalizacja: lowercase + usunięcie polskich znaków diakrytycznych.
            function norm(s) {
                s = (s || '').toString().toLowerCase();
                var map = { 'ą':'a','ć':'c','ę':'e','ł':'l','ń':'n','ó':'o','ś':'s','ź':'z','ż':'z' };
                return s.replace(/[ąćęłńóśźż]/g, function (c) { return map[c] || c; });
            }

            // Filtruje karty wg pól. updateUrl=true → zapisuje kryteria do adresu URL
            // (linkowalny / pozycjonowalny wynik), bez przeładowania strony.
            function apply(updateUrl) {
                var q    = norm(qInput ? qInput.value.trim() : '');
                var city = norm(cityInput ? cityInput.value.trim() : '');
                var voiv = norm(voivSelect ? voivSelect.value : '');
                var visible = 0;

                cards.forEach(function (card) {
                    var show = (!q    || norm(card.getAttribute('data-name')).indexOf(q) !== -1)
                            && (!city || norm(card.getAttribute('data-city')).indexOf(city) !== -1)
                            && (!voiv || norm(card.getAttribute('data-voiv')) === voiv);
                    card.style.display = show ? '' : 'none';
                    if (show) { visible++; }
                });

                if (noResults) { noResults.hidden = visible !== 0; }
                if (grid)      { grid.style.display = visible === 0 ? 'none' : ''; }

                if (updateUrl) {
                    var p = new URLSearchParams();
                    if (qInput    && qInput.value.trim())    { p.set('q', qInput.value.trim()); }
                    if (cityInput && cityInput.value.trim()) { p.set('miasto', cityInput.value.trim()); }
                    if (voivSelect && voivSelect.value)      { p.set('woj', voivSelect.value); }
                    var qs = p.toString();
                    history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
                }
            }

            // Wczytaj kryteria z adresu URL przy wejściu (deeplink/pozycjonowanie) i przefiltruj.
            var sp = new URLSearchParams(location.search);
            if (qInput    && sp.get('q'))      { qInput.value = sp.get('q'); }
            if (cityInput && sp.get('miasto')) { cityInput.value = sp.get('miasto'); }
            if (voivSelect && sp.get('woj'))   { voivSelect.value = sp.get('woj'); }
            apply(false);

            // Szukanie DOPIERO po kliknięciu „Szukaj" (lub Enter w polu tekstowym).
            if (goBtn) { goBtn.addEventListener('click', function () { apply(true); }); }
            [qInput, cityInput].forEach(function (el) {
                if (el) { el.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); apply(true); } }); }
            });
        })();
        </script>
    @endif
@endsection
