package com.supportme.pay.orgsvc.shopitem;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface ShopItemRepository extends JpaRepository<ShopItem, Long> {
    boolean existsByOrganizationIdAndSlug(Long organizationId, String slug);

    List<ShopItem> findByOrganizationIdOrderBySortAscIdAsc(Long organizationId);

    List<ShopItem> findByOrganizationIdAndActiveTrueOrderBySortAscIdAsc(Long organizationId);

    List<ShopItem> findAllByOrderByNameAsc();

    Optional<ShopItem> findByIdAndOrganizationId(Long id, Long organizationId);
}
