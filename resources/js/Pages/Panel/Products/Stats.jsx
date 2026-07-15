import { Link } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import BarChart from '@/Components/BarChart'

function StatCard({ label, value, sub, brand, success }) {
    return (
        <div className={`stat-card${brand ? ' stat-brand' : ''}`}>
            <div className="stat-label">{label}</div>
            <div className={`stat-value${success ? ' text-success' : ''}`}>{value}</div>
            {sub && <div className="stat-sub">{sub}</div>}
        </div>
    )
}

/** Panel — statystyki pojedynczej parafii (metryki + lejek + wykres zakupów 30 dni). */
export default function Stats({ product, total, last30, funnel, series, indexUrl }) {
    const max = Math.max(1, funnel[0]?.value ?? 0)

    return (
        <>
            <div className="panel-title">
                <h1>Statystyki: {product.name}</h1>
                <Link href={indexUrl} className="btn btn-secondary btn-sm">← Produkty</Link>
            </div>

            <h3 className="mb-1">Łącznie</h3>
            <div className="stat-grid">
                <StatCard label="Otwarcia tagów" value={total.opens} />
                <StatCard label="Wejścia na stronę" value={total.views} />
                <StatCard label={'Kliki „Kup”'} value={total.clicks} />
                <StatCard label="Zakupy" value={total.purchases} success />
                <StatCard brand label="Przychód" value={total.revenue} />
                <StatCard label="Konwersja" value={`${total.conversion}%`} sub="zakupy / otwarcia" />
            </div>

            <h3 className="mb-1">Ostatnie 30 dni</h3>
            <div className="stat-grid">
                <StatCard label="Otwarcia" value={last30.opens} />
                <StatCard label="Wejścia" value={last30.views} />
                <StatCard label={'Kliki „Kup”'} value={last30.clicks} />
                <StatCard label="Zakupy" value={last30.purchases} success />
                <StatCard brand label="Przychód" value={last30.revenue} />
                <StatCard label="Konwersja" value={`${last30.conversion}%`} />
            </div>

            <div className="chart-card">
                <h3>Lejek sprzedażowy (łącznie)</h3>
                <div className="funnel">
                    {funnel.map((step) => (
                        <div className="funnel-row" key={step.label}>
                            <div className="funnel-label">{step.label}</div>
                            <div className="funnel-bar-wrap">
                                <div className="funnel-bar" style={{ width: `${Math.max(4, Math.round(step.value / max * 100))}%` }}>{step.value}</div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="chart-card">
                <h3>Zakupy — dzień po dniu (30 dni)</h3>
                <BarChart labels={series.labels} data={series.counts} />
            </div>
        </>
    )
}

Stats.layout = (page) => <PanelLayout title="Statystyki">{page}</PanelLayout>
