import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import RichEditor from '@/Components/RichEditor'

/** Panel „Praca" — dodawanie/edycja stanowiska (opis: Quill). */
export default function Form({ item, storeUrl, indexUrl }) {
    const editing = !!item
    const form = useForm({
        title: item?.title ?? '',
        location: item?.location ?? '',
        employment_type: item?.employment_type ?? '',
        description_html: item?.description_html ?? '',
        sort: item?.sort ?? 0,
        active: item?.active ?? true,
    })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        if (editing) form.transform((d) => ({ ...d, _method: 'put' })).post(item.update_url)
        else form.post(storeUrl)
    }

    return (
        <>
            <div className="panel-title"><h1>{editing ? `Edytuj: ${item.title}` : 'Dodaj stanowisko'}</h1></div>
            {Object.keys(err).length > 0 && <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>}

            <form onSubmit={submit}>
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
                    <div className="form-group">
                        <label htmlFor="title">Tytuł *</label>
                        <input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
                        {err.title && <div className="form-error">{err.title}</div>}
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="location">Lokalizacja</label>
                            <input id="location" value={form.data.location} onChange={(e) => form.setData('location', e.target.value)} placeholder="np. Ełk / zdalnie" />
                            {err.location && <div className="form-error">{err.location}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="employment_type">Rodzaj zatrudnienia</label>
                            <input id="employment_type" value={form.data.employment_type} onChange={(e) => form.setData('employment_type', e.target.value)} placeholder="np. Pełny etat / Wolontariat" />
                            {err.employment_type && <div className="form-error">{err.employment_type}</div>}
                        </div>
                        <div className="form-group" style={{ flex: '0 0 120px' }}>
                            <label htmlFor="sort">Kolejność</label>
                            <input id="sort" type="number" min={0} max={65535} value={form.data.sort} onChange={(e) => form.setData('sort', e.target.value)} />
                            {err.sort && <div className="form-error">{err.sort}</div>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label>Opis stanowiska (WYSIWYG)</label>
                        <RichEditor value={item?.description_html ?? ''} onChange={(html) => form.setData('description_html', html)} />
                        {err.description_html && <div className="form-error">{err.description_html}</div>}
                    </div>

                    <div className="form-group">
                        <label><input type="checkbox" checked={form.data.active} onChange={(e) => form.setData('active', e.target.checked)} style={{ width: 'auto' }} /> aktywne</label>
                    </div>

                    <div className="d-flex gap-1">
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                        <Link href={indexUrl} className="btn btn-secondary">Anuluj</Link>
                    </div>
                </div></div>
            </form>
        </>
    )
}

Form.layout = (page) => <PanelLayout title="Stanowisko">{page}</PanelLayout>
