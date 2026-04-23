package com.loyalte.app.presentation.screens.scan;

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
public final class QrScanViewModel_Factory implements Factory<QrScanViewModel> {
  private final Provider<LookupCustomerUseCase> lookupCustomerUseCaseProvider;

  public QrScanViewModel_Factory(Provider<LookupCustomerUseCase> lookupCustomerUseCaseProvider) {
    this.lookupCustomerUseCaseProvider = lookupCustomerUseCaseProvider;
  }

  @Override
  public QrScanViewModel get() {
    return newInstance(lookupCustomerUseCaseProvider.get());
  }

  public static QrScanViewModel_Factory create(
      Provider<LookupCustomerUseCase> lookupCustomerUseCaseProvider) {
    return new QrScanViewModel_Factory(lookupCustomerUseCaseProvider);
  }

  public static QrScanViewModel newInstance(LookupCustomerUseCase lookupCustomerUseCase) {
    return new QrScanViewModel(lookupCustomerUseCase);
  }
}
