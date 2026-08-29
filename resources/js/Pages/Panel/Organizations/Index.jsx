import { useState } from 'react'
import { router, useForm } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'

/** Panel „Moje organizacje" — przełącznik aktywnej + zakładanie nowej. */
export default function Index({ organizations, activeId, switchUrl, storeUrl }) {
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
            <div className="panel-title">
                <h1>Moje organizacje</h1>
            </div>

            <div className="card card-static mb-3" style={{ maxWidth: 640 }}>
                <div className="card-body">
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

            <div className="card card-static" style={{ maxWidth: 640 }}>
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

Index.layout = (page) => <PanelLayout title="Moje organizacje">{page}</PanelLayout>
