package com.supportme.pay.gateway.api.security

import com.fasterxml.jackson.databind.ObjectMapper
import com.supportme.pay.gateway.domain.repository.ShopRepository
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.annotation.Order
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.annotation.web.invoke
import org.springframework.security.config.http.SessionCreationPolicy
import org.springframework.security.web.SecurityFilterChain
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter

/**
 * REST API client-facing (`/api/gateway/v1/...`) — sklepy-klienci bramki
 * uwierzytelniają się nagłówkiem `X-Api-Key`, BEZ sesji (stateless, jak
 * dziś — to serwer-do-serwera, nie przeglądarka).
 */
@Configuration
class GatewayApiKeySecurityConfig {

    @Bean
    @Order(2)
    fun gatewayApiKeyFilterChain(
        http: HttpSecurity,
        shopRepository: ShopRepository,
        objectMapper: ObjectMapper,
    ): SecurityFilterChain {
        http {
            securityMatcher("/api/gateway/v1/**")
            csrf { disable() }
            sessionManagement { sessionCreationPolicy = SessionCreationPolicy.STATELESS }
            authorizeHttpRequests {
                authorize(anyRequest, authenticated)
            }
            addFilterBefore<UsernamePasswordAuthenticationFilter>(
                ShopApiKeyAuthenticationFilter(shopRepository, objectMapper),
            )
        }
        return http.build()
    }
}
