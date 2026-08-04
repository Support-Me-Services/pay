package com.supportme.pay.app

import org.springframework.boot.autoconfigure.SpringBootApplication
import org.springframework.boot.autoconfigure.jdbc.DataSourceAutoConfiguration
import org.springframework.boot.autoconfigure.orm.jpa.HibernateJpaAutoConfiguration
import org.springframework.boot.runApplication
import org.springframework.boot.context.properties.ConfigurationPropertiesScan

/**
 * Root Spring Boot. DataSource'y i EntityManagerFactory dla obu persistence
 * unitów (gateway, tenant) konfigurowane ręcznie w
 * [com.supportme.pay.app.config] — dlatego wykluczamy domyślną,
 * jednodatasource'ową auto-konfigurację Spring Boota, która by kolidowała
 * z naszymi dwoma.
 */
@SpringBootApplication(
    scanBasePackages = ["com.supportme.pay"],
    exclude = [DataSourceAutoConfiguration::class, HibernateJpaAutoConfiguration::class],
)
@ConfigurationPropertiesScan("com.supportme.pay")
class PayBackendApplication

fun main(args: Array<String>) {
    runApplication<PayBackendApplication>(*args)
}
