package com.supportme.pay.gateway.api.panel.auth

import com.supportme.pay.storefront.domain.auth.LoginRequest
import com.supportme.pay.storefront.domain.auth.PanelAuthService
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.security.core.AuthenticationException
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

/** Odpowiednik `Panel\LoginController` (login/logout) na hoście Gateway. */
@RestController
@RequestMapping("/api/gateway/panel")
class GatewayLoginController(private val panelAuthService: PanelAuthService) {

    @PostMapping("/login")
    fun login(
        @RequestBody body: LoginRequest,
        request: HttpServletRequest,
        response: HttpServletResponse,
    ): ResponseEntity<Map<String, String>> = try {
        val principal = panelAuthService.login(body.email, body.password, request, response)
        ResponseEntity.ok(mapOf("email" to principal.username))
    } catch (e: AuthenticationException) {
        ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(mapOf("error" to "Nieprawidłowy e-mail lub hasło."))
    }

    @PostMapping("/logout")
    fun logout(request: HttpServletRequest): ResponseEntity<Void> {
        panelAuthService.logout(request)
        return ResponseEntity.noContent().build()
    }
}
