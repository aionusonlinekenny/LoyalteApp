package com.loyalte.app;

import android.app.Activity;
import android.app.Service;
import android.view.View;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.SavedStateHandle;
import androidx.lifecycle.ViewModel;
import com.loyalte.app.data.local.prefs.AuthPreferences;
import com.loyalte.app.data.remote.RetrofitCustomerRepositoryImpl;
import com.loyalte.app.data.remote.RetrofitLoyaltyRepositoryImpl;
import com.loyalte.app.data.remote.RetrofitRewardRepositoryImpl;
import com.loyalte.app.data.remote.api.LoyalteApiService;
import com.loyalte.app.di.NetworkModule_ProvideAuthInterceptorFactory;
import com.loyalte.app.di.NetworkModule_ProvideLoyalteApiServiceFactory;
import com.loyalte.app.di.NetworkModule_ProvideOkHttpClientFactory;
import com.loyalte.app.di.NetworkModule_ProvideRetrofitFactory;
import com.loyalte.app.domain.repository.CustomerRepository;
import com.loyalte.app.domain.repository.LoyaltyRepository;
import com.loyalte.app.domain.repository.RewardRepository;
import com.loyalte.app.domain.usecase.LookupCustomerUseCase;
import com.loyalte.app.domain.usecase.RedeemRewardUseCase;
import com.loyalte.app.presentation.screens.auth.StaffLoginViewModel;
import com.loyalte.app.presentation.screens.auth.StaffLoginViewModel_HiltModules;
import com.loyalte.app.presentation.screens.codes.CodeHistoryViewModel;
import com.loyalte.app.presentation.screens.codes.CodeHistoryViewModel_HiltModules;
import com.loyalte.app.presentation.screens.customer.AddCustomerViewModel;
import com.loyalte.app.presentation.screens.customer.AddCustomerViewModel_HiltModules;
import com.loyalte.app.presentation.screens.customer.CustomerListViewModel;
import com.loyalte.app.presentation.screens.customer.CustomerListViewModel_HiltModules;
import com.loyalte.app.presentation.screens.customer.CustomerProfileViewModel;
import com.loyalte.app.presentation.screens.customer.CustomerProfileViewModel_HiltModules;
import com.loyalte.app.presentation.screens.home.HomeViewModel;
import com.loyalte.app.presentation.screens.home.HomeViewModel_HiltModules;
import com.loyalte.app.presentation.screens.rewards.RewardsViewModel;
import com.loyalte.app.presentation.screens.rewards.RewardsViewModel_HiltModules;
import com.loyalte.app.presentation.screens.scan.QrScanViewModel;
import com.loyalte.app.presentation.screens.scan.QrScanViewModel_HiltModules;
import com.loyalte.app.util.SeedDataUtil;
import dagger.hilt.android.ActivityRetainedLifecycle;
import dagger.hilt.android.ViewModelLifecycle;
import dagger.hilt.android.internal.builders.ActivityComponentBuilder;
import dagger.hilt.android.internal.builders.ActivityRetainedComponentBuilder;
import dagger.hilt.android.internal.builders.FragmentComponentBuilder;
import dagger.hilt.android.internal.builders.ServiceComponentBuilder;
import dagger.hilt.android.internal.builders.ViewComponentBuilder;
import dagger.hilt.android.internal.builders.ViewModelComponentBuilder;
import dagger.hilt.android.internal.builders.ViewWithFragmentComponentBuilder;
import dagger.hilt.android.internal.lifecycle.DefaultViewModelFactories;
import dagger.hilt.android.internal.lifecycle.DefaultViewModelFactories_InternalFactoryFactory_Factory;
import dagger.hilt.android.internal.managers.ActivityRetainedComponentManager_LifecycleModule_ProvideActivityRetainedLifecycleFactory;
import dagger.hilt.android.internal.managers.SavedStateHandleHolder;
import dagger.hilt.android.internal.modules.ApplicationContextModule;
import dagger.hilt.android.internal.modules.ApplicationContextModule_ProvideContextFactory;
import dagger.internal.DaggerGenerated;
import dagger.internal.DoubleCheck;
import dagger.internal.IdentifierNameString;
import dagger.internal.KeepFieldType;
import dagger.internal.LazyClassKeyMap;
import dagger.internal.MapBuilder;
import dagger.internal.Preconditions;
import dagger.internal.Provider;
import java.util.Collections;
import java.util.Map;
import java.util.Set;
import javax.annotation.processing.Generated;
import okhttp3.Interceptor;
import okhttp3.OkHttpClient;
import retrofit2.Retrofit;

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
public final class DaggerLoyalteApplication_HiltComponents_SingletonC {
  private DaggerLoyalteApplication_HiltComponents_SingletonC() {
  }

  public static Builder builder() {
    return new Builder();
  }

  public static final class Builder {
    private ApplicationContextModule applicationContextModule;

    private Builder() {
    }

    public Builder applicationContextModule(ApplicationContextModule applicationContextModule) {
      this.applicationContextModule = Preconditions.checkNotNull(applicationContextModule);
      return this;
    }

    public LoyalteApplication_HiltComponents.SingletonC build() {
      Preconditions.checkBuilderRequirement(applicationContextModule, ApplicationContextModule.class);
      return new SingletonCImpl(applicationContextModule);
    }
  }

  private static final class ActivityRetainedCBuilder implements LoyalteApplication_HiltComponents.ActivityRetainedC.Builder {
    private final SingletonCImpl singletonCImpl;

    private SavedStateHandleHolder savedStateHandleHolder;

    private ActivityRetainedCBuilder(SingletonCImpl singletonCImpl) {
      this.singletonCImpl = singletonCImpl;
    }

    @Override
    public ActivityRetainedCBuilder savedStateHandleHolder(
        SavedStateHandleHolder savedStateHandleHolder) {
      this.savedStateHandleHolder = Preconditions.checkNotNull(savedStateHandleHolder);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.ActivityRetainedC build() {
      Preconditions.checkBuilderRequirement(savedStateHandleHolder, SavedStateHandleHolder.class);
      return new ActivityRetainedCImpl(singletonCImpl, savedStateHandleHolder);
    }
  }

  private static final class ActivityCBuilder implements LoyalteApplication_HiltComponents.ActivityC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private Activity activity;

    private ActivityCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
    }

    @Override
    public ActivityCBuilder activity(Activity activity) {
      this.activity = Preconditions.checkNotNull(activity);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.ActivityC build() {
      Preconditions.checkBuilderRequirement(activity, Activity.class);
      return new ActivityCImpl(singletonCImpl, activityRetainedCImpl, activity);
    }
  }

  private static final class FragmentCBuilder implements LoyalteApplication_HiltComponents.FragmentC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private Fragment fragment;

    private FragmentCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
    }

    @Override
    public FragmentCBuilder fragment(Fragment fragment) {
      this.fragment = Preconditions.checkNotNull(fragment);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.FragmentC build() {
      Preconditions.checkBuilderRequirement(fragment, Fragment.class);
      return new FragmentCImpl(singletonCImpl, activityRetainedCImpl, activityCImpl, fragment);
    }
  }

  private static final class ViewWithFragmentCBuilder implements LoyalteApplication_HiltComponents.ViewWithFragmentC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final FragmentCImpl fragmentCImpl;

    private View view;

    private ViewWithFragmentCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl,
        FragmentCImpl fragmentCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
      this.fragmentCImpl = fragmentCImpl;
    }

    @Override
    public ViewWithFragmentCBuilder view(View view) {
      this.view = Preconditions.checkNotNull(view);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.ViewWithFragmentC build() {
      Preconditions.checkBuilderRequirement(view, View.class);
      return new ViewWithFragmentCImpl(singletonCImpl, activityRetainedCImpl, activityCImpl, fragmentCImpl, view);
    }
  }

  private static final class ViewCBuilder implements LoyalteApplication_HiltComponents.ViewC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private View view;

    private ViewCBuilder(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
        ActivityCImpl activityCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
    }

    @Override
    public ViewCBuilder view(View view) {
      this.view = Preconditions.checkNotNull(view);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.ViewC build() {
      Preconditions.checkBuilderRequirement(view, View.class);
      return new ViewCImpl(singletonCImpl, activityRetainedCImpl, activityCImpl, view);
    }
  }

  private static final class ViewModelCBuilder implements LoyalteApplication_HiltComponents.ViewModelC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private SavedStateHandle savedStateHandle;

    private ViewModelLifecycle viewModelLifecycle;

    private ViewModelCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
    }

    @Override
    public ViewModelCBuilder savedStateHandle(SavedStateHandle handle) {
      this.savedStateHandle = Preconditions.checkNotNull(handle);
      return this;
    }

    @Override
    public ViewModelCBuilder viewModelLifecycle(ViewModelLifecycle viewModelLifecycle) {
      this.viewModelLifecycle = Preconditions.checkNotNull(viewModelLifecycle);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.ViewModelC build() {
      Preconditions.checkBuilderRequirement(savedStateHandle, SavedStateHandle.class);
      Preconditions.checkBuilderRequirement(viewModelLifecycle, ViewModelLifecycle.class);
      return new ViewModelCImpl(singletonCImpl, activityRetainedCImpl, savedStateHandle, viewModelLifecycle);
    }
  }

  private static final class ServiceCBuilder implements LoyalteApplication_HiltComponents.ServiceC.Builder {
    private final SingletonCImpl singletonCImpl;

    private Service service;

    private ServiceCBuilder(SingletonCImpl singletonCImpl) {
      this.singletonCImpl = singletonCImpl;
    }

    @Override
    public ServiceCBuilder service(Service service) {
      this.service = Preconditions.checkNotNull(service);
      return this;
    }

    @Override
    public LoyalteApplication_HiltComponents.ServiceC build() {
      Preconditions.checkBuilderRequirement(service, Service.class);
      return new ServiceCImpl(singletonCImpl, service);
    }
  }

  private static final class ViewWithFragmentCImpl extends LoyalteApplication_HiltComponents.ViewWithFragmentC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final FragmentCImpl fragmentCImpl;

    private final ViewWithFragmentCImpl viewWithFragmentCImpl = this;

    private ViewWithFragmentCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl,
        FragmentCImpl fragmentCImpl, View viewParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
      this.fragmentCImpl = fragmentCImpl;


    }
  }

  private static final class FragmentCImpl extends LoyalteApplication_HiltComponents.FragmentC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final FragmentCImpl fragmentCImpl = this;

    private FragmentCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl,
        Fragment fragmentParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;


    }

    @Override
    public DefaultViewModelFactories.InternalFactoryFactory getHiltInternalFactoryFactory() {
      return activityCImpl.getHiltInternalFactoryFactory();
    }

    @Override
    public ViewWithFragmentComponentBuilder viewWithFragmentComponentBuilder() {
      return new ViewWithFragmentCBuilder(singletonCImpl, activityRetainedCImpl, activityCImpl, fragmentCImpl);
    }
  }

  private static final class ViewCImpl extends LoyalteApplication_HiltComponents.ViewC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final ViewCImpl viewCImpl = this;

    private ViewCImpl(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
        ActivityCImpl activityCImpl, View viewParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;


    }
  }

  private static final class ActivityCImpl extends LoyalteApplication_HiltComponents.ActivityC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl = this;

    private ActivityCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, Activity activityParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;


    }

    @Override
    public void injectMainActivity(MainActivity mainActivity) {
      injectMainActivity2(mainActivity);
    }

    @Override
    public DefaultViewModelFactories.InternalFactoryFactory getHiltInternalFactoryFactory() {
      return DefaultViewModelFactories_InternalFactoryFactory_Factory.newInstance(getViewModelKeys(), new ViewModelCBuilder(singletonCImpl, activityRetainedCImpl));
    }

    @Override
    public Map<Class<?>, Boolean> getViewModelKeys() {
      return LazyClassKeyMap.<Boolean>of(MapBuilder.<String, Boolean>newMapBuilder(8).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_customer_AddCustomerViewModel, AddCustomerViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_codes_CodeHistoryViewModel, CodeHistoryViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_customer_CustomerListViewModel, CustomerListViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_customer_CustomerProfileViewModel, CustomerProfileViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_home_HomeViewModel, HomeViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_scan_QrScanViewModel, QrScanViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_rewards_RewardsViewModel, RewardsViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_auth_StaffLoginViewModel, StaffLoginViewModel_HiltModules.KeyModule.provide()).build());
    }

    @Override
    public ViewModelComponentBuilder getViewModelComponentBuilder() {
      return new ViewModelCBuilder(singletonCImpl, activityRetainedCImpl);
    }

    @Override
    public FragmentComponentBuilder fragmentComponentBuilder() {
      return new FragmentCBuilder(singletonCImpl, activityRetainedCImpl, activityCImpl);
    }

    @Override
    public ViewComponentBuilder viewComponentBuilder() {
      return new ViewCBuilder(singletonCImpl, activityRetainedCImpl, activityCImpl);
    }

    private MainActivity injectMainActivity2(MainActivity instance) {
      MainActivity_MembersInjector.injectAuthPreferences(instance, singletonCImpl.authPreferencesProvider.get());
      return instance;
    }

    @IdentifierNameString
    private static final class LazyClassKeyProvider {
      static String com_loyalte_app_presentation_screens_rewards_RewardsViewModel = "com.loyalte.app.presentation.screens.rewards.RewardsViewModel";

      static String com_loyalte_app_presentation_screens_auth_StaffLoginViewModel = "com.loyalte.app.presentation.screens.auth.StaffLoginViewModel";

      static String com_loyalte_app_presentation_screens_scan_QrScanViewModel = "com.loyalte.app.presentation.screens.scan.QrScanViewModel";

      static String com_loyalte_app_presentation_screens_customer_CustomerProfileViewModel = "com.loyalte.app.presentation.screens.customer.CustomerProfileViewModel";

      static String com_loyalte_app_presentation_screens_codes_CodeHistoryViewModel = "com.loyalte.app.presentation.screens.codes.CodeHistoryViewModel";

      static String com_loyalte_app_presentation_screens_customer_CustomerListViewModel = "com.loyalte.app.presentation.screens.customer.CustomerListViewModel";

      static String com_loyalte_app_presentation_screens_home_HomeViewModel = "com.loyalte.app.presentation.screens.home.HomeViewModel";

      static String com_loyalte_app_presentation_screens_customer_AddCustomerViewModel = "com.loyalte.app.presentation.screens.customer.AddCustomerViewModel";

      @KeepFieldType
      RewardsViewModel com_loyalte_app_presentation_screens_rewards_RewardsViewModel2;

      @KeepFieldType
      StaffLoginViewModel com_loyalte_app_presentation_screens_auth_StaffLoginViewModel2;

      @KeepFieldType
      QrScanViewModel com_loyalte_app_presentation_screens_scan_QrScanViewModel2;

      @KeepFieldType
      CustomerProfileViewModel com_loyalte_app_presentation_screens_customer_CustomerProfileViewModel2;

      @KeepFieldType
      CodeHistoryViewModel com_loyalte_app_presentation_screens_codes_CodeHistoryViewModel2;

      @KeepFieldType
      CustomerListViewModel com_loyalte_app_presentation_screens_customer_CustomerListViewModel2;

      @KeepFieldType
      HomeViewModel com_loyalte_app_presentation_screens_home_HomeViewModel2;

      @KeepFieldType
      AddCustomerViewModel com_loyalte_app_presentation_screens_customer_AddCustomerViewModel2;
    }
  }

  private static final class ViewModelCImpl extends LoyalteApplication_HiltComponents.ViewModelC {
    private final SavedStateHandle savedStateHandle;

    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ViewModelCImpl viewModelCImpl = this;

    private Provider<AddCustomerViewModel> addCustomerViewModelProvider;

    private Provider<CodeHistoryViewModel> codeHistoryViewModelProvider;

    private Provider<CustomerListViewModel> customerListViewModelProvider;

    private Provider<CustomerProfileViewModel> customerProfileViewModelProvider;

    private Provider<HomeViewModel> homeViewModelProvider;

    private Provider<QrScanViewModel> qrScanViewModelProvider;

    private Provider<RewardsViewModel> rewardsViewModelProvider;

    private Provider<StaffLoginViewModel> staffLoginViewModelProvider;

    private ViewModelCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, SavedStateHandle savedStateHandleParam,
        ViewModelLifecycle viewModelLifecycleParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.savedStateHandle = savedStateHandleParam;
      initialize(savedStateHandleParam, viewModelLifecycleParam);

    }

    private LookupCustomerUseCase lookupCustomerUseCase() {
      return new LookupCustomerUseCase(singletonCImpl.bindCustomerRepositoryProvider.get());
    }

    private RedeemRewardUseCase redeemRewardUseCase() {
      return new RedeemRewardUseCase(singletonCImpl.bindCustomerRepositoryProvider.get(), singletonCImpl.bindRewardRepositoryProvider.get(), singletonCImpl.bindLoyaltyRepositoryProvider.get());
    }

    @SuppressWarnings("unchecked")
    private void initialize(final SavedStateHandle savedStateHandleParam,
        final ViewModelLifecycle viewModelLifecycleParam) {
      this.addCustomerViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 0);
      this.codeHistoryViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 1);
      this.customerListViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 2);
      this.customerProfileViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 3);
      this.homeViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 4);
      this.qrScanViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 5);
      this.rewardsViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 6);
      this.staffLoginViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 7);
    }

    @Override
    public Map<Class<?>, javax.inject.Provider<ViewModel>> getHiltViewModelMap() {
      return LazyClassKeyMap.<javax.inject.Provider<ViewModel>>of(MapBuilder.<String, javax.inject.Provider<ViewModel>>newMapBuilder(8).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_customer_AddCustomerViewModel, ((Provider) addCustomerViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_codes_CodeHistoryViewModel, ((Provider) codeHistoryViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_customer_CustomerListViewModel, ((Provider) customerListViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_customer_CustomerProfileViewModel, ((Provider) customerProfileViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_home_HomeViewModel, ((Provider) homeViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_scan_QrScanViewModel, ((Provider) qrScanViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_rewards_RewardsViewModel, ((Provider) rewardsViewModelProvider)).put(LazyClassKeyProvider.com_loyalte_app_presentation_screens_auth_StaffLoginViewModel, ((Provider) staffLoginViewModelProvider)).build());
    }

    @Override
    public Map<Class<?>, Object> getHiltViewModelAssistedMap() {
      return Collections.<Class<?>, Object>emptyMap();
    }

    @IdentifierNameString
    private static final class LazyClassKeyProvider {
      static String com_loyalte_app_presentation_screens_codes_CodeHistoryViewModel = "com.loyalte.app.presentation.screens.codes.CodeHistoryViewModel";

      static String com_loyalte_app_presentation_screens_customer_CustomerProfileViewModel = "com.loyalte.app.presentation.screens.customer.CustomerProfileViewModel";

      static String com_loyalte_app_presentation_screens_rewards_RewardsViewModel = "com.loyalte.app.presentation.screens.rewards.RewardsViewModel";

      static String com_loyalte_app_presentation_screens_scan_QrScanViewModel = "com.loyalte.app.presentation.screens.scan.QrScanViewModel";

      static String com_loyalte_app_presentation_screens_customer_AddCustomerViewModel = "com.loyalte.app.presentation.screens.customer.AddCustomerViewModel";

      static String com_loyalte_app_presentation_screens_auth_StaffLoginViewModel = "com.loyalte.app.presentation.screens.auth.StaffLoginViewModel";

      static String com_loyalte_app_presentation_screens_customer_CustomerListViewModel = "com.loyalte.app.presentation.screens.customer.CustomerListViewModel";

      static String com_loyalte_app_presentation_screens_home_HomeViewModel = "com.loyalte.app.presentation.screens.home.HomeViewModel";

      @KeepFieldType
      CodeHistoryViewModel com_loyalte_app_presentation_screens_codes_CodeHistoryViewModel2;

      @KeepFieldType
      CustomerProfileViewModel com_loyalte_app_presentation_screens_customer_CustomerProfileViewModel2;

      @KeepFieldType
      RewardsViewModel com_loyalte_app_presentation_screens_rewards_RewardsViewModel2;

      @KeepFieldType
      QrScanViewModel com_loyalte_app_presentation_screens_scan_QrScanViewModel2;

      @KeepFieldType
      AddCustomerViewModel com_loyalte_app_presentation_screens_customer_AddCustomerViewModel2;

      @KeepFieldType
      StaffLoginViewModel com_loyalte_app_presentation_screens_auth_StaffLoginViewModel2;

      @KeepFieldType
      CustomerListViewModel com_loyalte_app_presentation_screens_customer_CustomerListViewModel2;

      @KeepFieldType
      HomeViewModel com_loyalte_app_presentation_screens_home_HomeViewModel2;
    }

    private static final class SwitchingProvider<T> implements Provider<T> {
      private final SingletonCImpl singletonCImpl;

      private final ActivityRetainedCImpl activityRetainedCImpl;

      private final ViewModelCImpl viewModelCImpl;

      private final int id;

      SwitchingProvider(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
          ViewModelCImpl viewModelCImpl, int id) {
        this.singletonCImpl = singletonCImpl;
        this.activityRetainedCImpl = activityRetainedCImpl;
        this.viewModelCImpl = viewModelCImpl;
        this.id = id;
      }

      @SuppressWarnings("unchecked")
      @Override
      public T get() {
        switch (id) {
          case 0: // com.loyalte.app.presentation.screens.customer.AddCustomerViewModel 
          return (T) new AddCustomerViewModel(singletonCImpl.provideLoyalteApiServiceProvider.get());

          case 1: // com.loyalte.app.presentation.screens.codes.CodeHistoryViewModel 
          return (T) new CodeHistoryViewModel(singletonCImpl.provideLoyalteApiServiceProvider.get());

          case 2: // com.loyalte.app.presentation.screens.customer.CustomerListViewModel 
          return (T) new CustomerListViewModel(singletonCImpl.bindCustomerRepositoryProvider.get());

          case 3: // com.loyalte.app.presentation.screens.customer.CustomerProfileViewModel 
          return (T) new CustomerProfileViewModel(viewModelCImpl.savedStateHandle, singletonCImpl.bindCustomerRepositoryProvider.get(), singletonCImpl.bindLoyaltyRepositoryProvider.get());

          case 4: // com.loyalte.app.presentation.screens.home.HomeViewModel 
          return (T) new HomeViewModel(viewModelCImpl.lookupCustomerUseCase());

          case 5: // com.loyalte.app.presentation.screens.scan.QrScanViewModel 
          return (T) new QrScanViewModel(viewModelCImpl.lookupCustomerUseCase());

          case 6: // com.loyalte.app.presentation.screens.rewards.RewardsViewModel 
          return (T) new RewardsViewModel(viewModelCImpl.savedStateHandle, singletonCImpl.bindCustomerRepositoryProvider.get(), singletonCImpl.bindRewardRepositoryProvider.get(), viewModelCImpl.redeemRewardUseCase());

          case 7: // com.loyalte.app.presentation.screens.auth.StaffLoginViewModel 
          return (T) new StaffLoginViewModel(singletonCImpl.provideLoyalteApiServiceProvider.get(), singletonCImpl.authPreferencesProvider.get());

          default: throw new AssertionError(id);
        }
      }
    }
  }

  private static final class ActivityRetainedCImpl extends LoyalteApplication_HiltComponents.ActivityRetainedC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl = this;

    private Provider<ActivityRetainedLifecycle> provideActivityRetainedLifecycleProvider;

    private ActivityRetainedCImpl(SingletonCImpl singletonCImpl,
        SavedStateHandleHolder savedStateHandleHolderParam) {
      this.singletonCImpl = singletonCImpl;

      initialize(savedStateHandleHolderParam);

    }

    @SuppressWarnings("unchecked")
    private void initialize(final SavedStateHandleHolder savedStateHandleHolderParam) {
      this.provideActivityRetainedLifecycleProvider = DoubleCheck.provider(new SwitchingProvider<ActivityRetainedLifecycle>(singletonCImpl, activityRetainedCImpl, 0));
    }

    @Override
    public ActivityComponentBuilder activityComponentBuilder() {
      return new ActivityCBuilder(singletonCImpl, activityRetainedCImpl);
    }

    @Override
    public ActivityRetainedLifecycle getActivityRetainedLifecycle() {
      return provideActivityRetainedLifecycleProvider.get();
    }

    private static final class SwitchingProvider<T> implements Provider<T> {
      private final SingletonCImpl singletonCImpl;

      private final ActivityRetainedCImpl activityRetainedCImpl;

      private final int id;

      SwitchingProvider(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
          int id) {
        this.singletonCImpl = singletonCImpl;
        this.activityRetainedCImpl = activityRetainedCImpl;
        this.id = id;
      }

      @SuppressWarnings("unchecked")
      @Override
      public T get() {
        switch (id) {
          case 0: // dagger.hilt.android.ActivityRetainedLifecycle 
          return (T) ActivityRetainedComponentManager_LifecycleModule_ProvideActivityRetainedLifecycleFactory.provideActivityRetainedLifecycle();

          default: throw new AssertionError(id);
        }
      }
    }
  }

  private static final class ServiceCImpl extends LoyalteApplication_HiltComponents.ServiceC {
    private final SingletonCImpl singletonCImpl;

    private final ServiceCImpl serviceCImpl = this;

    private ServiceCImpl(SingletonCImpl singletonCImpl, Service serviceParam) {
      this.singletonCImpl = singletonCImpl;


    }
  }

  private static final class SingletonCImpl extends LoyalteApplication_HiltComponents.SingletonC {
    private final ApplicationContextModule applicationContextModule;

    private final SingletonCImpl singletonCImpl = this;

    private Provider<AuthPreferences> authPreferencesProvider;

    private Provider<Interceptor> provideAuthInterceptorProvider;

    private Provider<OkHttpClient> provideOkHttpClientProvider;

    private Provider<Retrofit> provideRetrofitProvider;

    private Provider<LoyalteApiService> provideLoyalteApiServiceProvider;

    private Provider<RetrofitCustomerRepositoryImpl> retrofitCustomerRepositoryImplProvider;

    private Provider<CustomerRepository> bindCustomerRepositoryProvider;

    private Provider<RetrofitLoyaltyRepositoryImpl> retrofitLoyaltyRepositoryImplProvider;

    private Provider<LoyaltyRepository> bindLoyaltyRepositoryProvider;

    private Provider<RetrofitRewardRepositoryImpl> retrofitRewardRepositoryImplProvider;

    private Provider<RewardRepository> bindRewardRepositoryProvider;

    private SingletonCImpl(ApplicationContextModule applicationContextModuleParam) {
      this.applicationContextModule = applicationContextModuleParam;
      initialize(applicationContextModuleParam);

    }

    private SeedDataUtil seedDataUtil() {
      return new SeedDataUtil(bindCustomerRepositoryProvider.get(), bindLoyaltyRepositoryProvider.get(), bindRewardRepositoryProvider.get());
    }

    @SuppressWarnings("unchecked")
    private void initialize(final ApplicationContextModule applicationContextModuleParam) {
      this.authPreferencesProvider = DoubleCheck.provider(new SwitchingProvider<AuthPreferences>(singletonCImpl, 5));
      this.provideAuthInterceptorProvider = DoubleCheck.provider(new SwitchingProvider<Interceptor>(singletonCImpl, 4));
      this.provideOkHttpClientProvider = DoubleCheck.provider(new SwitchingProvider<OkHttpClient>(singletonCImpl, 3));
      this.provideRetrofitProvider = DoubleCheck.provider(new SwitchingProvider<Retrofit>(singletonCImpl, 2));
      this.provideLoyalteApiServiceProvider = DoubleCheck.provider(new SwitchingProvider<LoyalteApiService>(singletonCImpl, 1));
      this.retrofitCustomerRepositoryImplProvider = new SwitchingProvider<>(singletonCImpl, 0);
      this.bindCustomerRepositoryProvider = DoubleCheck.provider((Provider) retrofitCustomerRepositoryImplProvider);
      this.retrofitLoyaltyRepositoryImplProvider = new SwitchingProvider<>(singletonCImpl, 6);
      this.bindLoyaltyRepositoryProvider = DoubleCheck.provider((Provider) retrofitLoyaltyRepositoryImplProvider);
      this.retrofitRewardRepositoryImplProvider = new SwitchingProvider<>(singletonCImpl, 7);
      this.bindRewardRepositoryProvider = DoubleCheck.provider((Provider) retrofitRewardRepositoryImplProvider);
    }

    @Override
    public void injectLoyalteApplication(LoyalteApplication loyalteApplication) {
      injectLoyalteApplication2(loyalteApplication);
    }

    @Override
    public Set<Boolean> getDisableFragmentGetContextFix() {
      return Collections.<Boolean>emptySet();
    }

    @Override
    public ActivityRetainedComponentBuilder retainedComponentBuilder() {
      return new ActivityRetainedCBuilder(singletonCImpl);
    }

    @Override
    public ServiceComponentBuilder serviceComponentBuilder() {
      return new ServiceCBuilder(singletonCImpl);
    }

    private LoyalteApplication injectLoyalteApplication2(LoyalteApplication instance) {
      LoyalteApplication_MembersInjector.injectSeedDataUtil(instance, seedDataUtil());
      return instance;
    }

    private static final class SwitchingProvider<T> implements Provider<T> {
      private final SingletonCImpl singletonCImpl;

      private final int id;

      SwitchingProvider(SingletonCImpl singletonCImpl, int id) {
        this.singletonCImpl = singletonCImpl;
        this.id = id;
      }

      @SuppressWarnings("unchecked")
      @Override
      public T get() {
        switch (id) {
          case 0: // com.loyalte.app.data.remote.RetrofitCustomerRepositoryImpl 
          return (T) new RetrofitCustomerRepositoryImpl(singletonCImpl.provideLoyalteApiServiceProvider.get());

          case 1: // com.loyalte.app.data.remote.api.LoyalteApiService 
          return (T) NetworkModule_ProvideLoyalteApiServiceFactory.provideLoyalteApiService(singletonCImpl.provideRetrofitProvider.get());

          case 2: // retrofit2.Retrofit 
          return (T) NetworkModule_ProvideRetrofitFactory.provideRetrofit(singletonCImpl.provideOkHttpClientProvider.get());

          case 3: // okhttp3.OkHttpClient 
          return (T) NetworkModule_ProvideOkHttpClientFactory.provideOkHttpClient(singletonCImpl.provideAuthInterceptorProvider.get());

          case 4: // okhttp3.Interceptor 
          return (T) NetworkModule_ProvideAuthInterceptorFactory.provideAuthInterceptor(singletonCImpl.authPreferencesProvider.get());

          case 5: // com.loyalte.app.data.local.prefs.AuthPreferences 
          return (T) new AuthPreferences(ApplicationContextModule_ProvideContextFactory.provideContext(singletonCImpl.applicationContextModule));

          case 6: // com.loyalte.app.data.remote.RetrofitLoyaltyRepositoryImpl 
          return (T) new RetrofitLoyaltyRepositoryImpl(singletonCImpl.provideLoyalteApiServiceProvider.get());

          case 7: // com.loyalte.app.data.remote.RetrofitRewardRepositoryImpl 
          return (T) new RetrofitRewardRepositoryImpl(singletonCImpl.provideLoyalteApiServiceProvider.get());

          default: throw new AssertionError(id);
        }
      }
    }
  }
}
