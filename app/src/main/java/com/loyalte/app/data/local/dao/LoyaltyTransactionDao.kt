package com.loyalte.app.data.local.dao

import androidx.room.*
import com.loyalte.app.data.local.entity.LoyaltyTransactionEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface LoyaltyTransactionDao {

    @Query(
        "SELECT * FROM loyalty_transactions WHERE customerId = :customerId " +
        "ORDER BY createdAt DESC"
    )
    fun getByCustomer(customerId: String): Flow<List<LoyaltyTransactionEntity>>

    @Query(
        "SELECT * FROM loyalty_transactions WHERE customerId = :customerId " +
        "ORDER BY createdAt DESC LIMIT :limit"
    )
    suspend fun getRecentByCustomer(customerId: String, limit: Int): List<LoyaltyTransactionEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(transaction: LoyaltyTransactionEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(transactions: List<LoyaltyTransactionEntity>)

    @Query("DELETE FROM loyalty_transactions")
    suspend fun deleteAll()
}
