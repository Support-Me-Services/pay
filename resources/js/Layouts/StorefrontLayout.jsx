import { useState } from 'react'
import { Head, usePage } from '@inertiajs/react'

const DEFAULT_TITLE = 'SupportME — technologia, która pomaga czynić dobro'
const DEFAULT_DESC = 'Łączymy ludzi, wartości i nowoczesne płatności, aby wspieranie ważnych inicjatyw było prostsze niż kiedykolwiek wcześniej.'
// Zakładka czasowo ukryta na życzenie — ustaw na true, aby przywrócić link w nav/stopce.
const SHOW_INVESTORS_LINK = false

/**
 * Layout publicznego storefrontu (marketing) — odwzorowuje church/layouts/landing.blade.php.
 * OG/meta (podglądy linków) z współdzielonego `seo` (obraz z hashem, URL liczone serwerowo).
 * Nawigacja/koszyk zależne od `shopHandle` (sklep per-konto) — przekazywane przez stronę.
 * Reużywa landing.css; strona może dołożyć własny arkusz przez `css`.
 */
export default function StorefrontLayout({ children, ...overrides }) {
    const { props: page, url: currentUrl } = usePage()
    const seo = page.seo || {}
    const r = page.routes || {}
    // Meta strony i CSS przychodzą jako propsy strony (z kontrolera/trasy); można
    // je nadpisać propsem komponentu. Domyślne wartości jak w layouts/landing.blade.
    const title = overrides.title ?? page.pageTitle ?? DEFAULT_TITLE
    const metaDescription = overrides.metaDescription ?? page.pageDescription ?? DEFAULT_DESC
    const css = overrides.css ?? page.css ?? null
    // Libre Baskerville jest używana wyłącznie w sekcjach marketingowych (lp-hero,
    // lp-head, lp-step...) — strony bez takich sekcji (np. paywin) nie muszą jej
    // pobierać. Domyślnie true, żeby nic nie przestało działać na stronach, które
    // faktycznie jej używają.
    const needsDisplayFont = overrides.needsDisplayFont ?? page.needsDisplayFont ?? true
    const shopHandle = overrides.shopHandle ?? page.shopHandle ?? null
    const ownerName = overrides.ownerName ?? page.ownerName ?? null
    const cartCount = overrides.cartCount ?? page.cartCount ?? 0
    const [menuOpen, setMenuOpen] = useState(false)
    const [qrOpen, setQrOpen] = useState(false)

    const cartUrl = shopHandle ? `/people/${shopHandle}/koszyk` : null
    const shopUrl = shopHandle ? `/people/${shopHandle}` : null
    // QR sklepu koduje ABSOLUTNY URL sklepu (jak w Blade: route('user.shop')),
    // nie bieżącą podstronę — inaczej kod QR np. w koszyku prowadzi w złe miejsce.
    const shopQrData = shopUrl && seo.url ? new URL(shopUrl, seo.url).href : (seo.url || '')
    const shopName = ownerName || shopHandle
    // aria-current na aktywnym linku nav (odpowiednik request()->routeIs() z Blade).
    const curPath = (currentUrl || '').split('?')[0]
    const navPath = (href) => { try { return new URL(href, 'http://x').pathname } catch { return href || '' } }
    const isNavActive = (href) => !!href && navPath(href) === curPath

    return (
        <div className="lp-page">
            <Head>
                <title>{title}</title>
                <meta name="theme-color" content="#1473C0" />
                <meta name="description" content={metaDescription} />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content={seo.siteName || 'SupportME'} />
                <meta property="og:title" content={title} />
                <meta property="og:description" content={metaDescription} />
                {seo.url && <meta property="og:url" content={seo.url} />}
                {seo.ogImage && <meta property="og:image" content={seo.ogImage} />}
                {seo.ogImage && <meta property="og:image:secure_url" content={seo.ogImage} />}
                <meta property="og:image:type" content="image/png" />
                <meta property="og:image:width" content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt" content="SupportME" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={title} />
                <meta name="twitter:description" content={metaDescription} />
                {seo.ogImage && <meta name="twitter:image" content={seo.ogImage} />}
                {/* Inter hostowany lokalnie (public/css/fonts-inter.css) — zero zależności
                    od fonts.googleapis.com/fonts.gstatic.com, żeby formularz nie czekał na
                    dodatkowe round-tripy do obcej domeny na wolnym/niestabilnym 3G. */}
                <link rel="stylesheet" href="/css/fonts-inter.css" />
                {needsDisplayFont && (
                    <>
                        <link rel="preconnect" href="https://fonts.googleapis.com" />
                        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
                        <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet" />
                    </>
                )}
                <link rel="stylesheet" href="/css/landing.css" />
                {css && <link rel="stylesheet" href={css} />}
            </Head>

            <header className={`lp-header${menuOpen ? ' lp-open' : ''}`}>
                <a href={shopUrl || r.main} aria-label="SupportME">
                    <img className="lp-logo" src="/img/landing/logo.svg" alt="SupportME" />
                </a>
                <nav className="lp-nav">
                    <a href={r.main} aria-current={isNavActive(r.main) ? 'page' : undefined}>Strona główna</a>
                    <a href={r.beneficiaries} aria-current={isNavActive(r.beneficiaries) ? 'page' : undefined}>O nas</a>
                    <a href={r.careers} aria-current={isNavActive(r.careers) ? 'page' : undefined}>Rekrutacja</a>
                    {SHOW_INVESTORS_LINK && (
                        <a href={r.investors} aria-current={isNavActive(r.investors) ? 'page' : undefined}>Inwestorzy i akcjonariusze</a>
                    )}
                    {shopHandle ? (
                        <a className="lp-nav__support" href={cartUrl}>Koszyk
                            {cartCount > 0 && <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', minWidth: 20, height: 20, marginLeft: 7, padding: '0 5px', borderRadius: 10, background: '#fff', color: '#2563eb', fontSize: 12, fontWeight: 800, lineHeight: 1, verticalAlign: 'middle' }}>{cartCount}</span>}
                        </a>
                    ) : (
                        <a className="lp-nav__support" href={r.home}>Wesprzyj</a>
                    )}
                </nav>
                <button className="lp-burger" type="button" aria-label="Menu" aria-expanded={menuOpen}
                    onClick={() => setMenuOpen((o) => !o)}>
                    <span></span><span></span><span></span>
                </button>
            </header>

            {children}

            <footer className="lp-footer">
                <div className="lp-footer__inner">
                    <img className="lp-footer__logo" src="/img/landing/logo-footer.svg" alt="SupportME" />
                    <div className="lp-footer__links">
                        <a href={r.careers}>Rekrutacja</a>
                        {SHOW_INVESTORS_LINK && <a href={r.investors}>Inwestorzy i akcjonariusze</a>}
                        <a href={r.regulamin}>Regulamin</a>
                        <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Polityka prywatności</a>
                        <a href={r.thanks}>Dziękujemy</a>
                        <a href={r.panel}>Panel</a>
                    </div>
                    <a className="lp-footer__social" href="https://www.linkedin.com/" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <img src="/img/landing/linkedin.svg" alt="LinkedIn" />
                    </a>
                    <div className="lp-footer__legal">
                        FUNDACJA SUPPORT ME HAVEN &amp; HEAVEN<br />
                        NIP: 5342715041<br />
                        REGON: 545334957<br />
                        <br />
                        <a href="mailto:founder@please-support-me.com" aria-label="E-mail"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style={{ width: '1em', height: '1em', verticalAlign: '-.12em', marginRight: '.45em' }}><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z" /></svg>founder@please-support-me.com</a><br />
                        <a href="tel:+48453613718" aria-label="Telefon"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style={{ width: '1em', height: '1em', verticalAlign: '-.12em', marginRight: '.45em' }}><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" /></svg>+48 453 613 718</a>
                    </div>
                    {shopHandle ? (
                        <div className="lp-footer__qr">
                            <a className="lp-footer__qr-card" href={shopUrl} aria-label={`Przejdź do sklepu ${shopName}`}>
                                <img src={`https://api.qrserver.com/v1/create-qr-code/?size=216x216&margin=0&data=${encodeURIComponent(shopQrData)}`} alt={`Kod QR do sklepu ${shopName}`} width="108" height="108" />
                            </a>
                            <div className="lp-footer__qr-caption"><strong>Zeskanuj — sklep</strong>{shopName}</div>
                        </div>
                    ) : (
                        <div className="lp-footer__qr">
                            <a className="lp-footer__qr-card" href="/img/qr-home.svg" aria-label="Powiększ kod QR"
                                onClick={(e) => { e.preventDefault(); setQrOpen(true) }} style={{ cursor: 'zoom-in' }}>
                                <img src="/img/qr-home.svg" alt="Kod QR prowadzący na stronę please-support-me.com" width="108" height="108" />
                            </a>
                            <div className="lp-footer__qr-caption"><strong>Zeskanuj — wejdź na stronę</strong>please-support-me.com</div>
                        </div>
                    )}
                </div>
            </footer>

            <div className={`qr-zoom${qrOpen ? ' is-open' : ''}`} role="dialog" aria-modal="true" aria-label="Powiększony kod QR"
                onClick={() => setQrOpen(false)}
                style={{ position: 'fixed', inset: 0, zIndex: 1000, display: qrOpen ? 'flex' : 'none', alignItems: 'center', justifyContent: 'center', padding: 24, background: 'rgba(0,0,0,.82)', cursor: 'zoom-out' }}>
                <img src="/img/qr-home.svg" alt="Kod QR prowadzący na stronę please-support-me.com"
                    style={{ width: 'min(80vw,80vh,420px)', height: 'auto', background: '#fff', padding: 18, borderRadius: 14 }} />
            </div>

            <style>{`
                .lp-footer__qr{ display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:4px; }
                .lp-footer__qr-card{ background:#fff; padding:8px; border-radius:12px; line-height:0; box-shadow:0 4px 14px rgba(0,0,0,.18); cursor:zoom-in; }
                .lp-footer__qr-card img{ display:block; width:108px; height:108px; }
                .lp-footer__qr-caption{ font-size:15px; line-height:1.35; color:#fff; opacity:.95; }
                .lp-footer__qr-caption strong{ display:block; font-weight:600; }
            `}</style>
        </div>
    )
}
