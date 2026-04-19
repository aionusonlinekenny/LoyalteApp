package com.loyalte.app.data.local.entity

import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey

/**
 * Immutable ledger record for every points event.
 * [points] is always positive; the [type] field ("EARNED", "REDEEMED", "ADJUSTMENT")
 * determines whether it was a credit or debit.
 */
@Entity(
    tableName = "loyalty_transactions",
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
data class LoyaltyTransactionEntity(
    @PrimaryKey val id: String,
    val customerId: String,
    val type: String,           // EARNED | REDEEMED | ADJUSTMENT
    val points: Int,
    val description: String,
    val createdAt: Long = System.currentTimeMillis()
)
