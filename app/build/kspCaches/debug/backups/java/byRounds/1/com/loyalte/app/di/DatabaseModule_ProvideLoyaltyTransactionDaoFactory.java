package com.loyalte.app.di;

import com.loyalte.app.data.local.LoyalteDatabase;
import com.loyalte.app.data.local.dao.LoyaltyTransactionDao;
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
public final class DatabaseModule_ProvideLoyaltyTransactionDaoFactory implements Factory<LoyaltyTransactionDao> {
  private final Provider<LoyalteDatabase> dbProvider;

  public DatabaseModule_ProvideLoyaltyTransactionDaoFactory(Provider<LoyalteDatabase> dbProvider) {
    this.dbProvider = dbProvider;
  }

  @Override
  public LoyaltyTransactionDao get() {
    return provideLoyaltyTransactionDao(dbProvider.get());
  }

  public static DatabaseModule_ProvideLoyaltyTransactionDaoFactory create(
      Provider<LoyalteDatabase> dbProvider) {
    return new DatabaseModule_ProvideLoyaltyTransactionDaoFactory(dbProvider);
  }

  public static LoyaltyTransactionDao provideLoyaltyTransactionDao(LoyalteDatabase db) {
    return Preconditions.checkNotNullFromProvides(DatabaseModule.INSTANCE.provideLoyaltyTransactionDao(db));
  }
}
