package com.loyalte.app.presentation.screens.kiosk

import androidx.compose.animation.*
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.loyalte.app.data.remote.api.dto.RewardDto
import com.loyalte.app.presentation.screens.kiosk.KioskViewModel.KioskState

// ── Colours ───────────────────────────────────────────────────────────────────
private val BgTop    = Color(0xFF1A0A3C)
private val BgBot    = Color(0xFF2D1B69)
private val Gold     = Color(0xFFFFD700)
private val GoldSoft = Color(0xFFFFF176)
private val White    = Color.White
private val WhiteDim = Color(0xCCFFFFFF)
private val PanelBg  = Color(0x22FFFFFF)
private val DividerC = Color(0x33FFFFFF)

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
        // Exit kiosk (small back button — for staff only)
        IconButton(onClick = onBack, modifier = Modifier.padding(4.dp).size(36.dp)) {
            Icon(Icons.Default.ArrowBack, contentDescription = "Exit", tint = Color(0x66FFFFFF))
        }

        Row(Modifier.fillMaxSize()) {
            // ── LEFT PANEL — numpad ───────────────────────────────────────────
            Column(
                modifier = Modifier
                    .weight(4f)
                    .fillMaxHeight()
                    .padding(start = 24.dp, end = 12.dp, top = 16.dp, bottom = 16.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.Center
            ) {
                Text("⭐", fontSize = 36.sp)
                Text(
                    "Stone Pho Loyalty",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = Gold
                )
                Spacer(Modifier.height(16.dp))

                val isIdle = state is KioskState.Idle || state is KioskState.Error
                val displayPhone = formatPhone(phone)

                Text(
                    text = if (displayPhone.isEmpty()) "( _ _ _ )  _ _ _ - _ _ _ _"
                           else displayPhone,
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = if (displayPhone.isEmpty()) Color(0x55FFFFFF) else White,
                    letterSpacing = 1.sp
                )
                Spacer(Modifier.height(4.dp))
                LinearProgressIndicator(
                    progress = { phone.length / 10f },
                    modifier = Modifier.fillMaxWidth().height(3.dp).clip(RoundedCornerShape(2.dp)),
                    color = Gold,
                    trackColor = Color(0x33FFFFFF)
                )

                if (state is KioskState.Error) {
                    Spacer(Modifier.height(4.dp))
                    Text(
                        (state as KioskState.Error).message,
                        color = Color(0xFFFF6B6B),
                        fontSize = 12.sp,
                        textAlign = TextAlign.Center
                    )
                }

                Spacer(Modifier.height(14.dp))
                Numpad(
                    enabled = isIdle,
                    onDigit = viewModel::appendDigit,
                    onBackspace = viewModel::backspace
                )
                Spacer(Modifier.height(12.dp))
                Button(
                    onClick = viewModel::claim,
                    enabled = phone.length == 10 && isIdle,
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Gold,
                        disabledContainerColor = Color(0x33FFFFFF),
                        contentColor = Color(0xFF1A0A3C),
                        disabledContentColor = WhiteDim
                    )
                ) {
                    Text("EARN POINTS", fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, letterSpacing = 1.sp)
                }
            }

            // ── DIVIDER ───────────────────────────────────────────────────────
            Divider(
                modifier = Modifier.fillMaxHeight().width(1.dp),
                color = DividerC
            )

            // ── RIGHT PANEL — dynamic content ─────────────────────────────────
            AnimatedContent(
                targetState = state,
                transitionSpec = { fadeIn(tween(250)) togetherWith fadeOut(tween(200)) },
                modifier = Modifier.weight(6f).fillMaxHeight(),
                label = "right_panel"
            ) { s ->
                when (s) {
                    is KioskState.Idle, is KioskState.Error -> WelcomePanel()
                    is KioskState.Claiming                   -> BusyPanel("Checking your account...")
                    is KioskState.Redeeming                  -> BusyPanel("Redeeming your reward...")
                    is KioskState.CustomerLoaded             -> CustomerPanel(s, viewModel::redeem, viewModel::reset)
                    is KioskState.RedeemSuccess              -> RedeemSuccessPanel(s)
                }
            }
        }
    }
}

// ── Left numpad ───────────────────────────────────────────────────────────────

@Composable
private fun Numpad(enabled: Boolean, onDigit: (Char) -> Unit, onBackspace: () -> Unit) {
    val rows = listOf(listOf('1','2','3'), listOf('4','5','6'), listOf('7','8','9'), listOf(' ','0','<'))
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        rows.forEach { row ->
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                row.forEach { key ->
                    when (key) {
                        ' ' -> Spacer(Modifier.size(64.dp))
                        '<' -> NumKey(enabled, onClick = onBackspace) {
                            Icon(Icons.Default.Backspace, null, tint = White, modifier = Modifier.size(22.dp))
                        }
                        else -> NumKey(enabled, onClick = { onDigit(key) }) {
                            Text(key.toString(), fontSize = 22.sp, fontWeight = FontWeight.Bold, color = White)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun NumKey(enabled: Boolean, onClick: () -> Unit, content: @Composable () -> Unit) {
    Surface(
        onClick = onClick,
        enabled = enabled,
        modifier = Modifier.size(64.dp),
        shape = CircleShape,
        color = if (enabled) Color(0x33FFFFFF) else Color(0x11FFFFFF)
    ) {
        Box(contentAlignment = Alignment.Center) { content() }
    }
}

// ── Right panels ──────────────────────────────────────────────────────────────

@Composable
private fun WelcomePanel() {
    Column(
        modifier = Modifier.fillMaxSize().padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("👋", fontSize = 56.sp)
        Spacer(Modifier.height(16.dp))
        Text("Welcome!", fontSize = 28.sp, fontWeight = FontWeight.ExtraBold, color = Gold)
        Spacer(Modifier.height(12.dp))
        Text(
            "Enter your phone number on the\nleft to earn loyalty points.",
            fontSize = 16.sp, color = WhiteDim, textAlign = TextAlign.Center, lineHeight = 24.sp
        )
        Spacer(Modifier.height(32.dp))
        listOf("1. Pay at the register", "2. Enter your phone here", "3. Earn points instantly!").forEach {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(vertical = 4.dp)) {
                Surface(color = Gold, shape = CircleShape, modifier = Modifier.size(8.dp)) {}
                Spacer(Modifier.width(12.dp))
                Text(it, color = WhiteDim, fontSize = 15.sp)
            }
        }
    }
}

@Composable
private fun BusyPanel(message: String) {
    Column(
        modifier = Modifier.fillMaxSize(),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        CircularProgressIndicator(color = Gold, modifier = Modifier.size(52.dp), strokeWidth = 4.dp)
        Spacer(Modifier.height(20.dp))
        Text(message, color = WhiteDim, fontSize = 16.sp)
    }
}

@Composable
private fun CustomerPanel(
    state: KioskState.CustomerLoaded,
    onRedeem: (String, String) -> Unit,
    onDone: () -> Unit
) {
    Column(
        modifier = Modifier.fillMaxSize().padding(horizontal = 24.dp, vertical = 16.dp)
    ) {
        // Header card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = PanelBg)
        ) {
            Row(
                modifier = Modifier.padding(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        state.customerName,
                        fontSize = 20.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = White,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(state.memberId, fontSize = 12.sp, color = WhiteDim)
                }
                Spacer(Modifier.width(16.dp))
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        "${state.totalPoints} pts",
                        fontSize = 22.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = Gold
                    )
                    TierBadge(state.tier)
                }
            }
        }

        // Points earned banner (if any)
        if (state.pointsEarned > 0 && state.paymentAmount != null) {
            Spacer(Modifier.height(10.dp))
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0x44004D00))
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("🎉", fontSize = 24.sp)
                    Spacer(Modifier.width(10.dp))
                    Column {
                        Text(
                            "+${state.pointsEarned} points earned!",
                            fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF81C784)
                        )
                        Text(
                            "For your \$${state.paymentAmount} purchase",
                            fontSize = 12.sp, color = WhiteDim
                        )
                    }
                }
            }
        } else if (state.pointsEarned == 0) {
            Spacer(Modifier.height(10.dp))
            Text(
                "⚠️ No recent payment found — points will sync automatically once you pay.",
                fontSize = 12.sp, color = Color(0xFFFFCC80), textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth()
            )
        }

        Spacer(Modifier.height(12.dp))

        // Rewards section
        if (state.rewards.isNotEmpty()) {
            Text(
                "Available Rewards",
                fontSize = 15.sp, fontWeight = FontWeight.Bold, color = GoldSoft,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            LazyColumn(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(state.rewards) { reward ->
                    RewardRow(
                        reward = reward,
                        currentPoints = state.totalPoints,
                        onRedeem = { onRedeem(reward.id, reward.name) }
                    )
                }
            }
        } else {
            Box(modifier = Modifier.weight(1f), contentAlignment = Alignment.Center) {
                Text("No rewards available yet.", color = WhiteDim, fontSize = 14.sp)
            }
        }

        Spacer(Modifier.height(10.dp))
        TextButton(onClick = onDone, modifier = Modifier.align(Alignment.CenterHorizontally)) {
            Text("Done — New Customer →", color = WhiteDim, fontSize = 13.sp)
        }
    }
}

@Composable
private fun RewardRow(reward: RewardDto, currentPoints: Int, onRedeem: () -> Unit) {
    val canRedeem = currentPoints >= reward.pointsRequired
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (canRedeem) Color(0x33FFD700) else PanelBg
        )
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    reward.name,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = if (canRedeem) Gold else WhiteDim,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Text(
                    "${reward.pointsRequired} points required",
                    fontSize = 11.sp,
                    color = if (canRedeem) GoldSoft else Color(0x88FFFFFF)
                )
            }
            Spacer(Modifier.width(10.dp))
            Button(
                onClick = onRedeem,
                enabled = canRedeem,
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp),
                shape = RoundedCornerShape(20.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor = Gold,
                    disabledContainerColor = Color(0x22FFFFFF),
                    contentColor = Color(0xFF1A0A3C),
                    disabledContentColor = Color(0x55FFFFFF)
                )
            ) {
                Text("Redeem", fontSize = 13.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}

@Composable
private fun RedeemSuccessPanel(state: KioskState.RedeemSuccess) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(Color(0xFF1B5E20), Color(0xFF2E7D32))))
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("🎁", fontSize = 64.sp)
        Spacer(Modifier.height(12.dp))
        Text(
            "Reward Redeemed!",
            fontSize = 28.sp, fontWeight = FontWeight.ExtraBold, color = White
        )
        Spacer(Modifier.height(8.dp))
        Text(
            state.rewardName,
            fontSize = 20.sp, fontWeight = FontWeight.Bold, color = GoldSoft,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(20.dp))
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = Color(0x33FFFFFF))
        ) {
            Column(modifier = Modifier.padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                Text("−${state.pointsUsed} pts used", fontSize = 16.sp, color = Color(0xFFEF9A9A))
                Spacer(Modifier.height(8.dp))
                Text("Remaining balance", fontSize = 13.sp, color = WhiteDim)
                Text(
                    "${state.newPoints} pts  •  ${state.tier}",
                    fontSize = 20.sp, fontWeight = FontWeight.Bold, color = GoldSoft
                )
            }
        }
        Spacer(Modifier.height(20.dp))
        Text("Please show this screen to staff to claim your reward.", fontSize = 14.sp, color = WhiteDim, textAlign = TextAlign.Center)
        Spacer(Modifier.height(8.dp))
        Text("Screen resets in a few seconds...", fontSize = 12.sp, color = Color(0x88FFFFFF))
    }
}

@Composable
private fun TierBadge(tier: String) {
    val (bg, text) = when (tier) {
        "PLATINUM" -> Color(0xFF90CAF9) to Color(0xFF0D47A1)
        "GOLD"     -> Gold to Color(0xFF1A0A3C)
        "SILVER"   -> Color(0xFFB0BEC5) to Color(0xFF263238)
        else       -> Color(0xFFBCAAA4) to Color(0xFF3E2723)
    }
    Surface(color = bg, shape = RoundedCornerShape(6.dp)) {
        Text(
            tier,
            fontSize = 10.sp,
            fontWeight = FontWeight.ExtraBold,
            color = text,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
        )
    }
}

private fun formatPhone(digits: String): String {
    if (digits.isEmpty()) return ""
    val sb = StringBuilder()
    digits.forEachIndexed { i, c ->
        when (i) { 0 -> sb.append("($c"); 3 -> sb.append(") $c"); 6 -> sb.append("-$c"); else -> sb.append(c) }
    }
    return sb.toString()
}
