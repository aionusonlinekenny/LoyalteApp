package com.loyalte.app.domain.usecase;

import com.loyalte.app.domain.repository.CustomerRepository;
import com.loyalte.app.domain.repository.LoyaltyRepository;
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
public final class GetCustomerProfileUseCase_Factory implements Factory<GetCustomerProfileUseCase> {
  private final Provider<CustomerRepository> customerRepositoryProvider;

  private final Provider<LoyaltyRepository> loyaltyRepositoryProvider;

  public GetCustomerProfileUseCase_Factory(Provider<CustomerRepository> customerRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider) {
    this.customerRepositoryProvider = customerRepositoryProvider;
    this.loyaltyRepositoryProvider = loyaltyRepositoryProvider;
  }

  @Override
  public GetCustomerProfileUseCase get() {
    return newInstance(customerRepositoryProvider.get(), loyaltyRepositoryProvider.get());
  }

  public static GetCustomerProfileUseCase_Factory create(
      Provider<CustomerRepository> customerRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider) {
    return new GetCustomerProfileUseCase_Factory(customerRepositoryProvider, loyaltyRepositoryProvider);
  }

  public static GetCustomerProfileUseCase newInstance(CustomerRepository customerRepository,
      LoyaltyRepository loyaltyRepository) {
    return new GetCustomerProfileUseCase(customerRepository, loyaltyRepository);
  }
}
