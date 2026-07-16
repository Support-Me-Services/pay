import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Handlowcy" — lista (CRM). */
export default function Index({ items, createUrl }) {
    const destroy = (sp) => {
        if (confirm('Usunąć handlowca? Parafie stracą przypisanie.')) router.delete(sp.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Handlowcy</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj handlowca</Link>
            </div>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Imię i nazwisko</th><th>Kontakt</th><th>Województwa</th><th>Parafie</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={6} className="text-muted">Brak handlowców.</td></tr>}
                        {items.map((sp) => (
                            <tr key={sp.id}>
                                <td className="fw-bold">{sp.name}</td>
                                <td>
                                    {sp.email || '—'}
                                    {sp.phone && <><br /><span className="text-muted" style={{ fontWeight: 400 }}>{sp.phone}</span></>}
                                </td>
                                <td>{sp.voivodeships.length ? sp.voivodeships.join(', ') : <span className="text-muted">—</span>}</td>
                                <td>{sp.parishes_count > 0 ? <a href={sp.parishes_url}>{sp.parishes_count}</a> : <span className="text-muted">0</span>}</td>
                                <td>{sp.active ? <span className="badge badge-success">aktywny</span> : <span className="badge badge-muted">nieaktywny</span>}</td>
                                <td className="actions nowrap">
                                    <Link href={sp.edit_url}>Edytuj</Link>{' '}
                                    <a href="#" onClick={(e) => { e.preventDefault(); destroy(sp) }}>Usuń</a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Handlowcy">{page}</PanelLayout>
