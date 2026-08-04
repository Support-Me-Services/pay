package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.Event
import org.springframework.data.jpa.repository.JpaRepository

/** Nazwa nie może kolidować z `GatewayEventRepository` — Spring nazywa bean po prostej nazwie klasy, nie FQN. */
interface StorefrontEventRepository : JpaRepository<Event, Long>
