import { useEffect, useRef, useState } from 'react'
import { router, useForm, usePage } from '@inertiajs/react'
import PanelLayout from '@/Layouts/PanelLayout'
import RichEditor from '@/Components/RichEditor'

const imgTransform = (x, y, scale) => `translate(-50%,-50%) translate(${x}%, ${y}%) scale(${scale / 100})`

/**
 * Panel „Wspieramy" — edytor węzłów (nagłówek + grafika + tekst).
 * Kolejność: natywny drag&drop (zapis AJAX). Dodawanie/edycja przez modal-kreator
 * z Quill (upload zdjęć), kadrowaniem grafiki w kole i podglądem na żywo.
 */
export default function Index({ nodes, urls }) {
    const csrf = usePage().props.csrf_token

    // Kolejność (lokalny stan) — resync po zmianie propsów.
    const [items, setItems] = useState(nodes)
    useEffect(() => { setItems(nodes) }, [nodes])

    const [modal, setModal] = useState(null) // null | {node|null}
    const dragId = useRef(null)

    // ── Drag & drop kolejności ──────────────────────────────────────────
    const onDragStart = (id) => { dragId.current = id }
    const onDragOver = (e, overId) => {
        e.preventDefault()
        const from = dragId.current
        if (from == null || from === overId) return
        setItems((prev) => {
            const arr = [...prev]
            const fromIdx = arr.findIndex((n) => n.id === from)
            const overIdx = arr.findIndex((n) => n.id === overId)
            if (fromIdx < 0 || overIdx < 0) return prev
            const [moved] = arr.splice(fromIdx, 1)
            arr.splice(overIdx, 0, moved)
            return arr
        })
    }
    const onDrop = () => {
        dragId.current = null
        const order = items.map((n) => n.id)
        fetch(urls.reorder, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ order }),
        })
    }

    const destroy = (n) => {
        if (!confirm(`Usunąć węzeł „${n.heading}”? Tej operacji nie można cofnąć.`)) return
        router.delete(urls.destroy.replace('__ID__', n.id), { preserveScroll: true })
    }

    return (
        <>
            <div className="panel-title">
                <h1>Wspieramy — edytor podstrony</h1>
                <a href={urls.public} target="_blank" rel="noreferrer" className="btn btn-secondary">Podgląd strony ↗</a>
            </div>

            <div className="bn-list">
                {items.map((n) => (
                    <div key={n.id} className="bn-item" draggable
                        onDragStart={() => onDragStart(n.id)}
                        onDragOver={(e) => onDragOver(e, n.id)}
                        onDrop={onDrop} onDragEnd={onDrop}>
                        <span className="bn-item__grip" title="Przeciągnij, aby zmienić kolejność">⠿</span>
                        <div className="bn-item__preview">
                            <div className={`bn-prev bn-prev--${n.image ? (n.image_right ? 'right' : 'left') : 'noimg'}`}>
                                {n.image && (
                                    <div className="bn-prev__media">
                                        <img src={n.image} alt="" style={{ transform: imgTransform(n.image_x, n.image_y, n.image_scale) }} />
                                    </div>
                                )}
                                <div className="bn-prev__body" style={{ textAlign: n.text_align }}>
                                    <div className="bn-prev__heading">{n.heading}</div>
                                    <div className="bn-prev__text" dangerouslySetInnerHTML={{ __html: n.body_html }} />
                                </div>
                            </div>
                        </div>
                        <div className="bn-item__actions">
                            <button type="button" className="btn btn-secondary" onClick={() => setModal({ node: n })}>Edytuj</button>
                            <button type="button" className="btn btn-danger" onClick={() => destroy(n)}>Usuń</button>
                        </div>
                    </div>
                ))}
            </div>

            <button type="button" className="bn-add" onClick={() => setModal({ node: null })}>
                <span className="bn-add__plus">+</span> Dodaj
            </button>

            {modal && (
                <NodeModal node={modal.node} urls={urls} csrf={csrf} onClose={() => setModal(null)} />
            )}

            <style>{CSS}</style>
        </>
    )
}

/** Modal-kreator węzła (dodawanie/edycja) z podglądem na żywo. */
function NodeModal({ node, urls, csrf, onClose }) {
    const editing = !!node
    const form = useForm({
        heading: node?.heading ?? '',
        image_side: node?.image_side ?? 'left',
        text_align: node?.text_align ?? 'left',
        image_scale: node?.image_scale ?? 100,
        image_x: node?.image_x ?? 0,
        image_y: node?.image_y ?? 0,
        body_html: node?.body_html ?? '',
        image_file: null,
        remove_image: false,
    })
    const d = form.data
    // URL podglądu grafiki: nowy plik > istniejąca (jeśli nie oznaczona do usunięcia) > brak.
    const [filePreview, setFilePreview] = useState(null)
    const previewUrl = filePreview || (!d.remove_image ? node?.image : null) || null
    const hasImage = !!previewUrl

    const onFile = (e) => {
        const f = e.target.files[0] || null
        form.setData('image_file', f)
        if (f) {
            form.setData('remove_image', false)
            const rd = new FileReader()
            rd.onload = (ev) => setFilePreview(ev.target.result)
            rd.readAsDataURL(f)
        } else {
            setFilePreview(null)
        }
    }

    const submit = (e) => {
        e.preventDefault()
        const url = editing ? urls.update.replace('__ID__', node.id) : urls.store
        form.post(url, { forceFormData: true, onSuccess: onClose })
    }

    const variant = hasImage ? (d.image_side === 'right' ? 'right' : 'left') : 'noimg'

    return (
        <div className="bmodal" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose() }}>
            <div className="bmodal__dialog">
                <div className="bmodal__head">{editing ? `${node.heading || 'Pozycja'} - edycja` : 'Nowa pozycja'}</div>
                <form onSubmit={submit} encType="multipart/form-data">
                    <div className="bmodal__grid">
                        {/* Podgląd na żywo */}
                        <div className="bmodal__preview">
                            <div className="pv-label">Podgląd</div>
                            <div className={`pv-node pv-node--${variant}`}>
                                {hasImage && (
                                    <div className="pv-node__media">
                                        <img src={previewUrl} alt="" style={{ transform: imgTransform(d.image_x, d.image_y, d.image_scale) }} />
                                    </div>
                                )}
                                <div className="pv-node__body" style={{ textAlign: d.text_align }}>
                                    <h3 className="pv-node__heading">{d.heading || 'Nagłówek'}</h3>
                                    <div className="pv-node__text" dangerouslySetInnerHTML={{ __html: d.body_html }} />
                                </div>
                            </div>
                        </div>

                        {/* Formularz */}
                        <div className="bmodal__form">
                            <div className="form-group">
                                <label htmlFor="bn-heading">Nagłówek *</label>
                                <input id="bn-heading" type="text" required maxLength={255} placeholder="np. Szkoły"
                                    value={d.heading} onChange={(e) => form.setData('heading', e.target.value)} />
                                {form.errors.heading && <div className="form-error">{form.errors.heading}</div>}
                            </div>

                            <div className="form-group">
                                <label>Grafika</label>
                                {node?.image && (
                                    <div className="thumb-row">
                                        <img src={node.image} alt="" />
                                        <label style={{ fontWeight: 400 }}>
                                            <input type="checkbox" style={{ width: 'auto' }} checked={d.remove_image}
                                                onChange={(e) => { form.setData('remove_image', e.target.checked); if (e.target.checked) { form.setData('image_file', null); setFilePreview(null) } }} /> usuń grafikę
                                        </label>
                                    </div>
                                )}
                                <input type="file" accept="image/*" onChange={onFile} />
                            </div>

                            <div className="form-group" style={{ opacity: hasImage ? 1 : 0.5 }}>
                                <label>Dopasowanie grafiki w kole</label>
                                <div className="crop-row"><span>Skala</span>
                                    <input type="range" min={20} max={400} step={1} disabled={!hasImage}
                                        value={d.image_scale} onChange={(e) => form.setData('image_scale', +e.target.value)} />
                                    <output>{d.image_scale}%</output></div>
                                <div className="crop-row"><span>Poziomo</span>
                                    <input type="range" min={-100} max={100} step={1} disabled={!hasImage}
                                        value={d.image_x} onChange={(e) => form.setData('image_x', +e.target.value)} />
                                    <output>{d.image_x}</output></div>
                                <div className="crop-row"><span>Pionowo</span>
                                    <input type="range" min={-100} max={100} step={1} disabled={!hasImage}
                                        value={d.image_y} onChange={(e) => form.setData('image_y', +e.target.value)} />
                                    <output>{d.image_y}</output></div>
                            </div>

                            <div className="form-group">
                                <label>Położenie grafiki (desktop)</label>
                                <div className="side-opts">
                                    {['left', 'right'].map((v) => (
                                        <label key={v} style={{ fontWeight: 400 }}>
                                            <input type="radio" name="image_side" value={v} checked={d.image_side === v}
                                                onChange={() => form.setData('image_side', v)} /> {v === 'left' ? 'po lewej' : 'po prawej'}
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="form-group">
                                <label>Wyrównanie tekstu</label>
                                <div className="side-opts">
                                    {[['left', 'do lewej'], ['center', 'środek'], ['right', 'do prawej']].map(([v, lbl]) => (
                                        <label key={v} style={{ fontWeight: 400 }}>
                                            <input type="radio" name="text_align" value={v} checked={d.text_align === v}
                                                onChange={() => form.setData('text_align', v)} /> {lbl}
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="form-group">
                                <label>Tekst (pod nagłówkiem)</label>
                                <RichEditor value={node?.body_html ?? ''} onChange={(html) => form.setData('body_html', html)}
                                    imageUploadUrl={urls.editorUpload} csrfToken={csrf} />
                                {form.errors.body_html && <div className="form-error">{form.errors.body_html}</div>}
                            </div>
                        </div>
                    </div>

                    <div className="bmodal__foot">
                        <button type="button" className="btn btn-secondary" onClick={onClose}>Odrzuć</button>
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>Zatwierdź</button>
                    </div>
                </form>
            </div>
        </div>
    )
}

const CSS = `
.bn-list{ display:flex; flex-direction:column; gap:10px; margin:0 0 16px; }
.bn-item{ display:flex; align-items:center; gap:14px; background:#fff; border:1px solid #e6eaef; border-radius:12px; padding:12px 14px; }
.bn-item__grip{ cursor:grab; color:#9aa4b2; font-size:20px; line-height:1; user-select:none; padding:0 4px; }
.bn-item__preview{ flex:1; min-width:0; }
.bn-item__actions{ display:flex; gap:8px; }
.bn-prev{ display:grid; gap:16px; align-items:center; }
.bn-prev--left{ grid-template-columns:auto 1fr; }
.bn-prev--right{ grid-template-columns:1fr auto; }
.bn-prev--right .bn-prev__media{ order:2; }
.bn-prev--noimg{ grid-template-columns:1fr; }
.bn-prev__media{ width:84px; height:84px; border-radius:50%; overflow:hidden; background:#eef2f7; position:relative; }
.bn-prev__media img{ position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:contain; transform-origin:center; }
.bn-prev__heading{ font-size:16px; font-weight:700; margin:0 0 4px; }
.bn-prev__text{ font-size:13px; color:#5a6674; line-height:1.45; max-height:4.4em; overflow:hidden; }
.bn-prev__text p{ margin:0 0 4px; }
.bn-prev__text img{ max-width:100%; height:auto; }
.bn-add{ width:100%; display:flex; align-items:center; justify-content:center; gap:10px; padding:22px; border:2px dashed #c7d0da; border-radius:14px; background:#fbfdff; color:#2563eb; font-weight:700; font-size:16px; cursor:pointer; }
.bn-add:hover{ background:#f2f7ff; border-color:#2563eb; }
.bn-add__plus{ font-size:26px; line-height:1; }
.bmodal{ position:fixed; inset:0; z-index:2000; display:flex; align-items:flex-start; justify-content:center; padding:28px 16px; background:rgba(15,23,42,.55); overflow:auto; }
.bmodal__dialog{ background:#fff; border-radius:16px; width:min(1000px,100%); box-shadow:0 24px 64px rgba(0,0,0,.3); }
.bmodal__head{ padding:16px 22px; border-bottom:1px solid #eef1f4; font-weight:800; font-size:18px; }
.bmodal__grid{ display:grid; grid-template-columns:1fr 1fr; gap:0; }
.bmodal__preview{ padding:22px; border-right:1px solid #eef1f4; background:#fafbfc; }
.bmodal__form{ padding:22px; }
.bmodal__foot{ display:flex; justify-content:flex-end; gap:10px; padding:16px 22px; border-top:1px solid #eef1f4; }
@media (max-width:820px){ .bmodal__grid{ grid-template-columns:1fr; } .bmodal__preview{ border-right:0; border-bottom:1px solid #eef1f4; } }
.pv-label{ font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#9aa4b2; margin-bottom:10px; }
.pv-node{ display:grid; gap:18px; align-items:center; }
.pv-node--left{ grid-template-columns:auto 1fr; }
.pv-node--right{ grid-template-columns:1fr auto; }
.pv-node--right .pv-node__media{ order:2; }
.pv-node--noimg{ grid-template-columns:1fr; }
.pv-node__media{ width:150px; height:150px; border-radius:50%; overflow:hidden; background:#eef2f7; position:relative; }
.pv-node__media img{ position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:contain; transform-origin:center; }
.pv-node__heading{ font-size:20px; margin:0 0 8px; }
.pv-node__text{ font-size:14px; line-height:1.55; color:#3f4a58; }
.pv-node__text img{ max-width:100%; border-radius:8px; }
.side-opts{ display:flex; gap:16px; flex-wrap:wrap; }
.thumb-row{ display:flex; align-items:center; gap:12px; margin-bottom:8px; }
.thumb-row img{ width:56px; height:56px; object-fit:contain; border-radius:50%; background:#eef2f7; }
.crop-row{ display:flex; align-items:center; gap:10px; margin-bottom:6px; }
.crop-row > span{ width:82px; color:#4a5568; font-size:13px; }
.crop-row input[type=range]{ flex:1; }
.crop-row output{ width:54px; text-align:right; font-variant-numeric:tabular-nums; font-size:13px; color:#334155; }
`

Index.layout = (page) => <PanelLayout title="Wspieramy">{page}</PanelLayout>
