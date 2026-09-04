package com.supportme.pay.apigateway.security

import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.annotation.Order
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity
import org.springframework.security.config.annotation.web.invoke
import org.springframework.security.oauth2.core.DelegatingOAuth2TokenValidator
import org.springframework.security.oauth2.jwt.JwtClaimValidator
import org.springframework.security.oauth2.jwt.JwtDecoder
import org.springframework.security.oauth2.jwt.JwtValidators
import org.springframework.security.oauth2.jwt.NimbusJwtDecoder
import org.springframework.security.web.SecurityFilterChain

/**
 * Faza 3 PoC — api-gateway jako resource server: waliduje JWT wystawiony
 * przez Keycloak (podpis przez JWKS, issuer, audience), NIE proxuje
 * logowania (patrz dokument architektury, sekcja "Keycloak i gateway").
 *
 * jwk-set-uri i issuer są rozdzielone celowo: issuer to string zaszyty w
 * realnym tokenie (to, czego użyła przeglądarka, żeby dotrzeć do
 * Keycloaka — localhost:8180), a jwk-set-uri to adres, pod którym TEN
 * kontener faktycznie dociąga klucze (wewnątrz Dockera: keycloak:8080).
 * Standardowy auto-config ze `issuer-uri` zakłada, że to ten sam adres —
 * tutaj nie jest, stąd ręczna konfiguracja obu.
 */
@Configuration
@EnableWebSecurity
open class SecurityConfig(
    @Value("\${pay.keycloak.jwk-set-uri}") private val jwkSetUri: String,
    @Value("\${pay.keycloak.issuer}") private val expectedIssuer: String,
    @Value("\${pay.keycloak.audience}") private val expectedAudience: String,
) {
    @Bean
    open fun jwtDecoder(): JwtDecoder {
        val decoder = NimbusJwtDecoder.withJwkSetUri(jwkSetUri).build()

        val issuerValidator = JwtClaimValidator<String>("iss") { it == expectedIssuer }
        val audienceValidator = JwtClaimValidator<List<String>?>("aud") {
            it != null && it.contains(expectedAudience)
        }

        decoder.setJwtValidator(
            DelegatingOAuth2TokenValidator(
                JwtValidators.createDefault(), // exp / nbf
                issuerValidator,
                audienceValidator,
            ),
        )

        return decoder
    }

    /**
     * Ścieżki pod prefiksem `/internal/` — osobny łańcuch, osobny mechanizm (nagłówek
     * X-Internal-Api-Key, nie JWT Keycloaka). `@Order(1)` = wyższy
     * priorytet niż główny łańcuch niżej: Spring Security wybiera pierwszy
     * łańcuch, którego `securityMatcher` pasuje, więc te ścieżki NIGDY nie
     * trafiają do łańcucha JWT. Sama autoryzacja dzieje się w
     * [InternalApiKeyFilter] — tu `permitAll`, bo brak tokenu nie jest
     * powodem odrzucenia (filtr już to zrobił, wcześniej w łańcuchu).
     */
    @Bean
    @Order(1)
    open fun internalFilterChain(http: HttpSecurity, internalApiKeyFilter: InternalApiKeyFilter): SecurityFilterChain {
        http {
            securityMatcher("/internal/**")
            csrf { disable() }
            authorizeHttpRequests {
                authorize(anyRequest, permitAll)
            }
            addFilterBefore<UsernamePasswordAuthenticationFilter>(internalApiKeyFilter)
        }
        return http.build()
    }

    @Bean
    @Order(2)
    open fun filterChain(http: HttpSecurity): SecurityFilterChain {
        http {
            csrf { disable() }
            cors { }
            authorizeHttpRequests {
                // Health check zostaje publiczny — to demo REST<->gRPC z Fazy 0/1,
                // niezwiązane z auth. /api/v1/me demonstruje ochronę tokenem.
                authorize("/api/v1/health", permitAll)
                authorize("/actuator/**", permitAll)
                // Faza 5 — publiczne skanowanie kodu inicjalizacji (tag NFC/QR),
                // patrz PublicInitController. Niezalogowane z natury (ktoś
                // zbliża telefon do fizycznego tagu), jak health check.
                authorize("/init/tag/**", permitAll)
                authorize("/init/qr/**", permitAll)
                authorize(anyRequest, authenticated)
            }
            oauth2ResourceServer {
                jwt { }
            }
        }
        return http.build()
    }
}
