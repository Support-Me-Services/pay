package com.supportme.pay.gateway.payments

/**
 * Trzy warianty payloadu PayU, odwzorowujące dokładną kolejność warunków
 * z `PayUProvider::createTransaction` w PHP: `pbl` -> `blik_code` -> `classic`
 * (domyślny). Kolejność MA znaczenie biznesowe — konsumenci tego typu
 * (`when` w [PayUProvider]) muszą sprawdzać w tej samej kolejności.
 */
sealed interface PaymentContext {
    data object None : PaymentContext
    data class Pbl(val bankCode: String) : PaymentContext
    data class BlikCode(val code: String) : PaymentContext
    data object Classic : PaymentContext
}
