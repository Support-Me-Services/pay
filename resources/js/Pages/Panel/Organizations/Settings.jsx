import { useState } from 'react'
import { router } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/**
 * Self-service: sama organizacja włącza/wyłącza swoje 5 sekcji panelu.
 * Bez udziału super-usera (patrz Panel/Users/Index.jsx — to osobny,
 * globalny bezpiecznik nad wszystkimi organizacjami).
 */
export default function Settings({ organizationName, sections, enabledSections, updateUrl }) {
    const [checked, setChecked] = useState(new Set(enabledSections))
    const [saving, setSaving] = useState(false)

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

    return (
        <>
            <div className="panel-title">
                <h1>Ustawienia organizacji — {organizationName}</h1>
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
