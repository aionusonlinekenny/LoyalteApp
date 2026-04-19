package com.loyalte.app.domain.usecase

import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.model.Redemption
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.LoyaltyRepository
import com.loyalte.app.domain.repository.RewardRepository
import java.util.UUID
import javax.inject.Inject

/**
 * Atomic reward redemption:
 * 1. Verify customer exists and has enough points
 * 2. Deduct points
 * 3. Write a REDEEMED transaction to the ledger
 * 4. Write a redemption log entry
 *
 * If any step fails the caller catches the exception; Room operations are not
 * wrapped in a single DB transaction here to keep the use-case testable without
 * a DB. For production, consider using a single @Transaction DAO method.
 */
class RedeemRewardUseCase @Inject constructor(
    private val customerRepository: CustomerRepository,
    private val rewardRepository: RewardRepository,
    private val loyaltyRepository: LoyaltyRepository
) {

    sealed class Result {
        data class Success(val updatedPoints: Int) : Result()
        object InsufficientPoints : Result()
        object CustomerNotFound : Result()
        object RewardNotFound : Result()
        data class Error(val message: String) : Result()
    }

    suspend operator fun invoke(customerId: String, rewardId: String): Result {
        val customer = customerRepository.getCustomerById(customerId)
            ?: return Result.CustomerNotFound

        val reward = rewardRepository.getRewardById(rewardId)
            ?: return Result.RewardNotFound

        if (!reward.isActive) return Result.Error("This reward is no longer available")
        if (customer.points < reward.pointsRequired) return Result.InsufficientPoints

        val newPoints = customer.points - reward.pointsRequired

        customerRepository.updatePoints(customerId, newPoints)

        loyaltyRepository.insertTransaction(
            LoyaltyTransaction(
                id = UUID.randomUUID().toString(),
                customerId = customerId,
                type = TransactionType.REDEEMED,
                points = reward.pointsRequired,
                description = "Redeemed: ${reward.name}",
                createdAt = System.currentTimeMillis()
            )
        )

        rewardRepository.insertRedemption(
            Redemption(
                id = UUID.randomUUID().toString(),
                customerId = customerId,
                rewardId = rewardId,
                rewardName = reward.name,
                pointsSpent = reward.pointsRequired,
                redeemedAt = System.currentTimeMillis()
            )
        )

        return Result.Success(newPoints)
    }
}
