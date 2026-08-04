package com.supportme.pay.gateway.domain.entity

import jakarta.persistence.AttributeConverter
import jakarta.persistence.Converter

/** `events.type` w PHP: enum('tag_open','payment_started','payment_success','payment_failed'). */
enum class EventType(val dbValue: String) {
    TAG_OPEN("tag_open"),
    PAYMENT_STARTED("payment_started"),
    PAYMENT_SUCCESS("payment_success"),
    PAYMENT_FAILED("payment_failed"),
}

@Converter(autoApply = true)
class EventTypeConverter : AttributeConverter<EventType, String> {
    override fun convertToDatabaseColumn(attribute: EventType?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): EventType? =
        dbData?.let { value -> EventType.entries.first { it.dbValue == value } }
}
