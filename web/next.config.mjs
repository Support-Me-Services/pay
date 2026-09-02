import { fileURLToPath } from "node:url";

/** @type {import('next').NextConfig} */
const nextConfig = {
  // Cache Components (PPR domyślnie) — świadomy wybór dla nowego projektu,
  // nie stary model cache'owania. Patrz app/page.js: 'use cache' + cacheLife.
  cacheComponents: true,
  // Monorepo: obok web/package-lock.json jest drugi (Laravel) w korzeniu
  // repo — bez tego Turbopack zgaduje zły root katalogu.
  turbopack: {
    root: fileURLToPath(new URL(".", import.meta.url)),
  },
};

export default nextConfig;
