package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.ContactMessage
import org.springframework.data.jpa.repository.JpaRepository

interface ContactMessageRepository : JpaRepository<ContactMessage, Long>
