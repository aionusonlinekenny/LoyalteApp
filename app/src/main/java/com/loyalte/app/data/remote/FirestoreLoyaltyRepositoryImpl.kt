package com.loyalte.app.data.remote

import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.Query
import com.google.firebase.firestore.snapshots
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.domain.repository.LoyaltyRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.tasks.await
import javax.inject.Inject

/**
 * Transactions are stored as a subcollection under each customer document:
 *   customers/{customerId}/transactions/{transactionId}
 *
 * This structure keeps per-customer data co-located and makes
 * Firestore security rules straightforward.
 */
class FirestoreLoyaltyRepositoryImpl @Inject constructor(
    private val db: FirebaseFirestore
) : LoyaltyRepository {

    private fun txCol(customerId: String) =
        db.collection(FirestoreCustomerRepositoryImpl.COLLECTION)
            .document(customerId)
            .collection(COLLECTION)

    override fun getTransactionsByCustomer(customerId: String): Flow<List<LoyaltyTransaction>> =
        txCol(customerId).orderBy("createdAt", Query.Direction.DESCENDING)
            .snapshots().map { snap ->
                snap.documents.mapNotNull { it.toTransaction() }
            }

    override suspend fun getRecentTransactions(
        customerId: String,
        limit: Int
    ): List<LoyaltyTransaction> =
        txCol(customerId)
            .orderBy("createdAt", Query.Direction.DESCENDING)
            .limit(limit.toLong())
            .get().await()
            .documents.mapNotNull { it.toTransaction() }

    override suspend fun insertTransaction(transaction: LoyaltyTransaction) {
        txCol(transaction.customerId).document(transaction.id)
            .set(transaction.toMap()).await()
    }

    override suspend fun insertTransactions(transactions: List<LoyaltyTransaction>) {
        val batch = db.batch()
        transactions.forEach { tx ->
            val ref = txCol(tx.customerId).document(tx.id)
            batch.set(ref, tx.toMap())
        }
        batch.commit().await()
    }

    companion object {
        const val COLLECTION = "transactions"
    }
}

// ─── Mappers ────────────────────────────────────────────────────────────────

private fun com.google.firebase.firestore.DocumentSnapshot.toTransaction(): LoyaltyTransaction? {
    if (!exists()) return null
    return try {
        LoyaltyTransaction(
            id = id,
            customerId = getString("customerId") ?: return null,
            type = TransactionType.fromString(getString("type") ?: "EARNED"),
            points = getLong("points")?.toInt() ?: 0,
            description = getString("description") ?: "",
            createdAt = getLong("createdAt") ?: System.currentTimeMillis()
        )
    } catch (e: Exception) { null }
}

private fun LoyaltyTransaction.toMap(): Map<String, Any?> = mapOf(
    "id" to id,
    "customerId" to customerId,
    "type" to type.name,
    "points" to points,
    "description" to description,
    "createdAt" to createdAt
)
