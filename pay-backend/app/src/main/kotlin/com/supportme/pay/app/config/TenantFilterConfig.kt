package com.supportme.pay.app.config

import com.supportme.pay.platform.tenant.TenantProperties
import com.supportme.pay.platform.tenant.TenantResolvingFilter
import org.springframework.boot.web.servlet.FilterRegistrationBean
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.Ordered

@Configuration
class TenantFilterConfig(private val tenantProperties: TenantProperties) {

    /**
     * `order = HIGHEST_PRECEDENCE` (ustawione też na filtrze samym) — musi
     * wykonać się PRZED `SecurityFilterChain` Spring Security, bo routing
     * datasource i sesja zależą od tenanta ustalonego tutaj. Dokładny
     * odpowiednik "ResolveTenant pierwszy w grupie web/api, przed sesją/CSRF".
     */
    @Bean
    fun tenantResolvingFilterRegistration(): FilterRegistrationBean<TenantResolvingFilter> =
        FilterRegistrationBean(TenantResolvingFilter(tenantProperties)).apply {
            order = Ordered.HIGHEST_PRECEDENCE
            addUrlPatterns("/*")
        }
}
