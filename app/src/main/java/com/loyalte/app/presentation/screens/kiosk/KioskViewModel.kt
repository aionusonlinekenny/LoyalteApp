package com.loyalte.app.presentation.screens.kiosk

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.KioskClaimRequest
import com.loyalte.app.data.remote.api.dto.KioskRedeemRequest
import com.loyalte.app.data.remote.api.dto.RewardDto
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
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
        object Claiming : KioskState()
        data class CustomerLoaded(
            val customerId: String,
            val customerName: String,
            val memberId: String,
            val totalPoints: Int,
            val tier: String,
            val pointsEarned: Int,
            val paymentAmount: String?,
            val rewards: List<RewardDto>,
            val phone: String,
            val hasPIN: Boolean
        ) : KioskState()
        object Redeeming : KioskState()
        data class PinEntry(
            val customer: CustomerLoaded,
            val targetRewardId: String,
            val targetRewardName: String,
            val pinDigits: String = "",
            val error: String? = null
        ) : KioskState()
        data class RedeemSuccess(
            val customerName: String,
            val rewardName: String,
            val pointsUsed: Int,
            val newPoints: Int,
            val tier: String
        ) : KioskState()
        data class Error(val message: String) : KioskState()
    }

    private val _state = MutableStateFlow<KioskState>(KioskState.Idle)
    val state: StateFlow<KioskState> = _state.asStateFlow()

    private val _phone = MutableStateFlow("")
    val phone: StateFlow<String> = _phone.asStateFlow()

    private var resetJob: Job? = null
    private var lastCustomerLoaded: KioskState.CustomerLoaded? = null

    fun appendDigit(digit: Char) {
        if (_phone.value.length < 10) _phone.value += digit
    }

    fun backspace() {
        if (_phone.value.isNotEmpty()) _phone.value = _phone.value.dropLast(1)
    }

    fun reset() {
        resetJob?.cancel()
        resetJob = null
        lastCustomerLoaded = null
        _phone.value = ""
        _state.value = KioskState.Idle
    }

    private fun scheduleAutoReset(delayMs: Long = 12_000L) {
        resetJob?.cancel()
        resetJob = viewModelScope.launch {
            delay(delayMs)
            reset()
        }
    }

    fun claim() {
        val phone = _phone.value
        if (phone.length < 10) {
            _state.value = KioskState.Error("Please enter a 10-digit phone number")
            viewModelScope.launch { delay(2500); _state.value = KioskState.Idle }
            return
        }
        viewModelScope.launch {
            _state.value = KioskState.Claiming
            try {
                val resp = api.kioskClaim(KioskClaimRequest(phone))
                val body = resp.body()
                if (resp.isSuccessful && body?.success == true) {
                    val loaded = KioskState.CustomerLoaded(
                        customerId    = body.customer?.id ?: "",
                        customerName  = body.customer?.name?.ifBlank { "Customer" } ?: "Customer",
                        memberId      = body.customer?.memberId ?: "",
                        totalPoints   = body.newTotal ?: body.customer?.points ?: 0,
                        tier          = body.tier ?: body.customer?.tier ?: "BRONZE",
                        pointsEarned  = body.pointsEarned ?: 0,
                        paymentAmount = if (body.status == "ok") body.amount else null,
                        rewards       = body.rewards ?: emptyList(),
                        phone         = phone,
                        hasPIN        = body.customer?.hasPin ?: false
                    )
                    lastCustomerLoaded = loaded
                    _state.value = loaded
                    scheduleAutoReset()
                } else {
                    _state.value = KioskState.Error(body?.message ?: "Something went wrong")
                    delay(3000)
                    _state.value = KioskState.Idle
                }
            } catch (e: Exception) {
                _state.value = KioskState.Error("Connection error. Please try again.")
                delay(3000)
                _state.value = KioskState.Idle
            }
        }
    }

    fun requestRedeem(rewardId: String, rewardName: String) {
        val loaded = lastCustomerLoaded ?: return
        resetJob?.cancel()
        if (!loaded.hasPIN) {
            _state.value = KioskState.Error("No PIN set. Please visit our website to set up your PIN first.")
            viewModelScope.launch {
                delay(4000)
                _state.value = loaded
                scheduleAutoReset()
            }
            return
        }
        _state.value = KioskState.PinEntry(
            customer         = loaded,
            targetRewardId   = rewardId,
            targetRewardName = rewardName
        )
    }

    fun pinAppendDigit(digit: Char) {
        val s = _state.value as? KioskState.PinEntry ?: return
        if (s.pinDigits.length >= 4) return
        val updated = s.copy(pinDigits = s.pinDigits + digit, error = null)
        _state.value = updated
        if (updated.pinDigits.length == 4) {
            submitPin(updated)
        }
    }

    fun pinBackspace() {
        val s = _state.value as? KioskState.PinEntry ?: return
        if (s.pinDigits.isNotEmpty()) _state.value = s.copy(pinDigits = s.pinDigits.dropLast(1), error = null)
    }

    fun cancelPin() {
        val s = _state.value as? KioskState.PinEntry ?: return
        _state.value = s.customer
        lastCustomerLoaded = s.customer
        scheduleAutoReset()
    }

    private fun submitPin(s: KioskState.PinEntry) {
        val prev = s.customer
        resetJob?.cancel()
        viewModelScope.launch {
            _state.value = KioskState.Redeeming
            try {
                val resp = api.kioskRedeem(KioskRedeemRequest(prev.phone, s.targetRewardId, s.pinDigits))
                val body = resp.body()
                if (resp.isSuccessful && body?.success == true) {
                    _state.value = KioskState.RedeemSuccess(
                        customerName = prev.customerName,
                        rewardName   = body.rewardName ?: s.targetRewardName,
                        pointsUsed   = body.pointsUsed ?: 0,
                        newPoints    = body.newPoints ?: 0,
                        tier         = body.tier ?: prev.tier
                    )
                    scheduleAutoReset(10_000L)
                } else {
                    val msg = body?.message ?: "Redemption failed"
                    _state.value = s.copy(pinDigits = "", error = msg)
                    scheduleAutoReset()
                }
            } catch (e: Exception) {
                _state.value = s.copy(pinDigits = "", error = "Connection error. Please try again.")
                scheduleAutoReset()
            }
        }
    }
}
