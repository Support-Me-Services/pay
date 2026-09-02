import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Zbiórki" — dodawanie/edycja produktu (odwzorowanie shop-items/form.blade.php). */
export default function Form({ item, organizations, storeUrl, indexUrl }) {
    const editing = !!item

    const form = useForm({
        name: item?.name ?? '',
        description: item?.description ?? '',
        slug: item?.slug ?? '',
        price_pln: item?.price_pln ?? 1,
        sort: item?.sort ?? 0,
        image_file: null,
        is_default: item?.is_default ?? false,
        active: item?.active ?? true,
        thank_you_heading: item?.thank_you_heading ?? '',
        thank_you_body: item?.thank_you_body ?? '',
        thank_you_image_file: null,
        remove_thank_you_image: false,
        mecenas_organization_id: item?.mecenas_organization_id ?? '',
    })

    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        // Upload pliku wymaga multipart; edycja przez spoofing metody PUT (POST + _method).
        if (editing) {
            form.transform((d) => ({ ...d, _method: 'put' }))
            form.post(item.update_url, { forceFormData: true })
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

                    </div>
                </div>

                <div className="card card-static mb-3" style={{ maxWidth: 'max(840px, 150ch)' }}>
                    <div className="card-body">
                        <h2 style={{ marginTop: 0 }}>Strona podziękowania</h2>
                        <div className="form-hint" style={{ marginBottom: 16 }}>
                            Pokazywana zamiast domyślnego tekstu, gdy ktoś wesprze akurat ten produkt. Puste pola = domyślna treść.
                        </div>

                        <div className="form-group">
                            <label htmlFor="thank_you_heading">Nagłówek</label>
                            <input id="thank_you_heading" value={form.data.thank_you_heading}
                                onChange={(e) => form.setData('thank_you_heading', e.target.value)}
                                placeholder="Dziękujemy za Twoje wsparcie!" style={{ minWidth: '150ch', maxWidth: '100%' }} />
                            {err.thank_you_heading && <div className="form-error">{err.thank_you_heading}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="thank_you_body">Treść</label>
                            <textarea id="thank_you_body" rows={4} value={form.data.thank_you_body}
                                onChange={(e) => form.setData('thank_you_body', e.target.value)}
                                placeholder="Jesteśmy wdzięczni za Twoje wsparcie…" />
                            <div className="form-hint">Oddziel akapity pustą linią.</div>
                            {err.thank_you_body && <div className="form-error">{err.thank_you_body}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="thank_you_image_file">Grafika podziękowania</label>
                            {item?.thank_you_image && !form.data.remove_thank_you_image && (
                                <div style={{ marginBottom: 8 }}>
                                    <img src={item.thank_you_image} alt="" style={{ maxWidth: 160, borderRadius: 8 }} />
                                </div>
                            )}
                            <input id="thank_you_image_file" type="file" accept="image/*"
                                onChange={(e) => form.setData('thank_you_image_file', e.target.files[0] ?? null)} />
                            {item?.thank_you_image && (
                                <label style={{ marginTop: 8 }}>
                                    <input type="checkbox" style={{ width: 'auto' }} checked={form.data.remove_thank_you_image}
                                        onChange={(e) => { form.setData('remove_thank_you_image', e.target.checked); if (e.target.checked) form.setData('thank_you_image_file', null) }} />
                                    {' '}usuń grafikę
                                </label>
                            )}
                            {err.thank_you_image_file && <div className="form-error">{err.thank_you_image_file}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="mecenas_organization_id">Mecenas — organizacja</label>
                            <select id="mecenas_organization_id" value={form.data.mecenas_organization_id}
                                onChange={(e) => form.setData('mecenas_organization_id', e.target.value)}>
                                <option value="">— brak —</option>
                                {organizations.map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
                            </select>
                            <div className="form-hint">Nazwa i logo mecenasa pochodzą z profilu wybranej organizacji.</div>
                            {err.mecenas_organization_id && <div className="form-error">{err.mecenas_organization_id}</div>}
                        </div>
                    </div>
                </div>

                <div className="d-flex gap-1 mb-3">
                    <button type="submit" className="btn btn-primary" disabled={form.processing}>Zapisz</button>
                    <Link href={indexUrl} className="btn btn-secondary">Anuluj</Link>
                </div>
            </form>
        </>
    )
}

Form.layout = (page) => <PanelLayout title="Produkt">{page}</PanelLayout>
