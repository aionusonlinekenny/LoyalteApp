package com.loyalte.app.data.local.dao

import androidx.room.*
import com.loyalte.app.data.local.entity.CustomerEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface CustomerDao {

    @Query("SELECT * FROM customers ORDER BY name ASC")
    fun getAllCustomers(): Flow<List<CustomerEntity>>

    @Query("SELECT * FROM customers WHERE id = :id LIMIT 1")
    suspend fun getById(id: String): CustomerEntity?

    @Query("SELECT * FROM customers WHERE phone = :phone LIMIT 1")
    suspend fun getByPhone(phone: String): CustomerEntity?

    @Query("SELECT * FROM customers WHERE qrCode = :qrCode LIMIT 1")
    suspend fun getByQrCode(qrCode: String): CustomerEntity?

    @Query("SELECT * FROM customers WHERE memberId = :memberId LIMIT 1")
    suspend fun getByMemberId(memberId: String): CustomerEntity?

    @Insert(onConflict = OnConflictStrategy.ABORT)
    suspend fun insert(customer: CustomerEntity)

    @Update
    suspend fun update(customer: CustomerEntity)

    @Query("UPDATE customers SET points = :points, updatedAt = :updatedAt WHERE id = :id")
    suspend fun updatePoints(id: String, points: Int, updatedAt: Long = System.currentTimeMillis())

    @Query("SELECT COUNT(*) FROM customers")
    suspend fun getCount(): Int

    @Query("DELETE FROM customers")
    suspend fun deleteAll()
}
