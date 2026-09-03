import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Button,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import * as AuthSession from "expo-auth-session";
import * as WebBrowser from "expo-web-browser";
import { StatusBar } from "expo-status-bar";

WebBrowser.maybeCompleteAuthSession();

const API_GATEWAY_URL = process.env.EXPO_PUBLIC_API_GATEWAY_URL;
const KEYCLOAK_ISSUER = process.env.EXPO_PUBLIC_KEYCLOAK_ISSUER;
const KEYCLOAK_CLIENT_ID = process.env.EXPO_PUBLIC_KEYCLOAK_CLIENT_ID;

const discovery = {
  authorizationEndpoint: `${KEYCLOAK_ISSUER}/protocol/openid-connect/auth`,
  tokenEndpoint: `${KEYCLOAK_ISSUER}/protocol/openid-connect/token`,
  revocationEndpoint: `${KEYCLOAK_ISSUER}/protocol/openid-connect/revoke`,
};

/**
 * PoC Fazy 4 — mobile jako konsument TEGO SAMEGO kontraktu REST co web
 * (patrz dokument architektury): api-gateway nie wie i nie musi wiedzieć,
 * czy woła go przeglądarka czy telefon. Logowanie to Authorization Code +
 * PKCE bezpośrednio w Keycloaku (system browser, expo-auth-session) — tak
 * samo jak web, tylko innym klientem Keycloaka ("mobile", inny redirect
 * URI niż "web").
 */
export default function App() {
  const [health, setHealth] = useState(null);
  const [healthError, setHealthError] = useState(null);
  const [tokens, setTokens] = useState(null);
  const [me, setMe] = useState(null);
  const [meError, setMeError] = useState(null);

  const redirectUri = AuthSession.makeRedirectUri({ scheme: "paymobile" });

  const [request, response, promptAsync] = AuthSession.useAuthRequest(
    {
      clientId: KEYCLOAK_CLIENT_ID,
      scopes: ["openid", "profile", "email"],
      redirectUri,
      usePKCE: true,
      responseType: AuthSession.ResponseType.Code,
    },
    discovery,
  );

  // Health check publiczny — dokładnie ten sam endpoint co / i /live w web/.
  useEffect(() => {
    fetch(`${API_GATEWAY_URL}/api/v1/health`)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then(setHealth)
      .catch((e) => setHealthError(e.message));
  }, []);

  // Wymiana kodu autoryzacji na token, gdy Keycloak przekieruje z powrotem.
  useEffect(() => {
    if (response?.type !== "success") return;
    AuthSession.exchangeCodeAsync(
      {
        clientId: KEYCLOAK_CLIENT_ID,
        code: response.params.code,
        redirectUri,
        extraParams: { code_verifier: request?.codeVerifier ?? "" },
      },
      discovery,
    )
      .then(setTokens)
      .catch((e) => setMeError(e.message));
  }, [response]);

  // Po zalogowaniu: dokładnie to samo wywołanie co web/app/panel/page.js —
  // chroniony endpoint api-gateway z tokenem Bearer z Keycloaka.
  useEffect(() => {
    if (!tokens?.accessToken) return;
    fetch(`${API_GATEWAY_URL}/api/v1/me`, {
      headers: { Authorization: `Bearer ${tokens.accessToken}` },
    })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then(setMe)
      .catch((e) => setMeError(e.message));
  }, [tokens]);

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <StatusBar style="auto" />
      <Text style={styles.h1}>pay — mobile (Faza 4 PoC)</Text>
      <Text style={styles.lede}>
        Ten sam kontrakt REST co web/, plus logowanie Keycloak innym
        klientem ("mobile" zamiast "web").
      </Text>

      <Text style={styles.h2}>GET /api/v1/health (publiczny)</Text>
      {healthError && <Text style={styles.err}>Błąd: {healthError}</Text>}
      {health && <Text style={styles.pre}>{JSON.stringify(health, null, 2)}</Text>}
      {!health && !healthError && <ActivityIndicator />}

      <Text style={styles.h2}>Logowanie</Text>
      {!tokens && (
        <Button
          title="Zaloguj przez Keycloak"
          disabled={!request}
          onPress={() => promptAsync()}
        />
      )}
      {tokens && (
        <View>
          <Text>Zalogowano — token pobrany.</Text>
          {meError && <Text style={styles.err}>Błąd /api/v1/me: {meError}</Text>}
          {me && <Text style={styles.pre}>{JSON.stringify(me, null, 2)}</Text>}
          <Button title="Wyloguj (wyczyść token lokalnie)" onPress={() => { setTokens(null); setMe(null); }} />
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 24, paddingTop: 64, gap: 12 },
  h1: { fontSize: 20, fontWeight: "700" },
  h2: { fontSize: 15, fontWeight: "600", marginTop: 16 },
  lede: { color: "#555" },
  pre: { fontFamily: "monospace", fontSize: 12, backgroundColor: "#f2f2f2", padding: 10, borderRadius: 6 },
  err: { color: "#b3332c" },
});
