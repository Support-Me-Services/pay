import { Link, useForm } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

/** Panel bramki — dodawanie/edycja sklepu. */
export default function Form({ shop, urls }) {
    const editing = shop.exists
    const form = useForm({
        name: shop.name ?? '',
        base_url: shop.base_url ?? '',
        payment_mode: shop.payment_mode ?? 'classic',
    })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        if (editing) form.transform((d) => ({ ...d, _method: 'put' })).post(urls.update)
        else form.post(urls.store)
    }

    return (
        <>
            <div className="panel-title"><h1>{editing ? `Edytuj sklep: ${shop.name}` : 'Dodaj sklep'}</h1></div>

            <div className="card card-static" style={{ maxWidth: 560 }}><div className="card-body">
                <form onSubmit={submit}>
                    <div className="form-group">
                        <label htmlFor="name">Nazwa *</label>
                        <input id="name" type="text" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                        {err.name && <div className="form-error">{err.name}</div>}
                    </div>
                    <div className="form-group">
                        <label htmlFor="base_url">Adres URL sklepu *</label>
                        <input id="base_url" type="text" value={form.data.base_url} onChange={(e) => form.setData('base_url', e.target.value)}
                            placeholder="https://please-support-me.com" required />
                        {err.base_url && <div className="form-error">{err.base_url}</div>}
                    </div>
                    <div className="form-group">
                        <label htmlFor="payment_mode">Tryb płatności *</label>
                        <select id="payment_mode" value={form.data.payment_mode} onChange={(e) => form.setData('payment_mode', e.target.value)} required>
                            <option value="classic">classic — przekierowanie na stronę płatności</option>
                            <option value="app2app">app2app — BLIK / aplikacja banku</option>
                        </select>
                        {err.payment_mode && <div className="form-error">{err.payment_mode}</div>}
                    </div>

                    {!editing && <p className="form-hint mb-2">Klucz API zostanie wygenerowany automatycznie i pokazany jednorazowo po zapisaniu.</p>}

                    <div className="d-flex gap-1">
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                        <Link href={urls.index} className="btn btn-secondary">Anuluj</Link>
                    </div>
                </form>
            </div></div>
        </>
    )
}

Form.layout = (page) => <GatewayPanelLayout title="Sklep">{page}</GatewayPanelLayout>
