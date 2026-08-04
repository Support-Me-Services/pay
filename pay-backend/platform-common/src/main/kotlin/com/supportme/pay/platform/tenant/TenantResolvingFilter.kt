package com.supportme.pay.platform.tenant

import jakarta.servlet.FilterChain
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.core.Ordered
import org.springframework.web.filter.OncePerRequestFilter

/**
 * Odpowiednik `App\Http\Middleware\ResolveTenant` — MUSI działać przed sesją/
 * bezpieczeństwem (stąd HIGHEST_PRECEDENCE), bo routing datasource zależy od
 * tenanta ustalonego tutaj. W przeciwieństwie do PHP nie przełączamy fizycznie
 * połączenia (`purge`) — TenantContext jest tylko odczytywany przez
 * TenantRoutingDataSource przy wyborze puli połączeń.
 */
class TenantResolvingFilter(
    private val tenantProperties: TenantProperties,
) : OncePerRequestFilter(), Ordered {

    override fun getOrder(): Int = Ordered.HIGHEST_PRECEDENCE

    override fun doFilterInternal(
        request: HttpServletRequest,
        response: HttpServletResponse,
        filterChain: FilterChain,
    ) {
        val tenant = tenantProperties.resolve(request.serverName)
        TenantContext.set(tenant)
        try {
            filterChain.doFilter(request, response)
        } finally {
            TenantContext.clear()
        }
    }
}
