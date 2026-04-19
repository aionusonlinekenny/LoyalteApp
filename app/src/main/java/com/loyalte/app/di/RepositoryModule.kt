package com.loyalte.app.di

import com.loyalte.app.data.repository.CustomerRepositoryImpl
import com.loyalte.app.data.repository.LoyaltyRepositoryImpl
import com.loyalte.app.data.repository.RewardRepositoryImpl
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.LoyaltyRepository
import com.loyalte.app.domain.repository.RewardRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class RepositoryModule {

    @Binds
    @Singleton
    abstract fun bindCustomerRepository(impl: CustomerRepositoryImpl): CustomerRepository

    @Binds
    @Singleton
    abstract fun bindLoyaltyRepository(impl: LoyaltyRepositoryImpl): LoyaltyRepository

    @Binds
    @Singleton
    abstract fun bindRewardRepository(impl: RewardRepositoryImpl): RewardRepository
}
