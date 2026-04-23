package com.loyalte.app.data.local

import androidx.room.Database
import androidx.room.RoomDatabase
import com.loyalte.app.data.local.dao.CustomerDao
import com.loyalte.app.data.local.dao.LoyaltyTransactionDao
import com.loyalte.app.data.local.dao.RedemptionDao
import com.loyalte.app.data.local.dao.RewardDao
import com.loyalte.app.data.local.entity.CustomerEntity
import com.loyalte.app.data.local.entity.LoyaltyTransactionEntity
import com.loyalte.app.data.local.entity.RedemptionEntity
import com.loyalte.app.data.local.entity.RewardEntity

/**
 * Main Room database.
 * Increment [version] and provide a Migration object whenever the schema changes in production.
 * During early development, fallbackToDestructiveMigration() is used instead.
 */
@Database(
    entities = [
        CustomerEntity::class,
        LoyaltyTransactionEntity::class,
        RewardEntity::class,
        RedemptionEntity::class
    ],
    version = 1,
    exportSchema = false
)
abstract class LoyalteDatabase : RoomDatabase() {
    abstract fun customerDao(): CustomerDao
    abstract fun loyaltyTransactionDao(): LoyaltyTransactionDao
    abstract fun rewardDao(): RewardDao
    abstract fun redemptionDao(): RedemptionDao
}
