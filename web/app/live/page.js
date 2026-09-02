"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

/**
 * PoC Fazy 2 — strona kliencka (CSR). Zero renderu na serwerze dla danych —
 * przeglądarka sama odpytuje api-gateway po zamontowaniu, dokładnie tak jak
 * będzie działał docelowo panel za logowaniem (dane prywatne, nic do
 * indeksowania, SEO bez znaczenia). Wymaga CORS na api-gateway — to
 * przeglądarka woła bezpośrednio, nie serwer Next.js.
 */
export default function Live() {
  const [health, setHealth] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const url = `${process.env.NEXT_PUBLIC_API_GATEWAY_URL}/api/v1/health`;
    fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then(setHealth)
      .catch((e) => setError(e.message));
  }, []);

  return (
    <main className="demo">
      <nav>
        <Link href="/">← SSG / ISR</Link>
        <Link href="/live">CSR (ta strona)</Link>
      </nav>
      <h1>web — Next.js, wariant CSR</h1>
      <p className="lede">
        Ta strona ładuje dane z przeglądarki po zamontowaniu — nawigacja tu
        i z powrotem nie przeładowuje dokumentu (kliknij link wyżej i zobacz:
        adres się zmienia, strona nie mruga). Dokładnie to, o co chodziło w
        rozmowie o słabym łączu na telefonach.
      </p>
      {error && <p className="err">Błąd: {error}</p>}
      {health && <pre>{JSON.stringify(health, null, 2)}</pre>}
      {!health && !error && <p>Ładowanie…</p>}
    </main>
  );
}
