import { usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

/** Podziękowanie po kontakcie/wsparciu (statyczna). */
export default function Dziekujemy() {
    const r = usePage().props.routes || {}
    return (
        <>
            <section className="sp-subhero">
                <div className="sp-subhero__inner">
                    <p className="sp-eyebrow">Dziękujemy</p>
                    <h1>Dziękujemy za Twoje wsparcie!</h1>
                    <p className="sp-lede">Twoja wiadomość do nas dotarła. To dzięki takim osobom jak Ty możemy
                        wspierać parafie, fundacje i&nbsp;lokalne społeczności.</p>
                </div>
            </section>

            <div className="sp-wrap">
                <div className="sp-container sp-doc">
                    <article className="sp-legal__body">
                        <section className="sp-legal__section">
                            <h2>Co dalej?</h2>
                            <ol>
                                <li>Nasz zespół zapozna się z&nbsp;Twoim zgłoszeniem.</li>
                                <li>Skontaktujemy się z&nbsp;Tobą — zwykle w&nbsp;ciągu jednego dnia roboczego.</li>
                                <li>Wspólnie ustalimy kolejne kroki współpracy lub wsparcia.</li>
                            </ol>
                        </section>
                        <section className="sp-legal__section">
                            <h2>Masz pytania?</h2>
                            <p>Napisz do nas na adres
                                <a href="mailto:office@please-support-me.com">office@please-support-me.com</a>
                                {' '}— chętnie odpowiemy i&nbsp;pomożemy.</p>
                            <p style={{ marginTop: '1.5rem' }}>
                                <a href={r.home}>← Wróć na stronę główną</a>
                            </p>
                        </section>
                    </article>
                </div>
            </div>
        </>
    )
}

Dziekujemy.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
