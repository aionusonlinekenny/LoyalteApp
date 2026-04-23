package com.loyalte.app.util;

import com.loyalte.app.domain.repository.CustomerRepository;
import com.loyalte.app.domain.repository.LoyaltyRepository;
import com.loyalte.app.domain.repository.RewardRepository;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata
@QualifierMetadata
@DaggerGenerated
@Generated(
    value = "dagger.internal.codegen.ComponentProcessor",
    comments = "https://dagger.dev"
)
@SuppressWarnings({
    "unchecked",
    "rawtypes",
    "KotlinInternal",
    "KotlinInternalInJava",
    "cast"
})
public final class SeedDataUtil_Factory implements Factory<SeedDataUtil> {
  private final Provider<CustomerRepository> customerRepositoryProvider;

  private final Provider<LoyaltyRepository> loyaltyRepositoryProvider;

  private final Provider<RewardRepository> rewardRepositoryProvider;

  public SeedDataUtil_Factory(Provider<CustomerRepository> customerRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider,
      Provider<RewardRepository> rewardRepositoryProvider) {
    this.customerRepositoryProvider = customerRepositoryProvider;
    this.loyaltyRepositoryProvider = loyaltyRepositoryProvider;
    this.rewardRepositoryProvider = rewardRepositoryProvider;
  }

  @Override
  public SeedDataUtil get() {
    return newInstance(customerRepositoryProvider.get(), loyaltyRepositoryProvider.get(), rewardRepositoryProvider.get());
  }

  public static SeedDataUtil_Factory create(Provider<CustomerRepository> customerRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider,
      Provider<RewardRepository> rewardRepositoryProvider) {
    return new SeedDataUtil_Factory(customerRepositoryProvider, loyaltyRepositoryProvider, rewardRepositoryProvider);
  }

  public static SeedDataUtil newInstance(CustomerRepository customerRepository,
      LoyaltyRepository loyaltyRepository, RewardRepository rewardRepository) {
    return new SeedDataUtil(customerRepository, loyaltyRepository, rewardRepository);
  }
}
