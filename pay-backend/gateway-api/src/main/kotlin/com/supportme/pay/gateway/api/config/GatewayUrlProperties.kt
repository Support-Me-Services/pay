package com.supportme.pay.gateway.api.config

import org.springframework.boot.context.properties.ConfigurationProperties

/**
 * Odpowiednik `config('app.url')` (Laravel `APP_URL`) użytego w
 * `route('webhooks.payu')` — PayU wywołuje ten adres z zewnątrz, więc MUSI
 * być stały, skonfigurowany, NIE wyprowadzany z hosta aktualnego żądania
 * (transakcję może zainicjować Storefront wywołaniem server-to-server,
 * z zupełnie innym hostem w kontekście niż publiczny adres bramki).
 */
@ConfigurationProperties(prefix = "gateway")
data class GatewayUrlProperties(val publicBaseUrl: String = "http://localhost:8080")
