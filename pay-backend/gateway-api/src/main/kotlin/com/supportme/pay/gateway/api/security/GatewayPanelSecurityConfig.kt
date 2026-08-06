package com.supportme.pay.gateway.api.security

import com.supportme.pay.storefront.domain.auth.PanelRememberMeServices
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.annotation.Order
import org.springframework.http.HttpMethod
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.annotation.web.invoke
import org.springframework.security.web.SecurityFilterChain

/**
 * Panel admina Gateway — sesja + cookie (nie JWT, decyzja z planu migracji).
 * Logowanie/wylogowanie obsługiwane RĘCZNIE w [com.supportme.pay.gateway.api.panel.auth.GatewayLoginController]
 * (`PanelAuthService`), nie przez `formLogin()` — stąd wyłączone tu, żeby
 * Spring nie próbował własnego przetwarzania `/login`.
 *
 * CSRF wyłączone na razie — do rozstrzygnięcia w Fazie 5 razem z ostatecznym
 * modelem osadzenia Next.js (same-origin + SameSite cookies vs jawne tokeny
 * CSRF), nie jest to pominięcie przypadkowe.
 */
@Configuration
class GatewayPanelSecurityConfig(private val panelRememberMeServices: PanelRememberMeServices) {

    // @Order MUSI być na metodzie @Bean, nie na klasie @Configuration — Spring
    // Security sortuje wstrzykiwaną `List<SecurityFilterChain>` przez
    // AnnotationAwareOrderComparator, który czyta @Order z metody fabrykującej
    // bean, nie z otaczającej klasy (ta ostatnia wpływa tylko na kolejność
    // przetwarzania samych @Configuration, nie na kolejność chainów).
    @Bean
    @Order(1)
    fun gatewayPanelFilterChain(http: HttpSecurity): SecurityFilterChain {
        http {
            securityMatcher("/api/gateway/panel/**")
            csrf { disable() }
            sessionManagement { sessionCreationPolicy = org.springframework.security.config.http.SessionCreationPolicy.IF_REQUIRED }
            authorizeHttpRequests {
                authorize(HttpMethod.POST, "/api/gateway/panel/login", permitAll)
                authorize(anyRequest, authenticated)
            }
            formLogin { disable() }
            httpBasic { disable() }
            logout { disable() }
            // Jak `Auth::attempt($credentials, true)` w PHP — patrz uzasadnienie w
            // StorefrontPanelSecurityConfig (identyczna konfiguracja, w tym `key`).
            rememberMe {
                rememberMeServices = panelRememberMeServices
                key = panelRememberMeServices.key
            }
        }
        return http.build()
    }
}
