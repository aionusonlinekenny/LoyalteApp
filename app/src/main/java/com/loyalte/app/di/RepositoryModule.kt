package com.loyalte.app.di

import com.loyalte.app.data.remote.FirestoreCustomerRepositoryImpl
import com.loyalte.app.data.remote.FirestoreLoyaltyRepositoryImpl
import com.loyalte.app.data.remote.FirestoreRewardRepositoryImpl
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.LoyaltyRepository
import com.loyalte.app.domain.repository.RewardRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

/**
 * Binds Firestore (cloud) implementations of each repository.
 *
 * TO SWITCH BACK TO LOCAL ROOM (offline/dev):
 *   Replace FirestoreCustomerRepositoryImpl → CustomerRepositoryImpl
 *   Replace FirestoreLoyaltyRepositoryImpl  → LoyaltyRepositoryImpl
 *   Replace FirestoreRewardRepositoryImpl   → RewardRepositoryImpl
 *   (All Room implementations are still compiled and available.)
 */
@Module
@InstallIn(SingletonComponent::class)
abstract class RepositoryModule {

    @Binds
    @Singleton
    abstract fun bindCustomerRepository(
        impl: FirestoreCustomerRepositoryImpl
    ): CustomerRepository

    @Binds
    @Singleton
    abstract fun bindLoyaltyRepository(
        impl: FirestoreLoyaltyRepositoryImpl
    ): LoyaltyRepository

    @Binds
    @Singleton
    abstract fun bindRewardRepository(
        impl: FirestoreRewardRepositoryImpl
    ): RewardRepository
}
