package com.loyalte.app.data.repository

import com.loyalte.app.data.local.dao.LoyaltyTransactionDao
import com.loyalte.app.data.local.entity.LoyaltyTransactionEntity
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.domain.repository.LoyaltyRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class LoyaltyRepositoryImpl @Inject constructor(
    private val transactionDao: LoyaltyTransactionDao
) : LoyaltyRepository {

    override fun getTransactionsByCustomer(customerId: String): Flow<List<LoyaltyTransaction>> =
        transactionDao.getByCustomer(customerId).map { list -> list.map { it.toDomain() } }

    override suspend fun getRecentTransactions(customerId: String, limit: Int): List<LoyaltyTransaction> =
        transactionDao.getRecentByCustomer(customerId, limit).map { it.toDomain() }

    override suspend fun insertTransaction(transaction: LoyaltyTransaction) =
        transactionDao.insert(transaction.toEntity())

    override suspend fun insertTransactions(transactions: List<LoyaltyTransaction>) =
        transactionDao.insertAll(transactions.map { it.toEntity() })
}

// ---- Mapper extensions ----

fun LoyaltyTransactionEntity.toDomain(): LoyaltyTransaction = LoyaltyTransaction(
    id = id,
    customerId = customerId,
    type = TransactionType.fromString(type),
    points = points,
    description = description,
    createdAt = createdAt
)

fun LoyaltyTransaction.toEntity(): LoyaltyTransactionEntity = LoyaltyTransactionEntity(
    id = id,
    customerId = customerId,
    type = type.name,
    points = points,
    description = description,
    createdAt = createdAt
)
