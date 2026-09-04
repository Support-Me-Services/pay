import { Head } from '@inertiajs/react'

/**
 * Logowanie do panelu bramki płatności — strona standalone (bez chromu).
 * Faza 6: Laravel nie sprawdza już żadnego hasła — jeden przycisk prowadzi
 * na realny ekran logowania Keycloaka.
 */
export default function Login({ redirectUrl }) {
    return (
        <>
            <Head>
                <title>Logowanie — panel bramki</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet" />
                <link rel="stylesheet" href="/css/theme.css" />
            </Head>

            <div className="auth-wrap">
                <div className="auth-card">
                    <h2 className="text-center">Support<span className="text-brand">ME</span></h2>
                    <p className="text-muted text-center mb-3">Panel bramki płatności</p>

                    <a href={redirectUrl} className="btn btn-primary btn-block">Zaloguj przez Keycloak</a>
                </div>
            </div>
        </>
    )
}
