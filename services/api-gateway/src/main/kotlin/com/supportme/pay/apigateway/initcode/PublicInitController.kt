package com.supportme.pay.apigateway.initcode

import io.grpc.StatusRuntimeException
import jakarta.servlet.http.HttpServletRequest
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.beans.factory.annotation.Value
import org.springframework.http.HttpStatus
import org.springframework.http.MediaType
import org.springframework.http.ResponseEntity
import org.slf4j.LoggerFactory
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.RestController
import pay.initcode.v1.InitCodeServiceGrpc
import pay.initcode.v1.ResolveRequest
import pay.initcode.v1.ResolveResponse
import pay.storefront.v1.ResolveRedirectTargetRequest
import pay.storefront.v1.StorefrontServiceGrpc
import java.net.URI
import java.util.concurrent.TimeUnit

/**
 * Publiczne (niezalogowane) skanowanie kodu inicjalizacji kontaktu — tag NFC
 * albo kod QR, ten sam handler pod obydwoma adresami (kanał tylko
 * informacyjnie), odpowiednik dzisiejszego `InitController::show()` w
 * Laravelu. Faza 5: TYLKO lokalna weryfikacja (`ecosystem/`) — dzisiejsze
 * trasy Laravela zostają nietknięte, patrz plan Fazy 5 w
 * claude/marcin/03-ekosystem-mikroserwisow.md.
 *
 * Dwa hopy gRPC, każdy z jawnym deadline'em — to jest publiczny,
 * niezalogowany endpoint (ktoś zbliża telefon do tagu), więc zawieszony
 * peer nie może wisieć w nieskończoność. Każdy błąd/timeout na dowolnym
 * hopie, albo `active == false`, kończy się bezpiecznym "nie znaleziono",
 * nie 500.
 */
@RestController
class PublicInitController(
    @Qualifier("coreSvcInitCodeStub") private val initCodeStub: InitCodeServiceGrpc.InitCodeServiceBlockingStub,
    @Qualifier("gatewaySvcStorefrontStub") private val storefrontStub: StorefrontServiceGrpc.StorefrontServiceBlockingStub,
    @Value("#{'\${pay.storefront.allowed-hosts}'.split(',')}") private val allowedHosts: List<String>,
) {
    private val log = LoggerFactory.getLogger(PublicInitController::class.java)

    @GetMapping("/init/tag/{uuid}", "/init/qr/{uuid}")
    fun scan(@PathVariable uuid: String, request: HttpServletRequest): ResponseEntity<String> {
        val host = request.getHeader("Host")?.substringBefore(":")
        log.info("scan uuid={} host={} allowedHosts={}", uuid, host, allowedHosts)
        // Host bieżącego żądania trafia wprost do Location przekierowania —
        // bez allowlisty to open-redirect. Tylko znane hosty storefrontu
        // (te same co config('tenants.map') w Laravelu) budują URL dalej.
        if (host == null || host !in allowedHosts) {
            log.warn("scan rejected: host not allowlisted")
            return notFound()
        }

        val resolved = try {
            initCodeStub.withDeadlineAfter(2, TimeUnit.SECONDS).resolve(
                ResolveRequest.newBuilder().setUuid(uuid).build(),
            )
        } catch (e: StatusRuntimeException) {
            log.warn("scan: core-svc.Resolve failed", e)
            return notFound()
        }
        log.info("scan: core-svc.Resolve -> found={} targetType={} targetId={}", resolved.found, resolved.targetType, resolved.targetId)

        if (!resolved.found) {
            return notFound()
        }

        val target = try {
            // Deadline dłuższy niż dla core-svc: ten hop u gateway-svc wywołuje
            // synchronicznie GatewayClient::sendEvent (analytics tag_open) —
            // zaobserwowane lokalnie: to konkretne wywołanie HTTP Guzzle z
            // WEWNĄTRZ długo żyjącego workera gRPC RoadRunner bywa wyraźnie
            // wolniejsze (~2.5-3s) niż to samo wywołanie ze świeżego procesu
            // CLI (<1s) — przyczyna nie w pełni zdiagnozowana, patrz
            // claude/marcin/03-ekosystem-mikroserwisow.md, sekcja Faza 5.
            storefrontStub.withDeadlineAfter(4, TimeUnit.SECONDS).resolveRedirectTarget(
                ResolveRedirectTargetRequest.newBuilder()
                    .setTargetType(toStorefrontTargetType(resolved.targetType))
                    .setTargetId(resolved.targetId)
                    .setSourceUuid(uuid)
                    .build(),
            )
        } catch (e: StatusRuntimeException) {
            log.warn("scan: gateway-svc.ResolveRedirectTarget failed", e)
            return notFound()
        }
        log.info("scan: gateway-svc.ResolveRedirectTarget -> found={} active={} urlPath={}", target.found, target.active, target.urlPath)

        if (!target.found || !target.active) {
            return notFound()
        }

        val scheme = if (request.isSecure) "https" else "http"
        return ResponseEntity.status(HttpStatus.FOUND)
            .location(URI.create("$scheme://$host${target.urlPath}"))
            .build()
    }

    private fun toStorefrontTargetType(type: ResolveResponse.TargetType): ResolveRedirectTargetRequest.TargetType =
        when (type) {
            ResolveResponse.TargetType.SHOP_ITEM -> ResolveRedirectTargetRequest.TargetType.SHOP_ITEM
            ResolveResponse.TargetType.ORGANIZATION -> ResolveRedirectTargetRequest.TargetType.ORGANIZATION
            else -> ResolveRedirectTargetRequest.TargetType.NONE
        }

    private fun notFound(): ResponseEntity<String> =
        ResponseEntity.status(HttpStatus.NOT_FOUND)
            .contentType(MediaType.TEXT_PLAIN)
            .body("Nie znaleziono aktywnego kodu.")
}
