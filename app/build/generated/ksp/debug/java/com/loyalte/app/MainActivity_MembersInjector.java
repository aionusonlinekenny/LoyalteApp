package com.loyalte.app;

import com.loyalte.app.data.local.prefs.AuthPreferences;
import dagger.MembersInjector;
import dagger.internal.DaggerGenerated;
import dagger.internal.InjectedFieldSignature;
import dagger.internal.QualifierMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

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
public final class MainActivity_MembersInjector implements MembersInjector<MainActivity> {
  private final Provider<AuthPreferences> authPreferencesProvider;

  public MainActivity_MembersInjector(Provider<AuthPreferences> authPreferencesProvider) {
    this.authPreferencesProvider = authPreferencesProvider;
  }

  public static MembersInjector<MainActivity> create(
      Provider<AuthPreferences> authPreferencesProvider) {
    return new MainActivity_MembersInjector(authPreferencesProvider);
  }

  @Override
  public void injectMembers(MainActivity instance) {
    injectAuthPreferences(instance, authPreferencesProvider.get());
  }

  @InjectedFieldSignature("com.loyalte.app.MainActivity.authPreferences")
  public static void injectAuthPreferences(MainActivity instance, AuthPreferences authPreferences) {
    instance.authPreferences = authPreferences;
  }
}
