package com.loyalte.app.domain.usecase;

import com.loyalte.app.domain.repository.CustomerRepository;
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
public final class LookupCustomerUseCase_Factory implements Factory<LookupCustomerUseCase> {
  private final Provider<CustomerRepository> customerRepositoryProvider;

  public LookupCustomerUseCase_Factory(Provider<CustomerRepository> customerRepositoryProvider) {
    this.customerRepositoryProvider = customerRepositoryProvider;
  }

  @Override
  public LookupCustomerUseCase get() {
    return newInstance(customerRepositoryProvider.get());
  }

  public static LookupCustomerUseCase_Factory create(
      Provider<CustomerRepository> customerRepositoryProvider) {
    return new LookupCustomerUseCase_Factory(customerRepositoryProvider);
  }

  public static LookupCustomerUseCase newInstance(CustomerRepository customerRepository) {
    return new LookupCustomerUseCase(customerRepository);
  }
}
