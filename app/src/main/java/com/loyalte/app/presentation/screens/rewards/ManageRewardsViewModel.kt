package com.loyalte.app.presentation.screens.rewards

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.RewardDto
import com.loyalte.app.data.remote.api.dto.SaveRewardRequest
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ManageRewardsViewModel @Inject constructor(
    private val api: LoyalteApiService
) : ViewModel() {

    data class UiState(
        val rewards: List<RewardDto> = emptyList(),
        val isLoading: Boolean = true,
        val showDialog: Boolean = false,
        val editingReward: RewardDto? = null,
        val isSaving: Boolean = false,
        val deleteTarget: RewardDto? = null,
        val error: String? = null,
        val successMessage: String? = null
    )

    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState.asStateFlow()

    init { loadRewards() }

    fun loadRewards() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            try {
                val resp = api.getAllRewards("false")
                if (resp.isSuccessful) {
                    _uiState.update { it.copy(rewards = resp.body()?.rewards ?: emptyList(), isLoading = false) }
                } else {
                    _uiState.update { it.copy(isLoading = false, error = "Failed to load rewards") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, error = "Connection error") }
            }
        }
    }

    fun openAddDialog() {
        _uiState.update { it.copy(showDialog = true, editingReward = null) }
    }

    fun openEditDialog(reward: RewardDto) {
        _uiState.update { it.copy(showDialog = true, editingReward = reward) }
    }

    fun dismissDialog() {
        _uiState.update { it.copy(showDialog = false, editingReward = null) }
    }

    fun saveReward(name: String, description: String, pointsRequired: Int, category: String, isActive: Boolean) {
        val editing = _uiState.value.editingReward
        val request = SaveRewardRequest(
            name           = name,
            description    = description,
            pointsRequired = pointsRequired,
            category       = category,
            isActive       = if (isActive) 1 else 0
        )
        _uiState.update { it.copy(isSaving = true) }
        viewModelScope.launch {
            try {
                val resp = if (editing != null) {
                    api.updateReward(editing.id, request)
                } else {
                    api.createReward(request)
                }
                if (resp.isSuccessful && resp.body()?.success == true) {
                    val msg = if (editing != null) "Reward updated" else "Reward created"
                    _uiState.update { it.copy(isSaving = false, showDialog = false, editingReward = null, successMessage = msg) }
                    loadRewards()
                    delay(2500)
                    _uiState.update { it.copy(successMessage = null) }
                } else {
                    _uiState.update { it.copy(isSaving = false, error = resp.body()?.message ?: "Save failed") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSaving = false, error = "Connection error") }
            }
        }
    }

    fun confirmDelete(reward: RewardDto) {
        _uiState.update { it.copy(deleteTarget = reward) }
    }

    fun dismissDelete() {
        _uiState.update { it.copy(deleteTarget = null) }
    }

    fun executeDelete() {
        val target = _uiState.value.deleteTarget ?: return
        _uiState.update { it.copy(deleteTarget = null) }
        viewModelScope.launch {
            try {
                val resp = api.deleteReward(target.id)
                if (resp.isSuccessful) {
                    _uiState.update { it.copy(successMessage = "\"${target.name}\" deleted") }
                    loadRewards()
                    delay(2500)
                    _uiState.update { it.copy(successMessage = null) }
                } else {
                    _uiState.update { it.copy(error = "Delete failed") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = "Connection error") }
            }
        }
    }

    fun clearError() { _uiState.update { it.copy(error = null) } }
}
