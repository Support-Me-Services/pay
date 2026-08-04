package com.supportme.pay.platform.tenant

/** Odpowiednik wpisu z `config/tenants.php` (Laravel) — jeden host => jeden tenant. */
enum class TenantModule { GATEWAY, STOREFRONT }

data class TenantInfo(
    val host: String,
    val module: TenantModule,
    val kind: String?,
    val db: String,
    val gatewayApiKey: String?,
)
