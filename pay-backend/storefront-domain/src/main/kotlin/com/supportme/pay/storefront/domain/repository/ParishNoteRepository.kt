package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.ParishNote
import com.supportme.pay.storefront.domain.entity.Product
import org.springframework.data.jpa.repository.JpaRepository

interface ParishNoteRepository : JpaRepository<ParishNote, Long> {
    /** Odpowiednik `Product::notes()->orderByDesc('id')` — najnowsze na górze. */
    fun findAllByProductOrderByIdDesc(product: Product): List<ParishNote>
}
