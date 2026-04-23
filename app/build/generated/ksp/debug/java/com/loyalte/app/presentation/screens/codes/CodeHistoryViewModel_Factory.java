package com.loyalte.app.presentation.screens.codes;

import com.loyalte.app.data.remote.api.LoyalteApiService;
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
public final class CodeHistoryViewModel_Factory implements Factory<CodeHistoryViewModel> {
  private final Provider<LoyalteApiService> apiProvider;

  public CodeHistoryViewModel_Factory(Provider<LoyalteApiService> apiProvider) {
    this.apiProvider = apiProvider;
  }

  @Override
  public CodeHistoryViewModel get() {
    return newInstance(apiProvider.get());
  }

  public static CodeHistoryViewModel_Factory create(Provider<LoyalteApiService> apiProvider) {
    return new CodeHistoryViewModel_Factory(apiProvider);
  }

  public static CodeHistoryViewModel newInstance(LoyalteApiService api) {
    return new CodeHistoryViewModel(api);
  }
}
