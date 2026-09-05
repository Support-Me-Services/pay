package com.supportme.pay.cmssvc.career;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface JobApplicationRepository extends JpaRepository<JobApplication, Long> {
    List<JobApplication> findByOrganizationId(Long organizationId);
}
