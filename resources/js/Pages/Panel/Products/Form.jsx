import { useState } from 'react'
import { Link, router, useForm, usePage } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import RichEditor from '@/Components/RichEditor'

/**
 * Panel „Parafie" — dodawanie/edycja parafii (produkt NFC).
 * Opis: Quill z uploadem zdjęć. Główne zdjęcie + galeria (upload plików).
 * Dla istniejącej parafii: pipeline statusów + notatki CRM (AJAX).
 */
export default function Form({ product, images, notes: initialNotes, statuses, voivodeships, noteTypes, urls }) {
    const csrf = usePage().props.csrf_token
    const editing = product.exists

    const form = useForm({
        name: product.name ?? '',
        city: product.city ?? '',
        voivodeship: product.voivodeship ?? '',
        phone: product.phone ?? '',
        website: product.website ?? '',
        status: product.status ?? 'kontakt',
        price: product.price ?? '',
        tag_uid: product.tag_uid ?? '',
        pickup_instruction: product.pickup_instruction ?? '',
        description_html: product.description_html ?? '',
        main_image: null,
        gallery: [],
    })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        const opts = { forceFormData: true }
        if (editing) {
            form.transform((d) => ({ ...d, _method: 'put' })).post(urls.update, opts)
        } else {
            form.post(urls.store, opts)
        }
    }

    // Szybkie przejście statusu (pipeline) — redirect + flash z kontrolera.
    const moveStatus = (key) => router.post(urls.status, { status: key }, { preserveScroll: true })

    const deleteImage = (id) => {
        if (!confirm('Usunąć zdjęcie z galerii?')) return
        router.delete(urls.imageDelete.replace('__ID__', id), { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title"><h1>{editing ? `Edytuj: ${product.name}` : 'Dodaj parafię'}</h1></div>

            {Object.keys(err).length > 0 && <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>}

            {/* Pipeline statusów */}
            {editing && (
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
                    <div className="d-flex gap-1" style={{ alignItems: 'center', flexWrap: 'wrap' }}>
                        <span>Status:</span>
                        <span className="badge" style={{ background: product.status_bg, color: product.status_fg, fontWeight: 600 }}>{product.status_label}</span>
                        <span className="text-muted" style={{ margin: '0 6px' }}>→ przenieś do:</span>
                        {statuses.filter((s) => s.value !== product.status).map((s) => (
                            <button key={s.value} type="button" className="btn btn-secondary btn-sm" onClick={() => moveStatus(s.value)}>{s.label}</button>
                        ))}
                    </div>
                    <div className="form-hint" style={{ marginTop: 6 }}>Status „Aktywna" publikuje parafię na stronie; pozostałe ją ukrywają (lead).</div>
                </div></div>
            )}

            <form onSubmit={submit} encType="multipart/form-data">
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
                    <div className="form-group">
                        <label htmlFor="name">Nazwa parafii *</label>
                        <input id="name" type="text" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                        {err.name && <div className="form-error">{err.name}</div>}
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="city">Miasto</label>
                            <input id="city" type="text" value={form.data.city} onChange={(e) => form.setData('city', e.target.value)} placeholder="np. Kraków" />
                            {err.city && <div className="form-error">{err.city}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="voivodeship">Województwo</label>
                            <select id="voivodeship" value={form.data.voivodeship} onChange={(e) => form.setData('voivodeship', e.target.value)}>
                                <option value="">— wybierz —</option>
                                {voivodeships.map((w) => <option key={w} value={w}>{w}</option>)}
                            </select>
                            {err.voivodeship && <div className="form-error">{err.voivodeship}</div>}
                        </div>
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="phone">Telefon</label>
                            <input id="phone" type="text" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} placeholder="np. 600 100 200" />
                            {err.phone && <div className="form-error">{err.phone}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 1, minWidth: 200 }}>
                            <label htmlFor="website">Strona www</label>
                            <input id="website" type="text" value={form.data.website} onChange={(e) => form.setData('website', e.target.value)} placeholder="np. parafia.pl" />
                            {err.website && <div className="form-error">{err.website}</div>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label htmlFor="status">Status *</label>
                        <select id="status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} required>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                        {err.status && <div className="form-error">{err.status}</div>}
                    </div>

                    <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                        <div className="form-group" style={{ flex: 1, minWidth: 160 }}>
                            <label htmlFor="price">Kwota wpłaty (zł) *</label>
                            <input id="price" type="text" inputMode="decimal" value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} required />
                            {err.price && <div className="form-error">{err.price}</div>}
                        </div>
                        <div className="form-group" style={{ flex: 2, minWidth: 220 }}>
                            <label htmlFor="tag_uid">UID taga NFC *</label>
                            <input id="tag_uid" type="text" value={form.data.tag_uid} onChange={(e) => form.setData('tag_uid', e.target.value)} placeholder="np. TAG-S1-001" required />
                            {err.tag_uid && <div className="form-error">{err.tag_uid}</div>}
                        </div>
                    </div>

                    <div className="form-group">
                        <label htmlFor="pickup_instruction">Instrukcja po wpłacie (pokazywana po opłaceniu)</label>
                        <textarea id="pickup_instruction" rows={3} value={form.data.pickup_instruction}
                            onChange={(e) => form.setData('pickup_instruction', e.target.value)} placeholder="np. Bóg zapłać za wsparcie parafii…" />
                        {err.pickup_instruction && <div className="form-error">{err.pickup_instruction}</div>}
                    </div>

                    <div className="form-group">
                        <label>Opis parafii (WYSIWYG)</label>
                        <RichEditor value={product.description_html ?? ''} onChange={(html) => form.setData('description_html', html)}
                            imageUploadUrl={urls.editorUpload} csrfToken={csrf} />
                        <div className="form-hint">Wstaw zdjęcie ikoną obrazka w pasku narzędzi — plik wgra się na serwer.</div>
                        {err.description_html && <div className="form-error">{err.description_html}</div>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="main_image">Zdjęcie główne {product.main_image_url ? '(zostaw puste, by zachować obecne)' : ''}</label>
                        {product.main_image_url && (
                            <img src={product.main_image_url} alt="" style={{ width: 96, height: 96, objectFit: 'cover', borderRadius: 8, marginBottom: 8, display: 'block' }} />
                        )}
                        <input id="main_image" type="file" accept="image/*" onChange={(e) => form.setData('main_image', e.target.files[0] || null)} />
                        {err.main_image && <div className="form-error">{err.main_image}</div>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="gallery">Galeria (możesz wybrać wiele plików)</label>
                        {editing && images.length > 0 && (
                            <div className="d-flex gap-1 mb-1" style={{ flexWrap: 'wrap' }}>
                                {images.map((img) => (
                                    <div key={img.id} style={{ position: 'relative' }}>
                                        <img src={img.url} alt="" style={{ width: 72, height: 72, objectFit: 'cover', borderRadius: 8 }} />
                                        <button type="button" className="btn btn-danger btn-sm" onClick={() => deleteImage(img.id)}
                                            style={{ position: 'absolute', top: -6, right: -6, padding: '0 7px', borderRadius: '50%' }}>×</button>
                                    </div>
                                ))}
                            </div>
                        )}
                        <input id="gallery" type="file" accept="image/*" multiple onChange={(e) => form.setData('gallery', Array.from(e.target.files))} />
                        {Object.entries(err).filter(([k]) => k === 'gallery' || k.startsWith('gallery.')).map(([k, v]) => <div key={k} className="form-error">{v}</div>)}
                    </div>

                    <div className="d-flex gap-1">
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                        <Link href={urls.index} className="btn btn-secondary">Anuluj</Link>
                    </div>
                </div></div>
            </form>

            {editing && <CrmNotes csrf={csrf} storeUrl={urls.notesStore} destroyTpl={urls.notesDestroy} noteTypes={noteTypes} initial={initialNotes} />}
        </>
    )
}

/** Panel notatek CRM (AJAX) — historia kontaktu z parafią. */
function CrmNotes({ csrf, storeUrl, destroyTpl, noteTypes, initial }) {
    const [notes, setNotes] = useState(initial)
    const [type, setType] = useState(noteTypes[0]?.value ?? 'kontakt')
    const [body, setBody] = useState('')
    const [error, setError] = useState(null)

    const add = (e) => {
        e.preventDefault()
        setError(null)
        if (!body.trim()) { setError('Wpisz treść notatki.'); return }
        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json', 'Content-Type': 'application/json',
            },
            body: JSON.stringify({ body: body.trim(), type }),
        })
            .then((r) => (r.ok ? r.json() : r.json().then((j) => Promise.reject(j))))
            .then((n) => { setNotes((prev) => [n, ...prev]); setBody('') })
            .catch(() => setError('Nie udało się zapisać notatki.'))
    }

    const remove = (id) => {
        if (!confirm('Usunąć notatkę?')) return
        fetch(destroyTpl.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        }).then((r) => { if (r.ok) setNotes((prev) => prev.filter((n) => n.id !== id)) })
    }

    return (
        <div className="card card-static mb-3" style={{ maxWidth: 840 }}><div className="card-body">
            <h2 style={{ marginTop: 0 }}>Notatki CRM</h2>
            <div className="form-hint">Historia kontaktu — kiedy i co się wydarzyło.</div>

            <form onSubmit={add} className="d-flex gap-2 mb-3" style={{ flexWrap: 'wrap', alignItems: 'flex-end' }}>
                <div className="form-group" style={{ flex: '0 0 160px', marginBottom: 0 }}>
                    <label htmlFor="note_type">Typ</label>
                    <select id="note_type" value={type} onChange={(e) => setType(e.target.value)}>
                        {noteTypes.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </select>
                </div>
                <div className="form-group" style={{ flex: 1, minWidth: 240, marginBottom: 0 }}>
                    <label htmlFor="note_body">Treść</label>
                    <textarea id="note_body" rows={2} value={body} onChange={(e) => setBody(e.target.value)}
                        placeholder="np. Rozmowa z ks. proboszczem, umówione spotkanie…" />
                </div>
                <button type="submit" className="btn btn-primary">Dodaj notatkę</button>
            </form>
            {error && <div className="form-error">{error}</div>}

            <div>
                {notes.length === 0 && <div className="text-muted">Brak notatek.</div>}
                {notes.map((n) => (
                    <div key={n.id} className="note-item" style={{ border: '1px solid var(--line,#e2e8f0)', borderRadius: 8, padding: '10px 12px', marginBottom: 8, background: '#fff' }}>
                        <div className="note-meta" style={{ fontSize: '.82rem', color: '#64748b', marginBottom: 4 }}>
                            <strong>{n.type_label}</strong> · {n.created_at}
                            {n.author ? ` · ${n.author}` : ''}
                            <a href="#" onClick={(e) => { e.preventDefault(); remove(n.id) }} style={{ float: 'right', color: '#b23b3b' }}>Usuń</a>
                        </div>
                        <div>{n.body}</div>
                    </div>
                ))}
            </div>
        </div></div>
    )
}

Form.layout = (page) => <PanelLayout title="Parafia">{page}</PanelLayout>
