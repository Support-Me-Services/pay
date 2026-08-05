package com.supportme.pay.storefront.api.common

import com.supportme.pay.platform.http.currentRequestBaseUrl
import java.util.UUID

/**
 * `returnUrl`/`notifyUrl` wysyłane do Gateway MUSZĄ zawierać port (lokalnie
 * np. `:8080`) — budowane zawsze przez ten sam helper co `GatewayClient`
 * (`currentRequestBaseUrl`), NIGDY ręcznie z `request.scheme`+`serverName`
 * (realny bug złapany przy weryfikacji: brakujący port w `returnUrl`).
 */
fun orderReturnUrl(orderId: UUID): String = "${currentRequestBaseUrl(DEFAULT_BASE_URL)}/zwrot/$orderId"

fun gatewayNotifyUrl(): String = "${currentRequestBaseUrl(DEFAULT_BASE_URL)}/webhooks/gateway"

private const val DEFAULT_BASE_URL = "http://localhost:8080"
