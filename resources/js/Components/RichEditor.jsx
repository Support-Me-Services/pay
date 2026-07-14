import { useEffect, useRef } from 'react'
import 'quill/dist/quill.snow.css'

/**
 * Edytor rich‑text (Quill 2) dla Inertia/React.
 * Quill dotyka DOM, więc ładowany dynamicznie w useEffect (nie na SSR).
 */
export default function RichEditor({ value = '', onChange }) {
    const elRef = useRef(null)
    const quillRef = useRef(null)
    const onChangeRef = useRef(onChange)
    onChangeRef.current = onChange

    useEffect(() => {
        let alive = true
        import('quill').then(({ default: Quill }) => {
            if (!alive || quillRef.current || !elRef.current) return
            const quill = new Quill(elRef.current, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link'],
                        ['clean'],
                    ],
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
