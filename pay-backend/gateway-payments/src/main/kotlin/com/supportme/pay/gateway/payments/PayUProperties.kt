package com.supportme.pay.gateway.payments

/**
 * Odpowiednik `config/payment.php` (`payu.*` + `payu_newpos.*`). Nie
 * `@ConfigurationProperties` bezpośrednio tutaj (moduł bez zależności od
 * `spring-boot`) — bindowane w module `app`, przekazywane przez konstruktor.
 */
data class PayUProperties(
    /** sandbox | production */
    val env: String,
    val merchantId: String,
    val posId: String,
    val clientId: String,
    val clientSecret: String,
    val secondKey: String,
) {
    val baseUrl: String
        get() = if (env == "production") "https://secure.payu.com" else "https://secure.snd.payu.com"

    /** PayU `client_id` domyślnie = `pos_id`, jeśli osobny client_id nie jest ustawiony. */
    val effectiveClientId: String
        get() = clientId.ifBlank { posId }
}

/** Osobna konfiguracja `payu_newpos` — używana WYŁĄCZNIE przez monitoring aktywacji (`ActivationStatusController`). */
data class PayUNewPosProperties(
    val clientId: String,
    val clientSecret: String,
)
