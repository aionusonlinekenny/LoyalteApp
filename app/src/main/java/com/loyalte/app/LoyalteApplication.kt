package com.loyalte.app

import android.app.Application
import com.loyalte.app.util.SeedDataUtil
import dagger.hilt.android.HiltAndroidApp
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltAndroidApp
class LoyalteApplication : Application() {

    // Application-scoped coroutine scope that outlives any single ViewModel
    private val appScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    @Inject
    lateinit var seedDataUtil: SeedDataUtil

    override fun onCreate() {
        super.onCreate()
        seedDatabase()
    }

    private fun seedDatabase() {
        appScope.launch {
            try {
                seedDataUtil.seedIfEmpty()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }
}
