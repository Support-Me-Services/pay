package com.supportme.pay.cmssvc.career;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface JobPositionRepository extends JpaRepository<JobPosition, Long> {
    List<JobPosition> findByOrganizationId(Long organizationId);

    List<JobPosition> findByOrganizationIdAndActiveTrue(Long organizationId);
}
