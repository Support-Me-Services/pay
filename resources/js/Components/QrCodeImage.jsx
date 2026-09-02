import { useState } from 'react'

// Ten sam zewnętrzny serwis QR co w StorefrontLayout.jsx (kod QR sklepu) —
// bez żadnej biblioteki PHP do generowania obrazków.
export const qrImageUrl = (url, size) => `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=0&data=${encodeURIComponent(url)}`

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
