import { Head, useForm } from '@inertiajs/react'

/**
 * Zakładanie konta sklepu — strona standalone (BEZ chromu panelu), bliźniacza
 * do Panel/Auth/Login. Zawsze theme.css (style admina), niezależnie od motywu
 * publicznego hosta. Błędy walidacji przez współdzielone `errors`.
 */
export default function Register({ brand, postUrl, loginUrl }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })

    const submit = (e) => {
        e.preventDefault()
        form.post(postUrl)
    }

    return (
        <>
            <Head>
                <title>{`Załóż konto — panel · ${brand}`}</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet" />
                <link rel="stylesheet" href="/css/theme.css" />
            </Head>

            <div className="auth-wrap">
                <div className="auth-card">
                    <h2 className="text-center">{brand}<span className="text-brand">.</span></h2>
                    <p className="text-muted text-center mb-3">Załóż konto sklepu</p>

                    <form onSubmit={submit}>
                        <div className="form-group">
                            <label htmlFor="name">Nazwa</label>
                            <input type="text" id="name" value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)} required autoFocus />
                            <div className="form-hint">Imię i nazwisko lub nazwa firmy — do identyfikacji konta w panelu.</div>
                            {form.errors.name && <div className="form-error">{form.errors.name}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="email">E-mail</label>
                            <input type="email" id="email" value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)} required />
                            {form.errors.email && <div className="form-error">{form.errors.email}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="password">Hasło</label>
                            <input type="password" id="password" value={form.data.password}
                                onChange={(e) => form.setData('password', e.target.value)} required />
                            <div className="form-hint">Minimum 8 znaków.</div>
                            {form.errors.password && <div className="form-error">{form.errors.password}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="password_confirmation">Powtórz hasło</label>
                            <input type="password" id="password_confirmation" value={form.data.password_confirmation}
                                onChange={(e) => form.setData('password_confirmation', e.target.value)} required />
                        </div>
                        <button type="submit" className="btn btn-primary btn-block" disabled={form.processing}>Załóż konto</button>
                    </form>

                    <p className="text-muted text-center mt-2 mb-0">
                        Masz już konto? <a href={loginUrl}>Zaloguj się</a>
                    </p>
                </div>
            </div>
        </>
    )
}
