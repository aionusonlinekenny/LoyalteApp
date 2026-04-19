package com.loyalte.app.domain.model

data class LoyaltyTransaction(
    val id: String,
    val customerId: String,
    val type: TransactionType,
    val points: Int,
    val description: String,
    val createdAt: Long
)

enum class TransactionType(val displayName: String) {
    EARNED("Earned"),
    REDEEMED("Redeemed"),
    ADJUSTMENT("Adjustment");

    companion object {
        fun fromString(value: String): TransactionType =
            entries.firstOrNull { it.name == value } ?: EARNED
    }
}
