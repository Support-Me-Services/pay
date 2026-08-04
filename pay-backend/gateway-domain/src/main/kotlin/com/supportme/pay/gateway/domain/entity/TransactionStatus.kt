package com.supportme.pay.gateway.domain.entity

import jakarta.persistence.AttributeConverter
import jakarta.persistence.Converter

/**
 * `transactions.status` w PHP: enum('created','pending','paid','failed','abandoned').
 * `ABANDONED` jest zachowany w schemacie mimo że żaden serwis go nie ustawia —
 * świadomie martwy stan, zgodnie z dzisiejszym zachowaniem (patrz plan migracji).
 */
enum class TransactionStatus(val dbValue: String) {
    CREATED("created"),
    PENDING("pending"),
    PAID("paid"),
    FAILED("failed"),
    ABANDONED("abandoned"),
    ;

    /** Odpowiednik `Transaction::isFinal()`. */
    fun isFinal(): Boolean = this == PAID || this == FAILED || this == ABANDONED
}

@Converter(autoApply = true)
class TransactionStatusConverter : AttributeConverter<TransactionStatus, String> {
    override fun convertToDatabaseColumn(attribute: TransactionStatus?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): TransactionStatus? =
        dbData?.let { value -> TransactionStatus.entries.first { it.dbValue == value } }
}
