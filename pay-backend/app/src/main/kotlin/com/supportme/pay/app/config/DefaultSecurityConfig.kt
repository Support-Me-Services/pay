package com.supportme.pay.app.config

import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.annotation.Order
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.annotation.web.invoke
import org.springframework.security.web.SecurityFilterChain

/**
 * Chain wyłapujący WSZYSTKO poza panelami/client-API (np. `/actuator/health`,
 * a od Fazy 3/4 publiczne trasy płatności i sklepu) — na razie permitAll,
 * bo te trasy jeszcze nie istnieją. Musi być zarejestrowany (bez
 * `securityMatcher`, więc dopasowuje wszystko, co nie trafiło w chainy
 * z wyższym priorytetem) — Spring Security wymaga jednego chaina łapiącego
 * resztę, inaczej nieprzypisane żądania kończą się 401 zamiast 404/200.
 */
@Configuration
class DefaultSecurityConfig {

    @Bean
    @Order(100)
    fun defaultFilterChain(http: HttpSecurity): SecurityFilterChain {
        http {
            csrf { disable() }
            authorizeHttpRequests {
                authorize(anyRequest, permitAll)
            }
        }
        return http.build()
    }
}
