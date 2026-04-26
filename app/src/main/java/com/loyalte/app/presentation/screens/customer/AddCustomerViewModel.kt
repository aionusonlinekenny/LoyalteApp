package com.loyalte.app.presentation.screens.customer

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.CreateCustomerRequest
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
class AddCustomerViewModel @Inject constructor(
    private val api: LoyalteApiService
) : ViewModel() {

    data class UiState(
        val name: String = "",
        val phone: String = "",
        val email: String = "",
        val isLoading: Boolean = false,
        val errorMessage: String? = null
    )

    sealed class Event {
        data class Success(val customerId: String) : Event()
    }

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<Event>()
    val events = _events.asSharedFlow()

    fun onNameChange(v: String)  = _uiState.update { it.copy(name = v, errorMessage = null) }
    fun onPhoneChange(v: String) = _uiState.update { it.copy(phone = v, errorMessage = null) }
    fun onEmailChange(v: String) = _uiState.update { it.copy(email = v, errorMessage = null) }

    fun submit() {
        val name  = _uiState.value.name.trim()
        val phone = _uiState.value.phone.trim()
        val email = _uiState.value.email.trim().ifBlank { null }

        if (name.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Name is required") }
            return
        }
        if (phone.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Phone number is required") }
            return
        }

        // Normalize: always store as 10-digit (strip country code)
        val digits = phone.filter { it.isDigit() }
        val normalized = if (digits.length == 11 && digits.startsWith("1")) digits.drop(1) else digits

        if (normalized.length != 10) {
            _uiState.update { it.copy(errorMessage = "Enter a valid 10-digit phone number") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val response = api.createCustomer(CreateCustomerRequest(name, normalized, email))
                val body = response.body()
                if (response.isSuccessful && body?.success == true && body.customer != null) {
                    _uiState.update { it.copy(isLoading = false) }
                    _events.emit(Event.Success(body.customer.id))
                } else {
                    val msg = body?.message ?: when (response.code()) {
                        409  -> "Phone number already registered"
                        else -> "Failed to create customer (${response.code()})"
                    }
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(isLoading = false, errorMessage = "Network error: ${e.message}")
                }
            }
        }
    }
}
