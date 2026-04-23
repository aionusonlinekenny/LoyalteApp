package com.loyalte.app.presentation.screens.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.local.prefs.AuthPreferences
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.LoginRequest
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class StaffLoginViewModel @Inject constructor(
    private val api: LoyalteApiService,
    private val authPreferences: AuthPreferences
) : ViewModel() {

    data class UiState(
        val email: String = "",
        val password: String = "",
        val isLoading: Boolean = false,
        val errorMessage: String? = null
    )

    sealed class Event {
        object LoginSuccess : Event()
    }

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<Event>()
    val events = _events.asSharedFlow()

    fun onEmailChange(value: String) =
        _uiState.update { it.copy(email = value, errorMessage = null) }

    fun onPasswordChange(value: String) =
        _uiState.update { it.copy(password = value, errorMessage = null) }

    fun signIn() {
        val email    = _uiState.value.email.trim()
        val password = _uiState.value.password

        if (email.isBlank() || password.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Email and password are required") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val response = api.login(LoginRequest(email, password))
                val body     = response.body()

                if (response.isSuccessful && body?.success == true && body.token != null) {
                    authPreferences.saveToken(body.token, body.expiresAt ?: Long.MAX_VALUE)
                    _uiState.update { it.copy(isLoading = false) }
                    _events.emit(Event.LoginSuccess)
                } else {
                    val msg = "HTTP ${response.code()} | ${body?.message ?: response.errorBody()?.string() ?: "no body"}"
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(isLoading = false, errorMessage = "ERR: ${e.javaClass.simpleName}: ${e.message}")
                }
            }
        }
    }
}
