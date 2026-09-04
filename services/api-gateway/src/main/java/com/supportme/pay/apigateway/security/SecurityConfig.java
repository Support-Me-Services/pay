package com.supportme.pay.apigateway.security;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.core.annotation.Order;
import org.springframework.security.config.Customizer;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.config.annotation.web.configurers.AbstractHttpConfigurer;
import org.springframework.security.oauth2.core.DelegatingOAuth2TokenValidator;
import org.springframework.security.oauth2.core.OAuth2TokenValidator;
import org.springframework.security.oauth2.jwt.Jwt;
import org.springframework.security.oauth2.jwt.JwtClaimValidator;
import org.springframework.security.oauth2.jwt.JwtDecoder;
import org.springframework.security.oauth2.jwt.JwtValidators;
import org.springframework.security.oauth2.jwt.NimbusJwtDecoder;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter;

import java.util.List;

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
public class SecurityConfig {

    private final String jwkSetUri;
    private final String expectedIssuer;
    private final String expectedAudience;

    public SecurityConfig(
            @Value("${pay.keycloak.jwk-set-uri}") String jwkSetUri,
            @Value("${pay.keycloak.issuer}") String expectedIssuer,
            @Value("${pay.keycloak.audience}") String expectedAudience) {
        this.jwkSetUri = jwkSetUri;
        this.expectedIssuer = expectedIssuer;
        this.expectedAudience = expectedAudience;
    }

    @Bean
    public JwtDecoder jwtDecoder() {
        NimbusJwtDecoder decoder = NimbusJwtDecoder.withJwkSetUri(jwkSetUri).build();

        OAuth2TokenValidator<Jwt> issuerValidator =
                new JwtClaimValidator<String>("iss", iss -> iss.equals(expectedIssuer));
        OAuth2TokenValidator<Jwt> audienceValidator =
                new JwtClaimValidator<List<String>>("aud", aud -> aud != null && aud.contains(expectedAudience));

        decoder.setJwtValidator(new DelegatingOAuth2TokenValidator<>(
                JwtValidators.createDefault(), // exp / nbf
                issuerValidator,
                audienceValidator
        ));

        return decoder;
    }

    /**
     * Ścieżki pod prefiksem `/internal/` — osobny łańcuch, osobny mechanizm (nagłówek
     * X-Internal-Api-Key, nie JWT Keycloaka). `@Order(1)` = wyższy
     * priorytet niż główny łańcuch niżej: Spring Security wybiera pierwszy
     * łańcuch, którego `securityMatcher` pasuje, więc te ścieżki NIGDY nie
     * trafiają do łańcucha JWT. Sama autoryzacja dzieje się w
     * InternalApiKeyFilter — tu `permitAll`, bo brak tokenu nie jest
     * powodem odrzucenia (filtr już to zrobił, wcześniej w łańcuchu).
     */
    @Bean
    @Order(1)
    public SecurityFilterChain internalFilterChain(HttpSecurity http, InternalApiKeyFilter internalApiKeyFilter) throws Exception {
        http
                .securityMatcher("/internal/**")
                .csrf(AbstractHttpConfigurer::disable)
                .authorizeHttpRequests(auth -> auth.anyRequest().permitAll())
                .addFilterBefore(internalApiKeyFilter, UsernamePasswordAuthenticationFilter.class);
        return http.build();
    }

    @Bean
    @Order(2)
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
                .csrf(AbstractHttpConfigurer::disable)
                .cors(Customizer.withDefaults())
                .authorizeHttpRequests(auth -> auth
                        // Health check zostaje publiczny — to demo REST<->gRPC z Fazy 0/1,
                        // niezwiązane z auth. /api/v1/me demonstruje ochronę tokenem.
                        .requestMatchers("/api/v1/health").permitAll()
                        .requestMatchers("/actuator/**").permitAll()
                        // Faza 5 — publiczne skanowanie kodu inicjalizacji (tag NFC/QR),
                        // patrz PublicInitController. Niezalogowane z natury (ktoś
                        // zbliża telefon do fizycznego tagu), jak health check.
                        .requestMatchers("/init/tag/**").permitAll()
                        .requestMatchers("/init/qr/**").permitAll()
                        .anyRequest().authenticated())
                .oauth2ResourceServer(oauth2 -> oauth2.jwt(Customizer.withDefaults()));
        return http.build();
    }
}
