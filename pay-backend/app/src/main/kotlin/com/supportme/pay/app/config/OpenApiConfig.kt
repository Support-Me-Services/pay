package com.supportme.pay.app.config

import io.swagger.v3.oas.models.OpenAPI
import io.swagger.v3.oas.models.info.Info
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration

/** Dostępne pod `/v3/api-docs` (JSON) i `/swagger-ui.html` — żywy kontrakt dla frontu Next.js. */
@Configuration
class OpenApiConfig {

    @Bean
    fun payBackendOpenApi(): OpenAPI = OpenAPI().info(
        Info()
            .title("pay/SupportME — Kotlin backend")
            .version("0.1.0-SNAPSHOT")
            .description("REST API dla paneli Gateway/Storefront, płatności PayU i publicznych stron sklepu."),
    )
}
