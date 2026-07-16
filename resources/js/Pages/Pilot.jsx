import { useState } from 'react'
import { Head } from '@inertiajs/react'

/**
 * Strona pilotażowa migracji na React (Inertia).
 * Dowodzi całego łańcucha: Laravel (kontroler + tenant) -> Inertia -> React (SSR + hydratacja).
 */
export default function Pilot({ message, tenant, items }) {
    const [count, setCount] = useState(0)

    return (
        <>
            <Head title="React pilot — SupportME" />
            <main style={{ fontFamily: 'Inter, system-ui, sans-serif', maxWidth: 760, margin: '48px auto', padding: '0 20px', color: '#1a2233' }}>
                <span style={{ display: 'inline-block', padding: '2px 10px', borderRadius: 999, background: '#e7f0ff', color: '#1d4ed8', fontSize: 13, fontWeight: 700 }}>
                    Inertia + React
                </span>
                <h1 style={{ fontSize: 34, margin: '12px 0 8px' }}>Pilotaż migracji frontendu ✅</h1>
                <p style={{ color: '#4a5568', fontSize: 17 }}>{message}</p>

                <p style={{ margin: '20px 0' }}>
                    Interaktywność po stronie klienta:{' '}
                    <button
                        onClick={() => setCount((c) => c + 1)}
                        style={{ padding: '8px 16px', border: 0, borderRadius: 10, background: '#2563eb', color: '#fff', fontWeight: 700, cursor: 'pointer' }}
                    >
                        Kliknięto {count}×
                    </button>
                </p>

                <p style={{ color: '#6b7280', fontSize: 14 }}>Tenant (z ResolveTenant): <strong>{tenant}</strong></p>

                <h2 style={{ fontSize: 20, marginTop: 28 }}>Dane z kontrolera Laravel (props):</h2>
                <ul style={{ lineHeight: 1.7 }}>
                    {items.map((it) => (
                        <li key={it.id}>{it.name} — <strong>{it.price}</strong></li>
                    ))}
                </ul>
            </main>
        </>
    )
}
