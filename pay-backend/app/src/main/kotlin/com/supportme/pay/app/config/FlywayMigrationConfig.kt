package com.supportme.pay.app.config

import com.supportme.pay.platform.tenant.TenantModule
import com.supportme.pay.platform.tenant.TenantProperties
import org.flywaydb.core.Flyway
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.boot.ApplicationArguments
import org.springframework.boot.ApplicationRunner
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.core.Ordered
import org.springframework.core.annotation.Order
import javax.sql.DataSource

/**
 * Migracje uruchamiane RĘCZNIE (nie przez auto-konfigurację Spring Boot Flyway,
 * którą wykluczyliśmy — próbowałaby migrować `@Primary` datasource routujący,
 * co przy starcie poza żądaniem HTTP nie ma ustawionego tenanta i by się wywaliło).
 *
 * `common` (tabela `users`) trafia na KAŻDĄ fizyczną bazę — również na bazę
 * Gateway (`nfc_pay`), bo panel Gateway loguje się przez ten sam `User` co
 * Storefront, tylko czytany z innej fizycznej bazy (żaden model Gateway nie
 * nadpisuje connection na `users`). `tenant` (tabele Storefrontu) trafia
 * TYLKO na fizyczne bazy tenantów modułu STOREFRONT — Gateway nigdy ich nie
 * potrzebuje. Migracje z różnych lokalizacji kierowane na tę samą bazę są
 * łączone w JEDNO wywołanie `.locations(...)`, bo Flyway trzyma jedną,
 * współdzieloną tabelę `flyway_schema_history` per baza — dwa osobne wywołania
 * z zachodzącymi numerami wersji (np. dwa różne pliki "V1") skończyłyby się
 * błędem `checksum mismatch`.
 */
@Configuration
class FlywayMigrationConfig(
    private val tenantProperties: TenantProperties,
    private val tenantDataSourcePools: TenantDataSourcePools,
    @param:Qualifier("gatewayDataSource") private val gatewayDataSource: DataSource,
) {

    @Bean
    @Order(Ordered.HIGHEST_PRECEDENCE)
    fun flywayMigrationRunner(): ApplicationRunner = ApplicationRunner { _: ApplicationArguments ->
        val moduleByDb: Map<String, TenantModule> = tenantProperties.map.values.associate { it.db to it.module }

        // Baza(y) modułu GATEWAY pominięte tutaj celowo — migrowane niżej przez
        // WŁASNĄ, stałą pulę (`gatewayDataSource`), nie przez pulę routowaną,
        // żeby nie migrować tej samej fizycznej bazy dwa razy przez dwie różne
        // pule połączeń.
        moduleByDb.forEach { (db, module) ->
            if (module == TenantModule.GATEWAY) return@forEach
            val locations = mutableListOf("classpath:db/migration/common", "classpath:db/migration/tenant")
            migrate(tenantDataSourcePools.byDatabase.getValue(db), locations)
        }

        migrate(gatewayDataSource, listOf("classpath:db/migration/common", "classpath:db/migration/gateway"))
    }

    private fun migrate(dataSource: DataSource, locations: List<String>) {
        Flyway.configure()
            .dataSource(dataSource)
            .locations(*locations.toTypedArray())
            .baselineOnMigrate(true)
            .load()
            .migrate()
    }
}
