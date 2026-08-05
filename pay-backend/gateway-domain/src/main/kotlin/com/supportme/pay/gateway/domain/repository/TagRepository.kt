package com.supportme.pay.gateway.domain.repository

import com.supportme.pay.gateway.domain.entity.Shop
import com.supportme.pay.gateway.domain.entity.Tag
import org.springframework.data.jpa.repository.JpaRepository

interface TagRepository : JpaRepository<Tag, Long> {
    /** Lookup tagu SCOPED do sklepu — odpowiednik `Tag::where('tag_uid',...)->where('shop_id',...)`. */
    fun findByTagUidAndShop(tagUid: String, shop: Shop): Tag?

    fun findAllByShopOrderById(shop: Shop): List<Tag>

    /** Ownership guard — odpowiednik `abort_unless($tag->shop_id === $shop->id, 404)`. */
    fun findByIdAndShop(id: Long, shop: Shop): Tag?

    fun countByShop(shop: Shop): Long

    fun existsByTagUid(tagUid: String): Boolean
}
