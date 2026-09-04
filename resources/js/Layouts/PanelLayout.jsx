import { Head, usePage } from '@inertiajs/react'

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

    // Faza 6 — wylogowanie kończy się przekierowaniem NA ZEWNĄTRZ (Keycloak
    // end-session, dla prawdziwego single-logout). router.post() Inertii
    // idzie przez fetch/XHR, którego przeglądarka NIE pozwala przekierować
    // cross-origin (CORS blokuje odczyt odpowiedzi) — więc zamiast tego
    // realny, natywny submit formularza (pełna nawigacja, bez tego
    // ograniczenia), tak jak zwykły <form method="POST"> na stronie.
    const logout = (e) => {
        e.preventDefault()
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = panel.logoutUrl
        const csrf = document.createElement('input')
        csrf.type = 'hidden'
        csrf.name = '_token'
        csrf.value = props.csrf_token
        form.appendChild(csrf)
        document.body.appendChild(form)
        form.submit()
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
                <a href={panel.activeOrganization?.shopUrl || panel.homeUrl} className="brand" style={{ display: 'block', textDecoration: 'none', color: 'inherit' }}>
                    {panel.activeOrganization
                        ? panel.activeOrganization.name
                        : <>{panel.brand || 'SupportME'}<span className="dot">.</span></>}
                </a>
                <nav className="panel-nav">
                    {nav.map((it) => (
                        <a key={it.href} href={it.href} className={it.active ? 'active' : ''}>
                            {it.label}
                            {it.badge ? <span className="badge badge-brand" style={{ marginLeft: 6 }}>{it.badge}</span> : null}
                        </a>
                    ))}
                    {panel.accountUrl && (
                        <>
                            <div className="nav-sep" />
                            {/* Faza 6 — konto Keycloaka, zewnętrzna konsola (nowa karta). */}
                            <a href={panel.accountUrl} target="_blank" rel="noopener noreferrer">Zarządzanie kontem ↗</a>
                        </>
                    )}
                    {panel.logoutUrl && (
                        <>
                            <div className="nav-sep" />
                            <a href="#" onClick={logout}>Wyloguj</a>
                        </>
                    )}
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
