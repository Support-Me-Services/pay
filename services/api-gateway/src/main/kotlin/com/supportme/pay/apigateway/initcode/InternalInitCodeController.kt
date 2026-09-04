package com.supportme.pay.apigateway.initcode

import io.grpc.Status
import io.grpc.StatusRuntimeException
import org.springframework.beans.factory.annotation.Qualifier
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.DeleteMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import pay.initcode.v1.CreateInitCodeRequest
import pay.initcode.v1.DeleteInitCodeRequest
import pay.initcode.v1.InitCodeServiceGrpc
import pay.initcode.v1.ListInitCodesRequest
import pay.initcode.v1.ToggleInitCodeRequest
import pay.initcode.v1.UpdateInitCodeRequest
import java.util.concurrent.TimeUnit

/**
 * CRUD dla kodów inicjalizacji kontaktu — WYŁĄCZNIE do wywołania przez
 * gateway-svc (Laravel), server-to-server, nigdy z przeglądarki/mobile.
 * Chronione osobnym mechanizmem niż JWT Keycloaka — patrz SecurityConfig
 * (prefiks `/internal/`, nagłówek X-Internal-Api-Key). Dziś nic w produkcyjnym
 * Laravelu tego nie woła — to weryfikacja end-to-end (curl), nie realne
 * podłączenie panelu, patrz plan Fazy 5 w
 * claude/marcin/03-ekosystem-mikroserwisow.md.
 */
@RestController
@RequestMapping("/internal/v1/init-codes")
class InternalInitCodeController(
    @Qualifier("coreSvcInitCodeStub") private val stub: InitCodeServiceGrpc.InitCodeServiceBlockingStub,
) {
    @PostMapping
    fun create(@RequestBody body: CreateInitCodeDto): ResponseEntity<Any> = handle {
        val request = CreateInitCodeRequest.newBuilder()
            .setOwner(body.owner.toProto())
            .setLabel(body.label)
        body.shopItemId?.let { request.shopItemId = it }
        body.targetOrganizationId?.let { request.targetOrganizationId = it }

        val response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).create(request.build())
        ResponseEntity.status(HttpStatus.CREATED).body(InitCodeDto.from(response))
    }

    @GetMapping
    fun list(
        @RequestParam(required = false) organizationId: Long?,
        @RequestParam(required = false) ownerUserId: Long?,
    ): ResponseEntity<Any> = handle {
        val owner = OwnerScopeDto(organizationId, ownerUserId).toProto()
        val response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).list(
            ListInitCodesRequest.newBuilder().setOwner(owner).build(),
        )
        ResponseEntity.ok(response.codesList.map(InitCodeDto::from))
    }

    @PutMapping("/{id}")
    fun update(@PathVariable id: Long, @RequestBody body: UpdateInitCodeDto): ResponseEntity<Any> = handle {
        val request = UpdateInitCodeRequest.newBuilder()
            .setId(id)
            .setOwner(body.owner.toProto())
            .setLabel(body.label)
        body.shopItemId?.let { request.shopItemId = it }
        body.targetOrganizationId?.let { request.targetOrganizationId = it }

        val response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).update(request.build())
        ResponseEntity.ok(InitCodeDto.from(response))
    }

    @PostMapping("/{id}/toggle")
    fun toggle(@PathVariable id: Long, @RequestBody body: ScopedRequestDto): ResponseEntity<Any> = handle {
        val response = stub.withDeadlineAfter(2, TimeUnit.SECONDS).toggle(
            ToggleInitCodeRequest.newBuilder().setId(id).setOwner(body.owner.toProto()).build(),
        )
        ResponseEntity.ok(InitCodeDto.from(response))
    }

    @DeleteMapping("/{id}")
    fun delete(@PathVariable id: Long, @RequestBody body: ScopedRequestDto): ResponseEntity<Any> = handle {
        stub.withDeadlineAfter(2, TimeUnit.SECONDS).delete(
            DeleteInitCodeRequest.newBuilder().setId(id).setOwner(body.owner.toProto()).build(),
        )
        ResponseEntity.noContent().build()
    }

    /** Tłumaczy statusy gRPC z core-svc (egzekwowanie własności, walidacja) na HTTP. */
    private fun handle(block: () -> ResponseEntity<Any>): ResponseEntity<Any> = try {
        block()
    } catch (e: StatusRuntimeException) {
        val status = when (e.status.code) {
            Status.Code.NOT_FOUND -> HttpStatus.NOT_FOUND
            Status.Code.PERMISSION_DENIED -> HttpStatus.FORBIDDEN
            Status.Code.INVALID_ARGUMENT -> HttpStatus.BAD_REQUEST
            else -> HttpStatus.BAD_GATEWAY
        }
        ResponseEntity.status(status).body(mapOf("error" to (e.status.description ?: e.status.code.name)))
    }
}
