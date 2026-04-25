package com.loyalte.app.presentation.screens.kiosk

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.KioskClaimRequest
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class KioskViewModel @Inject constructor(
    private val api: LoyalteApiService
) : ViewModel() {

    sealed class KioskState {
        object Idle : KioskState()
        object Loading : KioskState()
        data class Success(
            val customerName: String,
            val memberId: String,
            val pointsEarned: Int,
            val newTotal: Int,
            val tier: String,
            val amount: String
        ) : KioskState()
        data class NoPayment(
            val customerName: String,
            val memberId: String,
            val currentPoints: Int
        ) : KioskState()
        data class Error(val message: String) : KioskState()
    }

    private val _state = MutableStateFlow<KioskState>(KioskState.Idle)
    val state: StateFlow<KioskState> = _state.asStateFlow()

    private val _phone = MutableStateFlow("")
    val phone: StateFlow<String> = _phone.asStateFlow()

    fun appendDigit(digit: Char) {
        if (_phone.value.length < 10) _phone.value += digit
    }

    fun backspace() {
        if (_phone.value.isNotEmpty()) _phone.value = _phone.value.dropLast(1)
    }

    fun reset() {
        _phone.value = ""
        _state.value = KioskState.Idle
    }

    fun claim() {
        val phone = _phone.value
        if (phone.length < 10) {
            _state.value = KioskState.Error("Please enter a 10-digit phone number")
            return
        }
        viewModelScope.launch {
            _state.value = KioskState.Loading
            try {
                val resp = api.kioskClaim(KioskClaimRequest(phone))
                val body = resp.body()
                if (resp.isSuccessful && body?.success == true) {
                    val customer = body.customer
                    if (body.status == "ok" && (body.pointsEarned ?: 0) > 0) {
                        _state.value = KioskState.Success(
                            customerName = customer?.name ?: "Customer",
                            memberId = customer?.memberId ?: "",
                            pointsEarned = body.pointsEarned ?: 0,
                            newTotal = body.newTotal ?: 0,
                            tier = body.tier ?: "BRONZE",
                            amount = body.amount ?: "0.00"
                        )
                    } else {
                        _state.value = KioskState.NoPayment(
                            customerName = customer?.name ?: "Customer",
                            memberId = customer?.memberId ?: "",
                            currentPoints = customer?.points ?: 0
                        )
                    }
                    // auto-reset after 8 seconds
                    delay(8000)
                    reset()
                } else {
                    _state.value = KioskState.Error(body?.message ?: "Something went wrong")
                    delay(4000)
                    _state.value = KioskState.Idle
                }
            } catch (e: Exception) {
                _state.value = KioskState.Error("Connection error. Please try again.")
                delay(4000)
                _state.value = KioskState.Idle
            }
        }
    }
}
