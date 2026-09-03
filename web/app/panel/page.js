import Link from "next/link";
import { auth } from "@/auth";
import { doSignIn, doSignOut } from "./actions";

// Cała strona zależy od sesji (cookie) — w pełni dynamiczna, celowo bez
// Partial Prerendering (panel prywatny, zero SEO — patrz dokument
// architektury, sekcja "web"). To oficjalna ścieżka ["block"] pod Cache
// Components dla tras w pełni blokujących — nie stary `export const dynamic`.
export const instant = false;

/**
 * PoC Fazy 3 — panel za logowaniem. Przeglądarka loguje się w Keycloaku
 * BEZPOŚREDNIO (przekierowanie z /api/auth/signin), nie przez api-gateway —
 * patrz dokument architektury, sekcja "Keycloak i gateway". api-gateway
 * dostaje tylko wystawiony token (Bearer) do walidacji.
 */
export default async function PanelPage() {
  const session = await auth();

  let me = null;
  let error = null;
  if (session) {
    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_API_GATEWAY_URL}/api/v1/me`, {
        headers: { Authorization: `Bearer ${session.accessToken}` },
        cache: "no-store",
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      me = await res.json();
    } catch (e) {
      error = e.message;
    }
  }

  return (
    <main className="demo">
      <nav>
        <Link href="/">← SSG / ISR</Link>
        <Link href="/live">CSR</Link>
        <Link href="/panel">panel (ta strona)</Link>
      </nav>
      <h1>web — panel, logowanie przez Keycloak</h1>
      <p className="lede">
        Zaloguj się, żeby zobaczyć wynik wywołania chronionego endpointu
        api-gateway (<code>/api/v1/me</code>) — bez ważnego tokenu api-gateway
        odpowie 401, zanim ten kod się wykona.
      </p>

      {!session && (
        <form action={doSignIn}>
          <button type="submit">Zaloguj przez Keycloak</button>
        </form>
      )}

      {session && (
        <div>
          <p>
            Zalogowano jako <b>{session.user?.name ?? session.user?.email}</b>.
          </p>
          {error && <p className="err">Błąd wołania /api/v1/me: {error}</p>}
          {me && <pre>{JSON.stringify(me, null, 2)}</pre>}
          <form action={doSignOut}>
            <button type="submit">Wyloguj</button>
          </form>
        </div>
      )}
    </main>
  );
}
