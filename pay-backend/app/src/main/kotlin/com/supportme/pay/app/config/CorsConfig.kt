package com.supportme.pay.app.config

import org.springframework.boot.context.properties.ConfigurationProperties
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.web.cors.CorsConfiguration
import org.springframework.web.cors.UrlBasedCorsConfigurationSource
import org.springframework.web.filter.CorsFilter

/**
 * Domyślnie PUSTA lista (same-origin przez nginx w produkcji — decyzja z
 * planu migracji: wszystkie ścieżki `/api/...` i front Next.js za tym samym hostem, więc CORS
 * w ogóle nie wchodzi w grę). Wypełnij `app.cors.allowed-origins`, jeśli
 * frontend Next.js jednak będzie serwowany z INNEGO originu (np. dev na
 * `localhost:3000` gadający do backendu na `localhost:8080`).
 */
@ConfigurationProperties(prefix = "app.cors")
data class CorsProperties(val allowedOrigins: List<String> = emptyList())

@Configuration
class CorsConfig(private val properties: CorsProperties) {

    @Bean
    fun corsFilter(): CorsFilter {
        val config = CorsConfiguration().apply {
            allowedOrigins = properties.allowedOrigins
            allowCredentials = properties.allowedOrigins.isNotEmpty() // sesja+cookie wymaga jawnych originów, nie "*"
            allowedHeaders = listOf("*")
            allowedMethods = listOf("GET", "POST", "PUT", "DELETE", "OPTIONS")
        }
        val source = UrlBasedCorsConfigurationSource().apply {
            registerCorsConfiguration("/**", config)
        }
        return CorsFilter(source)
    }
}
