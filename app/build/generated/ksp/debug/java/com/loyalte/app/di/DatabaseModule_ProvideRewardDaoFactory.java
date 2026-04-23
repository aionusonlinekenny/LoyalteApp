package com.loyalte.app.di;

import com.loyalte.app.data.local.LoyalteDatabase;
import com.loyalte.app.data.local.dao.RewardDao;
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
public final class DatabaseModule_ProvideRewardDaoFactory implements Factory<RewardDao> {
  private final Provider<LoyalteDatabase> dbProvider;

  public DatabaseModule_ProvideRewardDaoFactory(Provider<LoyalteDatabase> dbProvider) {
    this.dbProvider = dbProvider;
  }

  @Override
  public RewardDao get() {
    return provideRewardDao(dbProvider.get());
  }

  public static DatabaseModule_ProvideRewardDaoFactory create(
      Provider<LoyalteDatabase> dbProvider) {
    return new DatabaseModule_ProvideRewardDaoFactory(dbProvider);
  }

  public static RewardDao provideRewardDao(LoyalteDatabase db) {
    return Preconditions.checkNotNullFromProvides(DatabaseModule.INSTANCE.provideRewardDao(db));
  }
}
