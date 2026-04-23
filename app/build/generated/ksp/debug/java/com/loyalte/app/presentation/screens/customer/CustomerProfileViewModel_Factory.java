package com.loyalte.app.presentation.screens.customer;

import androidx.lifecycle.SavedStateHandle;
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
public final class CustomerProfileViewModel_Factory implements Factory<CustomerProfileViewModel> {
  private final Provider<SavedStateHandle> savedStateHandleProvider;

  private final Provider<CustomerRepository> customerRepositoryProvider;

  private final Provider<LoyaltyRepository> loyaltyRepositoryProvider;

  public CustomerProfileViewModel_Factory(Provider<SavedStateHandle> savedStateHandleProvider,
      Provider<CustomerRepository> customerRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider) {
    this.savedStateHandleProvider = savedStateHandleProvider;
    this.customerRepositoryProvider = customerRepositoryProvider;
    this.loyaltyRepositoryProvider = loyaltyRepositoryProvider;
  }

  @Override
  public CustomerProfileViewModel get() {
    return newInstance(savedStateHandleProvider.get(), customerRepositoryProvider.get(), loyaltyRepositoryProvider.get());
  }

  public static CustomerProfileViewModel_Factory create(
      Provider<SavedStateHandle> savedStateHandleProvider,
      Provider<CustomerRepository> customerRepositoryProvider,
      Provider<LoyaltyRepository> loyaltyRepositoryProvider) {
    return new CustomerProfileViewModel_Factory(savedStateHandleProvider, customerRepositoryProvider, loyaltyRepositoryProvider);
  }

  public static CustomerProfileViewModel newInstance(SavedStateHandle savedStateHandle,
      CustomerRepository customerRepository, LoyaltyRepository loyaltyRepository) {
    return new CustomerProfileViewModel(savedStateHandle, customerRepository, loyaltyRepository);
  }
}
