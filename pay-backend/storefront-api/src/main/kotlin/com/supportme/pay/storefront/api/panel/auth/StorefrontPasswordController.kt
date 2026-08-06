package com.supportme.pay.storefront.api.panel.auth

import com.supportme.pay.storefront.domain.auth.ChangePasswordRequest
import com.supportme.pay.storefront.domain.auth.PanelAuthService
import com.supportme.pay.storefront.domain.auth.PanelPasswordService
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.security.authentication.BadCredentialsException
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

/** Odpowiednik `Panel\PasswordController::update` na hostach Storefront. */
@RestController
@RequestMapping("/api/storefront/panel/password")
class StorefrontPasswordController(
    private val panelAuthService: PanelAuthService,
    private val panelPasswordService: PanelPasswordService,
) {

    @PutMapping
    fun update(@RequestBody body: ChangePasswordRequest): ResponseEntity<Map<String, String>> {
        val principal = panelAuthService.currentPrincipal()
            ?: return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build()

        return try {
            panelPasswordService.changePassword(principal, body.currentPassword, body.newPassword, body.newPasswordConfirmation)
            ResponseEntity.ok(mapOf("status" to "ok"))
        } catch (e: BadCredentialsException) {
            ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to (e.message ?: "Błąd.")))
        } catch (e: IllegalArgumentException) {
            ResponseEntity.status(HttpStatus.UNPROCESSABLE_ENTITY).body(mapOf("error" to (e.message ?: "Błąd.")))
        }
    }
}
