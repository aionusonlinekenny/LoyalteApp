package com.loyalte.app.presentation.screens.customer;

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
public final class CustomerListViewModel_Factory implements Factory<CustomerListViewModel> {
  private final Provider<CustomerRepository> customerRepositoryProvider;

  public CustomerListViewModel_Factory(Provider<CustomerRepository> customerRepositoryProvider) {
    this.customerRepositoryProvider = customerRepositoryProvider;
  }

  @Override
  public CustomerListViewModel get() {
    return newInstance(customerRepositoryProvider.get());
  }

  public static CustomerListViewModel_Factory create(
      Provider<CustomerRepository> customerRepositoryProvider) {
    return new CustomerListViewModel_Factory(customerRepositoryProvider);
  }

  public static CustomerListViewModel newInstance(CustomerRepository customerRepository) {
    return new CustomerListViewModel(customerRepository);
  }
}
