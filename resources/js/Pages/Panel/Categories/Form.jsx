import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Kategorie" — dodawanie/edycja (select rodzica + upload ikony). */
export default function Form({ item, parentOptions = [], sourceOptions = [], storeUrl, indexUrl }) {
    const editing = !!item
    const form = useForm({
        parent_id: item?.parent_id ?? '',
        label: item?.label ?? '',
        label_text: item?.label_text ?? '',
        slug: item?.slug ?? '',
        label_html: item?.label_html ?? '',
        intro: item?.intro ?? '',
        source: item?.source ?? 'none',
        position: item?.position ?? 0,
        icon: null,
        active: item?.active ?? true,
    })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        if (editing) form.transform((d) => ({ ...d, _method: 'put' })).post(item.update_url, { forceFormData: true })
        else form.post(storeUrl, { forceFormData: true })
    }

    return (
        <>
            <div className="panel-title"><h1>{editing ? `Edytuj: ${item.label_text}` : 'Dodaj kategorię'}</h1></div>
            {Object.keys(err).length > 0 && <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>}

            <form onSubmit={submit} encType="multipart/form-data">
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
                    <div className="form-group">
                        <label htmlFor="parent_id">Kategoria nadrzędna</label>
                        <select id="parent_id" value={form.data.parent_id ?? ''} onChange={(e) => form.setData('parent_id', e.target.value)}>
                            <option value="">— (kategoria główna)</option>
                            {parentOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                        {err.parent_id && <div className="form-error">{err.parent_id}</div>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="label">Nazwa *</label>
                        <input id="label" value={form.data.label} onChange={(e) => form.setData('label', e.target.value)} required />
                        {err.label && <div className="form-error">{err.label}</div>}
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 240 }}>
                            <label htmlFor="label_text">Nazwa na stronie kategorii (tekst)</label>
                            <input id="label_text" value={form.data.label_text} onChange={(e) => form.setData('label_text', e.target.value)} />
                            <div className="form-hint">Gdy puste — użyje „Nazwa".</div>
                            {err.label_text && <div className="form-error">{err.label_text}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 1, minWidth: 240 }}>
                            <label htmlFor="slug">Slug (URL)</label>
                            <input id="slug" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                            <div className="form-hint">Gdy puste — wygeneruje z nazwy.</div>
                            {err.slug && <div className="form-error">{err.slug}</div>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label htmlFor="label_html">Nazwa na kafelku (HTML)</label>
                        <textarea id="label_html" rows={2} value={form.data.label_html} onChange={(e) => form.setData('label_html', e.target.value)} />
                        <div className="form-hint">Można użyć {'<br>'} do łamania wiersza. Gdy puste — użyje nazwy tekstowej.</div>
                        {err.label_html && <div className="form-error">{err.label_html}</div>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="intro">Opis (intro strony kategorii)</label>
                        <textarea id="intro" rows={3} value={form.data.intro} onChange={(e) => form.setData('intro', e.target.value)} />
                        {err.intro && <div className="form-error">{err.intro}</div>}
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="source">Źródło pozycji</label>
                            <select id="source" value={form.data.source} onChange={(e) => form.setData('source', e.target.value)}>
                                {sourceOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                            </select>
                            {err.source && <div className="form-error">{err.source}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="position">Kolejność</label>
                            <input id="position" type="number" min={0} value={form.data.position} onChange={(e) => form.setData('position', e.target.value)} />
                            {err.position && <div className="form-error">{err.position}</div>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label htmlFor="icon">Ikonka</label>
                        {item?.icon && <div style={{ marginBottom: 8 }}><img src={item.icon} alt="" style={{ width: 64, height: 64, borderRadius: '50%', objectFit: 'cover' }} /></div>}
                        <input id="icon" type="file" accept="image/*" onChange={(e) => form.setData('icon', e.target.files[0] ?? null)} />
                        <div className="form-hint">PNG/JPG/SVG. Pozostaw puste, aby zachować obecną.</div>
                        {err.icon && <div className="form-error">{err.icon}</div>}
                    </div>

                    <div className="form-group">
                        <label><input type="checkbox" checked={form.data.active} onChange={(e) => form.setData('active', e.target.checked)} style={{ width: 'auto' }} /> aktywna (widoczna na stronie)</label>
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

Form.layout = (page) => <PanelLayout title="Kategoria">{page}</PanelLayout>
