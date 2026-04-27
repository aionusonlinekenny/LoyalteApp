package com.loyalte.app.presentation.screens.rewards

import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.RewardDto
import com.loyalte.app.data.remote.api.dto.SaveRewardRequest
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject

@HiltViewModel
class ManageRewardsViewModel @Inject constructor(
    private val api: LoyalteApiService,
    @ApplicationContext private val context: Context
) : ViewModel() {

    data class UiState(
        val rewards: List<RewardDto> = emptyList(),
        val isLoading: Boolean = true,
        val showDialog: Boolean = false,
        val editingReward: RewardDto? = null,
        val pendingImageUri: Uri? = null,
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
        _uiState.update { it.copy(showDialog = true, editingReward = null, pendingImageUri = null) }
    }

    fun openEditDialog(reward: RewardDto) {
        _uiState.update { it.copy(showDialog = true, editingReward = reward, pendingImageUri = null) }
    }

    fun dismissDialog() {
        _uiState.update { it.copy(showDialog = false, editingReward = null, pendingImageUri = null) }
    }

    fun setPendingImage(uri: Uri?) {
        _uiState.update { it.copy(pendingImageUri = uri) }
    }

    fun saveReward(name: String, description: String, pointsRequired: Int, category: String, isActive: Boolean) {
        val editing = _uiState.value.editingReward
        val imageUri = _uiState.value.pendingImageUri
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
                    val rewardId = resp.body()?.reward?.id ?: editing?.id
                    // Upload image if one was selected
                    if (rewardId != null && imageUri != null) {
                        uploadImage(rewardId, imageUri)
                    }
                    val msg = if (editing != null) "Reward updated" else "Reward created"
                    _uiState.update {
                        it.copy(isSaving = false, showDialog = false, editingReward = null,
                                pendingImageUri = null, successMessage = msg)
                    }
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

    private suspend fun uploadImage(rewardId: String, uri: Uri) {
        try {
            val cr = context.contentResolver
            val mimeType = cr.getType(uri) ?: "image/jpeg"
            val ext = when (mimeType) {
                "image/png"  -> "png"
                "image/webp" -> "webp"
                "image/gif"  -> "gif"
                else         -> "jpg"
            }
            val bytes = cr.openInputStream(uri)?.use { it.readBytes() } ?: return
            val requestBody = bytes.toRequestBody(mimeType.toMediaType())
            val part = MultipartBody.Part.createFormData("image", "reward.$ext", requestBody)
            api.uploadRewardImage(rewardId, part)
        } catch (_: Exception) { /* image upload failure is non-fatal */ }
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
