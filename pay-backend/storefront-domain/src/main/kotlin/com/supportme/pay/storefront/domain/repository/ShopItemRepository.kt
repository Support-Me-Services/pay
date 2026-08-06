package com.supportme.pay.storefront.domain.repository

import com.supportme.pay.storefront.domain.entity.ShopItem
import com.supportme.pay.storefront.domain.entity.User
import org.springframework.data.jpa.repository.JpaRepository

interface ShopItemRepository : JpaRepository<ShopItem, Long> {
    /** Odpowiednik `ShopItem::forUser($userId)->ordered()`. */
    fun findAllByOwnerOrderBySortAscIdAsc(owner: User): List<ShopItem>

    fun findByOwnerAndId(owner: User, id: Long): ShopItem?

    fun findByTagUid(tagUid: String): ShopItem?

    fun findByTagUidAndActiveTrue(tagUid: String): ShopItem?
}
