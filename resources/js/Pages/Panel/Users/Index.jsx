import { useState } from 'react'
import { router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/**
 * Panel „Wszystkie organizacje" (tylko super-user) — globalny podgląd/
 * nadzór nad widocznością sekcji WSZYSTKICH organizacji. Bezpiecznik, nie
 * główny mechanizm — każda organizacja steruje sobą sama (self-service,
 * patrz Panel/Organizations/Settings.jsx).
 */
function OrgRow({ org, sections, users }) {
    const [checked, setChecked] = useState(new Set(org.enabled_sections))
    const [saving, setSaving] = useState(false)
    const [ownerId, setOwnerId] = useState(org.ownerId)
    const [reassigning, setReassigning] = useState(false)

    const toggle = (key) => {
        const next = new Set(checked)
        next.has(key) ? next.delete(key) : next.add(key)
        setChecked(next)
    }

    const save = () => {
        setSaving(true)
        router.post(org.update_url, { sections: Array.from(checked) }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        })
    }

    const reassign = () => {
        setReassigning(true)
        router.post(org.owner_url, { user_id: ownerId }, {
            preserveScroll: true,
            onFinish: () => setReassigning(false),
        })
    }

    return (
        <tr>
            <td className="fw-bold">{org.name}</td>
            <td>
                <select value={ownerId} onChange={(e) => setOwnerId(Number(e.target.value))}>
                    {users.map((u) => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                </select>
            </td>
            <td>{org.handle || '—'}</td>
            {sections.map((s) => (
                <td key={s.key} style={{ textAlign: 'center' }}>
                    <input type="checkbox" checked={checked.has(s.key)} onChange={() => toggle(s.key)} />
                </td>
            ))}
            <td className="actions nowrap">
                <a href="#" onClick={(e) => { e.preventDefault(); save() }}>{saving ? 'Zapisuję…' : 'Zapisz'}</a>
                {' · '}
                <a href="#" onClick={(e) => { e.preventDefault(); reassign() }}>{reassigning ? 'Przepinam…' : 'Przepnij'}</a>
            </td>
        </tr>
    )
}

export default function Index({ items, sections, users }) {
    return (
        <>
            <div className="panel-title">
                <h1>Wszystkie organizacje — widoczność sekcji</h1>
            </div>

            <div className="card card-static">
                <div className="card-body">
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Nazwa</th><th>Właściciel</th><th>Handle</th>
                                    {sections.map((s) => <th key={s.key} style={{ textAlign: 'center' }}>{s.label}</th>)}
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 && (
                                    <tr><td colSpan={4 + sections.length} className="text-muted">Brak organizacji.</td></tr>
                                )}
                                {items.map((org) => (
                                    <OrgRow key={org.id} org={org} sections={sections} users={users} />
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Wszystkie organizacje">{page}</PanelLayout>
