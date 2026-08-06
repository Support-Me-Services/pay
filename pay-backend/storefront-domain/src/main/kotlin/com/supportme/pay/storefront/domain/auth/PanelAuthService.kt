package com.supportme.pay.storefront.domain.auth

import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.security.authentication.AuthenticationManager
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken
import org.springframework.security.core.context.SecurityContextHolder
import org.springframework.security.web.context.SecurityContextRepository
import org.springframework.stereotype.Service

/**
 * Logowanie/wylogowanie programistyczne (JSON, nie `formLogin`) — współdzielone
 * przez OBA panele (Gateway i Storefront), bo logika jest identyczna: różni je
 * tylko host/ścieżka, o czym decyduje `SecurityFilterChain` wołającego modułu.
 * Odpowiednik `Auth::attempt($credentials, true)` + regeneracja sesji z
 * `Panel\LoginController` w PHP.
 */
@Service
class PanelAuthService(
    private val authenticationManager: AuthenticationManager,
    private val securityContextRepository: SecurityContextRepository,
    private val rememberMeServices: PanelRememberMeServices,
) {
    fun login(
        email: String,
        password: String,
        request: HttpServletRequest,
        response: HttpServletResponse,
    ): TenantPrincipal {
        val authRequest = UsernamePasswordAuthenticationToken.unauthenticated(email, password)
        val authResult = authenticationManager.authenticate(authRequest)

        // Ochrona przed session fixation — nowe ID sesji PRZED ustanowieniem
        // uwierzytelnionego kontekstu, odpowiednik `$request->session()->regenerate()`.
        request.getSession(false)?.let { request.changeSessionId() }

        val context = SecurityContextHolder.createEmptyContext()
        context.authentication = authResult
        SecurityContextHolder.setContext(context)
        securityContextRepository.saveContext(context, request, response)

        // Jak `Auth::attempt($credentials, true)` w PHP — remember-me WŁĄCZONE
        // ZAWSZE, oba `Panel\LoginController` przekazują `true` na sztywno.
        rememberMeServices.loginSuccess(request, response, authResult)

        return authResult.principal as TenantPrincipal
    }

    fun logout(request: HttpServletRequest, response: HttpServletResponse) {
        val userId = currentPrincipal()?.user?.id
        rememberMeServices.forgetMe(request, response, userId)
        SecurityContextHolder.clearContext()
        request.getSession(false)?.invalidate()
    }

    fun currentPrincipal(): TenantPrincipal? =
        SecurityContextHolder.getContext().authentication?.principal as? TenantPrincipal
}
