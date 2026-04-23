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

    override fun getAllActiveRewards(): Flow<List<Reward>> = flow {
        emit(api.getActiveRewards().body()?.rewards?.map { it.toDomain() } ?: emptyList())
    }

    override fun getAllRewards(): Flow<List<Reward>> = getAllActiveRewards()

    override suspend fun getRewardById(id: String): Reward? {
        return api.getActiveRewards().body()?.rewards?.firstOrNull { it.id == id }?.toDomain()
    }

    override suspend fun insertReward(reward: Reward) {
        // Rewards are managed server-side.
    }

    override suspend fun insertRewards(rewards: List<Reward>) {
        // Rewards are managed server-side.
    }

    override suspend fun updateReward(reward: Reward) {
        // Rewards are managed server-side.
    }

    override fun getRedemptionsByCustomer(customerId: String): Flow<List<Redemption>> = flow {
        emit(
            api.getRedemptions(customerId).body()?.redemptions?.map { it.toDomain() } ?: emptyList()
        )
    }

    override suspend fun insertRedemption(redemption: Redemption) {
        api.redeemReward(RedeemRequest(redemption.customerId, redemption.rewardId))
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
