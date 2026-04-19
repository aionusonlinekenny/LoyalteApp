package com.loyalte.app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * A redeemable reward offered to loyalty customers.
 * [isActive] lets staff disable a reward without deleting historical redemptions.
 */
@Entity(tableName = "rewards")
data class RewardEntity(
    @PrimaryKey val id: String,
    val name: String,
    val description: String,
    val pointsRequired: Int,
    val isActive: Boolean = true,
    val category: String = "GENERAL",   // FOOD | DRINK | DISCOUNT | GENERAL
    val createdAt: Long = System.currentTimeMillis()
)
