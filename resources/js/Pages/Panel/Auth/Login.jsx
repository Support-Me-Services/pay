import { Head } from '@inertiajs/react'

/**
 * Logowanie do panelu — strona standalone (BEZ chromu panelu). Zawsze
 * theme.css (style admina), niezależnie od motywu publicznego hosta.
 * Faza 6: Laravel nie sprawdza już żadnego hasła ani nie ma osobnego
 * ekranu rejestracji — jeden przycisk prowadzi na realny ekran logowania
 * Keycloaka, który (registrationAllowed w realm) sam oferuje "Zarejestruj
 * się" dla nowych kont.
 */
export default function Login({ brand, redirectUrl }) {
    return (
        <>
            <Head>
                <title>{`Logowanie — panel · ${brand}`}</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet" />
                <link rel="stylesheet" href="/css/theme.css" />
            </Head>

            <div className="auth-wrap">
                <div className="auth-card">
                    <h2 className="text-center">{brand}<span className="text-brand">.</span></h2>
                    <p className="text-muted text-center mb-3">Panel sklepu</p>

                    <a href={redirectUrl} className="btn btn-primary btn-block">Zaloguj przez Keycloak</a>
                </div>
            </div>
        </>
    )
}
