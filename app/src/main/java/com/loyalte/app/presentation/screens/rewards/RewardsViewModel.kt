package com.loyalte.app.presentation.screens.rewards

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.Redemption
import com.loyalte.app.domain.model.Reward
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.RewardRepository
import com.loyalte.app.domain.usecase.RedeemRewardUseCase
import com.loyalte.app.presentation.navigation.Screen
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RewardsViewModel @Inject constructor(
    savedStateHandle: SavedStateHandle,
    private val customerRepository: CustomerRepository,
    private val rewardRepository: RewardRepository,
    private val redeemRewardUseCase: RedeemRewardUseCase
) : ViewModel() {

    private val customerId: String =
        checkNotNull(savedStateHandle[Screen.Rewards.ARG_CUSTOMER_ID])

    data class UiState(
        val customer: Customer? = null,
        val rewards: List<Reward> = emptyList(),
        val redemptions: List<Redemption> = emptyList(),
        val isLoading: Boolean = true,
        val redeemingRewardId: String? = null,
        val confirmReward: Reward? = null,
        val successMessage: String? = null,
        val errorMessage: String? = null
    )

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    private fun loadData() {
        viewModelScope.launch {
            val customer = customerRepository.getCustomerById(customerId)
            _uiState.update { it.copy(customer = customer) }
        }
        viewModelScope.launch {
            rewardRepository.getAllActiveRewards().collect { rewards ->
                _uiState.update { it.copy(rewards = rewards, isLoading = false) }
            }
        }
        viewModelScope.launch {
            rewardRepository.getRedemptionsByCustomer(customerId).collect { redemptions ->
                _uiState.update { it.copy(redemptions = redemptions) }
            }
        }
    }

    fun onRedeemClick(reward: Reward) {
        _uiState.update { it.copy(confirmReward = reward, errorMessage = null) }
    }

    fun onDismissConfirm() {
        _uiState.update { it.copy(confirmReward = null) }
    }

    fun onConfirmRedeem() {
        val reward = _uiState.value.confirmReward ?: return
        _uiState.update { it.copy(confirmReward = null, redeemingRewardId = reward.id) }

        viewModelScope.launch {
            when (val result = redeemRewardUseCase(customerId, reward.id)) {
                is RedeemRewardUseCase.Result.Success -> {
                    _uiState.update {
                        it.copy(
                            redeemingRewardId = null,
                            customer = it.customer?.copy(points = result.updatedPoints),
                            successMessage = "✓ ${reward.name} redeemed! " +
                                "${reward.pointsRequired} pts deducted. " +
                                "New balance: ${result.updatedPoints} pts"
                        )
                    }
                    delay(4000)
                    _uiState.update { it.copy(successMessage = null) }
                }
                RedeemRewardUseCase.Result.InsufficientPoints -> {
                    _uiState.update {
                        it.copy(
                            redeemingRewardId = null,
                            errorMessage = "Not enough points to redeem ${reward.name}."
                        )
                    }
                }
                RedeemRewardUseCase.Result.CustomerNotFound -> {
                    _uiState.update {
                        it.copy(redeemingRewardId = null, errorMessage = "Customer not found.")
                    }
                }
                RedeemRewardUseCase.Result.RewardNotFound -> {
                    _uiState.update {
                        it.copy(redeemingRewardId = null, errorMessage = "Reward is no longer available.")
                    }
                }
                is RedeemRewardUseCase.Result.Error -> {
                    _uiState.update {
                        it.copy(redeemingRewardId = null, errorMessage = result.message)
                    }
                }
            }
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(successMessage = null, errorMessage = null) }
    }
}
