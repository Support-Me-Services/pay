package com.supportme.pay.app.config

import com.supportme.pay.storefront.domain.auth.PanelRememberMeServices
import com.supportme.pay.storefront.domain.auth.TenantUserDetailsService
import com.supportme.pay.storefront.domain.repository.UserRepository
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.security.authentication.AuthenticationManager
import org.springframework.security.authentication.ProviderManager
import org.springframework.security.authentication.RememberMeAuthenticationProvider
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

    /** Osobny provider dla tokenów remember-me — `DaoAuthenticationProvider` nie akceptowałby ich (oczekuje weryfikacji hasła). */
    @Bean
    fun rememberMeAuthenticationProvider(): RememberMeAuthenticationProvider = RememberMeAuthenticationProvider(REMEMBER_ME_KEY)

    @Bean
    fun authenticationManager(provider: DaoAuthenticationProvider, rememberMeProvider: RememberMeAuthenticationProvider): AuthenticationManager =
        ProviderManager(listOf(provider, rememberMeProvider))

    /** Jawny zapis SecurityContext do sesji HTTP przy programistycznym logowaniu (poza formLogin). */
    @Bean
    fun securityContextRepository(): SecurityContextRepository = HttpSessionSecurityContextRepository()

    /**
     * Współdzielony przez oba panele (jak [TenantUserDetailsService]) — jedna
     * fizyczna tabela `users`, jeden mechanizm remember-me niezależnie od hosta.
     */
    @Bean
    fun panelRememberMeServices(userRepository: UserRepository): PanelRememberMeServices =
        PanelRememberMeServices(userRepository, REMEMBER_ME_KEY)

    companion object {
        /** Sekret integralności `RememberMeAuthenticationToken` Springa — NIE zabezpiecza samego cookie (to robi porównanie z `users.remember_token`). */
        private const val REMEMBER_ME_KEY = "panel-remember-me-token-key"
    }
}
