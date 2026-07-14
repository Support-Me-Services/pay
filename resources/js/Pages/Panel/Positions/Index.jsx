import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Praca" — lista stanowisk. */
export default function Index({ items, createUrl }) {
    const toggle = (it) => router.post(it.toggle_url, {}, { preserveScroll: true })
    const destroy = (it) => {
        if (confirm('Usunąć stanowisko?')) router.delete(it.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Praca — stanowiska</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj stanowisko</Link>
            </div>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Kol.</th><th>Tytuł</th><th>Lokalizacja</th><th>Rodzaj</th><th>Zgłoszenia</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={7} className="text-muted">Brak stanowisk.</td></tr>}
                        {items.map((it) => (
                            <tr key={it.id}>
                                <td>{it.sort}</td>
                                <td className="fw-bold">{it.title}</td>
                                <td>{it.location || '—'}</td>
                                <td>{it.employment_type || '—'}</td>
                                <td>{it.applications_count > 0 ? <a href={it.applications_url}>{it.applications_count}</a> : <span className="text-muted">0</span>}</td>
                                <td>{it.active ? <span className="badge badge-success">aktywne</span> : <span className="badge badge-muted">nieaktywne</span>}</td>
                                <td className="actions nowrap">
                                    <Link href={it.edit_url}>Edytuj</Link>{' '}
                                    <a href="#" onClick={(e) => { e.preventDefault(); toggle(it) }}>{it.active ? 'Dezaktywuj' : 'Aktywuj'}</a>{' '}
                                    <a href="#" onClick={(e) => { e.preventDefault(); destroy(it) }}>Usuń</a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div></div></div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Praca">{page}</PanelLayout>
