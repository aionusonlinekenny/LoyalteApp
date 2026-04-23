package com.loyalte.app.data.repository;

import com.loyalte.app.data.local.dao.RedemptionDao;
import com.loyalte.app.data.local.dao.RewardDao;
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
public final class RewardRepositoryImpl_Factory implements Factory<RewardRepositoryImpl> {
  private final Provider<RewardDao> rewardDaoProvider;

  private final Provider<RedemptionDao> redemptionDaoProvider;

  public RewardRepositoryImpl_Factory(Provider<RewardDao> rewardDaoProvider,
      Provider<RedemptionDao> redemptionDaoProvider) {
    this.rewardDaoProvider = rewardDaoProvider;
    this.redemptionDaoProvider = redemptionDaoProvider;
  }

  @Override
  public RewardRepositoryImpl get() {
    return newInstance(rewardDaoProvider.get(), redemptionDaoProvider.get());
  }

  public static RewardRepositoryImpl_Factory create(Provider<RewardDao> rewardDaoProvider,
      Provider<RedemptionDao> redemptionDaoProvider) {
    return new RewardRepositoryImpl_Factory(rewardDaoProvider, redemptionDaoProvider);
  }

  public static RewardRepositoryImpl newInstance(RewardDao rewardDao, RedemptionDao redemptionDao) {
    return new RewardRepositoryImpl(rewardDao, redemptionDao);
  }
}
