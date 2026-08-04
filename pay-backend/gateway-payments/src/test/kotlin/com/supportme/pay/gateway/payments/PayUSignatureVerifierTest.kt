package com.supportme.pay.gateway.payments

import java.security.MessageDigest
import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertTrue

class PayUSignatureVerifierTest {

    private val secondKey = "test-second-key"
    private val body = """{"order":{"orderId":"ABC123","extOrderId":"tx-1","status":"COMPLETED"}}"""

    private fun signatureHeader(body: String, secondKey: String, algorithm: String, digestName: String): String {
        val digest = MessageDigest.getInstance(digestName).digest((body + secondKey).toByteArray(Charsets.UTF_8))
        val hex = digest.joinToString("") { "%02x".format(it) }
        return "sender=test;signature=$hex;algorithm=$algorithm;content=DOCUMENT"
    }

    @Test
    fun `accepts valid MD5 signature (default algorithm)`() {
        val header = signatureHeader(body, secondKey, "MD5", "MD5")
        assertTrue(PayUSignatureVerifier.verify(body, header, secondKey))
    }

    @Test
    fun `accepts valid SHA-256 signature`() {
        val header = signatureHeader(body, secondKey, "SHA-256", "SHA-256")
        assertTrue(PayUSignatureVerifier.verify(body, header, secondKey))
    }

    @Test
    fun `accepts valid SHA-1 signature`() {
        val header = signatureHeader(body, secondKey, "SHA-1", "SHA-1")
        assertTrue(PayUSignatureVerifier.verify(body, header, secondKey))
    }

    @Test
    fun `rejects tampered body`() {
        val header = signatureHeader(body, secondKey, "MD5", "MD5")
        assertFalse(PayUSignatureVerifier.verify(body + "tampered", header, secondKey))
    }

    @Test
    fun `rejects wrong second key`() {
        val header = signatureHeader(body, secondKey, "MD5", "MD5")
        assertFalse(PayUSignatureVerifier.verify(body, header, "wrong-key"))
    }

    @Test
    fun `rejects missing header`() {
        assertFalse(PayUSignatureVerifier.verify(body, null, secondKey))
    }

    @Test
    fun `rejects blank second key even with matching header format`() {
        val header = signatureHeader(body, "", "MD5", "MD5")
        assertFalse(PayUSignatureVerifier.verify(body, header, ""))
    }

    @Test
    fun `extracts extOrderId and status from webhook body`() {
        val json = com.fasterxml.jackson.databind.ObjectMapper().readTree(body)
        kotlin.test.assertEquals("tx-1", PayUSignatureVerifier.extractExtOrderId(json))
        kotlin.test.assertEquals("COMPLETED", PayUSignatureVerifier.extractOrderStatus(json))
    }
}
