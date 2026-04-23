package com.loyalte.app.di

import com.loyalte.app.data.remote.RetrofitCustomerRepositoryImpl
import com.loyalte.app.data.remote.RetrofitLoyaltyRepositoryImpl
import com.loyalte.app.data.remote.RetrofitRewardRepositoryImpl
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
    abstract fun bindCustomerRepository(
        impl: RetrofitCustomerRepositoryImpl
    ): CustomerRepository

    @Binds
    @Singleton
    abstract fun bindLoyaltyRepository(
        impl: RetrofitLoyaltyRepositoryImpl
    ): LoyaltyRepository

    @Binds
    @Singleton
    abstract fun bindRewardRepository(
        impl: RetrofitRewardRepositoryImpl
    ): RewardRepository
}
