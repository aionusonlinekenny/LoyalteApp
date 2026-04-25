package com.loyalte.app.presentation.screens.customer

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CardGiftcard
import androidx.compose.material.icons.filled.CreditCard
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.presentation.components.FullScreenLoading
import com.loyalte.app.presentation.components.PointsBadge
import com.loyalte.app.presentation.components.SectionHeader
import com.loyalte.app.presentation.components.TierBadge
import com.loyalte.app.presentation.components.TransactionTypeIndicator
import com.loyalte.app.presentation.theme.EarnedGreen
import com.loyalte.app.presentation.theme.RedeemedRed
import com.loyalte.app.util.PhoneNumberValidator
import com.loyalte.app.util.toFormattedDateTime
import com.loyalte.app.util.toPointsLabel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CustomerProfileScreen(
    onNavigateToRewards: (customerId: String) -> Unit,
    onBack: () -> Unit,
    viewModel: CustomerProfileViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.adjustSuccess) {
        uiState.adjustSuccess?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearAdjustSuccess()
        }
    }

    LaunchedEffect(uiState.editSuccess) {
        uiState.editSuccess?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearEditSuccess()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Customer Profile") },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (uiState.customer != null) {
                        IconButton(onClick = viewModel::openEditDialog) {
                            Icon(Icons.Default.Edit, contentDescription = "Edit Info")
                        }
                        IconButton(onClick = viewModel::openAdjustDialog) {
                            Icon(Icons.Default.Tune, contentDescription = "Adjust Points")
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        floatingActionButton = {
            if (uiState.customer != null) {
                ExtendedFloatingActionButton(
                    onClick = { onNavigateToRewards(viewModel.getCustomerId()) },
                    icon = {
                        Icon(Icons.Default.CardGiftcard, contentDescription = null)
                    },
                    text = { Text("Redeem Points", fontWeight = FontWeight.Bold) },
                    containerColor = MaterialTheme.colorScheme.primary,
                    contentColor = MaterialTheme.colorScheme.onPrimary
                )
            }
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        when {
            uiState.isLoading -> FullScreenLoading()
            uiState.errorMessage != null -> {
                Box(
                    modifier = Modifier.fillMaxSize().padding(padding),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = uiState.errorMessage!!,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyLarge
                    )
                }
            }
            uiState.customer != null -> {
                val customer = uiState.customer!!
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding),
                    contentPadding = PaddingValues(
                        start = 16.dp,
                        end = 16.dp,
                        top = 16.dp,
                        bottom = 100.dp    // space for FAB
                    ),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    // Header card
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(20.dp),
                            elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                        ) {
                            Column(
                                modifier = Modifier.padding(20.dp),
                                horizontalAlignment = Alignment.CenterHorizontally
                            ) {
                                // Avatar placeholder
                                Surface(
                                    shape = RoundedCornerShape(50),
                                    color = MaterialTheme.colorScheme.primaryContainer,
                                    modifier = Modifier.size(72.dp)
                                ) {
                                    Box(contentAlignment = Alignment.Center) {
                                        Text(
                                            text = customer.name.take(1).uppercase(),
                                            style = MaterialTheme.typography.headlineLarge,
                                            color = MaterialTheme.colorScheme.primary
                                        )
                                    }
                                }

                                Spacer(Modifier.height(12.dp))

                                Text(
                                    text = customer.name,
                                    style = MaterialTheme.typography.headlineMedium,
                                    fontWeight = FontWeight.Bold
                                )

                                Spacer(Modifier.height(4.dp))
                                TierBadge(tier = customer.tier)

                                Spacer(Modifier.height(16.dp))

                                // Points balance — the centrepiece
                                PointsBadge(
                                    points = customer.points,
                                    modifier = Modifier.fillMaxWidth()
                                )
                            }
                        }
                    }

                    // Customer details card
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp)
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                SectionHeader("Account Details")

                                DetailRow(
                                    icon = { Icon(Icons.Default.Phone, contentDescription = null) },
                                    label = "Phone",
                                    value = PhoneNumberValidator.formatForDisplay(customer.phone)
                                )

                                if (customer.email != null) {
                                    DetailRow(
                                        icon = { Icon(Icons.Default.Email, contentDescription = null) },
                                        label = "Email",
                                        value = customer.email
                                    )
                                }

                                DetailRow(
                                    icon = { Icon(Icons.Default.CreditCard, contentDescription = null) },
                                    label = "Member ID",
                                    value = customer.memberId
                                )

                                DetailRow(
                                    icon = { Icon(Icons.Default.Person, contentDescription = null) },
                                    label = "Tier",
                                    value = customer.tier.displayName
                                )
                            }
                        }
                    }

                    // Transaction history
                    item {
                        SectionHeader(
                            title = "Transaction History",
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }

                    if (uiState.transactions.isEmpty()) {
                        item {
                            Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp)) {
                                Text(
                                    text = "No transactions yet.",
                                    modifier = Modifier.padding(20.dp),
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    } else {
                        items(uiState.transactions, key = { it.id }) { transaction ->
                            TransactionItem(transaction = transaction)
                        }
                    }
                }
            }
        }
    }

    // Edit customer info dialog
    if (uiState.showEditDialog && uiState.customer != null) {
        EditCustomerDialog(
            customer = uiState.customer!!,
            isSaving = uiState.isSaving,
            error = uiState.editError,
            onDismiss = viewModel::closeEditDialog,
            onConfirm = { name, phone, email -> viewModel.saveCustomerInfo(name, phone, email) }
        )
    }

    // Adjust Points dialog
    if (uiState.showAdjustDialog) {
        AdjustPointsDialog(
            isAdjusting = uiState.isAdjusting,
            error = uiState.adjustError,
            onDismiss = viewModel::closeAdjustDialog,
            onConfirm = { delta, desc -> viewModel.adjustPoints(delta, desc) }
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun EditCustomerDialog(
    customer: com.loyalte.app.domain.model.Customer,
    isSaving: Boolean,
    error: String?,
    onDismiss: () -> Unit,
    onConfirm: (name: String, phone: String, email: String) -> Unit
) {
    var name  by remember { mutableStateOf(customer.name) }
    var phone by remember { mutableStateOf(customer.phone) }
    var email by remember { mutableStateOf(customer.email ?: "") }
    var nameError  by remember { mutableStateOf(false) }
    var phoneError by remember { mutableStateOf(false) }

    AlertDialog(
        onDismissRequest = { if (!isSaving) onDismiss() },
        title = { Text("Edit Customer Info", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it; nameError = false },
                    label = { Text("Full Name *") },
                    leadingIcon = { Icon(Icons.Default.Person, contentDescription = null) },
                    singleLine = true,
                    isError = nameError,
                    supportingText = if (nameError) ({ Text("Name is required") }) else null,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = phone,
                    onValueChange = { phone = it; phoneError = false },
                    label = { Text("Phone *") },
                    leadingIcon = { Icon(Icons.Default.Phone, contentDescription = null) },
                    singleLine = true,
                    isError = phoneError,
                    supportingText = if (phoneError) ({ Text("Phone is required") }) else null,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Email (optional)") },
                    leadingIcon = { Icon(Icons.Default.Email, contentDescription = null) },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                    modifier = Modifier.fillMaxWidth()
                )

                if (error != null) {
                    Text(
                        text = error,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val trimmedName  = name.trim()
                    val trimmedPhone = phone.trim()
                    if (trimmedName.isEmpty()) { nameError = true; return@Button }
                    if (trimmedPhone.isEmpty()) { phoneError = true; return@Button }
                    onConfirm(trimmedName, trimmedPhone, email)
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
                    Text("Save")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSaving) { Text("Cancel") }
        }
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun AdjustPointsDialog(
    isAdjusting: Boolean,
    error: String?,
    onDismiss: () -> Unit,
    onConfirm: (delta: Int, description: String) -> Unit
) {
    var deltaText by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var deltaError by remember { mutableStateOf(false) }

    val delta = deltaText.trim().toIntOrNull()

    AlertDialog(
        onDismissRequest = { if (!isAdjusting) onDismiss() },
        title = { Text("Adjust Points", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Enter a positive number to add points, negative to deduct.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                // Quick preset buttons
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf(-50, -10, +10, +50, +100).forEach { preset ->
                        val label = if (preset > 0) "+$preset" else "$preset"
                        FilterChip(
                            selected = delta == preset,
                            onClick = { deltaText = preset.toString() },
                            label = { Text(label, style = MaterialTheme.typography.labelSmall) }
                        )
                    }
                }

                OutlinedTextField(
                    value = deltaText,
                    onValueChange = { deltaText = it; deltaError = false },
                    label = { Text("Points *") },
                    placeholder = { Text("e.g. +50 or -20") },
                    singleLine = true,
                    isError = deltaError,
                    supportingText = if (deltaError) ({ Text("Enter a non-zero number") }) else null,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Reason (optional)") },
                    placeholder = { Text("Staff adjustment") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                if (error != null) {
                    Text(
                        text = error,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val d = deltaText.trim().toIntOrNull()
                    if (d == null || d == 0) {
                        deltaError = true
                    } else {
                        onConfirm(d, description)
                    }
                },
                enabled = !isAdjusting
            ) {
                if (isAdjusting) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(18.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                } else {
                    Text("Apply")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isAdjusting) { Text("Cancel") }
        }
    )
}

@Composable
private fun DetailRow(
    icon: @Composable () -> Unit,
    label: String,
    value: String
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        CompositionLocalProvider(
            LocalContentColor provides MaterialTheme.colorScheme.onSurfaceVariant
        ) {
            icon()
        }
        Spacer(Modifier.width(12.dp))
        Column {
            Text(
                text = label,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Text(
                text = value,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.Medium
            )
        }
    }
    HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
}

@Composable
private fun TransactionItem(transaction: LoyaltyTransaction) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = transaction.description,
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    text = transaction.createdAt.toFormattedDateTime(),
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(Modifier.height(4.dp))
                TransactionTypeIndicator(type = transaction.type)
            }

            Spacer(Modifier.width(16.dp))

            val (pointsColor, sign) = when (transaction.type) {
                TransactionType.EARNED     -> Pair(EarnedGreen, "+")
                TransactionType.REDEEMED   -> Pair(RedeemedRed, "−")
                TransactionType.ADJUSTMENT -> Pair(MaterialTheme.colorScheme.onSurface, "±")
            }
            Text(
                text = "$sign${transaction.points.toPointsLabel()}",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = pointsColor
            )
        }
    }
}
