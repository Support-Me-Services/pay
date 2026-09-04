package com.supportme.pay.apigateway.initcode;

import io.grpc.StatusRuntimeException;
import jakarta.servlet.http.HttpServletRequest;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RestController;
import pay.initcode.v1.InitCodeServiceGrpc;
import pay.initcode.v1.ResolveRequest;
import pay.initcode.v1.ResolveResponse;
import pay.storefront.v1.ResolveRedirectTargetRequest;
import pay.storefront.v1.ResolveRedirectTargetResponse;
import pay.storefront.v1.StorefrontServiceGrpc;

import java.net.URI;
import java.util.List;
import java.util.concurrent.TimeUnit;

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
public class PublicInitController {

    private static final Logger log = LoggerFactory.getLogger(PublicInitController.class);

    private final InitCodeServiceGrpc.InitCodeServiceBlockingStub initCodeStub;
    private final StorefrontServiceGrpc.StorefrontServiceBlockingStub storefrontStub;
    private final List<String> allowedHosts;
    private final String storefrontPort;

    public PublicInitController(
            @Qualifier("coreSvcInitCodeStub") InitCodeServiceGrpc.InitCodeServiceBlockingStub initCodeStub,
            @Qualifier("gatewaySvcStorefrontStub") StorefrontServiceGrpc.StorefrontServiceBlockingStub storefrontStub,
            @Value("#{'${pay.storefront.allowed-hosts}'.split(',')}") List<String> allowedHosts,
            @Value("${pay.storefront.port:}") String storefrontPort) {
        this.initCodeStub = initCodeStub;
        this.storefrontStub = storefrontStub;
        this.allowedHosts = allowedHosts;
        this.storefrontPort = storefrontPort;
    }

    @GetMapping({"/init/tag/{uuid}", "/init/qr/{uuid}"})
    public ResponseEntity<String> scan(@PathVariable String uuid, HttpServletRequest request) {
        String hostHeader = request.getHeader("Host");
        // Sam Host (bez portu) — port żądania to port API GATEWAY, nie
        // storefrontu (lokalnie: 8081 vs 8000), więc nie nadaje się do
        // budowy Location — patrz pay.storefront.port niżej.
        String host = hostHeader != null ? hostHeader.split(":")[0] : null;
        log.info("scan uuid={} host={} allowedHosts={}", uuid, host, allowedHosts);
        // Host bieżącego żądania trafia wprost do Location przekierowania —
        // bez allowlisty to open-redirect. Tylko znane hosty storefrontu
        // (te same co config('tenants.map') w Laravelu) budują URL dalej.
        if (host == null || !allowedHosts.contains(host)) {
            log.warn("scan rejected: host not allowlisted");
            return notFound();
        }

        ResolveResponse resolved;
        try {
            resolved = initCodeStub.withDeadlineAfter(2, TimeUnit.SECONDS).resolve(
                    ResolveRequest.newBuilder().setUuid(uuid).build());
        } catch (StatusRuntimeException e) {
            log.warn("scan: core-svc.Resolve failed", e);
            return notFound();
        }
        log.info("scan: core-svc.Resolve -> found={} targetType={} targetId={}",
                resolved.getFound(), resolved.getTargetType(), resolved.getTargetId());

        if (!resolved.getFound()) {
            return notFound();
        }

        ResolveRedirectTargetResponse target;
        try {
            // Deadline dłuższy niż dla core-svc: ten hop u gateway-svc wywołuje
            // synchronicznie GatewayClient::sendEvent (analytics tag_open) —
            // zaobserwowane lokalnie: to konkretne wywołanie HTTP Guzzle z
            // WEWNĄTRZ długo żyjącego workera gRPC RoadRunner bywa wyraźnie
            // wolniejsze (~2.5-3s) niż to samo wywołanie ze świeżego procesu
            // CLI (<1s) — przyczyna nie w pełni zdiagnozowana, patrz
            // claude/marcin/03-ekosystem-mikroserwisow.md, sekcja Faza 5.
            target = storefrontStub.withDeadlineAfter(4, TimeUnit.SECONDS).resolveRedirectTarget(
                    ResolveRedirectTargetRequest.newBuilder()
                            .setTargetType(toStorefrontTargetType(resolved.getTargetType()))
                            .setTargetId(resolved.getTargetId())
                            .setSourceUuid(uuid)
                            .build());
        } catch (StatusRuntimeException e) {
            log.warn("scan: gateway-svc.ResolveRedirectTarget failed", e);
            return notFound();
        }
        log.info("scan: gateway-svc.ResolveRedirectTarget -> found={} active={} urlPath={}",
                target.getFound(), target.getActive(), target.getUrlPath());

        if (!target.getFound() || !target.getActive()) {
            return notFound();
        }

        String scheme = request.isSecure() ? "https" : "http";
        // pay.storefront.port: PUSTE domyślnie (prod — storefront i
        // api-gateway docelowo za tym samym portem 443/80, port w URL
        // zbędny). Lokalnie Laravel stoi na INNYM porcie niż api-gateway
        // (8000 vs 8081) — bez jawnego portu Location wskazywałby na
        // domyślny port 80/443 hosta, gdzie nic nie odpowiada.
        String portSuffix = storefrontPort.isBlank() ? "" : ":" + storefrontPort;
        return ResponseEntity.status(HttpStatus.FOUND)
                .location(URI.create(scheme + "://" + host + portSuffix + target.getUrlPath()))
                .build();
    }

    private ResolveRedirectTargetRequest.TargetType toStorefrontTargetType(ResolveResponse.TargetType type) {
        return switch (type) {
            case SHOP_ITEM -> ResolveRedirectTargetRequest.TargetType.SHOP_ITEM;
            case ORGANIZATION -> ResolveRedirectTargetRequest.TargetType.ORGANIZATION;
            default -> ResolveRedirectTargetRequest.TargetType.NONE;
        };
    }

    private ResponseEntity<String> notFound() {
        return ResponseEntity.status(HttpStatus.NOT_FOUND)
                .contentType(MediaType.TEXT_PLAIN)
                .body("Nie znaleziono aktywnego kodu.");
    }
}
