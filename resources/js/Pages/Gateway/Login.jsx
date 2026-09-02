import { Head, useForm } from '@inertiajs/react'
import PasswordInput from '@/Components/PasswordInput'

/** Logowanie do panelu bramki płatności — strona standalone (bez chromu). */
export default function Login({ postUrl }) {
    const form = useForm({ email: '', password: '' })

    const submit = (e) => {
        e.preventDefault()
        form.post(postUrl)
    }

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

                    <form onSubmit={submit}>
                        <div className="form-group">
                            <label htmlFor="email">E-mail</label>
                            <input type="email" id="email" value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)} required autoFocus />
                            {form.errors.email && <div className="form-error">{form.errors.email}</div>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="password">Hasło</label>
                            <PasswordInput id="password" value={form.data.password}
                                onChange={(e) => form.setData('password', e.target.value)} required />
                        </div>
                        <button type="submit" className="btn btn-primary btn-block" disabled={form.processing}>Zaloguj się</button>
                    </form>
                </div>
            </div>
        </>
    )
}
