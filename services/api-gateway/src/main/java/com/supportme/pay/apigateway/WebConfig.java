package com.supportme.pay.apigateway;

import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

/**
 * CORS dla tras REST — to jedyne miejsce w ekosystemie, gdzie przeglądarka
 * (web, CSR) woła serwer spoza swojego originu bezpośrednio. Origin Next.js
 * dev servera na sztywno, bo to PoC Fazy 2 — do rozszerzenia listą per
 * środowisko (prod/stage), gdy web przestanie być szkieletem.
 */
@Configuration
public class WebConfig implements WebMvcConfigurer {

    @Override
    public void addCorsMappings(CorsRegistry registry) {
        registry.addMapping("/api/**")
                .allowedOrigins("http://localhost:3000")
                .allowedMethods("GET", "POST", "PUT", "DELETE", "OPTIONS");
    }
}
