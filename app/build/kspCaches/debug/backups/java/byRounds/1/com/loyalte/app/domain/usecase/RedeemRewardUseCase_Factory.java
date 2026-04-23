package com.loyalte.app.domain.usecase;

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
public final class RedeemRewardUseCase_Factory implements Factory<RedeemRewardUseCase> {
  private final Provider<CustomerRepository> customerRepositoryProvider;

  private final Provider<RewardRepository> rewardRepositoryProvider;

  private final Provider<LoyaltyRepository> loyaltyRepositoryProvider;

  public RedeemRewardUseCase_Factory(Provider<CustomerRepository> customerRepositoryProvider,
      Provider<RewardRepository> rewardRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider) {
    this.customerRepositoryProvider = customerRepositoryProvider;
    this.rewardRepositoryProvider = rewardRepositoryProvider;
    this.loyaltyRepositoryProvider = loyaltyRepositoryProvider;
  }

  @Override
  public RedeemRewardUseCase get() {
    return newInstance(customerRepositoryProvider.get(), rewardRepositoryProvider.get(), loyaltyRepositoryProvider.get());
  }

  public static RedeemRewardUseCase_Factory create(
      Provider<CustomerRepository> customerRepositoryProvider,
      Provider<RewardRepository> rewardRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider) {
    return new RedeemRewardUseCase_Factory(customerRepositoryProvider, rewardRepositoryProvider, loyaltyRepositoryProvider);
  }

  public static RedeemRewardUseCase newInstance(CustomerRepository customerRepository,
      RewardRepository rewardRepository, LoyaltyRepository loyaltyRepository) {
    return new RedeemRewardUseCase(customerRepository, rewardRepository, loyaltyRepository);
  }
}
