package com.loyalte.app.data.local.dao

import androidx.room.*
import com.loyalte.app.data.local.entity.RedemptionEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface RedemptionDao {

    @Query(
        "SELECT * FROM redemptions WHERE customerId = :customerId " +
        "ORDER BY redeemedAt DESC"
    )
    fun getByCustomer(customerId: String): Flow<List<RedemptionEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(redemption: RedemptionEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(redemptions: List<RedemptionEntity>)

    @Query("DELETE FROM redemptions")
    suspend fun deleteAll()
}
