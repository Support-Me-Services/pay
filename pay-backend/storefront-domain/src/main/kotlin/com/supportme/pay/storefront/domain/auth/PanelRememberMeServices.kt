package com.supportme.pay.storefront.domain.auth

import com.supportme.pay.storefront.domain.repository.UserRepository
import jakarta.servlet.http.Cookie
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.security.authentication.RememberMeAuthenticationToken
import org.springframework.security.core.Authentication
import org.springframework.security.web.authentication.RememberMeServices
import java.security.SecureRandom
import java.util.Base64

/**
 * Odpowiednik Laravel remember-me (`Auth::attempt($credentials, true)`,
 * WŁĄCZONE ZAWSZE — oba `Panel\LoginController` przekazują `true` na sztywno).
 * Cookie koduje `userId|token`; token trzymany w `users.remember_token`
 * (kolumna istniała od Fazy 1, dotąd nieużywana) — jednokolumnowy model
 * Laravela, NIE `TokenBasedRememberMeServices` (HMAC bez DB) ani
 * `PersistentTokenBasedRememberMeServices` (osobna tabela series+token) ze
 * Springa, bo obie mają inny model persystencji.
 *
 * `loginSuccess`/`forgetMe` są wołane RĘCZNIE z [PanelAuthService] — logowanie
 * jest programistyczne (JSON, nie `formLogin`), więc standardowy hook filtra
 * uwierzytelniającego nigdy się nie odpali. `autoLogin` odpala WŁĄCZNIE
 * standardowy `RememberMeAuthenticationFilter` (skonfigurowany w
 * `SecurityFilterChain` obu paneli) na żądaniach bez aktywnej sesji.
 */
class PanelRememberMeServices(
    private val userRepository: UserRepository,
    /** Publiczny — obie `SecurityFilterChain` (Gateway/Storefront) muszą skonfigurować `rememberMe { key = ... }` z TĄ SAMĄ wartością, inaczej DSL auto-generuje własny losowy key i `RememberMeAuthenticationProvider` odrzuca token ("does not contain the expected key"). */
    val key: String,
) : RememberMeServices {

    override fun autoLogin(request: HttpServletRequest, response: HttpServletResponse): Authentication? {
        val cookieValue = request.cookies?.firstOrNull { it.name == COOKIE_NAME }?.value ?: return null
        val decoded = runCatching { String(Base64.getUrlDecoder().decode(cookieValue)) }.getOrNull() ?: return null
        val parts = decoded.split("|", limit = 2)
        if (parts.size != 2) return null
        val userId = parts[0].toLongOrNull() ?: return null

        val user = userRepository.findById(userId).orElse(null) ?: return null
        if (user.rememberToken.isNullOrBlank() || user.rememberToken != parts[1]) return null

        val principal = TenantPrincipal(user)
        return RememberMeAuthenticationToken(key, principal, principal.authorities)
    }

    override fun loginFail(request: HttpServletRequest, response: HttpServletResponse) {
        // Jak w PHP — nic do wyczyszczenia na nieudanym logowaniu.
    }

    override fun loginSuccess(request: HttpServletRequest, response: HttpServletResponse, successfulAuthentication: Authentication) {
        val principal = successfulAuthentication.principal as? TenantPrincipal ?: return
        val user = userRepository.findById(principal.user.id!!).orElseThrow()
        val token = generateToken()
        user.rememberToken = token
        userRepository.save(user)
        setCookie(request, response, "${user.id}|$token")
    }

    /** Jak Laravel `logout()`: rotacja `remember_token` (unieważnia stare cookie) + wygaszenie cookie klienta. */
    fun forgetMe(request: HttpServletRequest, response: HttpServletResponse, userId: Long?) {
        userId?.let { id ->
            userRepository.findById(id).orElse(null)?.let { user ->
                user.rememberToken = generateToken()
                userRepository.save(user)
            }
        }
        val cookie = Cookie(COOKIE_NAME, "")
        cookie.path = "/"
        cookie.maxAge = 0
        cookie.isHttpOnly = true
        cookie.secure = request.isSecure
        response.addCookie(cookie)
    }

    private fun setCookie(request: HttpServletRequest, response: HttpServletResponse, value: String) {
        val cookie = Cookie(COOKIE_NAME, Base64.getUrlEncoder().withoutPadding().encodeToString(value.toByteArray(Charsets.UTF_8)))
        cookie.path = "/"
        cookie.maxAge = REMEMBER_ME_SECONDS
        cookie.isHttpOnly = true
        cookie.secure = request.isSecure
        response.addCookie(cookie)
    }

    private fun generateToken(): String {
        val bytes = ByteArray(32)
        SECURE_RANDOM.nextBytes(bytes)
        return bytes.joinToString("") { "%02x".format(it) }
    }

    companion object {
        private const val COOKIE_NAME = "panel_remember"
        private const val REMEMBER_ME_SECONDS = 60 * 60 * 24 * 30
        private val SECURE_RANDOM = SecureRandom()
    }
}
