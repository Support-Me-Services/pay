package com.supportme.pay.coresvc.initcode;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface InitCodeRepository extends JpaRepository<InitCode, Long> {
    Optional<InitCode> findByUuidAndActiveTrue(String uuid);

    List<InitCode> findByOrganizationId(Long organizationId);

    List<InitCode> findByOwnerUserId(Long ownerUserId);
}
