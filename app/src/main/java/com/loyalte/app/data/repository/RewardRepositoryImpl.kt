package com.loyalte.app.data.repository

import com.loyalte.app.data.local.dao.RedemptionDao
import com.loyalte.app.data.local.dao.RewardDao
import com.loyalte.app.data.local.entity.RedemptionEntity
import com.loyalte.app.data.local.entity.RewardEntity
import com.loyalte.app.domain.model.Redemption
import com.loyalte.app.domain.model.Reward
import com.loyalte.app.domain.model.RewardCategory
import com.loyalte.app.domain.repository.RewardRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class RewardRepositoryImpl @Inject constructor(
    private val rewardDao: RewardDao,
    private val redemptionDao: RedemptionDao
) : RewardRepository {

    override fun getAllActiveRewards(): Flow<List<Reward>> =
        rewardDao.getAllActive().map { list -> list.map { it.toDomain() } }

    override fun getAllRewards(): Flow<List<Reward>> =
        rewardDao.getAll().map { list -> list.map { it.toDomain() } }

    override suspend fun getRewardById(id: String): Reward? =
        rewardDao.getById(id)?.toDomain()

    override suspend fun insertReward(reward: Reward) =
        rewardDao.insert(reward.toEntity())

    override suspend fun insertRewards(rewards: List<Reward>) =
        rewardDao.insertAll(rewards.map { it.toEntity() })

    override suspend fun updateReward(reward: Reward) =
        rewardDao.update(reward.toEntity())

    override fun getRedemptionsByCustomer(customerId: String): Flow<List<Redemption>> =
        redemptionDao.getByCustomer(customerId).map { list -> list.map { it.toDomain() } }

    override suspend fun insertRedemption(redemption: Redemption) =
        redemptionDao.insert(redemption.toEntity())
}

// ---- Mapper extensions ----

fun RewardEntity.toDomain(): Reward = Reward(
    id = id,
    name = name,
    description = description,
    pointsRequired = pointsRequired,
    isActive = isActive,
    category = RewardCategory.fromString(category),
    createdAt = createdAt
)

fun Reward.toEntity(): RewardEntity = RewardEntity(
    id = id,
    name = name,
    description = description,
    pointsRequired = pointsRequired,
    isActive = isActive,
    category = category.name,
    createdAt = createdAt
)

fun RedemptionEntity.toDomain(): Redemption = Redemption(
    id = id,
    customerId = customerId,
    rewardId = rewardId,
    rewardName = rewardName,
    pointsSpent = pointsSpent,
    redeemedAt = redeemedAt
)

fun Redemption.toEntity(): RedemptionEntity = RedemptionEntity(
    id = id,
    customerId = customerId,
    rewardId = rewardId,
    rewardName = rewardName,
    pointsSpent = pointsSpent,
    redeemedAt = redeemedAt
)
