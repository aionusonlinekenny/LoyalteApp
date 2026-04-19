package com.loyalte.app.data.local.prefs

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.longPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "auth_prefs")

@Singleton
class AuthPreferences @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val keyToken     = stringPreferencesKey("auth_token")
    private val keyExpiresAt = longPreferencesKey("token_expires_at")

    val tokenFlow: Flow<String?> = context.dataStore.data.map { it[keyToken] }

    suspend fun saveToken(token: String, expiresAt: Long) {
        context.dataStore.edit { prefs ->
            prefs[keyToken]     = token
            prefs[keyExpiresAt] = expiresAt
        }
    }

    suspend fun clearToken() {
        context.dataStore.edit { prefs ->
            prefs.remove(keyToken)
            prefs.remove(keyExpiresAt)
        }
    }

    fun isLoggedIn(): Flow<Boolean> = context.dataStore.data.map { prefs ->
        val token     = prefs[keyToken]
        val expiresAt = prefs[keyExpiresAt] ?: 0L
        !token.isNullOrBlank() && System.currentTimeMillis() < expiresAt
    }
}
