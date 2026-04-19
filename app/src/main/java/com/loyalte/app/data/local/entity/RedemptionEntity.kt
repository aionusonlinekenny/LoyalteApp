package com.loyalte.app.data.local.entity

import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey

/**
 * Permanent log of every reward redemption.
 * [rewardName] is denormalized so history stays accurate even if the reward is later renamed.
 */
@Entity(
    tableName = "redemptions",
    foreignKeys = [
        ForeignKey(
            entity = CustomerEntity::class,
            parentColumns = ["id"],
            childColumns = ["customerId"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index(value = ["customerId"])]
)
data class RedemptionEntity(
    @PrimaryKey val id: String,
    val customerId: String,
    val rewardId: String,
    val rewardName: String,
    val pointsSpent: Int,
    val redeemedAt: Long = System.currentTimeMillis()
)
