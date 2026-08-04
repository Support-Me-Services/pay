package com.supportme.pay.gateway.payments

import java.time.Instant
import java.util.concurrent.ConcurrentHashMap

/**
 * Odpowiednik `Cache::remember('payu_access_token', $ttl, ...)` w PHP — TTL
 * WARIABLE per-fetch (`expires_in - 300`, min 60s), stąd zwykła mapa +
 * ręczne sprawdzanie wygaśnięcia zamiast Caffeine (które ma stały TTL per
 * builder, niewygodny dla TTL zależnego od odpowiedzi OAuth). Osobny klucz
 * dla głównego POS-a i `payu_newpos` (`ActivationStatusController`).
 */
class PayUOAuthTokenCache {
    private data class CachedToken(val token: String, val expiresAt: Instant)

    private val tokens = ConcurrentHashMap<String, CachedToken>()

    /** @param fetch zwraca (token, expiresInSeconds) — jak surowa odpowiedź OAuth PayU. */
    fun getOrFetch(key: String, fetch: () -> Pair<String, Long>): String {
        tokens[key]?.let { cached ->
            if (cached.expiresAt.isAfter(Instant.now())) return cached.token
        }

        val (token, expiresInSeconds) = fetch()
        val ttlSeconds = maxOf(expiresInSeconds - 300, 60)
        tokens[key] = CachedToken(token, Instant.now().plusSeconds(ttlSeconds))
        return token
    }

    companion object {
        const val MAIN_POS_KEY = "payu_access_token"
        const val NEW_POS_KEY = "payu_newpos_access_token"
    }
}
