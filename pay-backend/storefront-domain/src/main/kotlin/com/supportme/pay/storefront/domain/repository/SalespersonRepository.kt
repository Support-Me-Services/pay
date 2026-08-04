package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Salesperson
import org.springframework.data.jpa.repository.JpaRepository

interface SalespersonRepository : JpaRepository<Salesperson, Long>
