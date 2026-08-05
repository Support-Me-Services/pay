package com.supportme.pay.storefront.api.panel.salesperson

import com.supportme.pay.storefront.domain.entity.Salesperson
import com.supportme.pay.storefront.domain.repository.ProductRepository
import com.supportme.pay.storefront.domain.repository.SalespersonRepository
import org.springframework.http.HttpStatus
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.DeleteMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.PathVariable
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.PutMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

data class SalespersonRequest(val name: String, val email: String? = null, val phone: String? = null, val voivodeships: List<String> = emptyList(), val active: Boolean = true)
data class SalespersonSummary(val id: Long, val name: String, val email: String?, val phone: String?, val voivodeships: List<String>, val active: Boolean, val parishesCount: Long)

/** Odpowiednik `Panel\SalespersonController` — `voivodeships` walidowane wg `Salesperson.VOIVODESHIPS`. */
@RestController
@RequestMapping("/api/storefront/panel/salespeople")
class SalespersonController(
    private val salespersonRepository: SalespersonRepository,
    private val productRepository: ProductRepository,
) {

    @GetMapping
    fun index(): List<SalespersonSummary> {
        val allProducts = productRepository.findAll()
        return salespersonRepository.findAll().map { sp ->
            SalespersonSummary(sp.id!!, sp.name, sp.email, sp.phone, sp.voivodeships ?: emptyList(), sp.active, allProducts.count { it.salesperson?.id == sp.id }.toLong())
        }
    }

    @PostMapping
    fun store(@RequestBody body: SalespersonRequest): ResponseEntity<Any> {
        val voivodeships = body.voivodeships.filter { it in Salesperson.VOIVODESHIPS }.ifEmpty { null }
        val sp = salespersonRepository.save(Salesperson(name = body.name, email = body.email, phone = body.phone, voivodeships = voivodeships, active = body.active))
        return ResponseEntity.status(HttpStatus.CREATED).body(mapOf("id" to sp.id!!))
    }

    @PutMapping("/{id}")
    fun update(@PathVariable id: Long, @RequestBody body: SalespersonRequest): ResponseEntity<Any> {
        val sp = salespersonRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        sp.name = body.name
        sp.email = body.email
        sp.phone = body.phone
        sp.voivodeships = body.voivodeships.filter { it in Salesperson.VOIVODESHIPS }.ifEmpty { null }
        sp.active = body.active
        salespersonRepository.save(sp)
        return ResponseEntity.ok(mapOf("id" to sp.id!!))
    }

    @DeleteMapping("/{id}")
    fun destroy(@PathVariable id: Long): ResponseEntity<Any> {
        val sp = salespersonRepository.findById(id).orElse(null) ?: return ResponseEntity.notFound().build()
        salespersonRepository.delete(sp)
        return ResponseEntity.noContent().build()
    }
}
