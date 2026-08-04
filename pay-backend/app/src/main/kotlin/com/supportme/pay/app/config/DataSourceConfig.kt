package com.supportme.pay.app.config

import com.supportme.pay.platform.tenant.TenantProperties
import com.zaxxer.hikari.HikariConfig
import com.zaxxer.hikari.HikariDataSource
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.context.annotation.Primary
import javax.sql.DataSource

/**
 * Buduje wszystkie fizyczne pule połączeń (jedna per baza danych) oraz
 * datasource routujący między nimi po tenancie ([TenantRoutingDataSource]) i
 * osobny, stały datasource "gateway" — dokładny odpowiednik dwóch połączeń
 * (`mysql` przełączane per-request przez ResolveTenant, oraz `gateway`
 * zawsze na `nfc_pay`) współistniejących w Laravelu w tym samym procesie.
 */
@Configuration
class DataSourceConfig(
    private val tenantProperties: TenantProperties,
    private val dbProps: PhysicalDatabaseProperties,
) {

    private fun buildPool(database: String): DataSource {
        val config = HikariConfig().apply {
            jdbcUrl = dbProps.jdbcUrlFor(database)
            username = dbProps.username
            password = dbProps.password
            driverClassName = dbProps.driverClassName
            poolName = "hikari-$database"
        }
        return HikariDataSource(config)
    }

    @Bean
    @Primary
    fun tenantRoutingDataSource(): DataSource {
        val physicalDatabases = tenantProperties.map.values.map { it.db }.toSet()
        val pools: Map<String, DataSource> = physicalDatabases.associateWith { db -> buildPool(db) }

        val defaultDb = tenantProperties.map.getValue(tenantProperties.defaultHost).db

        val routingDataSource = TenantRoutingDataSource()
        routingDataSource.setTargetDataSources(pools as Map<Any, Any>)
        routingDataSource.setDefaultTargetDataSource(pools.getValue(defaultDb))
        routingDataSource.afterPropertiesSet()
        return routingDataSource
    }

    @Bean
    @Qualifier("gatewayDataSource")
    fun gatewayDataSource(): DataSource = buildPool(dbProps.gatewayDatabase)
}
