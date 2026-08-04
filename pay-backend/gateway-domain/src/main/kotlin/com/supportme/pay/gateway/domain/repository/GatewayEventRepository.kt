package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Event
import org.springframework.data.jpa.repository.JpaRepository

/** Nazwa nie może kolidować z `StorefrontEventRepository` — Spring nazywa bean po prostej nazwie klasy, nie FQN. */
interface GatewayEventRepository : JpaRepository<Event, Long>
