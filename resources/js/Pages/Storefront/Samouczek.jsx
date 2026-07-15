import StorefrontLayout from '@/Layouts/StorefrontLayout'
import { usePage } from '@inertiajs/react'

/* Page-scoped: siatka kart przeglądu modułów (sekcja 15). Tylko ta strona.
 * Przeniesione 1:1 z drugiego @push('head') widoku samouczek.blade.php. */
const PAGE_CSS = `
.dc-modgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;margin:1.4rem 0 .4rem}
.dc-modcard{border:1px solid rgba(0,0,0,.10);border-radius:12px;padding:14px 16px;background:rgba(255,255,255,.55);box-shadow:0 1px 2px rgba(0,0,0,.04)}
.dc-modcard h4{margin:.5rem 0 .35rem;font-size:1rem;line-height:1.25}
.dc-modcard p{margin:0;font-size:.9rem;opacity:.85;line-height:1.45}
.dc-modcard code{font-size:.82em}
.dc-modcard__tag{display:inline-block;font-size:.7rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:.18em .6em;border-radius:999px;line-height:1.4}
.dc-modcard__tag--pub{background:#e8f3ec;color:#1f7a45}
.dc-modcard__tag--adm{background:#eceaf6;color:#4a3f9e}
@media (prefers-color-scheme:dark){
  .dc-modcard{border-color:rgba(255,255,255,.12);background:rgba(255,255,255,.04)}
  .dc-modcard__tag--pub{background:rgba(40,160,90,.18);color:#7fd6a3}
  .dc-modcard__tag--adm{background:rgba(120,100,230,.18);color:#b6acf2}
}
`

/**
 * Samouczek (/samouczek) — obszerny, statyczny przewodnik.
 * Treść verbatim w resources/inertia-content/samouczek.html; figury rozstrzygane
 * serwerowo. Renderowana przez SSR (dangerouslySetInnerHTML). CSS współdzielony
 * docs.css dostarcza StorefrontLayout (prop css); poniżej page-scoped siatka kart.
 */
export default function Samouczek() {
    const { html } = usePage().props

    return (
        <>
            <style>{PAGE_CSS}</style>
            <div dangerouslySetInnerHTML={{ __html: html }} />
        </>
    )
}

Samouczek.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
