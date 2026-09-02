import { useState } from 'react'
import { router, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/**
 * Panel „Organizacja" — jeden ekran łączący dawne trzy osobne strony:
 * lista/przełącznik organizacji konta + tworzenie nowej, self-service
 * ustawienia AKTYWNEJ organizacji (nazwa + widoczność 5 sekcji), oraz —
 * tylko dla super-usera — globalny podgląd/nadzór nad WSZYSTKIMI
 * organizacjami z przepinaniem administratora.
 */
function MyOrganizations({ organizations, activeId, switchUrl, storeUrl }) {
    const [switching, setSwitching] = useState(null)
    const form = useForm({ name: '' })

    const switchTo = (id) => {
        if (id === activeId) return
        setSwitching(id)
        router.post(switchUrl, { organization_id: id }, { onFinish: () => setSwitching(null) })
    }

    const submit = (e) => {
        e.preventDefault()
        form.post(storeUrl, { onSuccess: () => form.reset() })
    }

    return (
        <>
            <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                <div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Moje organizacje</h2>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Nazwa</th><th>Handle</th><th></th></tr>
                            </thead>
                            <tbody>
                                {organizations.length === 0 && (
                                    <tr><td colSpan={3} className="text-muted">Brak organizacji.</td></tr>
                                )}
                                {organizations.map((org) => (
                                    <tr key={org.id}>
                                        <td className="fw-bold">{org.name}{org.id === activeId && <span className="badge badge-brand" style={{ marginLeft: 6 }}>aktywna</span>}</td>
                                        <td>{org.handle}</td>
                                        <td className="actions nowrap">
                                            {org.id !== activeId && (
                                                <a href="#" onClick={(e) => { e.preventDefault(); switchTo(org.id) }}>
                                                    {switching === org.id ? 'Przełączam…' : 'Przełącz'}
                                                </a>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                <div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Dodaj organizację</h2>
                    <form onSubmit={submit}>
                        <div className="form-group">
                            <label htmlFor="org_name">Nazwa</label>
                            <input id="org_name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required placeholder="np. Parafia św. Anny" />
                            {form.errors.name && <div className="form-error">{form.errors.name}</div>}
                        </div>
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Załóż organizację</button>
                    </form>
                </div>
            </div>
        </>
    )
}

function ActiveOrgSettings({ activeOrg }) {
    const [checked, setChecked] = useState(new Set(activeOrg.enabledSections))
    const [saving, setSaving] = useState(false)
    const nameForm = useForm({ name: activeOrg.name })

    const toggle = (key) => {
        const next = new Set(checked)
        next.has(key) ? next.delete(key) : next.add(key)
        setChecked(next)
    }

    const saveSections = (e) => {
        e.preventDefault()
        setSaving(true)
        router.post(activeOrg.sectionsUpdateUrl, { sections: Array.from(checked) }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        })
    }

    const saveName = (e) => {
        e.preventDefault()
        nameForm.post(activeOrg.nameUpdateUrl, { preserveScroll: true })
    }

    return (
        <>
            <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                <div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Nazwa aktywnej organizacji</h2>
                    <form onSubmit={saveName}>
                        <div className="form-group">
                            <label htmlFor="active_org_name">Nazwa</label>
                            <input id="active_org_name" value={nameForm.data.name}
                                onChange={(e) => nameForm.setData('name', e.target.value)} required />
                            {nameForm.errors.name && <div className="form-error">{nameForm.errors.name}</div>}
                        </div>
                        <button type="submit" className="btn btn-primary" disabled={nameForm.processing}>
                            {nameForm.processing ? 'Zapisuję…' : 'Zapisz nazwę'}
                        </button>
                    </form>
                </div>
            </div>

            <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                <div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Widoczność sekcji aktywnej organizacji</h2>
                    <div className="form-hint" style={{ marginBottom: 16 }}>
                        Wyłączona sekcja znika z menu i jest niedostępna (403), dopóki jej nie włączysz z powrotem.
                    </div>
                    <form onSubmit={saveSections}>
                        {activeOrg.sections.map((s) => (
                            <div className="form-group" key={s.key}>
                                <label>
                                    <input type="checkbox" style={{ width: 'auto' }} checked={checked.has(s.key)} onChange={() => toggle(s.key)} />
                                    {' '}{s.label}
                                </label>
                            </div>
                        ))}
                        <button type="submit" className="btn btn-primary" disabled={saving}>{saving ? 'Zapisuję…' : 'Zapisz'}</button>
                    </form>
                </div>
            </div>
        </>
    )
}

function AllOrgRow({ org, sections, users }) {
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

function AllOrganizations({ allOrganizations }) {
    const { items, sections, users } = allOrganizations

    return (
        <div className="card card-static">
            <div className="card-body">
                <h2 style={{ marginTop: 0 }}>Wszystkie organizacje</h2>
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
                                <AllOrgRow key={org.id} org={org} sections={sections} users={users} />
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    )
}

export default function Index({ organizations, activeId, switchUrl, storeUrl, activeOrg, allOrganizations }) {
    return (
        <>
            <div className="panel-title">
                <h1>Organizacja</h1>
            </div>

            <MyOrganizations organizations={organizations} activeId={activeId} switchUrl={switchUrl} storeUrl={storeUrl} />

            {/* key={activeId} — wymusza pełny remount przy zmianie aktywnej
                organizacji (przełączenie, założenie nowej), inaczej stan
                zaznaczonych checkboxów (useState) zostaje z POPRZEDNIEJ
                organizacji, mimo że propsy (activeOrg.enabledSections) się
                zmieniły — useState czyta initial value tylko przy montowaniu. */}
            {activeOrg && <ActiveOrgSettings key={activeId} activeOrg={activeOrg} />}

            {allOrganizations && <AllOrganizations allOrganizations={allOrganizations} />}
        </>
    )
}

Index.layout = (page) => <PanelLayout title="Organizacja">{page}</PanelLayout>
