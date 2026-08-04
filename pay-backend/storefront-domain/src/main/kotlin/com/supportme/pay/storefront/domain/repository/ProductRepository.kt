package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Product
import org.springframework.data.jpa.repository.JpaRepository

interface ProductRepository : JpaRepository<Product, Long> {
    fun findBySlug(slug: String): Product?

    fun findByTagUid(tagUid: String): Product?

    fun findAllByActiveTrue(): List<Product>
}
