package com.loyalte.app.presentation.screens.customer

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
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
    private val loyaltyRepository: LoyaltyRepository
) : ViewModel() {

    private val customerId: String =
        checkNotNull(savedStateHandle[Screen.CustomerProfile.ARG_CUSTOMER_ID])

    data class UiState(
        val customer: Customer? = null,
        val transactions: List<LoyaltyTransaction> = emptyList(),
        val isLoading: Boolean = true,
        val errorMessage: String? = null
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
                // Refresh customer on every transaction update
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

    fun getCustomerId(): String = customerId
}
