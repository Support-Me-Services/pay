package com.supportme.pay.apigateway.security;

import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;

/**
 * Bramka dla ścieżek pod prefiksem `/internal/` — WYŁĄCZNIE gateway-svc (Laravel) jako wołający,
 * server-to-server, nigdy przeglądarka/mobile. Świadomie prostszy mechanizm
 * niż Keycloak (shared secret zamiast client-credentials): Laravel nie ma
 * dziś ŻADNEJ możliwości bycia klientem gRPC ani Keycloaka, a stawianie
 * pełnego OAuth2 service-account flow dla jednego wewnętrznego wołającego
 * byłoby przedwczesną złożonością na tym etapie projektu — patrz plan Fazy 5
 * w claude/marcin/03-ekosystem-mikroserwisow.md.
 *
 * Porównanie stałoczasowe (MessageDigest.isEqual) — zwykłe `==`/`.equals()`
 * na sekrecie porównywanym przez sieć to niepotrzebny kanał boczny czasowy,
 * nawet w ruchu wyłącznie wewnętrznym.
 */
@Component
public class InternalApiKeyFilter extends OncePerRequestFilter {

    private final String expectedKey;

    public InternalApiKeyFilter(@Value("${pay.internal.api-key}") String expectedKey) {
        this.expectedKey = expectedKey;
    }

    @Override
    protected boolean shouldNotFilter(HttpServletRequest request) {
        return !request.getRequestURI().startsWith("/internal/");
    }

    @Override
    protected void doFilterInternal(HttpServletRequest request, HttpServletResponse response, FilterChain filterChain)
            throws ServletException, IOException {
        String provided = request.getHeader("X-Internal-Api-Key");
        if (provided == null) {
            provided = "";
        }

        if (!constantTimeEquals(provided, expectedKey)) {
            response.setStatus(HttpServletResponse.SC_FORBIDDEN);
            return;
        }

        filterChain.doFilter(request, response);
    }

    private boolean constantTimeEquals(String a, String b) {
        return MessageDigest.isEqual(a.getBytes(StandardCharsets.UTF_8), b.getBytes(StandardCharsets.UTF_8));
    }
}
