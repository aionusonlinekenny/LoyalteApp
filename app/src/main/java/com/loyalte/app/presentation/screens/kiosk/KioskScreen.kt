package com.loyalte.app.presentation.screens.kiosk

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Backspace
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.loyalte.app.presentation.screens.kiosk.KioskViewModel.KioskState

private val BgTop    = Color(0xFF1A0A3C)
private val BgBot    = Color(0xFF2D1B69)
private val Gold     = Color(0xFFFFD700)
private val GoldSoft = Color(0xFFFFF176)
private val White    = Color.White
private val WhiteDim = Color(0xCCFFFFFF)
private val NumpadBg = Color(0x33FFFFFF)
private val NumpadPress = Color(0x66FFFFFF)

@Composable
fun KioskScreen(
    onBack: () -> Unit,
    viewModel: KioskViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    val phone by viewModel.phone.collectAsStateWithLifecycle()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(BgTop, BgBot)))
    ) {
        // Back button (top-left, small — for staff to exit kiosk mode)
        IconButton(
            onClick = onBack,
            modifier = Modifier.padding(8.dp)
        ) {
            Icon(Icons.Default.ArrowBack, contentDescription = "Exit kiosk", tint = WhiteDim)
        }

        AnimatedContent(
            targetState = state,
            transitionSpec = {
                fadeIn(tween(300)) togetherWith fadeOut(tween(200))
            },
            modifier = Modifier.fillMaxSize(),
            label = "kiosk_state"
        ) { currentState ->
            when (currentState) {
                is KioskState.Idle, is KioskState.Error -> {
                    val errorMsg = (currentState as? KioskState.Error)?.message
                    IdleScreen(
                        phone = phone,
                        errorMsg = errorMsg,
                        onDigit = viewModel::appendDigit,
                        onBackspace = viewModel::backspace,
                        onSubmit = viewModel::claim
                    )
                }
                is KioskState.Loading -> {
                    LoadingScreen()
                }
                is KioskState.Success -> {
                    SuccessScreen(state = currentState)
                }
                is KioskState.NoPayment -> {
                    NoPaymentScreen(state = currentState)
                }
            }
        }
    }
}

@Composable
private fun IdleScreen(
    phone: String,
    errorMsg: String?,
    onDigit: (Char) -> Unit,
    onBackspace: () -> Unit,
    onSubmit: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("⭐", fontSize = 52.sp)
        Spacer(Modifier.height(4.dp))
        Text(
            "Stone Pho Loyalty",
            fontSize = 26.sp,
            fontWeight = FontWeight.ExtraBold,
            color = Gold,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(6.dp))
        Text(
            "Enter your phone number to earn points",
            fontSize = 15.sp,
            color = WhiteDim,
            textAlign = TextAlign.Center
        )

        Spacer(Modifier.height(28.dp))

        // Phone display
        val displayPhone = formatPhone(phone)
        Text(
            text = if (displayPhone.isEmpty()) "( _ _ _ )  _ _ _ - _ _ _ _" else displayPhone,
            fontSize = 32.sp,
            fontWeight = FontWeight.Bold,
            color = if (displayPhone.isEmpty()) Color(0x66FFFFFF) else White,
            letterSpacing = 2.sp,
            textAlign = TextAlign.Center
        )

        Spacer(Modifier.height(8.dp))

        // Progress bar (filled based on digits entered)
        LinearProgressIndicator(
            progress = { phone.length / 10f },
            modifier = Modifier
                .fillMaxWidth()
                .height(4.dp)
                .clip(RoundedCornerShape(2.dp)),
            color = Gold,
            trackColor = Color(0x33FFFFFF)
        )

        if (errorMsg != null) {
            Spacer(Modifier.height(8.dp))
            Text(
                text = errorMsg,
                color = Color(0xFFFF6B6B),
                fontSize = 14.sp,
                textAlign = TextAlign.Center
            )
        }

        Spacer(Modifier.height(28.dp))

        // Number pad
        Numpad(onDigit = onDigit, onBackspace = onBackspace)

        Spacer(Modifier.height(20.dp))

        // Submit button
        Button(
            onClick = onSubmit,
            enabled = phone.length == 10,
            modifier = Modifier
                .fillMaxWidth()
                .height(60.dp),
            shape = RoundedCornerShape(16.dp),
            colors = ButtonDefaults.buttonColors(
                containerColor = Gold,
                disabledContainerColor = Color(0x44FFFFFF),
                contentColor = Color(0xFF1A0A3C),
                disabledContentColor = WhiteDim
            )
        ) {
            Text(
                "EARN POINTS",
                fontSize = 18.sp,
                fontWeight = FontWeight.ExtraBold,
                letterSpacing = 1.sp
            )
        }
    }
}

@Composable
private fun Numpad(onDigit: (Char) -> Unit, onBackspace: () -> Unit) {
    val rows = listOf(
        listOf('1', '2', '3'),
        listOf('4', '5', '6'),
        listOf('7', '8', '9'),
        listOf(' ', '0', '<')  // ' ' = empty, '<' = backspace
    )
    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        rows.forEach { row ->
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                row.forEach { key ->
                    when (key) {
                        ' '  -> Spacer(Modifier.size(80.dp))
                        '<'  -> NumKey(
                            content = {
                                Icon(
                                    Icons.Default.Backspace,
                                    contentDescription = "Backspace",
                                    tint = White,
                                    modifier = Modifier.size(26.dp)
                                )
                            },
                            onClick = onBackspace
                        )
                        else -> NumKey(
                            content = {
                                Text(
                                    key.toString(),
                                    fontSize = 26.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = White
                                )
                            },
                            onClick = { onDigit(key) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun NumKey(content: @Composable () -> Unit, onClick: () -> Unit) {
    Surface(
        onClick = onClick,
        modifier = Modifier.size(80.dp),
        shape = CircleShape,
        color = NumpadBg,
        contentColor = White
    ) {
        Box(contentAlignment = Alignment.Center) {
            content()
        }
    }
}

@Composable
private fun LoadingScreen() {
    Column(
        modifier = Modifier.fillMaxSize(),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        CircularProgressIndicator(color = Gold, modifier = Modifier.size(64.dp), strokeWidth = 5.dp)
        Spacer(Modifier.height(24.dp))
        Text("Finding your payment...", color = WhiteDim, fontSize = 18.sp)
    }
}

@Composable
private fun SuccessScreen(state: KioskState.Success) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(Color(0xFF1B5E20), Color(0xFF2E7D32))))
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("🎉", fontSize = 72.sp)
        Spacer(Modifier.height(16.dp))
        Text(
            "Welcome, ${state.customerName}!",
            fontSize = 28.sp,
            fontWeight = FontWeight.ExtraBold,
            color = White,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(24.dp))

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = Color(0x33FFFFFF))
        ) {
            Column(
                modifier = Modifier.padding(24.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    "+${state.pointsEarned}",
                    fontSize = 64.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = GoldSoft
                )
                Text(
                    "points earned!",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    color = White
                )
                Spacer(Modifier.height(8.dp))
                Text(
                    "For your \$${state.amount} purchase",
                    fontSize = 15.sp,
                    color = WhiteDim
                )
                Spacer(Modifier.height(16.dp))
                Divider(color = Color(0x44FFFFFF))
                Spacer(Modifier.height(16.dp))
                Text(
                    "Total: ${state.newTotal} pts  •  ${state.tier}",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = GoldSoft
                )
            }
        }

        Spacer(Modifier.height(32.dp))
        Text("Thank you for dining with us!", fontSize = 18.sp, color = WhiteDim)
        Spacer(Modifier.height(8.dp))
        Text("Screen resets in a few seconds...", fontSize = 13.sp, color = Color(0x88FFFFFF))
    }
}

@Composable
private fun NoPaymentScreen(state: KioskState.NoPayment) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(Color(0xFF4A2700), Color(0xFFE65100))))
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("👋", fontSize = 72.sp)
        Spacer(Modifier.height(16.dp))
        Text(
            "Welcome back, ${state.customerName}!",
            fontSize = 26.sp,
            fontWeight = FontWeight.ExtraBold,
            color = White,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(24.dp))

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = Color(0x33FFFFFF))
        ) {
            Column(
                modifier = Modifier.padding(24.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text("⚠️ No recent payment found", fontSize = 18.sp, color = White, fontWeight = FontWeight.Bold)
                Spacer(Modifier.height(8.dp))
                Text(
                    "Please enter your phone within 20 minutes of paying.",
                    fontSize = 14.sp,
                    color = WhiteDim,
                    textAlign = TextAlign.Center
                )
                Spacer(Modifier.height(16.dp))
                Divider(color = Color(0x44FFFFFF))
                Spacer(Modifier.height(16.dp))
                Text("Your balance: ${state.currentPoints} pts", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = GoldSoft)
            }
        }

        Spacer(Modifier.height(28.dp))
        Text("Please see staff if you need help.", fontSize = 15.sp, color = WhiteDim)
        Spacer(Modifier.height(8.dp))
        Text("Screen resets in a few seconds...", fontSize = 13.sp, color = Color(0x88FFFFFF))
    }
}

private fun formatPhone(digits: String): String {
    if (digits.isEmpty()) return ""
    val sb = StringBuilder()
    digits.forEachIndexed { i, c ->
        when (i) {
            0    -> sb.append("($c")
            3    -> sb.append(") $c")
            6    -> sb.append("-$c")
            else -> sb.append(c)
        }
    }
    return sb.toString()
}
