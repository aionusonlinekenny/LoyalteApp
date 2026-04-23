package com.loyalte.app.domain.repository

import com.loyalte.app.domain.model.Redemption
import com.loyalte.app.domain.model.Reward
import kotlinx.coroutines.flow.Flow

interface RewardRepository {
    fun getAllActiveRewards(): Flow<List<Reward>>
    fun getAllRewards(): Flow<List<Reward>>
    suspend fun getRewardById(id: String): Reward?
    suspend fun insertReward(reward: Reward)
    suspend fun insertRewards(rewards: List<Reward>)
    suspend fun updateReward(reward: Reward)
    fun getRedemptionsByCustomer(customerId: String): Flow<List<Redemption>>
    suspend fun insertRedemption(redemption: Redemption)
}
