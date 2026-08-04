package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.PotentialParish
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.jpa.repository.JpaSpecificationExecutor

/**
 * `JpaSpecificationExecutor` — filtrowanie PotentialParishController jest
 * bardzo złożone (voivodeship/status/salesperson/name+city search/tri-state
 * has_phone) i lepiej wyrazić je jako `Specification` w Fazie 4, niż jako
 * dziesiątki metod `findByXAndYAndZ...`.
 */
interface PotentialParishRepository : JpaRepository<PotentialParish, Long>, JpaSpecificationExecutor<PotentialParish>
