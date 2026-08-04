package com.supportme.pay.app.config

import org.springframework.boot.context.properties.ConfigurationProperties

/**
 * Fizyczne parametry połączenia (jeden serwer Postgres, wiele baz danych na
 * nim — jak dziś `nfc_shop1`/`nfc_pay` na tym samym MySQL/Cloud SQL). Nazwy
 * baz per-tenant pochodzą z [com.supportme.pay.platform.tenant.TenantProperties];
 * `gatewayDatabase` to odpowiednik stałego połączenia `gateway` w Laravelu
 * (`DB_GATEWAY_DATABASE`, zawsze `nfc_pay`).
 */
@ConfigurationProperties(prefix = "app.datasource")
data class PhysicalDatabaseProperties(
    val host: String,
    val port: Int = 5432,
    val username: String,
    val password: String,
    val driverClassName: String = "org.postgresql.Driver",
    val gatewayDatabase: String,
) {
    fun jdbcUrlFor(database: String): String = "jdbc:postgresql://$host:$port/$database"
}
