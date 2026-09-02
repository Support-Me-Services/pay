import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import QrCodeImage, { qrImageUrl } from '@/Components/QrCodeImage'
import CopyButton from '@/Components/CopyButton'

/** Panel „Tagi/QR" (organizacji) — dodawanie/edycja kodu, cel: konkretny produkt. */
export default function Form({ item, shopItems, storeUrl, indexUrl }) {
    const editing = !!item

    const form = useForm({
        label: item?.label ?? '',
        shop_item_id: item?.shop_item_id ?? '',
        active: item?.active ?? true,
    })

    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        if (editing) {
            form.transform((d) => ({ ...d, _method: 'put' }))
            form.post(item.update_url)
        } else {
            form.post(storeUrl)
        }
    }

    return (
        <>
            <div className="panel-title">
                <h1>{editing ? 'Edytuj kod' : 'Dodaj kod'}</h1>
            </div>

            {Object.keys(err).length > 0 && (
                <div className="alert alert-error">Popraw zaznaczone pola formularza.</div>
            )}

            <form onSubmit={submit}>
                <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                    <div className="card-body">
                        <div className="form-group">
                            <label htmlFor="label">Etykieta</label>
                            <input id="label" value={form.data.label} onChange={(e) => form.setData('label', e.target.value)}
                                placeholder="np. Taca przy wejściu (opcjonalne)" />
                            {err.label && <div className="form-error">{err.label}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="shop_item_id">Produkt docelowy</label>
                            <select id="shop_item_id" value={form.data.shop_item_id}
                                onChange={(e) => form.setData('shop_item_id', e.target.value)}>
                                <option value="">— nieprzypisany —</option>
                                {shopItems.map((i) => <option key={i.id} value={i.id}>{i.name}</option>)}
                            </select>
                            <div className="form-hint">
                                Cel można zmienić w każdej chwili — ten sam fizyczny tag/kod QR
                                zacznie od razu prowadzić na nowy produkt.
                            </div>
                            {err.shop_item_id && <div className="form-error">{err.shop_item_id}</div>}
                        </div>

                        <div className="form-group">
                            <label>
                                <input type="checkbox" checked={form.data.active} onChange={(e) => form.setData('active', e.target.checked)} style={{ width: 'auto' }} />
                                {' '}aktywny
                            </label>
                        </div>

                        {editing && (
                            <div className="form-group">
                                <label>Adresy publiczne</label>
                                <div className="d-flex gap-1" style={{ alignItems: 'center' }}>
                                    <a href={item.tag_url} target="_blank" rel="noopener noreferrer">{item.tag_url}</a>
                                    <span className="text-muted">(tag NFC)</span>
                                    <CopyButton value={item.tag_url} />
                                </div>
                                <div className="d-flex gap-1" style={{ alignItems: 'center', marginTop: 4 }}>
                                    <a href={item.qr_url} target="_blank" rel="noopener noreferrer">{item.qr_url}</a>
                                    <span className="text-muted">(kod QR)</span>
                                </div>
                                <div style={{ marginTop: 8 }}>
                                    <QrCodeImage url={item.qr_url} size={160} />
                                    <div className="form-hint">Kliknij kod, aby powiększyć na cały ekran (ułatwia skanowanie).</div>
                                    <div><a href={qrImageUrl(item.qr_url, 640)} target="_blank" rel="noopener noreferrer">Pobierz QR</a></div>
                                </div>
                            </div>
                        )}
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

Form.layout = (page) => <PanelLayout title="Tag/kod QR">{page}</PanelLayout>
