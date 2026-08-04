package com.supportme.pay.app.config

import org.springframework.boot.autoconfigure.orm.jpa.JpaProperties
import org.springframework.boot.context.properties.EnableConfigurationProperties
import org.springframework.boot.orm.jpa.EntityManagerFactoryBuilder
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.orm.jpa.JpaVendorAdapter
import org.springframework.orm.jpa.vendor.HibernateJpaVendorAdapter

/**
 * Wykluczyliśmy `HibernateJpaAutoConfiguration`/`DataSourceAutoConfiguration`
 * (patrz [com.supportme.pay.app.PayBackendApplication]), więc `EntityManagerFactoryBuilder`
 * i `JpaProperties`, które normalnie dostarcza ta auto-konfiguracja, trzeba
 * złożyć ręcznie — używane przez [GatewayPersistenceConfig] i
 * [TenantPersistenceConfig] do zbudowania DWÓCH niezależnych
 * EntityManagerFactory z tych samych właściwości `spring.jpa.*`.
 */
@Configuration
@EnableConfigurationProperties(JpaProperties::class)
class JpaSupportConfig {

    // Dialekt bazy ustawiany przez `spring.jpa.properties.hibernate.dialect`
    // w application.yml (trafia do EntityManagerFactoryBuilder przez
    // jpaProperties.properties) — nie przez `determineDatabase(dataSource)`,
    // które wymaga żywego połączenia w momencie tworzenia beana.
    @Bean
    fun jpaVendorAdapter(jpaProperties: JpaProperties): JpaVendorAdapter =
        HibernateJpaVendorAdapter().apply {
            setShowSql(jpaProperties.isShowSql)
            setGenerateDdl(jpaProperties.isGenerateDdl)
        }

    @Bean
    fun entityManagerFactoryBuilder(
        jpaVendorAdapter: JpaVendorAdapter,
        jpaProperties: JpaProperties,
    ): EntityManagerFactoryBuilder = EntityManagerFactoryBuilder(jpaVendorAdapter, jpaProperties.properties, null)
}
