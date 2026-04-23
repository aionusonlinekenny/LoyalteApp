package com.loyalte.app.presentation.screens.auth;

import com.loyalte.app.data.local.prefs.AuthPreferences;
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
public final class StaffLoginViewModel_Factory implements Factory<StaffLoginViewModel> {
  private final Provider<LoyalteApiService> apiProvider;

  private final Provider<AuthPreferences> authPreferencesProvider;

  public StaffLoginViewModel_Factory(Provider<LoyalteApiService> apiProvider,
      Provider<AuthPreferences> authPreferencesProvider) {
    this.apiProvider = apiProvider;
    this.authPreferencesProvider = authPreferencesProvider;
  }

  @Override
  public StaffLoginViewModel get() {
    return newInstance(apiProvider.get(), authPreferencesProvider.get());
  }

  public static StaffLoginViewModel_Factory create(Provider<LoyalteApiService> apiProvider,
      Provider<AuthPreferences> authPreferencesProvider) {
    return new StaffLoginViewModel_Factory(apiProvider, authPreferencesProvider);
  }

  public static StaffLoginViewModel newInstance(LoyalteApiService api,
      AuthPreferences authPreferences) {
    return new StaffLoginViewModel(api, authPreferences);
  }
}
