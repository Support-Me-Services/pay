import { Link, useForm } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

/** Panel bramki — dodawanie/edycja taga NFC sklepu. */
export default function Form({ shop, tag, urls }) {
    const editing = tag.exists
    const form = useForm({
        tag_uid: tag.tag_uid ?? '',
        label: tag.label ?? '',
        target_url: tag.target_url ?? '',
        active: tag.active ?? true,
    })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        if (editing) form.transform((d) => ({ ...d, _method: 'put' })).post(urls.update)
        else form.post(urls.store)
    }

    return (
        <>
            <div className="panel-title"><h1>{editing ? `Edytuj tag: ${tag.tag_uid}` : `Dodaj tag — ${shop.name}`}</h1></div>

            <div className="card card-static" style={{ maxWidth: 560 }}><div className="card-body">
                <form onSubmit={submit}>
                    <div className="form-group">
                        <label htmlFor="tag_uid">UID taga *</label>
                        <input id="tag_uid" type="text" value={form.data.tag_uid} onChange={(e) => form.setData('tag_uid', e.target.value)}
                            placeholder="np. TAG-S1-001 lub UID NTAG" required />
                        {err.tag_uid && <div className="form-error">{err.tag_uid}</div>}
                    </div>
                    <div className="form-group">
                        <label htmlFor="label">Etykieta</label>
                        <input id="label" type="text" value={form.data.label} onChange={(e) => form.setData('label', e.target.value)}
                            placeholder="np. Zamrażarka — lody pistacjowe" />
                        {err.label && <div className="form-error">{err.label}</div>}
                    </div>
                    <div className="form-group">
                        <label htmlFor="target_url">Docelowy URL *</label>
                        <input id="target_url" type="text" value={form.data.target_url} onChange={(e) => form.setData('target_url', e.target.value)}
                            placeholder={`${shop.base_url}/t/TAG-S1-001`} required />
                        <div className="form-hint">Adres, do którego prowadzi tag — zapisz go na tagu NFC (rekord NDEF typu URL).</div>
                        {err.target_url && <div className="form-error">{err.target_url}</div>}
                    </div>
                    <div className="form-group">
                        <label><input type="checkbox" checked={form.data.active} onChange={(e) => form.setData('active', e.target.checked)} style={{ width: 'auto' }} /> aktywny</label>
                    </div>

                    <div className="d-flex gap-1">
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                        <Link href={urls.index} className="btn btn-secondary">Anuluj</Link>
                    </div>
                </form>
            </div></div>
        </>
    )
}

Form.layout = (page) => <GatewayPanelLayout title="Tag">{page}</GatewayPanelLayout>
