package com.supportme.pay.orgsvc.careers;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface JobApplicationRepository extends JpaRepository<JobApplication, Long> {
    List<JobApplication> findByOrganizationIdOrderByIdDesc(Long organizationId);

    List<JobApplication> findByOrganizationIdAndJobPositionIdOrderByIdDesc(Long organizationId, Long jobPositionId);

    List<JobApplication> findByOrganizationIdAndStatusOrderByIdDesc(Long organizationId, String status);

    List<JobApplication> findByOrganizationIdAndJobPositionIdAndStatusOrderByIdDesc(
            Long organizationId, Long jobPositionId, String status);

    long countByJobPositionId(Long jobPositionId);
}
