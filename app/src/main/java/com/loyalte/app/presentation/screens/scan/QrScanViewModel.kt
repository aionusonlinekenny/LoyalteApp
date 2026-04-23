package com.loyalte.app.presentation.screens.scan

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.domain.usecase.LookupCustomerUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class QrScanViewModel @Inject constructor(
    private val lookupCustomerUseCase: LookupCustomerUseCase
) : ViewModel() {

    data class UiState(
        val isProcessing: Boolean = false,
        val errorMessage: String? = null
    )

    sealed class Event {
        data class NavigateToProfile(val customerId: String) : Event()
    }

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<Event>()
    val events = _events.asSharedFlow()

    // Debounce duplicate scans of the same code
    private var lastScannedCode: String? = null

    fun onQrCodeScanned(rawValue: String) {
        if (_uiState.value.isProcessing || rawValue == lastScannedCode) return
        lastScannedCode = rawValue

        viewModelScope.launch {
            _uiState.update { it.copy(isProcessing = true, errorMessage = null) }

            when (val result = lookupCustomerUseCase.byQrCode(rawValue)) {
                is LookupCustomerUseCase.Result.Found -> {
                    _uiState.update { it.copy(isProcessing = false) }
                    _events.emit(Event.NavigateToProfile(result.customer.id))
                }
                LookupCustomerUseCase.Result.NotFound -> {
                    _uiState.update {
                        it.copy(isProcessing = false, errorMessage = "QR code not recognized. Please try again.")
                    }
                    // Reset after 2s so the next scan attempt can go through
                    delay(2000)
                    lastScannedCode = null
                    _uiState.update { it.copy(errorMessage = null) }
                }
                is LookupCustomerUseCase.Result.Error -> {
                    _uiState.update { it.copy(isProcessing = false, errorMessage = result.message) }
                    delay(2000)
                    lastScannedCode = null
                    _uiState.update { it.copy(errorMessage = null) }
                }
            }
        }
    }
}
