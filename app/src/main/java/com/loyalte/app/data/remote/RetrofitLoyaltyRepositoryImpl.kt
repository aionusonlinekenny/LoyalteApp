package com.loyalte.app.data.remote

import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.EarnPointsRequest
import com.loyalte.app.data.remote.api.dto.TransactionDto
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.domain.repository.LoyaltyRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject

class RetrofitLoyaltyRepositoryImpl @Inject constructor(
    private val api: LoyalteApiService
) : LoyaltyRepository {

    override fun getTransactionsByCustomer(customerId: String): Flow<List<LoyaltyTransaction>> = flow {
        val txns = api.getTransactions(customerId).body()?.transactions?.map { it.toDomain() }
            ?: emptyList()
        emit(txns)
    }

    override suspend fun getRecentTransactions(customerId: String, limit: Int): List<LoyaltyTransaction> =
        api.getTransactions(customerId, limit = limit).body()?.transactions?.map { it.toDomain() }
            ?: emptyList()

    override suspend fun insertTransaction(transaction: LoyaltyTransaction) {
        if (transaction.type == TransactionType.EARNED) {
            api.earnPoints(
                EarnPointsRequest(
                    customerId  = transaction.customerId,
                    points      = transaction.points,
                    description = transaction.description
                )
            )
        }
        // REDEEMED transactions are handled by RetrofitRewardRepositoryImpl.insertRedemption
    }

    override suspend fun insertTransactions(transactions: List<LoyaltyTransaction>) {
        transactions.forEach { insertTransaction(it) }
    }
}

private fun TransactionDto.toDomain() = LoyaltyTransaction(
    id          = id,
    customerId  = customerId,
    type        = TransactionType.fromString(type),
    points      = points,
    description = description,
    createdAt   = createdAt
)
