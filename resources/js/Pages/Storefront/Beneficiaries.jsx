import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.ben{ max-width:1080px; margin:0 auto; padding:36px 20px 72px; }
.ben__head{ text-align:center; margin-bottom:36px; }
.ben__head h1{ font-size:34px; margin:0; }
.ben-node{ display:grid; gap:40px; align-items:center; padding:32px 0; border-bottom:1px solid #eef1f4; }
.ben-node:last-child{ border-bottom:0; }
.ben-node--left{ grid-template-columns:auto 1fr; }
.ben-node--right{ grid-template-columns:1fr auto; }
.ben-node--right .ben-node__media{ order:2; }
.ben-node--noimg{ grid-template-columns:1fr; }
.ben-node__media{ justify-self:center; width:280px; height:280px; border-radius:50%; overflow:hidden; background:#eef2f7; position:relative; }
.ben-node__media img{ position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:contain; display:block; transform-origin:center; }
.ben-node__heading{ font-size:26px; margin:0 0 12px; }
.ben-node__text{ font-size:16px; line-height:1.6; color:#3f4a58; }
.ben-node__text p{ margin:0 0 12px; }
.ben-node__text img{ max-width:100%; height:auto; border-radius:10px; }
.ben-node__text a{ color:#2563eb; }
.ben__empty{ text-align:center; color:#6b7280; padding:40px 0; }
@media (max-width:720px){
  .ben-node--left, .ben-node--right, .ben-node--noimg{ grid-template-columns:1fr; gap:20px; }
  .ben-node--right .ben-node__media{ order:0; }
  .ben-node__media{ width:220px; height:220px; }
  .ben-node__body{ text-align:center !important; }
}
`

const imgTransform = (x, y, scale) => `translate(-50%,-50%) translate(${x}%, ${y}%) scale(${scale / 100})`

/** Publiczna podstrona „Wspieramy" (/beneficiaries) — węzły z panelu. */
export default function Beneficiaries({ nodes }) {
    return (
        <main className="ben">
            <style>{CSS}</style>
            <div className="ben__head"><h1>Wspieramy</h1></div>

            {nodes.length === 0 && <p className="ben__empty">Wkrótce więcej informacji o tym, kogo wspieramy.</p>}

            {nodes.map((n) => {
                const variant = n.image ? (n.image_right ? 'right' : 'left') : 'noimg'
                return (
                    <section key={n.id} className={`ben-node ben-node--${variant}`}>
                        {n.image && (
                            <div className="ben-node__media">
                                <img src={n.image} alt={n.heading} style={{ transform: imgTransform(n.image_x, n.image_y, n.image_scale) }} />
                            </div>
                        )}
                        <div className="ben-node__body" style={{ textAlign: n.text_align }}>
                            <h2 className="ben-node__heading">{n.heading}</h2>
                            <div className="ben-node__text" dangerouslySetInnerHTML={{ __html: n.body_html }} />
                        </div>
                    </section>
                )
            })}
        </main>
    )
}

Beneficiaries.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
