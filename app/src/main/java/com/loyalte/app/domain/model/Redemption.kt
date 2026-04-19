package com.loyalte.app.domain.model

data class Redemption(
    val id: String,
    val customerId: String,
    val rewardId: String,
    val rewardName: String,
    val pointsSpent: Int,
    val redeemedAt: Long
)
