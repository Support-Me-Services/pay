import { router } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'
import BarChart from '@/Components/BarChart'

function StatCard({ label, value, sub, brand, success, error }) {
    const cls = success ? ' text-success' : error ? ' text-error' : ''
    return (
        <div className={`stat-card${brand ? ' stat-brand' : ''}`}>
            <div className="stat-label">{label}</div>
            <div className={`stat-value${cls}`}>{value}</div>
            {sub && <div className="stat-sub">{sub}</div>}
        </div>
    )
}

/** Panel bramki — statystyki (filtr sklep/tag, metryki + wykres). */
export default function Stats({ shops, tags, shop, tag, total, last30, series, statsUrl }) {
    const onShop = (e) => {
        const v = e.target.value
        router.get(statsUrl, v ? { shop_id: v } : {}, { preserveScroll: true })
    }
    const onTag = (e) => {
        const v = e.target.value
        router.get(statsUrl, { shop_id: shop.id, ...(v ? { tag_id: v } : {}) }, { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title"><h1>Statystyki</h1></div>

            <div className="card card-static mb-3"><div className="card-body d-flex gap-2" style={{ flexWrap: 'wrap', alignItems: 'flex-end' }}>
                <div style={{ minWidth: 220 }}>
                    <label htmlFor="shop_id">Sklep</label>
                    <select id="shop_id" value={shop?.id ?? ''} onChange={onShop}>
                        <option value="">— wszystkie sklepy —</option>
                        {shops.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                    </select>
                </div>
                {shop && (
                    <div style={{ minWidth: 220 }}>
                        <label htmlFor="tag_id">Tag</label>
                        <select id="tag_id" value={tag?.id ?? ''} onChange={onTag}>
                            <option value="">— wszystkie tagi —</option>
                            {tags.map((t) => <option key={t.id} value={t.id}>{t.tag_uid}{t.label ? ` (${t.label})` : ''}</option>)}
                        </select>
                    </div>
                )}
            </div></div>

            <h3 className="mb-1">Łącznie {shop ? `— ${shop.name}` : ''} {tag ? `— ${tag.tag_uid}` : ''}</h3>
            <div className="stat-grid">
                <StatCard label="Otwarcia tagów" value={total.opens} />
                <StatCard label="Płatności rozpoczęte" value={total.started} />
                <StatCard label="Opłacone" value={total.paid} success />
                <StatCard label="Nieudane" value={total.failed} error />
                <StatCard label="Konwersja" value={`${total.conversion}%`} />
                <StatCard brand label="Przychód" value={total.revenue} />
            </div>

            <h3 className="mb-1">Ostatnie 30 dni</h3>
            <div className="stat-grid">
                <StatCard label="Otwarcia" value={last30.opens} />
                <StatCard label="Rozpoczęte" value={last30.started} />
                <StatCard label="Opłacone" value={last30.paid} success />
                <StatCard label="Nieudane" value={last30.failed} error />
                <StatCard label="Konwersja" value={`${last30.conversion}%`} />
                <StatCard brand label="Przychód" value={last30.revenue} />
            </div>

            <div className="chart-card">
                <h3>Opłacone transakcje — dzień po dniu (30 dni)</h3>
                <BarChart labels={series.labels} data={series.counts} label="Opłacone" />
            </div>
        </>
    )
}

Stats.layout = (page) => <GatewayPanelLayout title="Statystyki">{page}</GatewayPanelLayout>
