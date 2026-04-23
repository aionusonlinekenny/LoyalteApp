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
public final class RetrofitLoyaltyRepositoryImpl_Factory implements Factory<RetrofitLoyaltyRepositoryImpl> {
  private final Provider<LoyalteApiService> apiProvider;

  public RetrofitLoyaltyRepositoryImpl_Factory(Provider<LoyalteApiService> apiProvider) {
    this.apiProvider = apiProvider;
  }

  @Override
  public RetrofitLoyaltyRepositoryImpl get() {
    return newInstance(apiProvider.get());
  }

  public static RetrofitLoyaltyRepositoryImpl_Factory create(
      Provider<LoyalteApiService> apiProvider) {
    return new RetrofitLoyaltyRepositoryImpl_Factory(apiProvider);
  }

  public static RetrofitLoyaltyRepositoryImpl newInstance(LoyalteApiService api) {
    return new RetrofitLoyaltyRepositoryImpl(api);
  }
}
