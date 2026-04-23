package com.loyalte.app.presentation.screens.home;

import com.loyalte.app.domain.usecase.LookupCustomerUseCase;
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
public final class HomeViewModel_Factory implements Factory<HomeViewModel> {
  private final Provider<LookupCustomerUseCase> lookupCustomerUseCaseProvider;

  public HomeViewModel_Factory(Provider<LookupCustomerUseCase> lookupCustomerUseCaseProvider) {
    this.lookupCustomerUseCaseProvider = lookupCustomerUseCaseProvider;
  }

  @Override
  public HomeViewModel get() {
    return newInstance(lookupCustomerUseCaseProvider.get());
  }

  public static HomeViewModel_Factory create(
      Provider<LookupCustomerUseCase> lookupCustomerUseCaseProvider) {
    return new HomeViewModel_Factory(lookupCustomerUseCaseProvider);
  }

  public static HomeViewModel newInstance(LookupCustomerUseCase lookupCustomerUseCase) {
    return new HomeViewModel(lookupCustomerUseCase);
  }
}
