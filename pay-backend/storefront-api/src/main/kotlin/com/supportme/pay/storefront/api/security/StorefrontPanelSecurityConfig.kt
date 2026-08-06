package com.supportme.pay.storefront.api.security

import com.supportme.pay.storefront.domain.auth.PanelRememberMeServices
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.annotation.Order
import org.springframework.http.HttpMethod
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.annotation.web.invoke
import org.springframework.security.config.http.SessionCreationPolicy
import org.springframework.security.web.SecurityFilterChain

/**
 * Panel admina Storefront — sesja + cookie, logowanie/wylogowanie ręczne
 * w [com.supportme.pay.storefront.api.panel.auth.StorefrontLoginController]
 * (`PanelAuthService`, współdzielony z Gateway — patrz uzasadnienie tam).
 * CSRF wyłączone na razie, jak w panelu Gateway (do Fazy 5).
 */
@Configuration
class StorefrontPanelSecurityConfig(private val panelRememberMeServices: PanelRememberMeServices) {

    @Bean
    @Order(3)
    fun storefrontPanelFilterChain(http: HttpSecurity): SecurityFilterChain {
        http {
            securityMatcher("/api/storefront/panel/**")
            csrf { disable() }
            sessionManagement { sessionCreationPolicy = SessionCreationPolicy.IF_REQUIRED }
            authorizeHttpRequests {
                authorize(HttpMethod.POST, "/api/storefront/panel/login", permitAll)
                authorize(anyRequest, authenticated)
            }
            formLogin { disable() }
            httpBasic { disable() }
            logout { disable() }
            // Jak `Auth::attempt($credentials, true)` w PHP — sesja wygasła/przeglądarka
            // zamknięta nie wymusza ponownego logowania, cookie `panel_remember`
            // reautoryzuje po cichu (RememberMeServices woła się ręcznie z PanelAuthService).
            // `key` MUSI matchować [PanelRememberMeServices.key] — inaczej DSL
            // auto-generuje własny losowy key i `RememberMeAuthenticationProvider`
            // odrzuca token z "does not contain the expected key" (złapane przy weryfikacji).
            rememberMe {
                rememberMeServices = panelRememberMeServices
                key = panelRememberMeServices.key
            }
        }
        return http.build()
    }
}
