package com.loyalte.app.data.repository;

import com.loyalte.app.data.local.dao.CustomerDao;
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
public final class CustomerRepositoryImpl_Factory implements Factory<CustomerRepositoryImpl> {
  private final Provider<CustomerDao> customerDaoProvider;

  public CustomerRepositoryImpl_Factory(Provider<CustomerDao> customerDaoProvider) {
    this.customerDaoProvider = customerDaoProvider;
  }

  @Override
  public CustomerRepositoryImpl get() {
    return newInstance(customerDaoProvider.get());
  }

  public static CustomerRepositoryImpl_Factory create(Provider<CustomerDao> customerDaoProvider) {
    return new CustomerRepositoryImpl_Factory(customerDaoProvider);
  }

  public static CustomerRepositoryImpl newInstance(CustomerDao customerDao) {
    return new CustomerRepositoryImpl(customerDao);
  }
}
