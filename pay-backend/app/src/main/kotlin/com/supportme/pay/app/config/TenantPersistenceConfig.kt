package com.supportme.pay.app.config

import com.supportme.pay.storefront.domain.StorefrontDomainMarker
import org.springframework.boot.autoconfigure.orm.jpa.JpaProperties
import org.springframework.boot.orm.jpa.EntityManagerFactoryBuilder
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.context.annotation.Primary
import org.springframework.data.jpa.repository.config.EnableJpaRepositories
import org.springframework.orm.jpa.JpaTransactionManager
import org.springframework.orm.jpa.LocalContainerEntityManagerFactoryBean
import org.springframework.transaction.PlatformTransactionManager
import javax.sql.DataSource

/**
 * Persistence unit "tenant" — na datasource ROUTOWANYM per host
 * ([TenantRoutingDataSource]). Odpowiednik połączenia domyślnego (`mysql`)
 * w Laravelu, przełączanego przez `ResolveTenant`. Hostuje encje/repozytoria
 * Storefrontu (User, ShopItem, Product, Order, ...) — trafiają do modułu
 * `storefront-domain` w Fazie 1. Oznaczony @Primary, żeby zwykłe
 * `@Transactional` (bez jawnego managera) domyślnie trafiało tutaj —
 * kod Gateway musi jawnie użyć `@Transactional("gatewayTransactionManager")`.
 */
@Configuration
@EnableJpaRepositories(
    basePackageClasses = [StorefrontDomainMarker::class],
    entityManagerFactoryRef = "tenantEntityManagerFactory",
    transactionManagerRef = "tenantTransactionManager",
)
class TenantPersistenceConfig(
    private val entityManagerFactoryBuilder: EntityManagerFactoryBuilder,
    private val tenantRoutingDataSource: DataSource,
    private val jpaProperties: JpaProperties,
) {

    @Bean
    @Primary
    fun tenantEntityManagerFactory(): LocalContainerEntityManagerFactoryBean =
        entityManagerFactoryBuilder
            .dataSource(tenantRoutingDataSource)
            .packages(StorefrontDomainMarker::class.java)
            .persistenceUnit("tenant")
            .properties(jpaProperties.properties)
            .build()

    @Bean
    @Primary
    fun tenantTransactionManager(): PlatformTransactionManager =
        JpaTransactionManager(tenantEntityManagerFactory().`object`!!)
}
