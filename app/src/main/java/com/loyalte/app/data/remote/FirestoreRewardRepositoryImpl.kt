package com.loyalte.app.data.remote

import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.Query
import com.google.firebase.firestore.snapshots
import com.loyalte.app.domain.model.Redemption
import com.loyalte.app.domain.model.Reward
import com.loyalte.app.domain.model.RewardCategory
import com.loyalte.app.domain.repository.RewardRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.tasks.await
import javax.inject.Inject

/**
 * Rewards are a top-level collection (shared across all customers).
 * Redemptions are a subcollection under each customer document.
 */
class FirestoreRewardRepositoryImpl @Inject constructor(
    private val db: FirebaseFirestore
) : RewardRepository {

    private val rewardsCol get() = db.collection(REWARDS_COLLECTION)

    private fun redemptionsCol(customerId: String) =
        db.collection(FirestoreCustomerRepositoryImpl.COLLECTION)
            .document(customerId)
            .collection(REDEMPTIONS_COLLECTION)

    override fun getAllActiveRewards(): Flow<List<Reward>> =
        rewardsCol.whereEqualTo("isActive", true)
            .orderBy("pointsRequired", Query.Direction.ASCENDING)
            .snapshots().map { snap ->
                snap.documents.mapNotNull { it.toReward() }
            }

    override fun getAllRewards(): Flow<List<Reward>> =
        rewardsCol.orderBy("pointsRequired", Query.Direction.ASCENDING)
            .snapshots().map { snap ->
                snap.documents.mapNotNull { it.toReward() }
            }

    override suspend fun getRewardById(id: String): Reward? =
        rewardsCol.document(id).get().await().toReward()

    override suspend fun insertReward(reward: Reward) {
        rewardsCol.document(reward.id).set(reward.toMap()).await()
    }

    override suspend fun insertRewards(rewards: List<Reward>) {
        val batch = db.batch()
        rewards.forEach { reward ->
            batch.set(rewardsCol.document(reward.id), reward.toMap())
        }
        batch.commit().await()
    }

    override suspend fun updateReward(reward: Reward) {
        rewardsCol.document(reward.id).set(reward.toMap()).await()
    }

    override fun getRedemptionsByCustomer(customerId: String): Flow<List<Redemption>> =
        redemptionsCol(customerId)
            .orderBy("redeemedAt", Query.Direction.DESCENDING)
            .snapshots().map { snap ->
                snap.documents.mapNotNull { it.toRedemption() }
            }

    override suspend fun insertRedemption(redemption: Redemption) {
        redemptionsCol(redemption.customerId).document(redemption.id)
            .set(redemption.toMap()).await()
    }

    companion object {
        const val REWARDS_COLLECTION = "rewards"
        const val REDEMPTIONS_COLLECTION = "redemptions"
    }
}

// ─── Mappers ────────────────────────────────────────────────────────────────

private fun com.google.firebase.firestore.DocumentSnapshot.toReward(): Reward? {
    if (!exists()) return null
    return try {
        Reward(
            id = id,
            name = getString("name") ?: return null,
            description = getString("description") ?: "",
            pointsRequired = getLong("pointsRequired")?.toInt() ?: 0,
            isActive = getBoolean("isActive") ?: true,
            category = RewardCategory.fromString(getString("category") ?: "GENERAL"),
            createdAt = getLong("createdAt") ?: System.currentTimeMillis()
        )
    } catch (e: Exception) { null }
}

private fun Reward.toMap(): Map<String, Any?> = mapOf(
    "id" to id,
    "name" to name,
    "description" to description,
    "pointsRequired" to pointsRequired,
    "isActive" to isActive,
    "category" to category.name,
    "createdAt" to createdAt
)

private fun com.google.firebase.firestore.DocumentSnapshot.toRedemption(): Redemption? {
    if (!exists()) return null
    return try {
        Redemption(
            id = id,
            customerId = getString("customerId") ?: return null,
            rewardId = getString("rewardId") ?: return null,
            rewardName = getString("rewardName") ?: "",
            pointsSpent = getLong("pointsSpent")?.toInt() ?: 0,
            redeemedAt = getLong("redeemedAt") ?: System.currentTimeMillis()
        )
    } catch (e: Exception) { null }
}

private fun Redemption.toMap(): Map<String, Any?> = mapOf(
    "id" to id,
    "customerId" to customerId,
    "rewardId" to rewardId,
    "rewardName" to rewardName,
    "pointsSpent" to pointsSpent,
    "redeemedAt" to redeemedAt
)
