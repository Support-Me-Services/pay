import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Sklep" — lista produktów (odwzorowanie shop-items/index.blade.php). */
export default function Index({ items, createUrl }) {
    const toggle = (item) => router.post(item.toggle_url, {}, { preserveScroll: true })
    const destroy = (item) => {
        if (confirm('Usunąć produkt?')) router.delete(item.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Zbiórki</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj produkt</Link>
            </div>

            <div className="card card-static">
                <div className="card-body">
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Kol.</th><th>Grafika</th><th>Nazwa</th><th>Min. kwota</th>
                                    <th>Tag NFC</th><th>Domyślny</th><th>Status</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 && (
                                    <tr><td colSpan={8} className="text-muted">Brak produktów.</td></tr>
                                )}
                                {items.map((item) => (
                                    <tr key={item.id}>
                                        <td>{item.sort}</td>
                                        <td>
                                            {item.image
                                                ? <img src={item.image} alt="" style={{ width: 40, height: 40, objectFit: 'contain', borderRadius: 6, background: '#f6f9fb' }} />
                                                : <span className="text-muted">—</span>}
                                        </td>
                                        <td className="fw-bold">{item.name}</td>
                                        <td>{item.min_amount_pln} zł</td>
                                        <td>{item.tag_uid || '—'}</td>
                                        <td>{item.is_default ? <span className="badge badge-brand">domyślny</span> : <span className="text-muted">—</span>}</td>
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

Index.layout = (page) => <PanelLayout title="Zbiórki — produkty">{page}</PanelLayout>
