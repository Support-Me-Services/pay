package com.supportme.pay.apigateway.security

import jakarta.servlet.FilterChain
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.beans.factory.annotation.Value
import org.springframework.stereotype.Component
import org.springframework.web.filter.OncePerRequestFilter
import java.nio.charset.StandardCharsets
import java.security.MessageDigest

/**
 * Bramka dla ścieżek pod prefiksem `/internal/` — WYŁĄCZNIE gateway-svc (Laravel) jako wołający,
 * server-to-server, nigdy przeglądarka/mobile. Świadomie prostszy mechanizm
 * niż Keycloak (shared secret zamiast client-credentials): Laravel nie ma
 * dziś ŻADNEJ możliwości bycia klientem gRPC ani Keycloaka, a stawianie
 * pełnego OAuth2 service-account flow dla jednego wewnętrznego wołającego
 * byłoby przedwczesną złożonością na tym etapie projektu — patrz plan Fazy 5
 * w claude/marcin/03-ekosystem-mikroserwisow.md.
 *
 * Porównanie stałoczasowe ([MessageDigest.isEqual]) — zwykłe `==`/`.equals()`
 * na sekrecie porównywanym przez sieć to niepotrzebny kanał boczny czasowy,
 * nawet w ruchu wyłącznie wewnętrznym.
 */
@Component
class InternalApiKeyFilter(
    @Value("\${pay.internal.api-key}") private val expectedKey: String,
) : OncePerRequestFilter() {

    override fun shouldNotFilter(request: HttpServletRequest): Boolean =
        !request.requestURI.startsWith("/internal/")

    override fun doFilterInternal(request: HttpServletRequest, response: HttpServletResponse, filterChain: FilterChain) {
        val provided = request.getHeader("X-Internal-Api-Key") ?: ""

        if (!constantTimeEquals(provided, expectedKey)) {
            response.status = HttpServletResponse.SC_FORBIDDEN
            return
        }

        filterChain.doFilter(request, response)
    }

    private fun constantTimeEquals(a: String, b: String): Boolean =
        MessageDigest.isEqual(a.toByteArray(StandardCharsets.UTF_8), b.toByteArray(StandardCharsets.UTF_8))
}
