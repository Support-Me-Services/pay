import { Head, usePage } from '@inertiajs/react'

/**
 * Chrom panelu bramki płatności (sidebar + flash) — odwzorowuje
 * gateway/layouts/panel.blade.php. Nawigacja przychodzi jako współdzielony
 * prop `panel` z HandleInertiaRequests (wariant gateway). Reużywa theme.css.
 */
export default function GatewayPanelLayout({ title, children }) {
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
                <title>{title ? `${title} — Panel bramki — SupportME` : 'Panel bramki — SupportME'}</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet" />
                <link rel="stylesheet" href="/css/theme.css" />
            </Head>

            <aside className="panel-sidebar">
                <div className="brand">Support<span className="dot">ME</span> panel</div>
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
