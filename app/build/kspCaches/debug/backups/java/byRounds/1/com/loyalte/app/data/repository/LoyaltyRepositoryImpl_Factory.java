package com.loyalte.app.data.repository;

import com.loyalte.app.data.local.dao.LoyaltyTransactionDao;
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
public final class LoyaltyRepositoryImpl_Factory implements Factory<LoyaltyRepositoryImpl> {
  private final Provider<LoyaltyTransactionDao> transactionDaoProvider;

  public LoyaltyRepositoryImpl_Factory(Provider<LoyaltyTransactionDao> transactionDaoProvider) {
    this.transactionDaoProvider = transactionDaoProvider;
  }

  @Override
  public LoyaltyRepositoryImpl get() {
    return newInstance(transactionDaoProvider.get());
  }

  public static LoyaltyRepositoryImpl_Factory create(
      Provider<LoyaltyTransactionDao> transactionDaoProvider) {
    return new LoyaltyRepositoryImpl_Factory(transactionDaoProvider);
  }

  public static LoyaltyRepositoryImpl newInstance(LoyaltyTransactionDao transactionDao) {
    return new LoyaltyRepositoryImpl(transactionDao);
  }
}
