package com.loyalte.app.presentation.screens.customer

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.AddPointsRequest
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
        val showAdjustDialog: Boolean = false,
        val isAdjusting: Boolean = false,
        val adjustError: String? = null,
        val adjustSuccess: String? = null
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
                loyaltyRepository.getTransactionsByCustomer(customerId).collect { transactions ->
                    val refreshed = customerRepository.getCustomerById(customerId)
                    _uiState.update {
                        it.copy(
                            customer = refreshed ?: customer,
                            transactions = transactions,
                            isLoading = false
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

    fun getCustomerId(): String = customerId
}

