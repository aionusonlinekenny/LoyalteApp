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
public final class RetrofitCustomerRepositoryImpl_Factory implements Factory<RetrofitCustomerRepositoryImpl> {
  private final Provider<LoyalteApiService> apiProvider;

  public RetrofitCustomerRepositoryImpl_Factory(Provider<LoyalteApiService> apiProvider) {
    this.apiProvider = apiProvider;
  }

  @Override
  public RetrofitCustomerRepositoryImpl get() {
    return newInstance(apiProvider.get());
  }

  public static RetrofitCustomerRepositoryImpl_Factory create(
      Provider<LoyalteApiService> apiProvider) {
    return new RetrofitCustomerRepositoryImpl_Factory(apiProvider);
  }

  public static RetrofitCustomerRepositoryImpl newInstance(LoyalteApiService api) {
    return new RetrofitCustomerRepositoryImpl(api);
  }
}
