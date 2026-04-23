package com.loyalte.app.presentation.screens.customer;

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
public final class AddCustomerViewModel_Factory implements Factory<AddCustomerViewModel> {
  private final Provider<LoyalteApiService> apiProvider;

  public AddCustomerViewModel_Factory(Provider<LoyalteApiService> apiProvider) {
    this.apiProvider = apiProvider;
  }

  @Override
  public AddCustomerViewModel get() {
    return newInstance(apiProvider.get());
  }

  public static AddCustomerViewModel_Factory create(Provider<LoyalteApiService> apiProvider) {
    return new AddCustomerViewModel_Factory(apiProvider);
  }

  public static AddCustomerViewModel newInstance(LoyalteApiService api) {
    return new AddCustomerViewModel(api);
  }
}
