import NextAuth from "next-auth";
import Keycloak from "next-auth/providers/keycloak";

/**
 * Faza 3 PoC — logowanie przez Keycloak (realm "pay"), klient "web"
 * (publiczny, PKCE — bez client secret). Przeglądarka rozmawia z
 * Keycloakiem bezpośrednio (przekierowanie), NIE przez api-gateway —
 * patrz dokument architektury, sekcja "Keycloak i gateway".
 *
 * jwt/session callbacks przechowują access_token z Keycloaka, żeby móc go
 * użyć jako Bearer przy wołaniu chronionych endpointów api-gateway
 * (patrz app/panel/page.js).
 */
export const { handlers, signIn, signOut, auth } = NextAuth({
  providers: [
    Keycloak({
      issuer: process.env.KEYCLOAK_ISSUER,
      clientId: process.env.KEYCLOAK_CLIENT_ID,
      // Klient publiczny (PKCE) — bez client secret.
      clientSecret: undefined,
      checks: ["pkce"],
    }),
  ],
  callbacks: {
    async jwt({ token, account }) {
      if (account) {
        token.accessToken = account.access_token;
      }
      return token;
    },
    async session({ session, token }) {
      session.accessToken = token.accessToken;
      return session;
    },
  },
});
