package com.supportme.pay.gateway.api.clientapi

import com.supportme.pay.gateway.domain.entity.Event
import com.supportme.pay.gateway.domain.entity.EventType
import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.repository.GatewayEventRepository
import com.supportme.pay.gateway.domain.repository.TagRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.NotBlank
import jakarta.validation.constraints.Pattern
import jakarta.validation.constraints.Size
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class CreateEventRequest(
    /** Dziś akceptowany wyłącznie `tag_open`, jak w oryginale. */
    @field:NotBlank @field:Pattern(regexp = "tag_open") val type: String,
    @field:Size(max = 255) val tagUid: String? = null,
)

/** Odpowiednik `Api\EventController` — `/api/gateway/v1/events`. */
@RestController
@RequestMapping("/api/gateway/v1/events")
class EventController(
    private val gatewayEventRepository: GatewayEventRepository,
    private val tagRepository: TagRepository,
) {

    @PostMapping
    fun store(@AuthenticationPrincipal shop: Shop, @Valid @RequestBody body: CreateEventRequest): ResponseEntity<Map<String, Boolean>> {
        val tag = body.tagUid?.let { tagRepository.findByTagUidAndShop(it, shop) }
        gatewayEventRepository.save(Event(shop = shop, tag = tag, type = EventType.TAG_OPEN))

        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true))
    }
}
