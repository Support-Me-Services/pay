package com.supportme.pay.platform.http

import org.springframework.web.context.request.RequestContextHolder
import org.springframework.web.context.request.ServletRequestAttributes

/**
 * Odpowiednik `GatewayClient::baseUrl()` w Laravelu — bazowy URL wyprowadzony
 * z HOSTA BIEŻĄCEGO ŻĄDANIA, nie ze stałej skonfigurowanej subdomeny. Pozwala
 * to klientowi płatności zostać na domenie sklepu (`please-support-me.com/pay/...`)
 * zamiast być przekierowanym na `pay.please-support-me.com`.
 *
 * @param fallback używany w kontekście CLI/scheduled job, gdzie nie ma żądania HTTP
 *   (odpowiednik `config('shop.gateway_url')` używanego przez GatewayClient poza web).
 */
fun currentRequestBaseUrl(fallback: String): String {
    val attrs = RequestContextHolder.getRequestAttributes() as? ServletRequestAttributes
        ?: return fallback
    val request = attrs.request
    val scheme = request.getHeader("X-Forwarded-Proto") ?: request.scheme
    return "$scheme://${request.serverName}"
}
