package com.loyalte.app.presentation.navigation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.loyalte.app.data.local.prefs.AuthPreferences
import com.loyalte.app.presentation.screens.auth.StaffLoginScreen
import com.loyalte.app.presentation.screens.customer.AddCustomerScreen
import com.loyalte.app.presentation.screens.customer.CustomerProfileScreen
import com.loyalte.app.presentation.screens.home.HomeScreen
import com.loyalte.app.presentation.screens.rewards.RewardsScreen
import com.loyalte.app.presentation.screens.scan.QrScanScreen

@Composable
fun AppNavigation(navController: NavHostController, authPreferences: AuthPreferences) {
    val isLoggedIn by authPreferences.isLoggedIn().collectAsState(initial = false)
    val startDestination = if (isLoggedIn) Screen.Home.route else Screen.StaffLogin.route

    NavHost(navController = navController, startDestination = startDestination) {

        composable(Screen.StaffLogin.route) {
            StaffLoginScreen(
                onLoginSuccess = {
                    navController.navigate(Screen.Home.route) {
                        popUpTo(Screen.StaffLogin.route) { inclusive = true }
                    }
                }
            )
        }

        composable(Screen.Home.route) {
            HomeScreen(
                onCustomerFound = { customerId ->
                    navController.navigate(Screen.CustomerProfile.createRoute(customerId))
                },
                onScanQr = {
                    navController.navigate(Screen.QrScan.route)
                },
                onAddCustomer = {
                    navController.navigate(Screen.AddCustomer.route)
                }
            )
        }

        composable(Screen.AddCustomer.route) {
            AddCustomerScreen(
                onCustomerCreated = { customerId ->
                    navController.navigate(Screen.CustomerProfile.createRoute(customerId)) {
                        popUpTo(Screen.AddCustomer.route) { inclusive = true }
                    }
                },
                onBack = { navController.popBackStack() }
            )
        }

        composable(Screen.QrScan.route) {
            QrScanScreen(
                onCustomerFound = { customerId ->
                    navController.navigate(Screen.CustomerProfile.createRoute(customerId)) {
                        popUpTo(Screen.QrScan.route) { inclusive = true }
                    }
                },
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            route = Screen.CustomerProfile.route,
            arguments = listOf(
                navArgument(Screen.CustomerProfile.ARG_CUSTOMER_ID) {
                    type = NavType.StringType
                }
            )
        ) {
            CustomerProfileScreen(
                onNavigateToRewards = { customerId ->
                    navController.navigate(Screen.Rewards.createRoute(customerId))
                },
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            route = Screen.Rewards.route,
            arguments = listOf(
                navArgument(Screen.Rewards.ARG_CUSTOMER_ID) {
                    type = NavType.StringType
                }
            )
        ) {
            RewardsScreen(
                onBack = { navController.popBackStack() }
            )
        }
    }
}
