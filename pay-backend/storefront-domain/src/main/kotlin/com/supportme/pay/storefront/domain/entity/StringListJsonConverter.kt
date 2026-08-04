package com.supportme.pay.storefront.domain.entity

import com.fasterxml.jackson.databind.ObjectMapper
import jakarta.persistence.AttributeConverter
import jakarta.persistence.Converter

/** Odpowiednik Eloquent `'voivodeships' => 'array'` cast — JSON w kolumnie TEXT. */
@Converter
class StringListJsonConverter : AttributeConverter<List<String>?, String?> {
    private val mapper = ObjectMapper()

    override fun convertToDatabaseColumn(attribute: List<String>?): String? =
        attribute?.let { mapper.writeValueAsString(it) }

    override fun convertToEntityAttribute(dbData: String?): List<String>? =
        dbData?.let { mapper.readValue(it, mapper.typeFactory.constructCollectionType(List::class.java, String::class.java)) }
}
