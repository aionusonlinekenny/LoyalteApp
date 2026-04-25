package com.loyalte.app.presentation.navigation

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CardGiftcard
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.TouchApp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.navArgument
import com.loyalte.app.data.local.prefs.AuthPreferences
import com.loyalte.app.presentation.screens.auth.StaffLoginScreen
import com.loyalte.app.presentation.screens.codes.CodeHistoryScreen
import com.loyalte.app.presentation.screens.customer.AddCustomerScreen
import com.loyalte.app.presentation.screens.customer.CustomerListScreen
import com.loyalte.app.presentation.screens.customer.CustomerProfileScreen
import com.loyalte.app.presentation.screens.home.HomeScreen
import com.loyalte.app.presentation.screens.kiosk.KioskScreen
import com.loyalte.app.presentation.screens.rewards.ManageRewardsScreen
import com.loyalte.app.presentation.screens.rewards.RewardsScreen
import com.loyalte.app.presentation.screens.scan.QrScanScreen
import kotlinx.coroutines.launch

private data class DrawerItem(
    val label: String,
    val icon: ImageVector,
    val route: String
)

private val drawerItems = listOf(
    DrawerItem("Home", Icons.Default.Home, Screen.Home.route),
    DrawerItem("Kiosk Mode", Icons.Default.TouchApp, Screen.Kiosk.route),
    DrawerItem("Customer List", Icons.Default.Group, Screen.CustomerList.route),
    DrawerItem("Manage Rewards", Icons.Default.CardGiftcard, Screen.ManageRewards.route),
    DrawerItem("Receipt Code History", Icons.Default.Receipt, Screen.CodeHistory.route)
)

@Composable
fun AppNavigation(navController: NavHostController, authPreferences: AuthPreferences) {
    val isLoggedIn by authPreferences.isLoggedIn().collectAsState(initial = false)
    val startDestination = if (isLoggedIn) Screen.Home.route else Screen.StaffLogin.route

    val drawerState = rememberDrawerState(DrawerValue.Closed)
    val scope = rememberCoroutineScope()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    val showDrawer = currentRoute != Screen.StaffLogin.route

    if (!showDrawer) {
        AppNavHost(
            navController = navController,
            startDestination = startDestination,
            authPreferences = authPreferences,
            openDrawer = {},
            drawerState = drawerState
        )
        return
    }

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            ModalDrawerSheet(
                modifier = Modifier.width(280.dp),
                drawerShape = RoundedCornerShape(topEnd = 16.dp, bottomEnd = 16.dp)
            ) {
                Spacer(Modifier.height(24.dp))
                Text(
                    text = "LoyalteApp",
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.ExtraBold,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(horizontal = 20.dp)
                )
                Text(
                    text = "Admin Panel",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(horizontal = 20.dp)
                )
                Spacer(Modifier.height(16.dp))
                Divider()
                Spacer(Modifier.height(8.dp))

                drawerItems.forEach { item ->
                    NavigationDrawerItem(
                        icon = { Icon(item.icon, contentDescription = null) },
                        label = { Text(item.label, fontWeight = FontWeight.Medium) },
                        selected = currentRoute == item.route,
                        onClick = {
                            scope.launch { drawerState.close() }
                            if (currentRoute != item.route) {
                                navController.navigate(item.route) {
                                    popUpTo(Screen.Home.route) { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            }
                        },
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 2.dp)
                    )
                }

                Spacer(Modifier.weight(1f))
                Divider()

                NavigationDrawerItem(
                    icon = {
                        Icon(
                            Icons.Default.ExitToApp,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.error
                        )
                    },
                    label = {
                        Text(
                            "Logout",
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.error
                        )
                    },
                    selected = false,
                    onClick = {
                        scope.launch {
                            drawerState.close()
                            authPreferences.clearToken()
                            navController.navigate(Screen.StaffLogin.route) {
                                popUpTo(0) { inclusive = true }
                            }
                        }
                    },
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 2.dp)
                )
                Spacer(Modifier.height(16.dp))
            }
        }
    ) {
        AppNavHost(
            navController = navController,
            startDestination = startDestination,
            authPreferences = authPreferences,
            openDrawer = { scope.launch { drawerState.open() } },
            drawerState = drawerState
        )
    }
}

@Composable
private fun AppNavHost(
    navController: NavHostController,
    startDestination: String,
    authPreferences: AuthPreferences,
    openDrawer: () -> Unit,
    drawerState: DrawerState
) {
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
                onAddCustomer = {
                    navController.navigate(Screen.AddCustomer.route)
                },
                onOpenDrawer = openDrawer
            )
        }

        composable(Screen.CustomerList.route) {
            CustomerListScreen(
                onOpenDrawer = openDrawer,
                onCustomerClick = { customerId ->
                    navController.navigate(Screen.CustomerProfile.createRoute(customerId))
                },
                onAddCustomer = {
                    navController.navigate(Screen.AddCustomer.route)
                }
            )
        }

        composable(Screen.CodeHistory.route) {
            CodeHistoryScreen(onOpenDrawer = openDrawer)
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

        composable(Screen.Kiosk.route) {
            KioskScreen(onBack = { navController.popBackStack() })
        }

        composable(Screen.ManageRewards.route) {
            ManageRewardsScreen(onOpenDrawer = openDrawer)
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
