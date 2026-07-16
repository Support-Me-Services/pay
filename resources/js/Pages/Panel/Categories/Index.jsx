import { Link, router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Kategorie" — drzewo (wcięcia wg depth) + reorder góra/dół. */
export default function Index({ items, createUrl }) {
    const reorder = (it, direction) => router.post(it.reorder_url, { direction }, { preserveScroll: true })
    const destroy = (it) => {
        if (confirm('Usunąć kategorię? Podkategorie staną się głównymi.')) router.delete(it.destroy_url, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Kategorie wsparcia</h1>
                <Link href={createUrl} className="btn btn-primary btn-sm">+ Dodaj kategorię</Link>
            </div>

            <div className="card card-static"><div className="card-body"><div className="table-wrap">
                <table className="table">
                    <thead>
                        <tr><th>Nazwa</th><th>Ikonka</th><th>Slug</th><th>Źródło</th><th>Kolejność</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && <tr><td colSpan={7} className="text-muted">Brak kategorii.</td></tr>}
                        {items.map((it) => (
                            <tr key={it.id}>
                                <td className="fw-bold">
                                    {it.depth > 0 && <span className="text-muted">{' '.repeat(it.depth * 4)}↳ </span>}
                                    {it.label_text}
                                </td>
                                <td>{it.icon ? <img src={it.icon} alt="" style={{ width: 32, height: 32, borderRadius: '50%', objectFit: 'cover' }} /> : <span className="text-muted">—</span>}</td>
                                <td><span className="text-muted">{it.slug}</span></td>
                                <td>{it.source_label}</td>
                                <td className="nowrap">
                                    {it.position}{' '}
                                    <button type="button" className="btn-link" title="W górę" onClick={() => reorder(it, 'up')}>↑</button>
                                    <button type="button" className="btn-link" title="W dół" onClick={() => reorder(it, 'down')}>↓</button>
                                </td>
                                <td>{it.active ? <span className="badge badge-success">aktywna</span> : <span className="badge badge-muted">ukryta</span>}</td>
                                <td className="actions nowrap">
                                    <Link href={it.edit_url}>Edytuj</Link>{' '}
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

Index.layout = (page) => <PanelLayout title="Kategorie">{page}</PanelLayout>
