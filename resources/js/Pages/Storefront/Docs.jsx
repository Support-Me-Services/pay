import StorefrontLayout from '@/Layouts/StorefrontLayout'
import { usePage } from '@inertiajs/react'

/**
 * Dokumentacja techniczna (/docs) — obszerny, statyczny dokument.
 * Treść trzymana verbatim w resources/inertia-content/docs.html; figury (screeny)
 * rozstrzygane serwerowo w trasie. Renderowana przez SSR (dangerouslySetInnerHTML)
 * — zero interaktywności, więc bez konwersji na drzewo elementów. Meta/OG i docs.css
 * dostarcza StorefrontLayout (propsy pageTitle/pageDescription/css z trasy).
 */
export default function Docs() {
    const { html } = usePage().props

    return <div dangerouslySetInnerHTML={{ __html: html }} />
}

Docs.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
