package com.supportme.pay.orgsvc.organization;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface OrganizationRepository extends JpaRepository<Organization, Long> {
    boolean existsByHandle(String handle);

    List<Organization> findByUserIdOrderByNameAsc(String userId);

    List<Organization> findAllByOrderByNameAsc();
}
