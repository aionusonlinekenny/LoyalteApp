package com.loyalte.app.presentation.navigation

sealed class Screen(val route: String) {
    object StaffLogin : Screen("staff_login")
    object Home : Screen("home")
    object QrScan : Screen("qr_scan")

    object CustomerProfile : Screen("customer_profile/{customerId}") {
        const val ARG_CUSTOMER_ID = "customerId"
        fun createRoute(customerId: String) = "customer_profile/$customerId"
    }

    object Rewards : Screen("rewards/{customerId}") {
        const val ARG_CUSTOMER_ID = "customerId"
        fun createRoute(customerId: String) = "rewards/$customerId"
    }
}
