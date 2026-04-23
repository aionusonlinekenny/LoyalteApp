package com.loyalte.app.data.remote;

import com.loyalte.app.data.remote.api.LoyalteApiService;
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
public final class RetrofitRewardRepositoryImpl_Factory implements Factory<RetrofitRewardRepositoryImpl> {
  private final Provider<LoyalteApiService> apiProvider;

  public RetrofitRewardRepositoryImpl_Factory(Provider<LoyalteApiService> apiProvider) {
    this.apiProvider = apiProvider;
  }

  @Override
  public RetrofitRewardRepositoryImpl get() {
    return newInstance(apiProvider.get());
  }

  public static RetrofitRewardRepositoryImpl_Factory create(
      Provider<LoyalteApiService> apiProvider) {
    return new RetrofitRewardRepositoryImpl_Factory(apiProvider);
  }

  public static RetrofitRewardRepositoryImpl newInstance(LoyalteApiService api) {
    return new RetrofitRewardRepositoryImpl(api);
  }
}
