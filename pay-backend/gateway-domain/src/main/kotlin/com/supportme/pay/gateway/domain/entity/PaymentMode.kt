package com.supportme.pay.gateway.domain.entity

import jakarta.persistence.AttributeConverter
import jakarta.persistence.Converter

/** `shops.payment_mode` i `transactions.mode` w PHP: enum('classic','app2app'). */
enum class PaymentMode(val dbValue: String) {
    CLASSIC("classic"),
    APP2APP("app2app"),
}

@Converter(autoApply = true)
class PaymentModeConverter : AttributeConverter<PaymentMode, String> {
    override fun convertToDatabaseColumn(attribute: PaymentMode?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): PaymentMode? =
        dbData?.let { value -> PaymentMode.entries.first { it.dbValue == value } }
}
