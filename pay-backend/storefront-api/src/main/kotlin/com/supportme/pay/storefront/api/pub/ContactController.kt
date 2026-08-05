package com.supportme.pay.storefront.api.pub

import com.supportme.pay.storefront.domain.entity.ContactMessage
import com.supportme.pay.storefront.domain.repository.ContactMessageRepository
import jakarta.validation.Valid
import jakarta.validation.constraints.NotBlank
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class ContactRequest(
    @field:NotBlank val name: String,
    @field:NotBlank val email: String,
    val phone: String? = null,
    val subject: String? = null,
    @field:NotBlank val message: String,
)

/** Odpowiednik `ContactController` — formularz `/kontakt` (`subject` może być wstępnie wypełniony z linku oferty pracy). */
@RestController
@RequestMapping("/kontakt")
class ContactController(private val contactMessageRepository: ContactMessageRepository) {

    @PostMapping
    fun store(@Valid @RequestBody body: ContactRequest): ResponseEntity<Map<String, Boolean>> {
        contactMessageRepository.save(ContactMessage(name = body.name, email = body.email, phone = body.phone, subject = body.subject, message = body.message))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("ok" to true))
    }
}
