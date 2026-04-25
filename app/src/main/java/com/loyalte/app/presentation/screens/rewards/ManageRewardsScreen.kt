package com.loyalte.app.presentation.screens.rewards

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.loyalte.app.data.remote.api.dto.RewardDto

private val CATEGORIES = listOf("FOOD", "DRINK", "DISCOUNT", "OTHER")
private val CATEGORY_LABELS = mapOf(
    "FOOD"     to "Food",
    "DRINK"    to "Drink",
    "DISCOUNT" to "Discount",
    "OTHER"    to "Other"
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManageRewardsScreen(
    onOpenDrawer: () -> Unit,
    viewModel: ManageRewardsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Manage Rewards", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onOpenDrawer) {
                        Icon(Icons.Default.Menu, contentDescription = "Menu")
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = viewModel::openAddDialog,
                containerColor = MaterialTheme.colorScheme.primary
            ) {
                Icon(Icons.Default.Add, contentDescription = "Add Reward")
            }
        }
    ) { padding ->

        Box(Modifier.fillMaxSize().padding(padding)) {
            when {
                uiState.isLoading -> {
                    CircularProgressIndicator(Modifier.align(Alignment.Center))
                }
                uiState.rewards.isEmpty() -> {
                    Column(
                        Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text("No rewards yet", style = MaterialTheme.typography.bodyLarge, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(Modifier.height(8.dp))
                        Text("Tap + to add one", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                else -> {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        items(uiState.rewards, key = { it.id }) { reward ->
                            RewardCard(
                                reward = reward,
                                onEdit = { viewModel.openEditDialog(reward) },
                                onDelete = { viewModel.confirmDelete(reward) }
                            )
                        }
                        item { Spacer(Modifier.height(72.dp)) }
                    }
                }
            }

            // Success snackbar
            uiState.successMessage?.let { msg ->
                Snackbar(
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(16.dp),
                    containerColor = MaterialTheme.colorScheme.primary
                ) {
                    Text(msg, color = MaterialTheme.colorScheme.onPrimary)
                }
            }

            // Error snackbar
            uiState.error?.let { err ->
                Snackbar(
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(16.dp),
                    containerColor = MaterialTheme.colorScheme.error,
                    action = {
                        TextButton(onClick = viewModel::clearError) {
                            Text("Dismiss", color = MaterialTheme.colorScheme.onError)
                        }
                    }
                ) {
                    Text(err, color = MaterialTheme.colorScheme.onError)
                }
            }
        }
    }

    // Add / Edit dialog
    if (uiState.showDialog) {
        RewardFormDialog(
            editing = uiState.editingReward,
            isSaving = uiState.isSaving,
            onDismiss = viewModel::dismissDialog,
            onSave = { name, desc, pts, cat, active ->
                viewModel.saveReward(name, desc, pts, cat, active)
            }
        )
    }

    // Delete confirmation dialog
    uiState.deleteTarget?.let { target ->
        AlertDialog(
            onDismissRequest = viewModel::dismissDelete,
            title = { Text("Delete Reward") },
            text = { Text("Delete \"${target.name}\"? This cannot be undone.") },
            confirmButton = {
                TextButton(
                    onClick = viewModel::executeDelete,
                    colors = ButtonDefaults.textButtonColors(contentColor = MaterialTheme.colorScheme.error)
                ) { Text("Delete") }
            },
            dismissButton = {
                TextButton(onClick = viewModel::dismissDelete) { Text("Cancel") }
            }
        )
    }
}

@Composable
private fun RewardCard(
    reward: RewardDto,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    val isActive = reward.isActive == 1
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (isActive)
                MaterialTheme.colorScheme.surface
            else
                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = if (isActive) 2.dp else 0.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Category badge
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .background(
                        color = categoryColor(reward.category).copy(alpha = 0.15f),
                        shape = RoundedCornerShape(8.dp)
                    ),
                contentAlignment = Alignment.Center
            ) {
                Text(categoryEmoji(reward.category), fontSize = 20.sp)
            }

            Spacer(Modifier.width(12.dp))

            Column(Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        text = reward.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = if (isActive) MaterialTheme.colorScheme.onSurface
                                else MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    if (!isActive) {
                        Surface(
                            shape = RoundedCornerShape(4.dp),
                            color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.12f)
                        ) {
                            Text(
                                "Inactive",
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                }
                if (reward.description.isNotBlank()) {
                    Text(
                        text = reward.description,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        maxLines = 2
                    )
                }
                Spacer(Modifier.height(4.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Surface(
                        shape = RoundedCornerShape(6.dp),
                        color = MaterialTheme.colorScheme.primaryContainer
                    ) {
                        Text(
                            "${reward.pointsRequired} pts",
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                        )
                    }
                    Surface(
                        shape = RoundedCornerShape(6.dp),
                        color = categoryColor(reward.category).copy(alpha = 0.12f)
                    ) {
                        Text(
                            CATEGORY_LABELS[reward.category] ?: reward.category,
                            style = MaterialTheme.typography.labelMedium,
                            color = categoryColor(reward.category),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                        )
                    }
                }
            }

            // Action buttons
            IconButton(onClick = onEdit) {
                Icon(
                    Icons.Default.Edit,
                    contentDescription = "Edit",
                    tint = MaterialTheme.colorScheme.primary
                )
            }
            IconButton(onClick = onDelete) {
                Icon(
                    Icons.Default.Delete,
                    contentDescription = "Delete",
                    tint = MaterialTheme.colorScheme.error
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun RewardFormDialog(
    editing: RewardDto?,
    isSaving: Boolean,
    onDismiss: () -> Unit,
    onSave: (name: String, description: String, pointsRequired: Int, category: String, isActive: Boolean) -> Unit
) {
    var name by remember(editing) { mutableStateOf(editing?.name ?: "") }
    var description by remember(editing) { mutableStateOf(editing?.description ?: "") }
    var pointsText by remember(editing) { mutableStateOf(editing?.pointsRequired?.toString() ?: "") }
    var category by remember(editing) { mutableStateOf(editing?.category ?: "OTHER") }
    var isActive by remember(editing) { mutableStateOf((editing?.isActive ?: 1) == 1) }
    var categoryExpanded by remember { mutableStateOf(false) }
    var nameError by remember { mutableStateOf(false) }
    var pointsError by remember { mutableStateOf(false) }

    AlertDialog(
        onDismissRequest = { if (!isSaving) onDismiss() },
        title = { Text(if (editing != null) "Edit Reward" else "New Reward", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it; nameError = false },
                    label = { Text("Name *") },
                    singleLine = true,
                    isError = nameError,
                    supportingText = if (nameError) ({ Text("Required") }) else null,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Description") },
                    maxLines = 2,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = pointsText,
                    onValueChange = { pointsText = it; pointsError = false },
                    label = { Text("Points Required *") },
                    singleLine = true,
                    isError = pointsError,
                    supportingText = if (pointsError) ({ Text("Must be > 0") }) else null,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth()
                )

                // Category dropdown
                ExposedDropdownMenuBox(
                    expanded = categoryExpanded,
                    onExpandedChange = { categoryExpanded = !categoryExpanded }
                ) {
                    OutlinedTextField(
                        value = CATEGORY_LABELS[category] ?: category,
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Category") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = categoryExpanded) },
                        modifier = Modifier.menuAnchor().fillMaxWidth()
                    )
                    ExposedDropdownMenu(
                        expanded = categoryExpanded,
                        onDismissRequest = { categoryExpanded = false }
                    ) {
                        CATEGORIES.forEach { cat ->
                            DropdownMenuItem(
                                text = { Text(CATEGORY_LABELS[cat] ?: cat) },
                                onClick = { category = cat; categoryExpanded = false }
                            )
                        }
                    }
                }

                // Active toggle
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Active", style = MaterialTheme.typography.bodyLarge)
                    Switch(checked = isActive, onCheckedChange = { isActive = it })
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val pts = pointsText.trim().toIntOrNull() ?: 0
                    nameError = name.isBlank()
                    pointsError = pts <= 0
                    if (!nameError && !pointsError) {
                        onSave(name.trim(), description.trim(), pts, category, isActive)
                    }
                },
                enabled = !isSaving
            ) {
                if (isSaving) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(18.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                } else {
                    Text(if (editing != null) "Save" else "Create")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSaving) { Text("Cancel") }
        }
    )
}

@Composable
private fun categoryColor(category: String): Color = when (category) {
    "FOOD"     -> Color(0xFFE65100)
    "DRINK"    -> Color(0xFF1565C0)
    "DISCOUNT" -> Color(0xFF2E7D32)
    else       -> Color(0xFF6A1B9A)
}

private fun categoryEmoji(category: String): String = when (category) {
    "FOOD"     -> "🍽"
    "DRINK"    -> "☕"
    "DISCOUNT" -> "%"
    else       -> "🎁"
}
