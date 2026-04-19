package com.loyalte.app.data.remote

import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.RedeemRequest
import com.loyalte.app.data.remote.api.dto.RedemptionDto
import com.loyalte.app.data.remote.api.dto.RewardDto
import com.loyalte.app.domain.model.Redemption
import com.loyalte.app.domain.model.Reward
import com.loyalte.app.domain.model.RewardCategory
import com.loyalte.app.domain.repository.RewardRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject

class RetrofitRewardRepositoryImpl @Inject constructor(
    private val api: LoyalteApiService
) : RewardRepository {

    override fun getActiveRewards(): Flow<List<Reward>> = flow {
        val rewards = api.getActiveRewards().body()?.rewards?.map { it.toDomain() } ?: emptyList()
        emit(rewards)
    }

    override suspend fun getRewardById(id: String): Reward? {
        val rewards = api.getActiveRewards().body()?.rewards ?: return null
        return rewards.firstOrNull { it.id == id }?.toDomain()
    }

    override suspend fun insertRewards(rewards: List<Reward>) {
        // Rewards are managed server-side; no local insert needed.
    }

    override suspend fun insertRedemption(redemption: Redemption) {
        api.redeemReward(RedeemRequest(redemption.customerId, redemption.rewardId))
    }

    override fun getRedemptionsForCustomer(customerId: String): Flow<List<Redemption>> = flow {
        val redemptions = api.getRedemptions(customerId).body()?.redemptions
            ?.map { it.toDomain() } ?: emptyList()
        emit(redemptions)
    }
}

private fun RewardDto.toDomain() = Reward(
    id             = id,
    name           = name,
    description    = description,
    pointsRequired = pointsRequired,
    isActive       = isActive == 1,
    category       = RewardCategory.fromString(category),
    createdAt      = createdAt
)

private fun RedemptionDto.toDomain() = Redemption(
    id          = id,
    customerId  = customerId,
    rewardId    = rewardId,
    rewardName  = rewardName ?: "",
    pointsSpent = pointsUsed,
    redeemedAt  = redeemedAt
)
