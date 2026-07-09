import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRoot, hydrateRoot } from 'react-dom/client'

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        // Jeśli serwer (SSR) już wyrenderował HTML -> hydratacja; inaczej render po stronie klienta.
        if (el.hasChildNodes()) {
            hydrateRoot(el, <App {...props} />)
        } else {
            createRoot(el).render(<App {...props} />)
        }
    },
})
