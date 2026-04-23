package com.loyalte.app.data.remote.api.dto

import com.google.gson.annotations.SerializedName

// ─── Auth ─────────────────────────────────────────────────────────────────────

data class LoginRequest(
    val email: String,
    val password: String
)

data class LoginResponse(
    val success: Boolean,
    val token: String?,
    @SerializedName("expires_at") val expiresAt: Long?,
    val staff: StaffDto?,
    val message: String?
)

data class StaffDto(
    val id: Int,
    val name: String,
    val email: String
)

// ─── Customer ─────────────────────────────────────────────────────────────────

data class CustomerDto(
    val id: String,
    @SerializedName("member_id")  val memberId: String,
    val name: String,
    val phone: String,
    val email: String?,
    val tier: String,
    val points: Int,
    @SerializedName("qr_code")    val qrCode: String,
    @SerializedName("created_at") val createdAt: Long,
    @SerializedName("updated_at") val updatedAt: Long
)

data class CustomerResponse(
    val success: Boolean,
    val customer: CustomerDto?,
    val message: String?
)

data class CustomersResponse(
    val success: Boolean,
    val customers: List<CustomerDto>?,
    val message: String?
)

data class CreateCustomerRequest(
    val name: String,
    val phone: String,
    val email: String?
)

data class AddPointsRequest(
    val points: Int,
    val description: String
)

// ─── Transaction ──────────────────────────────────────────────────────────────

data class TransactionDto(
    val id: String,
    @SerializedName("customer_id")  val customerId: String,
    val type: String,
    val points: Int,
    val description: String,
    @SerializedName("created_at")   val createdAt: Long
)

data class TransactionsResponse(
    val success: Boolean,
    val transactions: List<TransactionDto>?,
    val message: String?
)

data class EarnPointsRequest(
    @SerializedName("customer_id") val customerId: String,
    val points: Int,
    val description: String
)

// ─── Reward ───────────────────────────────────────────────────────────────────

data class RewardDto(
    val id: String,
    val name: String,
    val description: String,
    @SerializedName("points_required") val pointsRequired: Int,
    @SerializedName("is_active")       val isActive: Int,
    val category: String,
    @SerializedName("created_at")      val createdAt: Long
)

data class RewardsResponse(
    val success: Boolean,
    val rewards: List<RewardDto>?,
    val message: String?
)

// ─── Redemption ───────────────────────────────────────────────────────────────

data class RedeemRequest(
    @SerializedName("customer_id") val customerId: String,
    @SerializedName("reward_id")   val rewardId: String
)

data class RedeemResponse(
    val success: Boolean,
    @SerializedName("new_points")     val newPoints: Int?,
    val tier: String?,
    val message: String?
)

data class RedemptionDto(
    val id: String,
    @SerializedName("customer_id")    val customerId: String,
    @SerializedName("reward_id")      val rewardId: String,
    @SerializedName("points_used")    val pointsUsed: Int,
    @SerializedName("redeemed_at")    val redeemedAt: Long,
    @SerializedName("reward_name")    val rewardName: String?,
    @SerializedName("reward_category") val rewardCategory: String?
)

data class RedemptionsResponse(
    val success: Boolean,
    val redemptions: List<RedemptionDto>?,
    val message: String?
)

// ─── Simple Response ──────────────────────────────────────────────────────────

data class SimpleResponse(
    val success: Boolean,
    val message: String?
)

// ─── Receipt Codes ────────────────────────────────────────────────────────────

data class ReceiptCodeDto(
    val id: String,
    val code: String,
    val points: Int,
    @SerializedName("expires_at")   val expiresAt: Long,
    @SerializedName("claimed_by")   val claimedBy: String?,
    @SerializedName("claimed_at")   val claimedAt: Long?,
    @SerializedName("created_by")   val createdBy: Int,
    @SerializedName("created_at")   val createdAt: Long,
    val note: String?
)

data class ReceiptCodesResponse(
    val success: Boolean,
    val codes: List<ReceiptCodeDto>?,
    val message: String?
)
