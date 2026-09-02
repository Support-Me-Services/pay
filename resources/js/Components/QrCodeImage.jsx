import { useState } from 'react'

// Ten sam zewnętrzny serwis QR co w StorefrontLayout.jsx (kod QR sklepu) —
// bez żadnej biblioteki PHP do generowania obrazków.
export const qrImageUrl = (url, size) => `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=0&data=${encodeURIComponent(url)}`

const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))

// Obszar A4 po odjęciu marginesów (@page margin: 8mm poniżej) — używany i do
// układu siatki, i do wyliczenia rozdzielczości obrazka QR.
const PAGE_W_MM = 210 - 2 * 8
const PAGE_H_MM = 297 - 2 * 8

/**
 * Dobiera liczbę kolumn/wierszy dla zadanej liczby kodów tak, by jak
 * najmniej miejsca się marnowało, a przy remisie — żeby proporcje siatki
 * były jak najbliższe proporcjom kartki A4 (kolumny węższe niż wiersze).
 */
export function computeGridLayout(count) {
    const targetRatio = PAGE_W_MM / PAGE_H_MM
    let best = null
    for (let cols = 1; cols <= count; cols++) {
        const rows = Math.ceil(count / cols)
        const waste = cols * rows - count
        const score = waste * 1000 + Math.abs(cols / rows - targetRatio) * 100
        if (!best || score < best.score) best = { cols, rows, score }
    }
    return { cols: best.cols, rows: best.rows }
}

/**
 * Otwiera nową kartę z arkuszem A4 — ten sam kod QR powtórzony `count` razy
 * w siatce dobranej pod wybraną liczbę (rozmiar kodu dopasowany do tego, ile
 * się zmieści), rozdzielony przerywanymi liniami do wycięcia, i od razu
 * wywołuje okno drukowania (przeglądarka pozwala tam "Zapisz jako PDF" —
 * bez żadnej biblioteki PDF po stronie serwera ani klienta).
 */
export function openQrPrintSheet(url, label, count = 20) {
    const win = window.open('', '_blank')
    if (!win) return

    const { cols, rows } = computeGridLayout(count)
    const cellWMm = PAGE_W_MM / cols
    const cellHMm = PAGE_H_MM / rows
    // 80% miejsca w komórce na sam kod (reszta na padding/etykietę); px
    // liczone pod ~300dpi wydruku, z rozsądnymi widełkami.
    const qrSizePx = Math.max(150, Math.min(1000, Math.round(Math.min(cellWMm, cellHMm) * 0.8 * (300 / 25.4))))

    const qrSrc = qrImageUrl(url, qrSizePx)
    const caption = label ? `<div class="caption">${escapeHtml(label)}</div>` : ''
    const cell = `<div class="cell"><img src="${qrSrc}" alt="Kod QR" />${caption}</div>`
    const cells = Array.from({ length: cols * rows }, (_, i) => (i < count ? cell : '<div class="cell"></div>')).join('')

    win.document.write(`<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Kody QR do wydruku</title>
<style>
    @page { size: A4; margin: 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, sans-serif; }
    .grid {
        display: grid;
        grid-template-columns: repeat(${cols}, 1fr);
        grid-template-rows: repeat(${rows}, 1fr);
        width: 100%; height: ${PAGE_H_MM}mm;
    }
    .cell {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        border: 1px dashed #999; padding: 3mm; overflow: hidden;
    }
    .cell img { width: 100%; height: auto; max-height: 85%; object-fit: contain; }
    .caption { margin-top: 2mm; font-size: 8px; color: #666; text-align: center; word-break: break-word; }
</style>
</head>
<body>
    <div class="grid">${cells}</div>
    <script>window.onload = function () { setTimeout(function () { window.print(); }, 300); };</script>
</body>
</html>`)
    win.document.close()
}

/**
 * Miniatura kodu QR — klik powiększa na cały ekran telefonu. Przeglądarka
 * nie ma dostępu do jasności ekranu urządzenia (brak takiego Web API) —
 * pełnoekranowe białe tło to najbliższe dostępne przybliżenie
 * "rozjaśnienia": maksymalna jasność, jaką strona faktycznie może wyemitować,
 * plus duży kod = łatwiej dla aparatu skanującego.
 */
export default function QrCodeImage({ url, size = 48, alt = 'Kod QR' }) {
    const [zoomed, setZoomed] = useState(false)

    return (
        <>
            <img src={qrImageUrl(url, size)} alt={alt} width={size} height={size}
                style={{ borderRadius: 6, cursor: 'zoom-in' }}
                onClick={() => setZoomed(true)} />

            {zoomed && (
                <div
                    role="dialog" aria-modal="true" aria-label="Powiększony kod QR"
                    onClick={() => setZoomed(false)}
                    style={{
                        position: 'fixed', inset: 0, zIndex: 9999,
                        background: '#fff', display: 'flex', flexDirection: 'column',
                        alignItems: 'center', justifyContent: 'center', gap: 16,
                        cursor: 'zoom-out',
                    }}
                >
                    <img src={qrImageUrl(url, 640)} alt={alt} width={640} height={640}
                        style={{ width: 'min(82vw, 82vh)', height: 'min(82vw, 82vh)' }} />
                    <div style={{ color: '#8a97a8', fontSize: 14 }}>Dotknij, aby zamknąć</div>
                </div>
            )}
        </>
    )
}
