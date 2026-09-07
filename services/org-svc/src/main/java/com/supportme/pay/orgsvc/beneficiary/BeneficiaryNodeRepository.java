package com.supportme.pay.orgsvc.beneficiary;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface BeneficiaryNodeRepository extends JpaRepository<BeneficiaryNode, Long> {
    List<BeneficiaryNode> findByOrganizationIdOrderByPositionAscIdAsc(Long organizationId);

    List<BeneficiaryNode> findByOrganizationIdAndActiveTrueOrderByPositionAscIdAsc(Long organizationId);
}
