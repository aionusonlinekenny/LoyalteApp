package com.loyalte.app.domain.repository

import com.loyalte.app.domain.model.LoyaltyTransaction
import kotlinx.coroutines.flow.Flow

interface LoyaltyRepository {
    fun getTransactionsByCustomer(customerId: String): Flow<List<LoyaltyTransaction>>
    suspend fun getRecentTransactions(customerId: String, limit: Int): List<LoyaltyTransaction>
    suspend fun insertTransaction(transaction: LoyaltyTransaction)
    suspend fun insertTransactions(transactions: List<LoyaltyTransaction>)
}
