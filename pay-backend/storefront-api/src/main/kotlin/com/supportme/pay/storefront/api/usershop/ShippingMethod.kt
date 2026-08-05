package com.supportme.pay.storefront.api.usershop

/** Odpowiednik `config('shipping.methods')` — dziś tylko `pickup` aktywny, reszta "wkrótce". */
data class ShippingMethod(val code: String, val label: String, val priceGrosze: Int, val enabled: Boolean, val requiresPoint: Boolean)

object ShippingMethods {
    val ALL = listOf(
        ShippingMethod("pickup", "Odbiór osobisty", 0, enabled = true, requiresPoint = false),
        ShippingMethod("inpost_locker", "Paczkomat InPost", 1200, enabled = false, requiresPoint = true),
        ShippingMethod("orlen", "Orlen Paczka", 900, enabled = false, requiresPoint = true),
        ShippingMethod("courier_inpost", "Kurier InPost", 1500, enabled = false, requiresPoint = false),
        ShippingMethod("courier_dpd", "Kurier DPD", 1500, enabled = false, requiresPoint = false),
        ShippingMethod("courier_dhl", "Kurier DHL", 1500, enabled = false, requiresPoint = false),
    )

    fun find(code: String): ShippingMethod? = ALL.firstOrNull { it.code == code }
}
