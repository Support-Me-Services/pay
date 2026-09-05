package com.supportme.pay.cmssvc.shopitem;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface ShopItemRepository extends JpaRepository<ShopItem, Long> {
    Optional<ShopItem> findByOrganizationIdAndSlug(Long organizationId, String slug);

    List<ShopItem> findByOrganizationId(Long organizationId);

    List<ShopItem> findByOrganizationIdAndActiveTrue(Long organizationId);
}
