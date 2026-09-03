import { cacheLife } from "next/cache";
import Link from "next/link";

/**
 * PoC Fazy 2 — strona statyczna/ISR. `use cache` + cacheLife oznacza:
 * odpowiedź api-gateway trafia do wygenerowanego HTML (część statycznego
 * shellu), a nie jest odpytywana przy każdym żądaniu — Next odświeża ją w
 * tle wg profilu 'minutes' (świeże ~1 min, wygasa po godzinie). Porównaj
 * z /live, która robi dokładnie to samo zapytanie, ale z przeglądarki.
 */
async function ApiGatewayHealth() {
  "use cache";
  cacheLife("minutes");

  const url = `${process.env.NEXT_PUBLIC_API_GATEWAY_URL}/api/v1/health`;

  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    return <pre>{JSON.stringify(data, null, 2)}</pre>;
  } catch (e) {
    return (
      <p className="err">
        api-gateway nieosiągalne pod {url} — sprawdź, czy działa
        (<code>ecosystem/docker-compose.yml</code>). Błąd: {e.message}
      </p>
    );
  }
}

export default function Home() {
  return (
    <main className="demo">
      <nav>
        <Link href="/">SSG / ISR (ta strona)</Link>
        <Link href="/live">CSR</Link>
        <Link href="/panel">panel (Keycloak) →</Link>
      </nav>
      <h1>web — Next.js, wariant SSG/ISR</h1>
      <p className="lede">
        Ta treść jest wyrenderowana na serwerze i wpieczona w statyczny HTML —
        przeglądarka dostaje gotową stronę, zero czekania na JS. Odświeża się
        w tle mniej więcej co minutę (<code>cacheLife(&quot;minutes&quot;)</code>),
        nie przy każdym wejściu.
      </p>
      <ApiGatewayHealth />
    </main>
  );
}
