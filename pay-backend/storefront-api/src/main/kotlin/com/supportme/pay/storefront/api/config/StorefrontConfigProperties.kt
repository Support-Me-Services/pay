package com.supportme.pay.storefront.api.config

import org.springframework.boot.context.properties.ConfigurationProperties

/** Odpowiednik `config('shop.*')` — właściciel katalogu sklepu firmowego (`CompanyStoreController`). */
@ConfigurationProperties(prefix = "shop")
data class StorefrontShopProperties(val mainAccountHandle: String = "lula-marcin")

/**
 * Odpowiednik `config('payment.bypass'/'payment.return_bypass')` — omija
 * realną płatność, gdy zatwierdzenie merchanta PayU jest w toku. Osobna
 * klasa od `gateway-api`'s `PaymentConfigProperties` (inny zestaw pól),
 * obie mogą bindować się do TEGO SAMEGO prefiksu `payment.*` niezależnie.
 */
@ConfigurationProperties(prefix = "payment")
data class PaymentBypassProperties(val bypass: Boolean = false, val returnBypass: Boolean = false)
