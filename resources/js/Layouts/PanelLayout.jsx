import { Head, router, usePage } from '@inertiajs/react'

/**
 * Chrom panelu admina (sidebar + flash), odwzorowuje layouts/panel.blade.php.
 * Dane nawigacji i konta przychodzą jako współdzielone propsy z HandleInertiaRequests.
 * Reużywa istniejący theme.css (te same klasy co Blade) — zero re-stylingu.
 */
export default function PanelLayout({ title, children }) {
    const { props } = usePage()
    const panel = props.panel || {}
    const flash = props.flash || {}
    const nav = panel.nav || []

    const logout = (e) => {
        e.preventDefault()
        router.post(panel.logoutUrl)
    }

    return (
        <div className="panel-wrap">
            <Head>
                <title>{title ? `${title} — ${panel.brand || 'Panel'}` : 'Panel'}</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet" />
                <link rel="stylesheet" href="/css/theme.css" />
            </Head>

            <aside className="panel-sidebar">
                <div className="brand">
                    {panel.activeOrganization
                        ? panel.activeOrganization.name
                        : <>{panel.brand || 'SupportME'}<span className="dot">.</span></>}
                </div>
                <nav className="panel-nav">
                    {nav.map((it) => (
                        <a key={it.href} href={it.href} className={it.active ? 'active' : ''}>
                            {it.label}
                            {it.badge ? <span className="badge badge-brand" style={{ marginLeft: 6 }}>{it.badge}</span> : null}
                        </a>
                    ))}
                    <div className="nav-sep" />
                    <a href="#" onClick={logout}>Wyloguj</a>
                </nav>
            </aside>

            <main className="panel-main">
                {flash.success ? <div className="alert alert-success">{flash.success}</div> : null}
                {flash.error ? <div className="alert alert-error">{flash.error}</div> : null}
                {children}
            </main>
        </div>
    )
}
