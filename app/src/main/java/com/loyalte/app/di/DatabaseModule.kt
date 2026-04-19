package com.loyalte.app.di

import android.content.Context
import androidx.room.Room
import com.loyalte.app.data.local.LoyalteDatabase
import com.loyalte.app.data.local.dao.CustomerDao
import com.loyalte.app.data.local.dao.LoyaltyTransactionDao
import com.loyalte.app.data.local.dao.RedemptionDao
import com.loyalte.app.data.local.dao.RewardDao
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    @Provides
    @Singleton
    fun provideDatabase(@ApplicationContext context: Context): LoyalteDatabase =
        Room.databaseBuilder(
            context,
            LoyalteDatabase::class.java,
            "loyalte.db"
        )
            // Use proper Migration objects in production instead of destructive migration.
            .fallbackToDestructiveMigration()
            .build()

    @Provides
    fun provideCustomerDao(db: LoyalteDatabase): CustomerDao = db.customerDao()

    @Provides
    fun provideLoyaltyTransactionDao(db: LoyalteDatabase): LoyaltyTransactionDao =
        db.loyaltyTransactionDao()

    @Provides
    fun provideRewardDao(db: LoyalteDatabase): RewardDao = db.rewardDao()

    @Provides
    fun provideRedemptionDao(db: LoyalteDatabase): RedemptionDao = db.redemptionDao()
}
