package com.loyalte.app.domain.model

/**
 * Clean domain model for a loyalty customer.
 * Decoupled from the Room entity so the domain layer has no Android/DB dependencies.
 */
data class Customer(
    val id: String,
    val memberId: String,
    val name: String,
    val phone: String,
    val email: String? = null,
    val tier: CustomerTier,
    val points: Int,
    val qrCode: String,
    val createdAt: Long,
    val updatedAt: Long
)

enum class CustomerTier(val displayName: String, val minPoints: Int, val color: Long) {
    BRONZE("Bronze", 0, 0xFFCD7F32),
    SILVER("Silver", 500, 0xFFC0C0C0),
    GOLD("Gold", 1000, 0xFFFFD700),
    PLATINUM("Platinum", 2500, 0xFFE5E4E2);

    companion object {
        fun fromPoints(points: Int): CustomerTier =
            entries.sortedByDescending { it.minPoints }.first { points >= it.minPoints }

        fun fromString(value: String): CustomerTier =
            entries.firstOrNull { it.name == value } ?: BRONZE
    }
}
