package com.supportme.pay.gateway.payments

import java.security.MessageDigest

/**
 * Odpowiednik weryfikacji `OpenPayu-Signature` w `PayUProvider::handleWebhook`.
 * Nagłówek: `sender=...;signature=...;algorithm=MD5|SHA-256|SHA-1;content=DOCUMENT`
 * (semicolon-delimited, klucze case-insensitive). Oczekiwany podpis =
 * hash(body + secondKey), porównanie TIMING-SAFE (`MessageDigest.isEqual`,
 * odpowiednik `hash_equals` w PHP — zwykłe `==`/`.equals()` na Stringu NIE
 * jest bezpieczne czasowo i nie wolno go tu użyć).
 */
object PayUSignatureVerifier {

    fun verify(body: String, signatureHeader: String?, secondKey: String): Boolean {
        if (signatureHeader.isNullOrBlank() || secondKey.isBlank()) return false

        val parts = signatureHeader.split(";").mapNotNull { part ->
            val idx = part.indexOf('=')
            if (idx < 0) null else part.substring(0, idx).trim().lowercase() to part.substring(idx + 1).trim()
        }.toMap()

        val signature = parts["signature"] ?: return false
        val digestAlgorithm = when (parts["algorithm"]?.uppercase()) {
            "SHA-256", "SHA256" -> "SHA-256"
            "SHA-1", "SHA1" -> "SHA-1"
            else -> "MD5"
        }

        val digest = MessageDigest.getInstance(digestAlgorithm)
        val expectedBytes = digest.digest((body + secondKey).toByteArray(Charsets.UTF_8))
        val expectedHex = expectedBytes.joinToString("") { "%02x".format(it) }

        return MessageDigest.isEqual(
            expectedHex.toByteArray(Charsets.UTF_8),
            signature.toByteArray(Charsets.UTF_8),
        )
    }

    /** Wyciąga `order.extOrderId` z body webhooka PayU (JSON), bez pełnego parsowania DTO. */
    fun extractExtOrderId(bodyJson: com.fasterxml.jackson.databind.JsonNode): String? =
        bodyJson.path("order").path("extOrderId").takeIf { it.isTextual }?.asText()

    fun extractOrderStatus(bodyJson: com.fasterxml.jackson.databind.JsonNode): String? =
        bodyJson.path("order").path("status").takeIf { it.isTextual }?.asText()
}
