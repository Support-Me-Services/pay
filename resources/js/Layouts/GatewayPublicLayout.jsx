import { Head, usePage } from '@inertiajs/react'

const DEFAULT_TITLE = 'SupportME — sprzedawaj bez kasy i bez obsługi'

/**
 * Layout publiczny bramki — odwzorowuje gateway/layouts/public.blade.php.
 * `bare` (jak @section('bare')) pomija nagłówek/stopkę — tylko treść + theme.css
 * (ekrany płatności klienta). Rola gateway nie ma współdzielonych `seo`/`routes`
 * (te są tylko dla storefront), więc linki są względne ('/' + kotwice sekcji).
 */
export default function GatewayPublicLayout({ children, bare = false, ...overrides }) {
    const page = usePage().props
    const title = overrides.title ?? page.pageTitle ?? DEFAULT_TITLE

    const head = (
        <Head>
            <title>{title}</title>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet" />
            <link rel="stylesheet" href="/css/theme.css" />
        </Head>
    )

    if (bare) {
        return <>{head}{children}</>
    }

    return (
        <>
            {head}
            <header className="site-header">
                <div className="container">
                    <a href="/" className="site-logo">Support<span className="dot">ME</span></a>
                    <nav className="site-nav">
                        <a href="/#wdrozenia">Wdrożenia</a>
                        <a href="/#bezpieczenstwo">Bezpieczeństwo</a>
                        <a href="/#cennik">Cennik</a>
                        <a href="/#faq">FAQ</a>
                    </nav>
                    <a href="#kontakt" className="btn btn-primary btn-sm">Zostaw kontakt</a>
                </div>
            </header>

            {children}

            <footer className="site-footer">
                <div className="container">
                    <strong>Support<span style={{ color: 'var(--brand)' }}>ME</span></strong> — sprzedaż bezobsługowa NFC · demo MVP<br />
                    <span className="small">&copy; {new Date().getFullYear()} please-support-me.com</span>
                </div>
            </footer>
        </>
    )
}
