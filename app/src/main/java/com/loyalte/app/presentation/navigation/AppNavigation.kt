package com.loyalte.app.presentation.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.loyalte.app.presentation.screens.customer.CustomerProfileScreen
import com.loyalte.app.presentation.screens.home.HomeScreen
import com.loyalte.app.presentation.screens.rewards.RewardsScreen
import com.loyalte.app.presentation.screens.scan.QrScanScreen

@Composable
fun AppNavigation(navController: NavHostController) {
    NavHost(
        navController = navController,
        startDestination = Screen.Home.route
    ) {

        composable(Screen.Home.route) {
            HomeScreen(
                onCustomerFound = { customerId ->
                    navController.navigate(Screen.CustomerProfile.createRoute(customerId))
                },
                onScanQr = {
                    navController.navigate(Screen.QrScan.route)
                }
            )
        }

        composable(Screen.QrScan.route) {
            QrScanScreen(
                onCustomerFound = { customerId ->
                    navController.navigate(Screen.CustomerProfile.createRoute(customerId)) {
                        // Remove the QR screen from back stack so Back goes to Home
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
