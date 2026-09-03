package com.supportme.pay.apigateway

import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.security.oauth2.jwt.Jwt
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RestController

/**
 * Faza 3 PoC — dowód, że api-gateway faktycznie chroni endpointy tokenem
 * z Keycloaka: bez ważnego JWT (podpis, issuer, audience "api-gateway")
 * Spring zwraca 401 zanim ten kod się wykona.
 */
@RestController
class MeController {
    @GetMapping("/api/v1/me")
    fun me(@AuthenticationPrincipal jwt: Jwt): Map<String, Any?> = mapOf(
        "subject" to jwt.subject,
        "username" to jwt.claims["preferred_username"],
        "email" to jwt.claims["email"],
        "issuer" to jwt.issuer.toString(),
        "audience" to jwt.audience,
    )
}
