package com.loyalte.app.data.local.dao

import androidx.room.*
import com.loyalte.app.data.local.entity.RewardEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface RewardDao {

    @Query("SELECT * FROM rewards WHERE isActive = 1 ORDER BY pointsRequired ASC")
    fun getAllActive(): Flow<List<RewardEntity>>

    @Query("SELECT * FROM rewards ORDER BY pointsRequired ASC")
    fun getAll(): Flow<List<RewardEntity>>

    @Query("SELECT * FROM rewards WHERE id = :id LIMIT 1")
    suspend fun getById(id: String): RewardEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(reward: RewardEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(rewards: List<RewardEntity>)

    @Update
    suspend fun update(reward: RewardEntity)

    @Query("DELETE FROM rewards")
    suspend fun deleteAll()
}
