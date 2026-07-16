import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Sklep" — dodawanie/edycja produktu (odwzorowanie shop-items/form.blade.php). */
export default function Form({ item, storeUrl, indexUrl }) {
    const editing = !!item

    const form = useForm({
        name: item?.name ?? '',
        description: item?.description ?? '',
        slug: item?.slug ?? '',
        price_pln: item?.price_pln ?? 1,
        sort: item?.sort ?? 0,
        tag_uid: item?.tag_uid ?? '',
        image_file: null,
        is_default: item?.is_default ?? false,
        active: item?.active ?? true,
    })

    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        // Upload pliku wymaga multipart; edycja przez spoofing metody PUT (POST + _method).
        if (editing) {
            form.transform((d) => ({ ...d, _method: 'put' })).post(item.update_url, { forceFormData: true })
        } else {
            form.post(storeUrl, { forceFormData: true })
        }
    }

    return (
        <>
            <div className="panel-title">
                <h1>{editing ? `Edytuj: ${item.name}` : 'Dodaj produkt'}</h1>
            </div>

            {Object.keys(err).length > 0 && (
                <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>
            )}

            <form onSubmit={submit} encType="multipart/form-data">
                <div className="card card-static mb-3" style={{ maxWidth: 840 }}>
                    <div className="card-body">
                        <div className="form-group">
                            <label htmlFor="name">Nazwa *</label>
                            <input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required placeholder="np. Serduszko" />
                            {err.name && <div className="form-error">{err.name}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="description">Opis produktu</label>
                            <textarea id="description" rows={3} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} placeholder="Krótki opis oferty (widoczny w sklepie)" />
                            {err.description && <div className="form-error">{err.description}</div>}
                        </div>

                        <div className="d-flex gap-2" style={{ flexWrap: 'wrap' }}>
                            <div className="form-group" style={{ flex: 1, minWidth: 220 }}>
                                <label htmlFor="slug">Slug (URL)</label>
                                <input id="slug" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} placeholder="puste = z nazwy" />
                                {err.slug && <div className="form-error">{err.slug}</div>}
                            </div>
                            <div className="form-group" style={{ flex: '0 0 160px' }}>
                                <label htmlFor="price_pln">Cena (zł) *</label>
                                <input id="price_pln" type="number" min={1} max={5000} required value={form.data.price_pln} onChange={(e) => form.setData('price_pln', e.target.value)} />
                                {err.price_pln && <div className="form-error">{err.price_pln}</div>}
                            </div>
                            <div className="form-group" style={{ flex: '0 0 120px' }}>
                                <label htmlFor="sort">Kolejność</label>
                                <input id="sort" type="number" min={0} max={65535} value={form.data.sort} onChange={(e) => form.setData('sort', e.target.value)} />
                                {err.sort && <div className="form-error">{err.sort}</div>}
                            </div>
                        </div>

                        <div className="form-group">
                            <label htmlFor="tag_uid">Tag NFC (UID) — kieruje na stronę sklepu z tym produktem</label>
                            <input id="tag_uid" value={form.data.tag_uid} onChange={(e) => form.setData('tag_uid', e.target.value)} placeholder="np. 04A1B2C3D4 (opcjonalne)" />
                            {err.tag_uid && <div className="form-error">{err.tag_uid}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="image_file">Grafika produktu (jpg/png/svg, do 5 MB)</label>
                            {item?.image && (
                                <div style={{ marginBottom: 8 }}>
                                    <img src={item.image} alt="" style={{ width: 80, height: 80, objectFit: 'contain', borderRadius: 8, background: '#f6f9fb' }} />
                                </div>
                            )}
                            <input id="image_file" type="file" accept="image/*" onChange={(e) => form.setData('image_file', e.target.files[0] ?? null)} />
                            {err.image_file && <div className="form-error">{err.image_file}</div>}
                        </div>

                        <div className="form-group">
                            <label>
                                <input type="checkbox" checked={form.data.is_default} onChange={(e) => form.setData('is_default', e.target.checked)} style={{ width: 'auto' }} />
                                {' '}domyślny produkt (pokazywany w modalu po wejściu na sklep)
                            </label>
                        </div>

                        <div className="form-group">
                            <label>
                                <input type="checkbox" checked={form.data.active} onChange={(e) => form.setData('active', e.target.checked)} style={{ width: 'auto' }} />
                                {' '}aktywny (widoczny w sklepie)
                            </label>
                        </div>

                        <div className="d-flex gap-1">
                            <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                            <Link href={indexUrl} className="btn btn-secondary">Anuluj</Link>
                        </div>
                    </div>
                </div>
            </form>
        </>
    )
}

Form.layout = (page) => <PanelLayout title="Produkt">{page}</PanelLayout>
