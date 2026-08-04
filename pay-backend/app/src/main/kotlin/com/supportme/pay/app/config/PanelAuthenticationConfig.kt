package com.supportme.pay.app.config

import com.supportme.pay.storefront.domain.auth.TenantUserDetailsService
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.security.authentication.AuthenticationManager
import org.springframework.security.authentication.ProviderManager
import org.springframework.security.authentication.dao.DaoAuthenticationProvider
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.security.web.context.HttpSessionSecurityContextRepository
import org.springframework.security.web.context.SecurityContextRepository

/**
 * Infrastruktura auth współdzielona przez OBA panele (Gateway i Storefront) —
 * jeden `AuthenticationManager` wspierający na [TenantUserDetailsService],
 * które i tak czyta z aktualnie routowanej (per host) fizycznej bazy. Żyje
 * w module `app`, bo to jedyny moduł widzący zarówno `gateway-api` jak
 * i `storefront-api`.
 *
 * `BCryptPasswordEncoder` — kompatybilny z Laravel `'password' => 'hashed'`
 * (bcrypt), więc zmigrowane hashe haseł adminów nie wymuszają resetu.
 */
@Configuration
class PanelAuthenticationConfig(
    private val tenantUserDetailsService: TenantUserDetailsService,
) {

    @Bean
    fun passwordEncoder(): PasswordEncoder = BCryptPasswordEncoder()

    @Bean
    fun panelAuthenticationProvider(passwordEncoder: PasswordEncoder): DaoAuthenticationProvider {
        // Spring Security 6.3+: konstruktor bierze PasswordEncoder, UserDetailsService
        // ustawiane osobnym setterem (stary konstruktor `DaoAuthenticationProvider(uds)` usunięty).
        val provider = DaoAuthenticationProvider(passwordEncoder)
        provider.setUserDetailsService(tenantUserDetailsService)
        return provider
    }

    @Bean
    fun authenticationManager(provider: DaoAuthenticationProvider): AuthenticationManager = ProviderManager(provider)

    /** Jawny zapis SecurityContext do sesji HTTP przy programistycznym logowaniu (poza formLogin). */
    @Bean
    fun securityContextRepository(): SecurityContextRepository = HttpSessionSecurityContextRepository()
}
