package com.loyalte.app.presentation.screens.customer

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.AddPointsRequest
import com.loyalte.app.data.remote.api.dto.SetCustomerPinRequest
import com.loyalte.app.data.remote.api.dto.UpdateCustomerRequest
import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.LoyaltyRepository
import com.loyalte.app.presentation.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CustomerProfileViewModel @Inject constructor(
    savedStateHandle: SavedStateHandle,
    private val customerRepository: CustomerRepository,
    private val loyaltyRepository: LoyaltyRepository,
    private val api: LoyalteApiService
) : ViewModel() {

    private val customerId: String =
        checkNotNull(savedStateHandle[Screen.CustomerProfile.ARG_CUSTOMER_ID])

    data class UiState(
        val customer: Customer? = null,
        val transactions: List<LoyaltyTransaction> = emptyList(),
        val isLoading: Boolean = true,
        val errorMessage: String? = null,
        // Adjust points dialog
        val showAdjustDialog: Boolean = false,
        val isAdjusting: Boolean = false,
        val adjustError: String? = null,
        val adjustSuccess: String? = null,
        // Edit info dialog
        val showEditDialog: Boolean = false,
        val isSaving: Boolean = false,
        val editError: String? = null,
        val editSuccess: String? = null,
        // PIN management
        val hasPin: Boolean? = null,
        val showPinDialog: Boolean = false,
        val isPinSaving: Boolean = false,
        val pinError: String? = null,
        val pinSuccess: String? = null
    )

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    init {
        loadProfile()
    }

    private fun loadProfile() {
        viewModelScope.launch {
            try {
                val customer = customerRepository.getCustomerById(customerId)
                if (customer == null) {
                    _uiState.update { it.copy(isLoading = false, errorMessage = "Customer not found") }
                    return@launch
                }
                // Fetch has_pin fresh from API (not cached in local Room DB)
                val pinResp = api.getCustomerById(customerId)
                val hasPin = pinResp.body()?.customer?.hasPin
                loyaltyRepository.getTransactionsByCustomer(customerId).collect { transactions ->
                    val refreshed = customerRepository.getCustomerById(customerId)
                    _uiState.update {
                        it.copy(
                            customer = refreshed ?: customer,
                            transactions = transactions,
                            isLoading = false,
                            hasPin = hasPin
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(isLoading = false, errorMessage = e.message ?: "Unknown error")
                }
            }
        }
    }

    // ── Adjust points ─────────────────────────────────────────────────────────

    fun openAdjustDialog() {
        _uiState.update { it.copy(showAdjustDialog = true, adjustError = null, adjustSuccess = null) }
    }

    fun closeAdjustDialog() {
        _uiState.update { it.copy(showAdjustDialog = false, adjustError = null) }
    }

    fun adjustPoints(delta: Int, description: String) {
        if (delta == 0) {
            _uiState.update { it.copy(adjustError = "Points delta cannot be zero") }
            return
        }
        _uiState.update { it.copy(isAdjusting = true, adjustError = null) }
        viewModelScope.launch {
            try {
                val resp = api.updatePoints(
                    customerId,
                    AddPointsRequest(
                        points = delta,
                        description = description.ifBlank { "Staff adjustment" }
                    )
                )
                if (resp.isSuccessful && resp.body()?.success == true) {
                    val sign = if (delta > 0) "+$delta" else "$delta"
                    _uiState.update {
                        it.copy(
                            isAdjusting = false,
                            showAdjustDialog = false,
                            adjustSuccess = "Points adjusted: $sign pts"
                        )
                    }
                    loadProfile()
                } else {
                    _uiState.update {
                        it.copy(isAdjusting = false, adjustError = resp.body()?.message ?: "Adjustment failed")
                    }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isAdjusting = false, adjustError = "Connection error") }
            }
        }
    }

    fun clearAdjustSuccess() {
        _uiState.update { it.copy(adjustSuccess = null) }
    }

    // ── Edit customer info ────────────────────────────────────────────────────

    fun openEditDialog() {
        _uiState.update { it.copy(showEditDialog = true, editError = null) }
    }

    fun closeEditDialog() {
        _uiState.update { it.copy(showEditDialog = false, editError = null) }
    }

    fun saveCustomerInfo(name: String, phone: String, email: String) {
        _uiState.update { it.copy(isSaving = true, editError = null) }
        viewModelScope.launch {
            try {
                val resp = api.updateCustomer(
                    customerId,
                    UpdateCustomerRequest(
                        name = name.trim(),
                        phone = phone.trim(),
                        email = email.trim().ifBlank { null }
                    )
                )
                if (resp.isSuccessful && resp.body()?.success == true) {
                    _uiState.update {
                        it.copy(isSaving = false, showEditDialog = false, editSuccess = "Customer info updated")
                    }
                    loadProfile()
                } else {
                    _uiState.update {
                        it.copy(isSaving = false, editError = resp.body()?.message ?: "Update failed")
                    }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSaving = false, editError = "Connection error") }
            }
        }
    }

    fun clearEditSuccess() {
        _uiState.update { it.copy(editSuccess = null) }
    }

    // ── PIN management ────────────────────────────────────────────────────────

    fun openPinDialog() {
        _uiState.update { it.copy(showPinDialog = true, pinError = null) }
    }

    fun closePinDialog() {
        _uiState.update { it.copy(showPinDialog = false, pinError = null) }
    }

    fun setCustomerPin(pin: String) {
        _uiState.update { it.copy(isPinSaving = true, pinError = null) }
        viewModelScope.launch {
            try {
                val resp = api.setCustomerPin(customerId, SetCustomerPinRequest(pin))
                if (resp.isSuccessful && resp.body()?.success == true) {
                    _uiState.update {
                        it.copy(isPinSaving = false, showPinDialog = false,
                                hasPin = true, pinSuccess = "PIN updated successfully")
                    }
                } else {
                    _uiState.update {
                        it.copy(isPinSaving = false, pinError = resp.body()?.message ?: "Failed to set PIN")
                    }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isPinSaving = false, pinError = "Connection error") }
            }
        }
    }

    fun clearCustomerPin() {
        viewModelScope.launch {
            try {
                val resp = api.clearCustomerPin(customerId)
                if (resp.isSuccessful && resp.body()?.success == true) {
                    _uiState.update { it.copy(hasPin = false, pinSuccess = "PIN cleared") }
                }
            } catch (_: Exception) { /* non-fatal */ }
        }
    }

    fun clearPinSuccess() {
        _uiState.update { it.copy(pinSuccess = null) }
    }

    fun getCustomerId(): String = customerId
}
