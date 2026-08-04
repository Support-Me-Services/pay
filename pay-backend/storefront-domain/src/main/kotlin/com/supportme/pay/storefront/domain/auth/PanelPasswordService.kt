package com.supportme.pay.storefront.domain.auth

import com.supportme.pay.storefront.domain.repository.UserRepository
import org.springframework.security.authentication.BadCredentialsException
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.stereotype.Service

/** Odpowiednik `Panel\PasswordController::update` — zmiana hasła zalogowanego admina. */
@Service
class PanelPasswordService(
    private val userRepository: UserRepository,
    private val passwordEncoder: PasswordEncoder,
) {
    fun changePassword(principal: TenantPrincipal, currentPassword: String, newPassword: String) {
        if (!passwordEncoder.matches(currentPassword, principal.user.password)) {
            throw BadCredentialsException("Nieprawidłowe obecne hasło.")
        }
        require(newPassword.length >= MIN_PASSWORD_LENGTH) {
            "Hasło musi mieć co najmniej $MIN_PASSWORD_LENGTH znaków."
        }

        val user = userRepository.findById(principal.user.id!!).orElseThrow()
        user.password = passwordEncoder.encode(newPassword)
        userRepository.save(user)
    }

    companion object {
        /** Jak `Password::min(8)` w PHP. */
        const val MIN_PASSWORD_LENGTH = 8
    }
}
