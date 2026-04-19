package com.loyalte.app.domain.model

data class Reward(
    val id: String,
    val name: String,
    val description: String,
    val pointsRequired: Int,
    val isActive: Boolean,
    val category: RewardCategory,
    val createdAt: Long
)

enum class RewardCategory(val displayName: String, val emoji: String) {
    FOOD("Food", "🍽"),
    DRINK("Drink", "☕"),
    DISCOUNT("Discount", "%"),
    GENERAL("General", "🎁");

    companion object {
        fun fromString(value: String): RewardCategory =
            entries.firstOrNull { it.name == value } ?: GENERAL
    }
}
