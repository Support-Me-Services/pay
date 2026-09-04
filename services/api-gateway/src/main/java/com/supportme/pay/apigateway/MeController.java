package com.supportme.pay.apigateway;

import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.security.oauth2.jwt.Jwt;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.LinkedHashMap;
import java.util.Map;

/**
 * Faza 3 PoC — dowód, że api-gateway faktycznie chroni endpointy tokenem
 * z Keycloaka: bez ważnego JWT (podpis, issuer, audience "api-gateway")
 * Spring zwraca 401 zanim ten kod się wykona.
 */
@RestController
public class MeController {

    @GetMapping("/api/v1/me")
    public Map<String, Object> me(@AuthenticationPrincipal Jwt jwt) {
        Map<String, Object> claims = new LinkedHashMap<>();
        claims.put("subject", jwt.getSubject());
        claims.put("username", jwt.getClaims().get("preferred_username"));
        claims.put("email", jwt.getClaims().get("email"));
        claims.put("issuer", jwt.getIssuer().toString());
        claims.put("audience", jwt.getAudience());
        return claims;
    }
}
