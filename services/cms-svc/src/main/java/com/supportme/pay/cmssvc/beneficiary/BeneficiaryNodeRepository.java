package com.supportme.pay.cmssvc.beneficiary;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface BeneficiaryNodeRepository extends JpaRepository<BeneficiaryNode, Long> {
    List<BeneficiaryNode> findByOrganizationIdOrderByPosition(Long organizationId);

    List<BeneficiaryNode> findByOrganizationIdAndActiveTrueOrderByPosition(Long organizationId);
}
