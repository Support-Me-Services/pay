import StorefrontLayout from '@/Layouts/StorefrontLayout'
import BeneficiaryNodes from '@/Components/BeneficiaryNodes'

const CSS = `
.ben{ max-width:1080px; margin:0 auto; padding:36px 20px 72px; }
.ben__head{ text-align:center; margin-bottom:36px; }
.ben__head h1{ font-size:34px; margin:0; }
`

/** Publiczna podstrona „Wspieramy" (/beneficiaries) — węzły z panelu. Zostaje jako
 * osobna podstrona (obok inline'owanej sekcji na /main) na wypadek odwrócenia zmiany. */
export default function Beneficiaries({ nodes }) {
    return (
        <main className="ben">
            <style>{CSS}</style>
            <div className="ben__head"><h1>Wspieramy</h1></div>
            <BeneficiaryNodes nodes={nodes} />
        </main>
    )
}

Beneficiaries.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>
