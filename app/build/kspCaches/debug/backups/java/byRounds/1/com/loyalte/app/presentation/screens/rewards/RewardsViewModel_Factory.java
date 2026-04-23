package com.loyalte.app.presentation.screens.rewards;

import androidx.lifecycle.SavedStateHandle;
import com.loyalte.app.domain.repository.CustomerRepository;
import com.loyalte.app.domain.repository.RewardRepository;
import com.loyalte.app.domain.usecase.RedeemRewardUseCase;
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
public final class RewardsViewModel_Factory implements Factory<RewardsViewModel> {
  private final Provider<SavedStateHandle> savedStateHandleProvider;

  private final Provider<CustomerRepository> customerRepositoryProvider;

  private final Provider<RewardRepository> rewardRepositoryProvider;

  private final Provider<RedeemRewardUseCase> redeemRewardUseCaseProvider;

  public RewardsViewModel_Factory(Provider<SavedStateHandle> savedStateHandleProvider,
      Provider<CustomerRepository> customerRepositoryProvider,
      Provider<RewardRepository> rewardRepositoryProvider,
      Provider<RedeemRewardUseCase> redeemRewardUseCaseProvider) {
    this.savedStateHandleProvider = savedStateHandleProvider;
    this.customerRepositoryProvider = customerRepositoryProvider;
    this.rewardRepositoryProvider = rewardRepositoryProvider;
    this.redeemRewardUseCaseProvider = redeemRewardUseCaseProvider;
  }

  @Override
  public RewardsViewModel get() {
    return newInstance(savedStateHandleProvider.get(), customerRepositoryProvider.get(), rewardRepositoryProvider.get(), redeemRewardUseCaseProvider.get());
  }

  public static RewardsViewModel_Factory create(Provider<SavedStateHandle> savedStateHandleProvider,
      Provider<CustomerRepository> customerRepositoryProvider,
      Provider<RewardRepository> rewardRepositoryProvider,
      Provider<RedeemRewardUseCase> redeemRewardUseCaseProvider) {
    return new RewardsViewModel_Factory(savedStateHandleProvider, customerRepositoryProvider, rewardRepositoryProvider, redeemRewardUseCaseProvider);
  }

  public static RewardsViewModel newInstance(SavedStateHandle savedStateHandle,
      CustomerRepository customerRepository, RewardRepository rewardRepository,
      RedeemRewardUseCase redeemRewardUseCase) {
    return new RewardsViewModel(savedStateHandle, customerRepository, rewardRepository, redeemRewardUseCase);
  }
}
