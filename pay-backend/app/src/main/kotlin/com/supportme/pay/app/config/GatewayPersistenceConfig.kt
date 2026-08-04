package com.supportme.pay.app.config

import com.supportme.pay.gateway.domain.GatewayDomainMarker
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.boot.autoconfigure.orm.jpa.JpaProperties
import org.springframework.boot.orm.jpa.EntityManagerFactoryBuilder
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.data.jpa.repository.config.EnableJpaRepositories
import org.springframework.orm.jpa.JpaTransactionManager
import org.springframework.orm.jpa.LocalContainerEntityManagerFactoryBean
import org.springframework.transaction.PlatformTransactionManager
import javax.sql.DataSource

/**
 * Persistence unit "gateway" — ZAWSZE `nfc_pay`, niezależnie od tenanta
 * bieżącego żądania. Odpowiednik `protected $connection = 'gateway'` na
 * wszystkich modelach Eloquent modułu Gateway. Encje/repozytoria trafiają
 * do modułu `gateway-domain` w Fazie 1; ten moduł fizycznie nie widzi
 * routowanego datasource Storefrontu (patrz [TenantPersistenceConfig]),
 * więc pomyłkowe wstrzyknięcie złego repozytorium jest błędem kompilacji,
 * nie bugiem runtime jak dziś mogłoby być w PHP.
 */
@Configuration
@EnableJpaRepositories(
    basePackageClasses = [GatewayDomainMarker::class],
    entityManagerFactoryRef = "gatewayEntityManagerFactory",
    transactionManagerRef = "gatewayTransactionManager",
)
class GatewayPersistenceConfig(
    private val entityManagerFactoryBuilder: EntityManagerFactoryBuilder,
    @param:Qualifier("gatewayDataSource") private val gatewayDataSource: DataSource,
    private val jpaProperties: JpaProperties,
) {

    @Bean
    fun gatewayEntityManagerFactory(): LocalContainerEntityManagerFactoryBean =
        entityManagerFactoryBuilder
            .dataSource(gatewayDataSource)
            .packages(GatewayDomainMarker::class.java)
            .persistenceUnit("gateway")
            .properties(jpaProperties.properties)
            .build()

    @Bean
    fun gatewayTransactionManager(): PlatformTransactionManager =
        JpaTransactionManager(gatewayEntityManagerFactory().`object`!!)
}
