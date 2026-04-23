package com.loyalte.app.di;

import com.loyalte.app.data.local.LoyalteDatabase;
import com.loyalte.app.data.local.dao.RedemptionDao;
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
public final class DatabaseModule_ProvideRedemptionDaoFactory implements Factory<RedemptionDao> {
  private final Provider<LoyalteDatabase> dbProvider;

  public DatabaseModule_ProvideRedemptionDaoFactory(Provider<LoyalteDatabase> dbProvider) {
    this.dbProvider = dbProvider;
  }

  @Override
  public RedemptionDao get() {
    return provideRedemptionDao(dbProvider.get());
  }

  public static DatabaseModule_ProvideRedemptionDaoFactory create(
      Provider<LoyalteDatabase> dbProvider) {
    return new DatabaseModule_ProvideRedemptionDaoFactory(dbProvider);
  }

  public static RedemptionDao provideRedemptionDao(LoyalteDatabase db) {
    return Preconditions.checkNotNullFromProvides(DatabaseModule.INSTANCE.provideRedemptionDao(db));
  }
}
