package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.User
import org.springframework.data.jpa.repository.JpaRepository

interface UserRepository : JpaRepository<User, Long> {
    fun findByEmail(email: String): User?

    fun findByHandle(handle: String): User?

    fun existsByHandle(handle: String): Boolean
}
