import { usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.mec-wrap{ --mec-navy:#24324A; --mec-blue:#1473C0; max-width:980px; margin:0 auto; padding:48px 22px 64px; font-family:var(--lp-ui,'Inter',system-ui,sans-serif); color:#3a4961; }
.mec-hero{ display:flex; flex-direction:column; align-items:center; text-align:center; gap:22px; padding:36px 26px 30px; margin-bottom:40px; background:linear-gradient(160deg,#F4F7FA 0%,#EAF1F8 100%); border:1px solid #E6EAF0; border-radius:28px; }
.mec-logo{ display:inline-flex; align-items:center; justify-content:center; background:#fff; border:1px solid #E6EAF0; border-radius:24px; padding:26px 34px; box-shadow:0 14px 40px rgba(20,30,48,.10); }
.mec-logo img{ height:96px; width:auto; max-width:280px; display:block; }
.mec-badge{ display:inline-block; font-size:.74rem; font-weight:700; letter-spacing:.10em; text-transform:uppercase; color:var(--mec-blue); background:#fff; border:1px solid #D7E5F4; border-radius:999px; padding:6px 14px; }
.mec-wrap h1{ font-family:var(--lp-display,'Libre Baskerville',Georgia,serif); color:var(--mec-navy); font-size:2.1rem; line-height:1.18; margin:0; }
.mec-lead{ font-size:1.12rem; line-height:1.6; color:#42526b; max-width:640px; margin:0 auto; }
.mec-section{ margin:34px 0; }
.mec-section h2{ font-family:var(--lp-display,'Libre Baskerville',Georgia,serif); color:var(--mec-navy); font-size:1.5rem; line-height:1.25; margin:0 0 14px; padding-bottom:10px; border-bottom:2px solid #EAF1F8; }
.mec-section p{ font-size:1.04rem; line-height:1.68; margin:0 0 14px; }
.mec-section p:last-child{ margin-bottom:0; }
.mec-section strong{ color:var(--mec-navy); }
.mec-card{ background:#F8FAFC; border:1px solid #E6EAF0; border-left:4px solid var(--mec-blue); border-radius:16px; padding:22px 24px; margin:22px 0; }
.mec-card h2{ border-bottom:0; padding-bottom:0; margin-bottom:10px; }
.mec-cta{ display:flex; flex-wrap:wrap; gap:14px; justify-content:center; margin-top:44px; padding-top:30px; border-top:1px solid #E6EAF0; }
.mec-btn{ display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; text-decoration:none; border-radius:999px; padding:14px 30px; transition:transform .12s ease, box-shadow .12s ease; }
.mec-btn:hover{ transform:translateY(-1px); }
.mec-btn--primary{ background:var(--mec-blue); color:#fff; box-shadow:0 10px 26px rgba(20,115,192,.30); }
.mec-btn--primary:hover{ box-shadow:0 14px 30px rgba(20,115,192,.38); }
.mec-btn--ghost{ background:#fff; color:var(--mec-navy); border:1.5px solid #D7E0EA; }
.mec-btn--ghost:hover{ border-color:var(--mec-blue); color:var(--mec-blue); }
@media(max-width:640px){ .mec-wrap{ padding:32px 16px 48px; } .mec-wrap h1{ font-size:1.65rem; } .mec-logo img{ height:72px; } .mec-section h2{ font-size:1.3rem; } .mec-btn{ width:100%; } }
`

/** Strona „Mecenas: LokalnyRolnik" (statyczna, wg Figmy). */
export default function MecenasiLokalnyRolnik() {
    const r = usePage().props.routes || {}
    return (
        <section className="lp-section">
            <style>{CSS}</style>
            <div className="mec-wrap">
                <div className="mec-hero">
                    <span className="mec-badge">Mecenas SupportME</span>
                    <div className="mec-logo"><img src="/img/mecenasi/lokalnyrolnik.svg" alt="LokalnyRolnik" /></div>
                    <h1>LokalnyRolnik — Mecenas SupportME</h1>
                    <p className="mec-lead">LokalnyRolnik dołącza do grona Mecenasów SupportME, wspierając dobre inicjatywy tak samo, jak na co dzień wspiera polskie rolnictwo i lokalną żywność.</p>
                </div>

                <div className="mec-section">
                    <h2>Kim jest LokalnyRolnik?</h2>
                    <p><strong>LokalnyRolnik</strong> to polska platforma e-commerce, która łączy klientów bezpośrednio z lokalnymi rolnikami oraz producentami żywności. Zamiast długiego, anonimowego łańcucha pośredników, platforma tworzy krótką i przejrzystą drogę „od pola do stołu" — kupujący zamawiają produkty wprost u sprawdzonych gospodarstw i wytwórców z najbliższej okolicy.</p>
                    <p>Ideą stojącą za projektem jest wspieranie lokalnej, sezonowej i świadomej konsumpcji. Dzięki temu klienci zyskują dostęp do świeżych produktów o znanym pochodzeniu, a mali producenci — uczciwy kanał sprzedaży i bezpośredni kontakt z odbiorcami. To model, w którym wygrywa zarówno konsument, jak i rolnik: pieniądze trafiają tam, gdzie powstaje wartość, a żywność pokonuje krótszą drogę.</p>
                    <p>LokalnyRolnik opiera się na wartościach, które są bliskie również SupportME: bliskości ludzi, zaufaniu, jawności i przekonaniu, że nowoczesna technologia powinna realnie ułatwiać codzienne, dobre wybory.</p>
                </div>

                <div className="mec-card">
                    <h2>Na czym polega mecenat?</h2>
                    <p>Jako <strong>Mecenas SupportME</strong>, LokalnyRolnik wspiera — poprzez platformę SupportME — cele fundacji oraz inicjatywy, którym SupportME pomaga zbierać środki. Mecenat oznacza, że marka świadomie staje po stronie pomagania i firmuje swoim imieniem ideę przekuwania dobrych intencji w realne wsparcie.</p>
                    <p>W praktyce wsparcie Mecenasa wzmacnia efekt każdej wpłaty dokonanej za pośrednictwem SupportME. Kiedy darczyńca przekazuje środki na wybrany cel, obecność Mecenasów podkreśla, że za tą pomocą stoi szersza wspólnota — firmy i instytucje, które również chcą, aby dobro szło dalej.</p>
                    <p>Dzięki takiemu partnerstwu wspieranie ważnych inicjatyw staje się prostsze, bardziej wiarygodne i bardziej widoczne — a darczyńcy zyskują pewność, że ich gest jest częścią większego, wspólnego działania.</p>
                </div>

                <div className="mec-section">
                    <h2>Dlaczego to ma znaczenie?</h2>
                    <p>Mecenat to nie tylko logo na stronie podziękowania. To deklaracja wartości. LokalnyRolnik, łącząc ludzi z lokalnymi producentami, na co dzień buduje społeczności oparte na zaufaniu i bliskości — dokładnie tych samych fundamentach, na których opiera się idea SupportME.</p>
                    <p>Wspólnie pokazujemy, że technologia i biznes mogą służyć dobru: ułatwiać pomaganie, wzmacniać lokalne społeczności i sprawiać, że dobre intencje zamieniają się w konkretne, odczuwalne wsparcie.</p>
                </div>

                <div className="mec-cta">
                    <a className="mec-btn mec-btn--ghost" href={r.main}>Wróć na stronę główną</a>
                    <a className="mec-btn mec-btn--primary" href={`${r.home}?produkt=serduszko`}>Wesprzyj</a>
                </div>
            </div>
        </section>
    )
}

MecenasiLokalnyRolnik.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
