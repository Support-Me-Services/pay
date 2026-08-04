package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Category
import org.springframework.data.jpa.repository.JpaRepository

interface CategoryRepository : JpaRepository<Category, Long> {
    fun findBySlug(slug: String): Category?

    /** Top-level, aktywne, uporządkowane — jak `scopeTopLevel + scopeActive + scopeOrdered`. */
    fun findAllByParentIsNullAndActiveTrueOrderByPositionAscIdAsc(): List<Category>

    fun findAllByParentOrderByPositionAscIdAsc(parent: Category): List<Category>
}
