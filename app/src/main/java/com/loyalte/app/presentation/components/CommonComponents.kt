package com.loyalte.app.presentation.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.loyalte.app.domain.model.CustomerTier
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.presentation.theme.AdjustmentBlue
import com.loyalte.app.presentation.theme.BronzeColor
import com.loyalte.app.presentation.theme.EarnedGreen
import com.loyalte.app.presentation.theme.GoldColor
import com.loyalte.app.presentation.theme.PlatinumColor
import com.loyalte.app.presentation.theme.RedeemedRed
import com.loyalte.app.presentation.theme.SilverColor

@Composable
fun LoadingOverlay() {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color.Black.copy(alpha = 0.3f)),
        contentAlignment = Alignment.Center
    ) {
        CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
    }
}

@Composable
fun FullScreenLoading() {
    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        CircularProgressIndicator()
    }
}

@Composable
fun ErrorMessage(message: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.errorContainer
        ),
        shape = RoundedCornerShape(12.dp)
    ) {
        Text(
            text = message,
            modifier = Modifier.padding(16.dp),
            color = MaterialTheme.colorScheme.onErrorContainer,
            style = MaterialTheme.typography.bodyMedium
        )
    }
}

@Composable
fun SuccessMessage(message: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFD4EDDA)),
        shape = RoundedCornerShape(12.dp)
    ) {
        Text(
            text = message,
            modifier = Modifier.padding(16.dp),
            color = Color(0xFF155724),
            style = MaterialTheme.typography.bodyMedium
        )
    }
}

@Composable
fun TierBadge(tier: CustomerTier, modifier: Modifier = Modifier) {
    val (bgColor, label) = when (tier) {
        CustomerTier.BRONZE   -> Pair(BronzeColor,   "Bronze")
        CustomerTier.SILVER   -> Pair(SilverColor,   "Silver")
        CustomerTier.GOLD     -> Pair(GoldColor,     "Gold")
        CustomerTier.PLATINUM -> Pair(PlatinumColor, "Platinum")
    }
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(50),
        color = bgColor.copy(alpha = 0.15f)
    ) {
        Text(
            text = "⭐ $label",
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp),
            color = bgColor,
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.Bold
        )
    }
}

@Composable
fun PointsBadge(points: Int, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.primaryContainer
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(horizontal = 24.dp, vertical = 16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = points.toString(),
                style = MaterialTheme.typography.displayLarge.copy(fontSize = 48.sp),
                fontWeight = FontWeight.ExtraBold,
                color = MaterialTheme.colorScheme.primary
            )
            Text(
                text = "Points",
                style = MaterialTheme.typography.titleMedium,
                color = MaterialTheme.colorScheme.onPrimaryContainer,
                textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
fun TransactionTypeIndicator(type: TransactionType) {
    val (color, text) = when (type) {
        TransactionType.EARNED     -> Pair(EarnedGreen,          "+ Earned")
        TransactionType.REDEEMED   -> Pair(RedeemedRed,          "− Redeemed")
        TransactionType.ADJUSTMENT -> Pair(AdjustmentBlue, "± Adjusted")
    }
    Surface(
        shape = RoundedCornerShape(50),
        color = color.copy(alpha = 0.12f)
    ) {
        Text(
            text = text,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
            color = color,
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.SemiBold
        )
    }
}

@Composable
fun SectionHeader(title: String, modifier: Modifier = Modifier) {
    Text(
        text = title,
        modifier = modifier.padding(vertical = 8.dp),
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.Bold,
        color = MaterialTheme.colorScheme.onSurface
    )
}
