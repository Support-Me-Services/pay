import { useForm, usePage } from '@inertiajs/react'
import StorefrontLayout from '@/Layouts/StorefrontLayout'

const CSS = `
.sp-thanks{ text-align:center; padding:48px 28px; }
.sp-thanks__icon{ width:72px; height:72px; margin:0 auto 18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:38px; color:#fff; background:linear-gradient(117deg,#4E7FA7,#1473C0); box-shadow:0 12px 30px rgba(20,115,192,.3); }
.sp-thanks h2{ font-family:'Libre Baskerville',serif; color:#24324A; margin:0 0 10px; font-size:28px; }
.sp-thanks p{ color:#3a3d47; font-size:18px; margin:0 0 24px; }
.sp-btn--inline{ display:inline-flex; width:auto; padding:14px 30px; }
`

/** Formularz aplikacji rekrutacyjnej (spontanicznej lub na ofertę). */
export default function Aplikuj({ position, storeUrl, careersUrl }) {
    const flash = usePage().props.flash || {}
    const form = useForm({ name: '', email: '', phone: '', message: '', cv: null, rodo: false, future_consent: false, website: '' })
    const err = form.errors

    const submit = (e) => {
        e.preventDefault()
        form.post(storeUrl, { forceFormData: true })
    }

    return (
        <>
            <style>{CSS}</style>
            <section className="sp-subhero">
                <div className="sp-subhero__inner">
                    <a href={careersUrl} className="sp-back">← wróć do ofert</a>
                    <h1>{position ? position.title : 'Pracuj z nami!'}</h1>
                    <p className="sp-lede">
                        {position
                            ? 'Wyślij swoje zgłoszenie na to stanowisko. Załącz CV — odezwiemy się.'
                            : 'Nie znalazłeś oferty dla siebie? Zostaw nam swoje zgłoszenie, a skontaktujemy się, gdy pojawi się odpowiednie stanowisko.'}
                    </p>
                </div>
            </section>

            <div className="sp-wrap">
                <div className="sp-container sp-formpage">
                    <div className="sp-formpage__wrap">
                        {flash.success ? (
                            <div className="sp-card sp-thanks">
                                <div className="sp-thanks__icon" aria-hidden="true">✓</div>
                                <h2>Zgłoszenie wysłane!</h2>
                                <p>{flash.success}</p>
                                <a href={careersUrl} className="sp-btn sp-btn--inline">Wróć do ofert</a>
                            </div>
                        ) : (
                            <>
                                {Object.keys(err).length > 0 && <div className="sp-alert sp-alert--err">Popraw zaznaczone pola formularza.</div>}

                                <div className="sp-card">
                                    <form onSubmit={submit} encType="multipart/form-data">
                                        {/* Honeypot antyspamowy — ukryte pole; człowiek go nie wypełni. */}
                                        <div aria-hidden="true" style={{ position: 'absolute', left: -9999, width: 1, height: 1, overflow: 'hidden' }}>
                                            <label htmlFor="website">Nie wypełniaj tego pola</label>
                                            <input type="text" id="website" tabIndex={-1} autoComplete="off"
                                                value={form.data.website} onChange={(e) => form.setData('website', e.target.value)} />
                                        </div>

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

                                        <div className="sp-field">
                                            <label htmlFor="phone">Numer telefonu</label>
                                            <input type="text" id="phone" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                                            {err.phone && <div className="sp-form-error">{err.phone}</div>}
                                        </div>

                                        <div className="sp-field">
                                            <label htmlFor="message">List motywacyjny</label>
                                            <textarea id="message" value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} />
                                            {err.message && <div className="sp-form-error">{err.message}</div>}
                                        </div>

                                        <div className="sp-field">
                                            <label htmlFor="cv">Dodaj CV *</label>
                                            <input type="file" id="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                onChange={(e) => form.setData('cv', e.target.files[0] || null)} required />
                                            <div className="sp-hint">Format PDF, DOC lub DOCX. Maksymalny rozmiar: 5 MB.</div>
                                            {err.cv && <div className="sp-form-error">{err.cv}</div>}
                                        </div>

                                        <div className="sp-consent">
                                            <input type="checkbox" id="rodo" checked={form.data.rodo} onChange={(e) => form.setData('rodo', e.target.checked)} required />
                                            <label htmlFor="rodo">Zapoznałem/am się z <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Polityką Prywatności</a> i wyrażam zgodę na przetwarzanie moich danych osobowych przez Fundację Support Me Haven &amp; Heaven w celu przeprowadzenia obecnego procesu rekrutacyjnego, w tym na udostępnienie moich danych potencjalnemu pracodawcy (klientowi Fundacji Support Me Haven &amp; Heaven) będącemu odbiorcą tego procesu. *</label>
                                        </div>
                                        {err.rodo && <div className="sp-form-error" style={{ marginTop: -6 }}>{err.rodo}</div>}

                                        <div className="sp-consent">
                                            <input type="checkbox" id="future_consent" checked={form.data.future_consent} onChange={(e) => form.setData('future_consent', e.target.checked)} />
                                            <label htmlFor="future_consent">Wyrażam zgodę na przetwarzanie moich danych osobowych przez Fundację Support Me Haven &amp; Heaven na potrzeby przyszłych procesów rekrutacyjnych, w tym na udostępnianie moich danych innym potencjalnym pracodawcom (klientom Fundacji Support Me Haven &amp; Heaven) w celu przedstawiania mi nowych ofert zatrudnienia.</label>
                                        </div>
                                        {err.future_consent && <div className="sp-form-error" style={{ marginTop: -6 }}>{err.future_consent}</div>}

                                        <div className="sp-field" style={{ marginTop: 0, marginBottom: 10 }}>
                                            <div className="sp-hint" style={{ marginTop: 0, lineHeight: 1.35 }}>Administratorem Twoich danych jest FUNDACJA SUPPORT ME HAVEN &amp; HEAVEN z siedzibą ul. dr Izabeli Wolfram 11, 05-800 Pruszków, KRS: 0001255725, NIP: 5342715041, REGON: 545334957. Dane przetwarzamy w celu rekrutacji i realizacji procesów rekrutacyjnych. Odbiorcami Twoich danych mogą być nasi klienci – firmy z sektora IT, których tożsamość ujawnimy Ci przed przekazaniem dokumentów. Dane przechowujemy do momentu zakończenia procesu lub wycofania zgody. Pełną informację o Twoich prawach i sposobie przetwarzania danych znajdziesz w naszej <a href="/polityka-prywatnosci.pdf" target="_blank" rel="noopener noreferrer">Polityce Prywatności</a>.</div>
                                        </div>

                                        <button type="submit" className="sp-btn sp-btn--block" disabled={form.processing}>Wyślij zgłoszenie</button>
                                    </form>
                                </div>
                            </>
                        )}

                        <p className="sp-formpage__foot">
                            <a href={careersUrl} className="sp-link">← Wróć do ofert</a>
                        </p>
                    </div>
                </div>
            </div>
        </>
    )
}

Aplikuj.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
