package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Product
import com.supportme.pay.storefront.domain.entity.ProductImage
import org.springframework.data.jpa.repository.JpaRepository

interface ProductImageRepository : JpaRepository<ProductImage, Long> {
    fun findAllByProductOrderBySortAsc(product: Product): List<ProductImage>
}
