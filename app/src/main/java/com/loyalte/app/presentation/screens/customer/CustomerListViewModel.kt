package com.loyalte.app.presentation.screens.customer

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.repository.CustomerRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.receiveAsFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CustomerListViewModel @Inject constructor(
    private val customerRepository: CustomerRepository
) : ViewModel() {

    data class UiState(
        val customers: List<Customer> = emptyList(),
        val isLoading: Boolean = false,
        val errorMessage: String? = null,
        val searchQuery: String = ""
    ) {
        val filtered: List<Customer>
            get() = if (searchQuery.isBlank()) customers
                    else customers.filter {
                        it.name.contains(searchQuery, ignoreCase = true) ||
                        it.phone.contains(searchQuery) ||
                        it.memberId.contains(searchQuery, ignoreCase = true)
                    }
    }

    sealed class Event {
        data class ShowMessage(val text: String) : Event()
    }

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    private val _events = Channel<Event>(Channel.BUFFERED)
    val events = _events.receiveAsFlow()

    init {
        loadCustomers()
    }

    fun loadCustomers() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, errorMessage = null)
            try {
                customerRepository.getAllCustomers().collect { list ->
                    _uiState.value = _uiState.value.copy(
                        customers = list,
                        isLoading = false
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    errorMessage = e.message ?: "Failed to load customers"
                )
            }
        }
    }

    fun onSearchQueryChange(query: String) {
        _uiState.value = _uiState.value.copy(searchQuery = query)
    }

    fun deleteCustomer(customer: Customer) {
        viewModelScope.launch {
            try {
                val ok = customerRepository.deleteCustomer(customer.id)
                if (ok) {
                    _events.send(Event.ShowMessage("${customer.name} deleted"))
                    loadCustomers()
                } else {
                    _events.send(Event.ShowMessage("Failed to delete customer"))
                }
            } catch (e: Exception) {
                _events.send(Event.ShowMessage(e.message ?: "Error deleting customer"))
            }
        }
    }
}
