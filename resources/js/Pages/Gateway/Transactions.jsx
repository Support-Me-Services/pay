import { Link, router } from '@inertiajs/react'
import GatewayPanelLayout from '@/Layouts/GatewayPanelLayout'

const STATUS_LABELS = {
    created: 'Utworzona',
    pending: 'W toku',
    paid: 'Opłacona',
    failed: 'Nieudana',
    abandoned: 'Porzucona',
}

const STATUS_BADGE = {
    paid: 'badge-success',
    failed: 'badge-error',
    pending: 'badge-brand',
    created: 'badge-muted',
    abandoned: 'badge-muted',
}

/** Panel bramki — zestawienie wszystkich płatności (filtr sklep/status + paginacja). */
export default function Transactions({ shops, shopId, status, transactions, totalAmount, transactionsUrl }) {
    const onFilter = (patch) => {
        const params = { shop_id: shopId ?? '', status: status ?? '', ...patch }
        Object.keys(params).forEach((k) => { if (!params[k]) delete params[k] })
        router.get(transactionsUrl, params, { preserveScroll: true, preserveState: true })
    }

    return (
        <>
            <div className="panel-title"><h1>Płatności</h1></div>

            <div className="card card-static mb-3"><div className="card-body d-flex gap-2" style={{ flexWrap: 'wrap', alignItems: 'flex-end' }}>
                <div style={{ minWidth: 220 }}>
                    <label htmlFor="shop_id">Sklep</label>
                    <select id="shop_id" value={shopId ?? ''} onChange={(e) => onFilter({ shop_id: e.target.value })}>
                        <option value="">— wszystkie sklepy —</option>
                        {shops.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                    </select>
                </div>
                <div style={{ minWidth: 200 }}>
                    <label htmlFor="status">Status</label>
                    <select id="status" value={status ?? ''} onChange={(e) => onFilter({ status: e.target.value })}>
                        <option value="">— wszystkie statusy —</option>
                        {Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                    </select>
                </div>
                <div className="text-muted" style={{ marginLeft: 'auto' }}>
                    Suma opłaconych: <strong>{totalAmount}</strong>
                </div>
            </div></div>

            <div className="card card-static"><div className="card-body">
                <div className="table-wrap">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Data</th><th>Sklep</th><th>Tag</th><th>Produkt</th>
                                <th>Kwota</th><th>Status</th><th>Tryb</th><th>Kupujący</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.data.length === 0 && <tr><td colSpan={8} className="text-muted">Brak transakcji.</td></tr>}
                            {transactions.data.map((tx) => (
                                <tr key={tx.id}>
                                    <td className="nowrap">{tx.created_at}</td>
                                    <td>{tx.shop || '—'}</td>
                                    <td>{tx.tag || '—'}</td>
                                    <td>{tx.product_name || '—'}</td>
                                    <td className="fw-bold nowrap">{tx.amount}</td>
                                    <td><span className={`badge ${STATUS_BADGE[tx.status] || 'badge-muted'}`}>{STATUS_LABELS[tx.status] || tx.status}</span></td>
                                    <td>{tx.mode}</td>
                                    <td>
                                        {tx.buyer && <div>{tx.buyer}</div>}
                                        {tx.buyer_email && <a href={`mailto:${tx.buyer_email}`}>{tx.buyer_email}</a>}
                                        {!tx.buyer && !tx.buyer_email && '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {transactions.last_page > 1 && (
                    <div className="mt-2 d-flex gap-1" style={{ alignItems: 'center', flexWrap: 'wrap' }}>
                        {transactions.prev_page_url
                            ? <Link href={transactions.prev_page_url} preserveScroll className="btn btn-secondary btn-sm">← Poprzednia</Link>
                            : <span className="btn btn-secondary btn-sm" style={{ opacity: 0.45, pointerEvents: 'none' }}>← Poprzednia</span>}
                        <span className="text-muted" style={{ fontSize: '.85rem' }}>Strona {transactions.current_page} z {transactions.last_page}</span>
                        {transactions.next_page_url
                            ? <Link href={transactions.next_page_url} preserveScroll className="btn btn-secondary btn-sm">Następna →</Link>
                            : <span className="btn btn-secondary btn-sm" style={{ opacity: 0.45, pointerEvents: 'none' }}>Następna →</span>}
                    </div>
                )}
            </div></div>
        </>
    )
}

Transactions.layout = (page) => <GatewayPanelLayout title="Płatności">{page}</GatewayPanelLayout>
