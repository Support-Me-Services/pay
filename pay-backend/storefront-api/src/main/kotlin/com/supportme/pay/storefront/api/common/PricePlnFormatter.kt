package com.supportme.pay.storefront.api.common

import java.text.DecimalFormat
import java.text.DecimalFormatSymbols
import java.util.Locale

/**
 * Jak `number_format($grosze/100, 2, ',', ' ')` w PHP (`Product::pricePln()`,
 * `Order::amountPln()`) — jawne `DecimalFormatSymbols`, NIE domyślny
 * separator locale `pl_PL` (bywa NBSP w Javie zamiast zwykłej spacji).
 */
object PricePlnFormatter {
    private val FORMAT = DecimalFormat(
        "#,##0.00",
        DecimalFormatSymbols(Locale.of("pl", "PL")).apply {
            groupingSeparator = ' '
            decimalSeparator = ','
        },
    )

    fun format(grosze: Int): String = FORMAT.format(grosze / 100.0)
}
