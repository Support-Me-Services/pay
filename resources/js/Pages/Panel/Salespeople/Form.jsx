import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Handlowcy" — dodawanie/edycja (multi‑select województw + lista parafii). */
export default function Form({ item, parishes = [], voivodeshipOptions = [], storeUrl, indexUrl }) {
    const editing = !!item
    const form = useForm({
        name: item?.name ?? '',
        email: item?.email ?? '',
        phone: item?.phone ?? '',
        voivodeships: item?.voivodeships ?? [],
        active: item?.active ?? true,
    })
    const err = form.errors

    const toggleVoiv = (v) => {
        const cur = form.data.voivodeships
        form.setData('voivodeships', cur.includes(v) ? cur.filter((x) => x !== v) : [...cur, v])
    }

    const submit = (e) => {
        e.preventDefault()
        if (editing) form.transform((d) => ({ ...d, _method: 'put' })).post(item.update_url)
        else form.post(storeUrl)
    }

    return (
        <>
            <div className="panel-title"><h1>{editing ? `Edytuj: ${item.name}` : 'Dodaj handlowca'}</h1></div>
            {Object.keys(err).length > 0 && <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>}

            <form onSubmit={submit}>
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
                    <div className="form-group">
                        <label htmlFor="name">Imię i nazwisko *</label>
                        <input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                        {err.name && <div className="form-error">{err.name}</div>}
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="email">E-mail</label>
                            <input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                            {err.email && <div className="form-error">{err.email}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="phone">Telefon</label>
                            <input id="phone" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                            {err.phone && <div className="form-error">{err.phone}</div>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label>Obsługiwane województwa</label>
                        <div className="d-flex gap-1" style={{ flexWrap: 'wrap' }}>
                            {voivodeshipOptions.map((v) => (
                                <label key={v} style={{ flex: '0 0 220px', fontWeight: 400 }}>
                                    <input type="checkbox" checked={form.data.voivodeships.includes(v)} onChange={() => toggleVoiv(v)} style={{ width: 'auto' }} /> {v}
                                </label>
                            ))}
                        </div>
                        {err.voivodeships && <div className="form-error">{err.voivodeships}</div>}
                    </div>

                    <div className="form-group">
                        <label><input type="checkbox" checked={form.data.active} onChange={(e) => form.setData('active', e.target.checked)} style={{ width: 'auto' }} /> aktywny</label>
                    </div>

                    <div className="d-flex gap-1">
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                        <Link href={indexUrl} className="btn btn-secondary">Anuluj</Link>
                    </div>
                </div></div>
            </form>

            {editing && parishes.length > 0 && (
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Parafie tego handlowca ({parishes.length})</h2>
                    <ul style={{ margin: 0, paddingLeft: 18 }}>
                        {parishes.map((p) => (
                            <li key={p.id}><a href={p.edit_url}>{p.name}</a>{p.city && <span className="text-muted"> — {p.city}</span>}</li>
                        ))}
                    </ul>
                    <div className="form-hint" style={{ marginTop: 8 }}>
                        <a href={item.parishes_url}>Pokaż w liście parafii →</a>
                    </div>
                </div></div>
            )}
        </>
    )
}

Form.layout = (page) => <PanelLayout title="Handlowiec">{page}</PanelLayout>
