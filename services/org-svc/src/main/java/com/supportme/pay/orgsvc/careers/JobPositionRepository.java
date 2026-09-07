package com.supportme.pay.orgsvc.careers;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface JobPositionRepository extends JpaRepository<JobPosition, Long> {
    List<JobPosition> findByOrganizationIdOrderBySortAscIdAsc(Long organizationId);

    List<JobPosition> findByOrganizationIdAndActiveTrueOrderBySortAscIdAsc(Long organizationId);
}
