package com.loyalte.app.di;

import com.loyalte.app.data.local.prefs.AuthPreferences;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.Preconditions;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;
import okhttp3.Interceptor;

@ScopeMetadata("javax.inject.Singleton")
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
public final class NetworkModule_ProvideAuthInterceptorFactory implements Factory<Interceptor> {
  private final Provider<AuthPreferences> authPreferencesProvider;

  public NetworkModule_ProvideAuthInterceptorFactory(
      Provider<AuthPreferences> authPreferencesProvider) {
    this.authPreferencesProvider = authPreferencesProvider;
  }

  @Override
  public Interceptor get() {
    return provideAuthInterceptor(authPreferencesProvider.get());
  }

  public static NetworkModule_ProvideAuthInterceptorFactory create(
      Provider<AuthPreferences> authPreferencesProvider) {
    return new NetworkModule_ProvideAuthInterceptorFactory(authPreferencesProvider);
  }

  public static Interceptor provideAuthInterceptor(AuthPreferences authPreferences) {
    return Preconditions.checkNotNullFromProvides(NetworkModule.INSTANCE.provideAuthInterceptor(authPreferences));
  }
}
