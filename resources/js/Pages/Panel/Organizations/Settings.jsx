import { useState } from 'react'
import { router, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/**
 * Self-service: sama organizacja włącza/wyłącza swoje 5 sekcji panelu oraz
 * zmienia swoją nazwę (handle/URL publiczny zostaje bez zmian). Bez udziału
 * super-usera (patrz Panel/Users/Index.jsx — to osobny, globalny bezpiecznik
 * nad wszystkimi organizacjami).
 */
export default function Settings({ organizationName, nameUpdateUrl, sections, enabledSections, updateUrl }) {
    const [checked, setChecked] = useState(new Set(enabledSections))
    const [saving, setSaving] = useState(false)
    const nameForm = useForm({ name: organizationName })

    const toggle = (key) => {
        const next = new Set(checked)
        next.has(key) ? next.delete(key) : next.add(key)
        setChecked(next)
    }

    const save = (e) => {
        e.preventDefault()
        setSaving(true)
        router.post(updateUrl, { sections: Array.from(checked) }, { onFinish: () => setSaving(false) })
    }

    const saveName = (e) => {
        e.preventDefault()
        nameForm.post(nameUpdateUrl, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Ustawienia organizacji — {organizationName}</h1>
            </div>

            <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                <div className="card-body">
                    <h2 style={{ marginTop: 0 }}>Nazwa organizacji</h2>
                    <form onSubmit={saveName}>
                        <div className="form-group">
                            <label htmlFor="org_name">Nazwa</label>
                            <input id="org_name" value={nameForm.data.name}
                                onChange={(e) => nameForm.setData('name', e.target.value)} required />
                            {nameForm.errors.name && <div className="form-error">{nameForm.errors.name}</div>}
                        </div>
                        <button type="submit" className="btn btn-primary" disabled={nameForm.processing}>
                            {nameForm.processing ? 'Zapisuję…' : 'Zapisz nazwę'}
                        </button>
                    </form>
                </div>
            </div>

            <div className="card card-static" style={{ maxWidth: 640 }}>
                <div className="card-body">
                    <div className="form-hint" style={{ marginBottom: 16 }}>
                        Wyłączona sekcja znika z menu i jest niedostępna (403), dopóki jej nie włączysz z powrotem.
                    </div>
                    <form onSubmit={save}>
                        {sections.map((s) => (
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

Settings.layout = (page) => <PanelLayout title="Ustawienia organizacji">{page}</PanelLayout>
