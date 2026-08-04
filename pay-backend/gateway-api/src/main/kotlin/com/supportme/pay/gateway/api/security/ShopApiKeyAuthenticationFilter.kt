package com.supportme.pay.gateway.api.security

import com.fasterxml.jackson.databind.ObjectMapper
import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.repository.ShopRepository
import jakarta.servlet.FilterChain
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.http.MediaType
import org.springframework.security.authentication.AbstractAuthenticationToken
import org.springframework.security.core.authority.SimpleGrantedAuthority
import org.springframework.security.core.context.SecurityContextHolder
import org.springframework.web.filter.OncePerRequestFilter

/** Principal reprezentujący uwierzytelniony sklep-klient bramki (odpowiednik `AuthenticateApiKey`). */
class ShopAuthenticationToken(val shop: Shop) :
    AbstractAuthenticationToken(listOf(SimpleGrantedAuthority("ROLE_SHOP"))) {
    init {
        isAuthenticated = true
    }

    override fun getCredentials(): Any? = null
    override fun getPrincipal(): Any = shop
}

/**
 * Odpowiednik `App\Modules\Gateway\Http\Middleware\AuthenticateApiKey` —
 * nagłówek `X-Api-Key` -> lookup `Shop`. Kształt błędu (`401 {"error": ...}`)
 * MUSI zostać zachowany 1:1 — to publiczny kontrakt zewnętrzny używany przez
 * realne sklepy-klientów, nie wolno go cicho zmienić przy porcie.
 */
class ShopApiKeyAuthenticationFilter(
    private val shopRepository: ShopRepository,
    private val objectMapper: ObjectMapper,
) : OncePerRequestFilter() {

    override fun doFilterInternal(request: HttpServletRequest, response: HttpServletResponse, filterChain: FilterChain) {
        val apiKey = request.getHeader("X-Api-Key")
        val shop = apiKey?.let { shopRepository.findByApiKey(it) }

        if (shop == null) {
            response.status = HttpServletResponse.SC_UNAUTHORIZED
            response.contentType = MediaType.APPLICATION_JSON_VALUE
            response.characterEncoding = "UTF-8"
            response.writer.write(objectMapper.writeValueAsString(mapOf("error" to "Niepoprawny klucz API")))
            return
        }

        SecurityContextHolder.getContext().authentication = ShopAuthenticationToken(shop)
        filterChain.doFilter(request, response)
    }
}
