package com.supportme.pay.platform.tenant

import org.springframework.boot.context.properties.ConfigurationProperties

/**
 * Odpowiednik `config/tenants.php`. Przykład w application.yml:
 *
 * tenants:
 *   default-host: pay.please-support-me.com
 *   map:
 *     please-support-me.com:
 *       module: STOREFRONT
 *       kind: church
 *       db: nfc_shop1
 *       gateway-api-key: ${GATEWAY_API_KEY_CHURCH:}
 *     pay.please-support-me.com:
 *       module: GATEWAY
 *       db: nfc_pay
 */
@ConfigurationProperties(prefix = "tenants")
data class TenantProperties(
    val defaultHost: String,
    val map: Map<String, TenantEntry>,
) {
    data class TenantEntry(
        val module: TenantModule,
        val kind: String? = null,
        val db: String,
        val gatewayApiKey: String? = null,
    )

    fun resolve(host: String?): TenantInfo {
        val entry = host?.let { map[it] } ?: map.getValue(defaultHost)
        val resolvedHost = if (host != null && map.containsKey(host)) host else defaultHost
        return TenantInfo(
            host = resolvedHost,
            module = entry.module,
            kind = entry.kind,
            db = entry.db,
            gatewayApiKey = entry.gatewayApiKey,
        )
    }
}
