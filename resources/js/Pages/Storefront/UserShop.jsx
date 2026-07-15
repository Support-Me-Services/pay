import { router, usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.shop{ max-width:1080px; margin:0 auto; padding:32px 20px 64px; }
.shop__head{ text-align:center; margin-bottom:24px; }
.shop__head h1{ font-size:32px; margin:0 0 6px; }
.shop__head p{ color:#4a5568; margin:0; }
.shop__flash{ max-width:640px; margin:0 auto 20px; padding:12px 16px; border-radius:10px; font-size:15px; }
.shop__flash.is-ok{ background:#e7f7ee; color:#1a7f45; border:1px solid #b7e4c7; }
.shop__flash.is-err{ background:#fdeaea; color:#b02525; border:1px solid #f5c2c2; }
.shop__grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; }
.pcard{ display:flex; flex-direction:column; background:#fff; border:1px solid #eef1f4; border-radius:16px; padding:16px; box-shadow:0 4px 14px rgba(20,40,80,.05); }
.pcard__img{ height:150px; display:flex; align-items:center; justify-content:center; background:#f6f9fb; border-radius:12px; margin-bottom:12px; overflow:hidden; }
.pcard__img img{ max-width:100%; max-height:100%; object-fit:contain; }
.pcard__img.is-svg img{ width:96px; height:96px; }
.pcard__name{ font-size:18px; margin:0 0 4px; }
.pcard__desc{ font-size:13.5px; line-height:1.4; color:#5a6674; margin:0 0 12px; flex:1; }
.pcard__price{ font-size:24px; font-weight:800; margin:0 0 12px; }
.pcard__price small{ font-size:15px; font-weight:700; margin-left:3px; }
.pcard__btn{ width:100%; padding:11px 14px; border:0; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; font-size:15px; cursor:pointer; }
.pcard__btn:hover{ background:#1d4ed8; }
.shop__ship{ text-align:center; color:#6b7280; font-size:13.5px; margin-top:28px; }
`

/** Sklep per-konto /people/{handle} — siatka z dodawaniem do koszyka. */
export default function UserShop({ items, ownerName }) {
    const flash = usePage().props.flash || {}

    const addToCart = (url) => router.post(url, {}, { preserveScroll: true })

    return (
        <main className="shop">
            <style>{CSS}</style>
            <div className="shop__head">
                <h1>Sklep — {ownerName}</h1>
                <p>Gadżety i tagi NFC — dodaj do koszyka i zapłać online.</p>
            </div>

            {flash.success && <div className="shop__flash is-ok">{flash.success}</div>}
            {flash.error && <div className="shop__flash is-err">{flash.error}</div>}

            <div className="shop__grid">
                {items.map((item) => (
                    <div className="pcard" key={item.id}>
                        <div className={`pcard__img${item.is_svg ? ' is-svg' : ''}`}>
                            <img src={item.image} alt={item.name} />
                        </div>
                        <h2 className="pcard__name">{item.name}</h2>
                        {item.description && <p className="pcard__desc">{item.description}</p>}
                        <div className="pcard__price">{item.price}<small>zł</small></div>
                        <button className="pcard__btn" type="button" onClick={() => addToCart(item.add_url)}>Dodaj do koszyka</button>
                    </div>
                ))}
            </div>

            <p className="shop__ship">Wysyłka kurierem: 1–3 dni robocze · zwrot do 14 dni</p>
        </main>
    )
}

UserShop.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
