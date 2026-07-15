import { Head, usePage } from '@inertiajs/react'

const DEFAULT_DESC = 'Cyfrowa taca — wesprzyj swój kościół jednym dotknięciem telefonu. Szybko, bezpiecznie, bez gotówki.'

/**
 * Layout publiczny sklepu church — odwzorowuje church/layouts/public.blade.php.
 * `bare` (jak @section('bare')) pomija nagłówek/stopkę — tylko treść + church.css
 * (ekrany statusu płatności). OG/meta z współdzielonego `seo`.
 */
export default function StorefrontPublicLayout({ children, bare = false, bodyClass = '', ...overrides }) {
    const page = usePage().props
    const seo = page.seo || {}
    const r = page.routes || {}
    const title = overrides.title ?? page.pageTitle ?? seo.shopName ?? 'SupportME'
    const metaDescription = overrides.metaDescription ?? page.pageDescription ?? DEFAULT_DESC

    const head = (
        <Head>
            <title>{title}</title>
            <meta name="theme-color" content="#141d33" />
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
            <meta name="twitter:card" content="summary_large_image" />
            {seo.ogImage && <meta name="twitter:image" content={seo.ogImage} />}
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
            <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet" />
            <link rel="stylesheet" href="/css/church.css" />
        </Head>
    )

    if (bare) {
        return <>{head}{children}</>
    }

    return (
        <>
            {head}
            <style>{`.header-nav{display:flex;gap:18px}.header-nav a{color:#f6f1e6;opacity:.85;font-size:.92rem;font-weight:500;text-decoration:none}.header-nav a:hover{opacity:1;color:#e2bf6a}`}</style>
            <header className="site-header">
                <div className="bar">
                    <a href={r.main} className="wordmark">
                        <span className="plate" aria-hidden="true"></span>{seo.shopName || 'SupportME'}
                    </a>
                    <nav className="header-nav">
                        <a href={r.careers}>Praca</a>
                        <a href={r.contact}>Kontakt</a>
                    </nav>
                </div>
            </header>

            {children}

            <footer className="site-footer">
                <div className="container">
                    <strong>{seo.shopName || 'SupportME'}</strong> — wpłaty obsługuje <span className="brand">PayU</span>.<br />
                    Twoje wsparcie trafia w całości do wybranej parafii.
                    <div className="fine">
                        Operator płatności: Support Me Services Marcin Lula · ul. dr Izabeli Wolfram 11, 05-800 Pruszków · NIP 8741624637<br />
                        <a href={r.careers}>Praca</a> · <a href={r.contact}>Kontakt</a> · <a href={r.regulamin}>Regulamin</a> · <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Polityka prywatności</a> · kontakt: kontakt@please-support-me.com · &copy; {new Date().getFullYear()}
                    </div>
                </div>
            </footer>
        </>
    )
}
