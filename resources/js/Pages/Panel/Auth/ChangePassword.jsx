import { useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel — zmiana hasła zalogowanego konta. */
export default function ChangePassword({ updateUrl }) {
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

    return (
        <>
            <div className="panel-title"><h1>Zmiana hasła</h1></div>
            {Object.keys(err).length > 0 && <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>}

            <form onSubmit={submit}>
                <div className="card card-static mb-3" style={{ maxWidth: 480 }}><div className="card-body">
                    <div className="form-group">
                        <label htmlFor="current_password">Obecne hasło *</label>
                        <input id="current_password" type="password" value={form.data.current_password}
                            onChange={(e) => form.setData('current_password', e.target.value)} required autoFocus />
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
        </>
    )
}

ChangePassword.layout = (page) => <PanelLayout title="Zmiana hasła">{page}</PanelLayout>
