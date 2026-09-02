import { useState } from 'react'

/** Mały przycisk "Kopiuj" — kopiuje wartość (np. adres URL) do schowka. */
export default function CopyButton({ value, label = 'Kopiuj' }) {
    const [copied, setCopied] = useState(false)

    const copy = async (e) => {
        e.preventDefault()
        try {
            await navigator.clipboard.writeText(value)
        } catch (err) {
            // Fallback — brak Clipboard API (starsze przeglądarki / kontekst nie-secure).
            const el = document.createElement('textarea')
            el.value = value
            el.style.position = 'fixed'
            el.style.opacity = '0'
            document.body.appendChild(el)
            el.select()
            try { document.execCommand('copy') } catch (e2) { /* noop */ }
            document.body.removeChild(el)
        }
        setCopied(true)
        setTimeout(() => setCopied(false), 1500)
    }

    return (
        <button type="button" onClick={copy} className="btn btn-secondary btn-sm" style={{ padding: '2px 10px', fontSize: 12, lineHeight: 1.6 }}>
            {copied ? 'Skopiowano!' : label}
        </button>
    )
}
