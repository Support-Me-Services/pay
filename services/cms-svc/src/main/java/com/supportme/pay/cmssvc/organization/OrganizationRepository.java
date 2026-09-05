package com.supportme.pay.cmssvc.organization;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface OrganizationRepository extends JpaRepository<Organization, Long> {
    Optional<Organization> findByHandle(String handle);

    List<Organization> findByOwnerKeycloakSub(String ownerKeycloakSub);
}
