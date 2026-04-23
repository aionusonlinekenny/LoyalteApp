package com.loyalte.app;

import com.loyalte.app.util.SeedDataUtil;
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
public final class LoyalteApplication_MembersInjector implements MembersInjector<LoyalteApplication> {
  private final Provider<SeedDataUtil> seedDataUtilProvider;

  public LoyalteApplication_MembersInjector(Provider<SeedDataUtil> seedDataUtilProvider) {
    this.seedDataUtilProvider = seedDataUtilProvider;
  }

  public static MembersInjector<LoyalteApplication> create(
      Provider<SeedDataUtil> seedDataUtilProvider) {
    return new LoyalteApplication_MembersInjector(seedDataUtilProvider);
  }

  @Override
  public void injectMembers(LoyalteApplication instance) {
    injectSeedDataUtil(instance, seedDataUtilProvider.get());
  }

  @InjectedFieldSignature("com.loyalte.app.LoyalteApplication.seedDataUtil")
  public static void injectSeedDataUtil(LoyalteApplication instance, SeedDataUtil seedDataUtil) {
    instance.seedDataUtil = seedDataUtil;
  }
}
