import { useState } from 'react'
import { Link, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import QrCodeImage, { openQrPrintSheet } from '@/Components/QrCodeImage'
import CopyButton from '@/Components/CopyButton'

// Ile sztuk kodu QR zmieści się na jednej kartce A4 do wyboru w formularzu
// wydruku — rozmiar kodu dopasowuje się automatycznie do wybranej liczby.
const PRINT_COUNT_OPTIONS = [1, 2, 4, 6, 8, 9, 12, 16, 20, 24, 30]

/** Panel „Moje tagi" — dodawanie/edycja tagu osobistego, cel: cała organizacja. */
export default function Form({ item, organizations, storeUrl, indexUrl }) {
    const editing = !!item
    const [printCount, setPrintCount] = useState(20)

    const form = useForm({
        label: item?.label ?? '',
        target_organization_id: item?.target_organization_id ?? '',
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
                <h1>{editing ? 'Edytuj tag' : 'Dodaj tag'}</h1>
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
                                placeholder="np. Mój wizytówkowy tag (opcjonalne)" />
                            {err.label && <div className="form-error">{err.label}</div>}
                        </div>

                        <div className="form-group">
                            <label htmlFor="target_organization_id">Organizacja docelowa</label>
                            <select id="target_organization_id" value={form.data.target_organization_id}
                                onChange={(e) => form.setData('target_organization_id', e.target.value)}>
                                <option value="">— nieprzypisany —</option>
                                {organizations.map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
                            </select>
                            <div className="form-hint">
                                Po zeskanowaniu/zbliżeniu użytkownik trafia na całą listę zbiórek tej
                                organizacji. Cel można zmienić w każdej chwili — ten sam fizyczny
                                tag/kod QR zacznie od razu prowadzić gdzie indziej.
                            </div>
                            {err.target_organization_id && <div className="form-error">{err.target_organization_id}</div>}
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
                                    <div className="d-flex gap-1 mt-1" style={{ alignItems: 'center' }}>
                                        <label htmlFor="print_count" className="text-muted" style={{ fontSize: 13 }}>Ile sztuk na kartce A4:</label>
                                        <select id="print_count" value={printCount} onChange={(e) => setPrintCount(Number(e.target.value))} style={{ width: 'auto' }}>
                                            {PRINT_COUNT_OPTIONS.map((n) => <option key={n} value={n}>{n}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <a href="#" onClick={(e) => { e.preventDefault(); openQrPrintSheet(item.qr_url, item.label, printCount) }}>
                                            Pobierz QR (PDF, arkusz A4 — {printCount} szt.)
                                        </a>
                                    </div>
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

Form.layout = (page) => <PanelLayout title="Mój tag">{page}</PanelLayout>
