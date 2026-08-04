package com.supportme.pay.storefront.domain.auth

import com.supportme.pay.storefront.domain.entity.User
import com.supportme.pay.storefront.domain.repository.UserRepository
import org.springframework.security.core.GrantedAuthority
import org.springframework.security.core.authority.SimpleGrantedAuthority
import org.springframework.security.core.userdetails.UserDetails
import org.springframework.security.core.userdetails.UserDetailsService
import org.springframework.security.core.userdetails.UsernameNotFoundException
import org.springframework.stereotype.Service

/**
 * Principal owijający encję `User`. Laravel nie ma pojęcia ról — jeden model
 * `User`, jedna fizyczna baza per host — więc jeden stały authority
 * wystarcza (autoryzacja "kto widzi co" i tak wynika z tego, KTÓRA fizyczna
 * baza została rozwiązana dla danego hosta, nie z ról w tej samej bazie).
 */
class TenantPrincipal(val user: User) : UserDetails {
    override fun getAuthorities(): Collection<GrantedAuthority> = AUTHORITIES

    override fun getPassword(): String = user.password

    override fun getUsername(): String = user.email

    override fun isAccountNonExpired() = true
    override fun isAccountNonLocked() = true
    override fun isCredentialsNonExpired() = true
    override fun isEnabled() = true

    companion object {
        private val AUTHORITIES = listOf(SimpleGrantedAuthority("ROLE_ADMIN"))
    }
}

/**
 * Współdzielony przez OBA panele (Gateway i Storefront) — odpowiednik tego,
 * że `Auth::attempt()` w Laravelu zawsze czyta ten sam model `User`,
 * niezależnie od hosta; to KTÓRA fizyczna baza jest aktywna (routing per
 * tenant) decyduje, czyj wiersz `users` zostanie znaleziony.
 */
@Service
class TenantUserDetailsService(private val userRepository: UserRepository) : UserDetailsService {
    override fun loadUserByUsername(username: String): UserDetails =
        userRepository.findByEmail(username)?.let { TenantPrincipal(it) }
            ?: throw UsernameNotFoundException("Nie znaleziono użytkownika: $username")
}
