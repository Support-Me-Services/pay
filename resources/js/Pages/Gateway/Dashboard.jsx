import { Link } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'
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

/** Panel bramki — dashboard (statystyki globalne + wykres + tabela sklepów). */
export default function Dashboard({ shopsCount, global, global30, series, perShop }) {
    return (
        <>
            <div className="panel-title"><h1>Dashboard</h1></div>

            <h3 className="mb-1">Łącznie</h3>
            <div className="stat-grid">
                <StatCard label="Sklepy" value={shopsCount} />
                <StatCard brand label="Suma opłaconych" value={global.revenue} />
                <StatCard label="Płatności opłacone" value={global.paid} />
                <StatCard label="Otwarcia tagów" value={global.opens} />
                <StatCard label="Konwersja" value={`${global.conversion}%`} sub="zakupy / otwarcia" />
            </div>

            <h3 className="mb-1">Ostatnie 30 dni</h3>
            <div className="stat-grid">
                <StatCard brand label="Przychód 30 dni" value={global30.revenue} />
                <StatCard label="Opłacone 30 dni" value={global30.paid} />
                <StatCard label="Otwarcia 30 dni" value={global30.opens} />
                <StatCard label="Konwersja 30 dni" value={`${global30.conversion}%`} />
            </div>

            <div className="chart-card">
                <h3>Opłacone transakcje — dzień po dniu (30 dni)</h3>
                <BarChart labels={series.labels} data={series.counts} label="Opłacone transakcje" />
            </div>

            <div className="card card-static"><div className="card-body">
                <h3>Sklepy</h3>
                <div className="table-wrap">
                    <table className="table">
                        <thead>
                            <tr><th>Sklep</th><th>Tryb</th><th>Tagi</th><th>Otwarcia</th><th>Zakupy</th><th>Przychód</th><th>Konwersja</th></tr>
                        </thead>
                        <tbody>
                            {perShop.map((row) => (
                                <tr key={row.id}>
                                    <td className="fw-bold"><Link href={row.tags_url}>{row.name}</Link></td>
                                    <td><span className={`badge ${row.payment_mode === 'app2app' ? 'badge-brand' : 'badge-muted'}`}>{row.payment_mode}</span></td>
                                    <td>{row.tags_count}</td>
                                    <td>{row.stats.opens}</td>
                                    <td>{row.stats.paid}</td>
                                    <td className="fw-bold">{row.stats.revenue}</td>
                                    <td>{row.stats.conversion}%</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div></div>
        </>
    )
}

Dashboard.layout = (page) => <GatewayPanelLayout title="Dashboard">{page}</GatewayPanelLayout>
