package com.supportme.pay.app

import org.springframework.boot.autoconfigure.SpringBootApplication
import org.springframework.boot.autoconfigure.flyway.FlywayAutoConfiguration
import org.springframework.boot.autoconfigure.jdbc.DataSourceAutoConfiguration
import org.springframework.boot.autoconfigure.orm.jpa.HibernateJpaAutoConfiguration
import org.springframework.boot.runApplication
import org.springframework.boot.context.properties.ConfigurationPropertiesScan

/**
 * Root Spring Boot. DataSource'y i EntityManagerFactory dla obu persistence
 * unitów (gateway, tenant) konfigurowane ręcznie w
 * [com.supportme.pay.app.config] — dlatego wykluczamy domyślną,
 * jednodatasource'ową auto-konfigurację Spring Boota (DataSource/JPA/Flyway),
 * która by kolidowała z naszymi dwoma persistence unitami (patrz
 * [com.supportme.pay.app.config.FlywayMigrationConfig] — Flyway auto-config
 * próbowałaby migrować `@Primary` datasource routujący, który poza żądaniem
 * HTTP nie ma ustawionego tenanta).
 */
@SpringBootApplication(
    scanBasePackages = ["com.supportme.pay"],
    exclude = [DataSourceAutoConfiguration::class, HibernateJpaAutoConfiguration::class, FlywayAutoConfiguration::class],
)
@ConfigurationPropertiesScan("com.supportme.pay")
class PayBackendApplication

fun main(args: Array<String>) {
    runApplication<PayBackendApplication>(*args)
}
