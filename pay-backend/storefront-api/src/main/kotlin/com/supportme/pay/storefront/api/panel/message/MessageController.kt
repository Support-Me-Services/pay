package com.supportme.pay.storefront.api.panel.message

import com.supportme.pay.storefront.domain.repository.ContactMessageRepository
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.DeleteMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class MessageSummary(val id: Long, val name: String, val email: String, val phone: String?, val subject: String?, val message: String, val isRead: Boolean, val createdAt: String?)

/** Odpowiednik `Panel\MessageController` — prosta skrzynka odbiorcza `ContactMessage`. */
@RestController
@RequestMapping("/api/storefront/panel/messages")
class MessageController(private val contactMessageRepository: ContactMessageRepository) {

    @GetMapping
    fun index(): List<MessageSummary> = contactMessageRepository.findAll().sortedByDescending { it.createdAt }.map(::summarize)

    @GetMapping("/{id}")
    fun show(@PathVariable id: Long): ResponseEntity<MessageSummary> {
        val message = contactMessageRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        if (!message.isRead) {
            message.isRead = true
            contactMessageRepository.save(message)
        }
        return ResponseEntity.ok(summarize(message))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val message = contactMessageRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        contactMessageRepository.delete(message)
        return ResponseEntity.noContent().build()
    }

    private fun summarize(m: com.supportme.pay.storefront.domain.entity.ContactMessage) =
        MessageSummary(m.id!!, m.name, m.email, m.phone, m.subject, m.message, m.isRead, m.createdAt?.toString())
}
