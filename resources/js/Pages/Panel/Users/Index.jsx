import { useState } from 'react'
import { router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/**
 * Panel „Użytkownicy" (tylko super-user) — widoczność sekcji per-konto.
 * `is_admin` nie jest tu edytowalne (nadawane wyłącznie ręcznie w bazie).
 */
function UserRow({ user, sections }) {
    const [checked, setChecked] = useState(new Set(user.enabled_sections))
    const [saving, setSaving] = useState(false)

    const toggle = (key) => {
        const next = new Set(checked)
        next.has(key) ? next.delete(key) : next.add(key)
        setChecked(next)
    }

    const save = () => {
        setSaving(true)
        router.post(user.update_url, { sections: Array.from(checked) }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        })
    }

    return (
        <tr>
            <td className="fw-bold">{user.name}{user.is_admin && <span className="badge badge-brand" style={{ marginLeft: 6 }}>super-user</span>}</td>
            <td>{user.email}</td>
            <td>{user.handle || '—'}</td>
            {sections.map((s) => (
                <td key={s.key} style={{ textAlign: 'center' }}>
                    <input type="checkbox" checked={checked.has(s.key)} onChange={() => toggle(s.key)} />
                </td>
            ))}
            <td className="actions nowrap">
                <a href="#" onClick={(e) => { e.preventDefault(); save() }}>{saving ? 'Zapisuję…' : 'Zapisz'}</a>
            </td>
        </tr>
    )
}

export default function Index({ items, sections }) {
    return (
        <>
            <div className="panel-title">
                <h1>Użytkownicy — widoczność sekcji</h1>
            </div>

            <div className="card card-static">
                <div className="card-body">
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Nazwa</th><th>E-mail</th><th>Handle</th>
                                    {sections.map((s) => <th key={s.key} style={{ textAlign: 'center' }}>{s.label}</th>)}
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 && (
                                    <tr><td colSpan={4 + sections.length} className="text-muted">Brak kont.</td></tr>
                                )}
                                {items.map((user) => (
                                    <UserRow key={user.id} user={user} sections={sections} />
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Użytkownicy">{page}</PanelLayout>
