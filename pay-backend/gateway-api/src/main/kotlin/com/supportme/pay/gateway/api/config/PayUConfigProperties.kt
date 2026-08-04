package com.supportme.pay.gateway.api.config

import org.springframework.boot.context.properties.ConfigurationProperties

/** Odpowiednik `config/payment.php` -> `payu.*`. */
@ConfigurationProperties(prefix = "payu")
data class PayUConfigProperties(
    val env: String = "sandbox",
    val merchantId: String = "",
    val posId: String = "",
    val clientId: String = "",
    val clientSecret: String = "",
    val secondKey: String = "",
)

/** Odpowiednik `payu_newpos.*` — WYŁĄCZNIE dla `ActivationStatusController`. */
@ConfigurationProperties(prefix = "payu-newpos")
data class PayUNewPosConfigProperties(
    val clientId: String = "",
    val clientSecret: String = "",
)

/**
 * Odpowiednik `config('payment.provider')`/`PAYMENT_PROVIDER` env. Pola
 * `bypass`/`returnBypass` (używane przez Storefront, nie Gateway) mają
 * ANALOGICZNĄ, osobną klasę w `storefront-api` (Faza 4) — nie tutaj.
 */
@ConfigurationProperties(prefix = "payment")
data class PaymentConfigProperties(
    val provider: String = "mock",
)
