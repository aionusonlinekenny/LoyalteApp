package com.loyalte.app.di;

import com.loyalte.app.data.local.LoyalteDatabase;
import com.loyalte.app.data.local.dao.CustomerDao;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.Preconditions;
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
public final class DatabaseModule_ProvideCustomerDaoFactory implements Factory<CustomerDao> {
  private final Provider<LoyalteDatabase> dbProvider;

  public DatabaseModule_ProvideCustomerDaoFactory(Provider<LoyalteDatabase> dbProvider) {
    this.dbProvider = dbProvider;
  }

  @Override
  public CustomerDao get() {
    return provideCustomerDao(dbProvider.get());
  }

  public static DatabaseModule_ProvideCustomerDaoFactory create(
      Provider<LoyalteDatabase> dbProvider) {
    return new DatabaseModule_ProvideCustomerDaoFactory(dbProvider);
  }

  public static CustomerDao provideCustomerDao(LoyalteDatabase db) {
    return Preconditions.checkNotNullFromProvides(DatabaseModule.INSTANCE.provideCustomerDao(db));
  }
}
