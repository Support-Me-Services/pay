import { Link } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import BarChart from '@/Components/BarChart'

function StatCard({ label, value, sub, brand }) {
    return (
        <div className={`stat-card${brand ? ' stat-brand' : ''}`}>
            <div className="stat-label">{label}</div>
            <div className="stat-value">{value}</div>
            {sub && <div className="stat-sub">{sub}</div>}
        </div>
    )
}

/** Panel — dashboard (statystyki sklepu + wykres zakupów 30 dni). */
export default function Dashboard({ paymentMode, total, last30, series, unread, messagesUrl }) {
    return (
        <>
            <div className="panel-title">
                <h1>Dashboard</h1>
                <span className="badge badge-brand">tryb płatności: {paymentMode}</span>
            </div>

            {unread > 0 && (
                <div className="alert alert-success" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
                    <span>Masz <strong>{unread}</strong> nieprzeczytanych wiadomości z formularza kontaktowego.</span>
                    <Link href={messagesUrl} className="btn btn-primary btn-sm">Zobacz wiadomości</Link>
                </div>
            )}

            <h3 className="mb-1">Łącznie</h3>
            <div className="stat-grid">
                <StatCard brand label="Przychód" value={total.revenue} />
                <StatCard label="Zakupy" value={total.purchases} />
                <StatCard label="Otwarcia tagów" value={total.opens} />
                <StatCard label="Konwersja" value={`${total.conversion}%`} sub="zakupy / otwarcia" />
            </div>

            <h3 className="mb-1">Ostatnie 30 dni</h3>
            <div className="stat-grid">
                <StatCard brand label="Przychód 30 dni" value={last30.revenue} />
                <StatCard label="Zakupy 30 dni" value={last30.purchases} />
                <StatCard label="Otwarcia 30 dni" value={last30.opens} />
                <StatCard label="Konwersja 30 dni" value={`${last30.conversion}%`} />
            </div>

            <div className="chart-card">
                <h3>Zakupy — dzień po dniu (30 dni)</h3>
                <BarChart labels={series.labels} data={series.counts} />
            </div>
        </>
    )
}

Dashboard.layout = (page) => <PanelLayout title="Dashboard">{page}</PanelLayout>
