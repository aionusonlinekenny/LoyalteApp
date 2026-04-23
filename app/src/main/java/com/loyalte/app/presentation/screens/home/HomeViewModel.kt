package com.loyalte.app.presentation.screens.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.domain.usecase.LookupCustomerUseCase
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
class HomeViewModel @Inject constructor(
    private val lookupCustomerUseCase: LookupCustomerUseCase
) : ViewModel() {

    data class UiState(
        val phoneInput: String = "",
        val isLoading: Boolean = false,
        val errorMessage: String? = null
    )

    sealed class Event {
        data class NavigateToProfile(val customerId: String) : Event()
    }

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<Event>()
    val events = _events.asSharedFlow()

    fun onPhoneInputChange(value: String) {
        // Allow only digits, spaces, dashes, parentheses, and leading +
        val filtered = value.filter { it.isDigit() || it in "+()- " }
        _uiState.update { it.copy(phoneInput = filtered, errorMessage = null) }
    }

    fun lookupByPhone() {
        val phone = _uiState.value.phoneInput.trim()
        if (phone.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter a phone number") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = lookupCustomerUseCase.byPhone(phone)) {
                is LookupCustomerUseCase.Result.Found -> {
                    _uiState.update { it.copy(isLoading = false) }
                    _events.emit(Event.NavigateToProfile(result.customer.id))
                }
                LookupCustomerUseCase.Result.NotFound -> {
                    _uiState.update {
                        it.copy(isLoading = false, errorMessage = "Customer not found. Please check the number.")
                    }
                }
                is LookupCustomerUseCase.Result.Error -> {
                    _uiState.update { it.copy(isLoading = false, errorMessage = result.message) }
                }
            }
        }
    }
}
