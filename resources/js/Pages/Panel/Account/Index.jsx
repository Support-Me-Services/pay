import { router, useForm, usePage } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel — zarządzanie kontem: dane konta, zmiana hasła, wylogowanie. */
export default function Index({ account, updateUrl }) {
    const { props } = usePage()
    const logoutUrl = props.panel?.logoutUrl

    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        form.put(updateUrl, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        })
    }

    const logout = (e) => {
        e.preventDefault()
        router.post(logoutUrl)
    }

    return (
        <>
            <div className="panel-title"><h1>Zarządzanie kontem</h1></div>

            <div className="card card-static mb-3" style={{ maxWidth: 480 }}>
                <div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Informacje o koncie</h2>
                    <div className="form-group">
                        <label>Imię i nazwisko</label>
                        <div>{account.name}</div>
                    </div>
                    <div className="form-group">
                        <label>E-mail</label>
                        <div>{account.email}</div>
                    </div>
                    <div className="form-group">
                        <label>Rola</label>
                        <div>{account.isAdmin ? 'Super-user' : 'Standardowe konto'}</div>
                    </div>
                    {account.createdAt && (
                        <div className="form-group">
                            <label>Konto założone</label>
                            <div>{account.createdAt}</div>
                        </div>
                    )}
                </div>
            </div>

            {Object.keys(err).length > 0 && <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>}

            <form onSubmit={submit}>
                <div className="card card-static mb-3" style={{ maxWidth: 480 }}><div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Zmiana hasła</h2>
                    <div className="form-group">
                        <label htmlFor="current_password">Obecne hasło *</label>
                        <input id="current_password" type="password" value={form.data.current_password}
                            onChange={(e) => form.setData('current_password', e.target.value)} required />
                        {err.current_password && <div className="form-error">{err.current_password}</div>}
                    </div>
                    <div className="form-group">
                        <label htmlFor="password">Nowe hasło *</label>
                        <input id="password" type="password" value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)} required />
                        {err.password && <div className="form-error">{err.password}</div>}
                    </div>
                    <div className="form-group">
                        <label htmlFor="password_confirmation">Powtórz nowe hasło *</label>
                        <input id="password_confirmation" type="password" value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)} required />
                    </div>

                    <button type="submit" className="btn btn-primary" disabled={form.processing}>Zmień hasło</button>
                </div></div>
            </form>

            <div className="card card-static" style={{ maxWidth: 480 }}>
                <div className="card-body">
                    <button type="button" className="btn btn-secondary" onClick={logout}>Wyloguj</button>
                </div>
            </div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Zarządzanie kontem">{page}</PanelLayout>
