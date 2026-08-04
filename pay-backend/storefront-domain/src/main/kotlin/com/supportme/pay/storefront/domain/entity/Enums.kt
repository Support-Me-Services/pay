package com.supportme.pay.storefront.domain.entity

import jakarta.persistence.AttributeConverter
import jakarta.persistence.Converter

/**
 * Wszystkie enumy Storefrontu w jednym pliku — każdy to prosty odpowiednik
 * kolumny `varchar` + `CHECK` w Postgresie (nie enum natywny, dla elastyczności
 * jak w MySQL `enum` czytanym jako string przez Eloquent). Wzorzec konwertera
 * jak w `gateway-domain` (patrz `PaymentMode`/`TransactionStatus`).
 */

enum class ProductStatus(val dbValue: String) {
    KONTAKT("kontakt"),
    TEST("test"),
    WDROZENIE("wdrozenie"),
    AKTYWNA("aktywna"),
}

@Converter(autoApply = true)
class ProductStatusConverter : AttributeConverter<ProductStatus, String> {
    override fun convertToDatabaseColumn(attribute: ProductStatus?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): ProductStatus? =
        dbData?.let { value -> ProductStatus.entries.first { it.dbValue == value } }
}

enum class OrderStatus(val dbValue: String) {
    PENDING("pending"),
    PAID("paid"),
    FAILED("failed"),
}

@Converter(autoApply = true)
class OrderStatusConverter : AttributeConverter<OrderStatus, String> {
    override fun convertToDatabaseColumn(attribute: OrderStatus?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): OrderStatus? =
        dbData?.let { value -> OrderStatus.entries.first { it.dbValue == value } }
}

enum class StorefrontEventType(val dbValue: String) {
    TAG_OPEN("tag_open"),
    PAGE_VIEW("page_view"),
    BUY_CLICK("buy_click"),
    PURCHASE("purchase"),
}

@Converter(autoApply = true)
class StorefrontEventTypeConverter : AttributeConverter<StorefrontEventType, String> {
    override fun convertToDatabaseColumn(attribute: StorefrontEventType?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): StorefrontEventType? =
        dbData?.let { value -> StorefrontEventType.entries.first { it.dbValue == value } }
}

enum class JobApplicationStatus(val dbValue: String) {
    PENDING("pending"),
    ACCEPTED("accepted"),
    REJECTED("rejected"),
}

@Converter(autoApply = true)
class JobApplicationStatusConverter : AttributeConverter<JobApplicationStatus, String> {
    override fun convertToDatabaseColumn(attribute: JobApplicationStatus?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): JobApplicationStatus? =
        dbData?.let { value -> JobApplicationStatus.entries.first { it.dbValue == value } }
}

enum class ParishNoteType(val dbValue: String) {
    KONTAKT("kontakt"),
    TELEFON("telefon"),
    MAIL("mail"),
    SPOTKANIE("spotkanie"),
    INNE("inne"),
}

@Converter(autoApply = true)
class ParishNoteTypeConverter : AttributeConverter<ParishNoteType, String> {
    override fun convertToDatabaseColumn(attribute: ParishNoteType?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): ParishNoteType? =
        dbData?.let { value -> ParishNoteType.entries.first { it.dbValue == value } }
}

enum class CategorySource(val dbValue: String) {
    NONE("none"),
    PARISHES("parishes"),
    BENEFICIARIES("beneficiaries"),
}

@Converter(autoApply = true)
class CategorySourceConverter : AttributeConverter<CategorySource, String> {
    override fun convertToDatabaseColumn(attribute: CategorySource?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): CategorySource? =
        dbData?.let { value -> CategorySource.entries.first { it.dbValue == value } }
}

enum class PotentialParishStatus(val dbValue: String) {
    NOWA("nowa"),
    DO_OBDZWONIENIA("do_obdzwonienia"),
    ZADZWONIONO("zadzwoniono"),
    ZAINTERESOWANA("zainteresowana"),
    ODRZUCONA("odrzucona"),
    DODANA("dodana"),
}

@Converter(autoApply = true)
class PotentialParishStatusConverter : AttributeConverter<PotentialParishStatus, String> {
    override fun convertToDatabaseColumn(attribute: PotentialParishStatus?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): PotentialParishStatus? =
        dbData?.let { value -> PotentialParishStatus.entries.first { it.dbValue == value } }
}

/** `image_side`/`text_align` w BeneficiaryNode — proste dwuwartościowe pola, bez osobnego typu w PHP. */
enum class Side(val dbValue: String) {
    LEFT("left"),
    RIGHT("right"),
    CENTER("center"), // text_align dopuszcza też "center"
}

@Converter(autoApply = false)
class SideConverter : AttributeConverter<Side, String> {
    override fun convertToDatabaseColumn(attribute: Side?): String? = attribute?.dbValue
    override fun convertToEntityAttribute(dbData: String?): Side? =
        dbData?.let { value -> Side.entries.first { it.dbValue == value } }
}
