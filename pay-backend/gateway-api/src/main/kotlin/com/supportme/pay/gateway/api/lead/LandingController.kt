package com.supportme.pay.gateway.api.lead

import com.fasterxml.jackson.databind.ObjectMapper
import com.supportme.pay.gateway.domain.entity.Lead
import com.supportme.pay.gateway.domain.repository.LeadRepository
import jakarta.validation.ConstraintViolationException
import jakarta.validation.Validator
import jakarta.validation.constraints.Email
import jakarta.validation.constraints.NotBlank
import jakarta.validation.constraints.Size
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class LeadRequest(
    @field:NotBlank @field:Size(max = 255) val name: String,
    @field:NotBlank @field:Email @field:Size(max = 255) val email: String,
    @field:NotBlank @field:Size(max = 50) val phone: String,
    @field:Size(max = 255) val company: String? = null,
    @field:NotBlank @field:Size(max = 5000) val message: String,
)

/** Odpowiednik `LandingController::storeLead` — formularz kontaktowy strony landing Gateway. */
@RestController
@RequestMapping("/lead")
class LandingController(
    private val leadRepository: LeadRepository,
    private val validator: Validator,
    private val objectMapper: ObjectMapper,
) {

    /**
     * Honeypot MUSI być sprawdzony PRZED deserializacją do `LeadRequest`
     * (jak `$request->filled('website')` w PHP, przed `$request->validate(...)`)
     * — bot z wypełnionym honeypotem dostaje udawany sukces NAWET jeśli resztę
     * pól ma niekompletną/niepoprawną. `LeadRequest` ma same non-null pola, więc
     * deserializacja od razu do tego DTO wywaliłaby się na brakujących polach
     * PRZED jakąkolwiek szansą sprawdzenia honeypota — dlatego czytamy surowe
     * body jako drzewo JSON najpierw.
     */
    @PostMapping
    fun store(@RequestBody rawBody: String): ResponseEntity<Map<String, Boolean>> {
        val json = objectMapper.readTree(rawBody)
        val website = json.path("website").takeIf { it.isTextual }?.asText()
        if (!website.isNullOrBlank()) {
            return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true)) // honeypot — udawany sukces, bez zapisu
        }

        val body = objectMapper.treeToValue(json, LeadRequest::class.java)
        val violations = validator.validate(body)
        if (violations.isNotEmpty()) throw ConstraintViolationException(violations)

        leadRepository.save(Lead(name = body.name, email = body.email, phone = body.phone, company = body.company, message = body.message))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true))
    }
}
