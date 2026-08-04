package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Lead
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.domain.Page
import org.springframework.data.domain.Pageable

interface LeadRepository : JpaRepository<Lead, Long> {
    /** Odpowiednik `Lead::orderByDesc('created_at')->paginate(50)` w `LeadController::index`. */
    fun findAllByOrderByCreatedAtDesc(pageable: Pageable): Page<Lead>
}
