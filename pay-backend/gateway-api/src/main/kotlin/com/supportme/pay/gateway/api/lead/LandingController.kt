package com.supportme.pay.gateway.api.lead

import com.supportme.pay.gateway.domain.entity.Lead
import com.supportme.pay.gateway.domain.repository.LeadRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.NotBlank
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class LeadRequest(
    @field:NotBlank val name: String,
    @field:NotBlank val email: String,
    @field:NotBlank val phone: String,
    val company: String? = null,
    @field:NotBlank val message: String,
)

/** Odpowiednik `LandingController::storeLead` — formularz kontaktowy strony landing Gateway. */
@RestController
@RequestMapping("/lead")
class LandingController(private val leadRepository: LeadRepository) {

    @PostMapping
    fun store(@Valid @RequestBody body: LeadRequest): ResponseEntity<Map<String, Boolean>> {
        leadRepository.save(Lead(name = body.name, email = body.email, phone = body.phone, company = body.company, message = body.message))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true))
    }
}
