import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import QrCodeImage from '@/Components/QrCodeImage'
import CopyButton from '@/Components/CopyButton'

/**
 * Panel „Moje tagi" — kody OSOBISTE (własność konta, nie organizacji). Cel:
 * cała lista zbiórek jednej z własnych organizacji, nie pojedynczy produkt.
 */
export default function Index({ items, createUrl }) {
    const toggle = (item) => router.post(item.toggle_url, {}, { preserveScroll: true })
    const destroy = (item) => {
        if (confirm('Usunąć tag?')) router.delete(item.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Moje tagi</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj tag</Link>
            </div>

            <div className="form-hint mb-3">
                Twoje własne tagi/kody QR — niezależne od organizacji, prowadzą na całą
                listę zbiórek wybranej przez Ciebie organizacji.
            </div>

            <div className="card card-static">
                <div className="card-body">
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Etykieta</th><th>Organizacja docelowa</th><th>Kod</th><th>Status</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 && (
                                    <tr><td colSpan={5} className="text-muted">Brak tagów.</td></tr>
                                )}
                                {items.map((item) => (
                                    <tr key={item.id}>
                                        <td>{item.label || <span className="text-muted">—</span>}</td>
                                        <td>{item.target_organization_name || <span className="text-muted">— nieprzypisany —</span>}</td>
                                        <td>
                                            <div className="d-flex gap-1" style={{ alignItems: 'center' }}>
                                                <QrCodeImage url={item.qr_url} size={48} />
                                                <span>
                                                    <a href={item.tag_url} target="_blank" rel="noopener noreferrer">tag</a>{' '}
                                                    <CopyButton value={item.tag_url} />
                                                </span>
                                                <a href={item.qr_url} target="_blank" rel="noopener noreferrer">qr</a>
                                            </div>
                                        </td>
                                        <td>{item.active ? <span className="badge badge-success">aktywny</span> : <span className="badge badge-muted">nieaktywny</span>}</td>
                                        <td className="actions nowrap">
                                            <Link href={item.edit_url}>Edytuj</Link>{' '}
                                            <a href="#" onClick={(e) => { e.preventDefault(); toggle(item) }}>
                                                {item.active ? 'Dezaktywuj' : 'Aktywuj'}
                                            </a>{' '}
                                            <a href="#" onClick={(e) => { e.preventDefault(); destroy(item) }}>Usuń</a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Moje tagi">{page}</PanelLayout>
