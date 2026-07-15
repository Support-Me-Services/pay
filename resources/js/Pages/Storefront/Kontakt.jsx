import { useForm, usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

/** Formularz kontaktowy (temat może przyjść z ?stanowisko= przez prop subject). */
export default function Kontakt({ subject, storeUrl }) {
    const flash = usePage().props.flash || {}
    const form = useForm({ name: '', email: '', phone: '', subject: subject || '', message: '' })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        form.post(storeUrl, { onSuccess: () => form.reset() })
    }

    return (
        <>
            <section className="sp-subhero">
                <div className="sp-subhero__inner">
                    <p className="sp-eyebrow">Jesteśmy do dyspozycji</p>
                    <h1>Kontakt</h1>
                    <p className="sp-lede">Masz pytanie lub chcesz aplikować na stanowisko? Napisz do nas — odpowiemy najszybciej, jak to możliwe.</p>
                </div>
            </section>

            <div className="sp-wrap">
                <div className="sp-container sp-formpage">
                    <div className="sp-formpage__wrap">
                        {flash.success && <div className="sp-alert sp-alert--ok">{flash.success}</div>}
                        {Object.keys(err).length > 0 && <div className="sp-alert sp-alert--err">Popraw zaznaczone pola formularza.</div>}

                        <div className="sp-card">
                            <form onSubmit={submit}>
                                <div className="sp-field-row">
                                    <div className="sp-field">
                                        <label htmlFor="name">Imię i nazwisko *</label>
                                        <input type="text" id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                                        {err.name && <div className="sp-form-error">{err.name}</div>}
                                    </div>
                                    <div className="sp-field">
                                        <label htmlFor="email">Adres e-mail *</label>
                                        <input type="email" id="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
                                        {err.email && <div className="sp-form-error">{err.email}</div>}
                                    </div>
                                </div>

                                <div className="sp-field-row">
                                    <div className="sp-field">
                                        <label htmlFor="phone">Numer telefonu</label>
                                        <input type="text" id="phone" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                                        {err.phone && <div className="sp-form-error">{err.phone}</div>}
                                    </div>
                                    <div className="sp-field">
                                        <label htmlFor="subject">Temat</label>
                                        <input type="text" id="subject" value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} />
                                        {err.subject && <div className="sp-form-error">{err.subject}</div>}
                                    </div>
                                </div>

                                <div className="sp-field">
                                    <label htmlFor="message">Wiadomość *</label>
                                    <textarea id="message" value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} required />
                                    {err.message && <div className="sp-form-error">{err.message}</div>}
                                </div>

                                <button type="submit" className="sp-btn sp-btn--block" disabled={form.processing}>Wyślij wiadomość</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}

Kontakt.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
