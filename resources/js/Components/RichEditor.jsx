import { useEffect, useRef } from 'react'
import 'quill/dist/quill.snow.css'

/**
 * Edytor rich‑text (Quill 2) dla Inertia/React.
 * Quill dotyka DOM, więc ładowany dynamicznie w useEffect (nie na SSR).
 *
 * Opcjonalnie (props `imageUploadUrl` + `csrfToken`) dodaje przycisk wstawiania
 * obrazka: plik wgrywa się na serwer, a zwrócony URL jest osadzany w treści.
 */
export default function RichEditor({ value = '', onChange, imageUploadUrl = null, csrfToken = null }) {
    const elRef = useRef(null)
    const quillRef = useRef(null)
    const onChangeRef = useRef(onChange)
    onChangeRef.current = onChange
    const uploadRef = useRef({ imageUploadUrl, csrfToken })
    uploadRef.current = { imageUploadUrl, csrfToken }

    useEffect(() => {
        let alive = true
        import('quill').then(({ default: Quill }) => {
            if (!alive || quillRef.current || !elRef.current) return

            const toolbar = [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                imageUploadUrl ? ['link', 'image'] : ['link'],
                ['clean'],
            ]

            // Handler wstawiania obrazka — wgrywa plik i osadza zwrócony URL.
            const imageHandler = () => {
                const input = document.createElement('input')
                input.type = 'file'
                input.accept = 'image/*'
                input.onchange = () => {
                    const file = input.files[0]
                    if (!file) return
                    const { imageUploadUrl: url, csrfToken: csrf } = uploadRef.current
                    const fd = new FormData()
                    fd.append('image', file)
                    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, body: fd })
                        .then((r) => r.json())
                        .then((d) => {
                            const quill = quillRef.current
                            const range = quill.getSelection(true)
                            quill.insertEmbed(range.index, 'image', d.url)
                        })
                        .catch(() => alert('Nie udało się wgrać zdjęcia.'))
                }
                input.click()
            }

            const quill = new Quill(elRef.current, {
                theme: 'snow',
                modules: {
                    toolbar: imageUploadUrl
                        ? { container: toolbar, handlers: { image: imageHandler } }
                        : toolbar,
                },
            })
            quillRef.current = quill
            if (value) quill.clipboard.dangerouslyPasteHTML(value)
            quill.on('text-change', () => onChangeRef.current(quill.root.innerHTML))
        })
        return () => { alive = false }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])

    return (
        <div style={{ background: '#fff' }}>
            <div ref={elRef} style={{ minHeight: 220 }} />
        </div>
    )
}
