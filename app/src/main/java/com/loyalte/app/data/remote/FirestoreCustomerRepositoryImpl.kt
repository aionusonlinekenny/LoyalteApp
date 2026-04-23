package com.loyalte.app.data.remote

import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.snapshots
import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.CustomerTier
import com.loyalte.app.domain.repository.CustomerRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.tasks.await
import javax.inject.Inject

class FirestoreCustomerRepositoryImpl @Inject constructor(
    private val db: FirebaseFirestore
) : CustomerRepository {

    private val col get() = db.collection(COLLECTION)

    override fun getAllCustomers(): Flow<List<Customer>> =
        col.orderBy("name").snapshots().map { snap ->
            snap.documents.mapNotNull { it.toCustomer() }
        }

    override suspend fun getCustomerById(id: String): Customer? =
        col.document(id).get().await().toCustomer()

    override suspend fun getCustomerByPhone(phone: String): Customer? =
        col.whereEqualTo("phone", phone).limit(1).get().await()
            .documents.firstOrNull()?.toCustomer()

    override suspend fun getCustomerByQrCode(qrCode: String): Customer? =
        col.whereEqualTo("qrCode", qrCode).limit(1).get().await()
            .documents.firstOrNull()?.toCustomer()

    override suspend fun getCustomerByMemberId(memberId: String): Customer? =
        col.whereEqualTo("memberId", memberId).limit(1).get().await()
            .documents.firstOrNull()?.toCustomer()

    override suspend fun insertCustomer(customer: Customer) {
        col.document(customer.id).set(customer.toMap()).await()
    }

    override suspend fun updateCustomer(customer: Customer) {
        col.document(customer.id).set(customer.toMap()).await()
    }

    override suspend fun updatePoints(customerId: String, points: Int) {
        col.document(customerId).update(
            mapOf(
                "points" to points,
                "tier" to CustomerTier.fromPoints(points).name,
                "updatedAt" to System.currentTimeMillis()
            )
        ).await()
    }

    override suspend fun getCustomerCount(): Int =
        col.count().get(com.google.firebase.firestore.AggregateSource.SERVER).await().count.toInt()

    companion object {
        const val COLLECTION = "customers"
    }
}

// ─── Mappers ────────────────────────────────────────────────────────────────

private fun com.google.firebase.firestore.DocumentSnapshot.toCustomer(): Customer? {
    if (!exists()) return null
    return try {
        Customer(
            id = id,
            memberId = getString("memberId") ?: return null,
            name = getString("name") ?: return null,
            phone = getString("phone") ?: return null,
            email = getString("email"),
            tier = CustomerTier.fromString(getString("tier") ?: "BRONZE"),
            points = getLong("points")?.toInt() ?: 0,
            qrCode = getString("qrCode") ?: getString("memberId") ?: id,
            createdAt = getLong("createdAt") ?: System.currentTimeMillis(),
            updatedAt = getLong("updatedAt") ?: System.currentTimeMillis()
        )
    } catch (e: Exception) { null }
}

private fun Customer.toMap(): Map<String, Any?> = mapOf(
    "id" to id,
    "memberId" to memberId,
    "name" to name,
    "phone" to phone,
    "email" to email,
    "tier" to tier.name,
    "points" to points,
    "qrCode" to qrCode,
    "createdAt" to createdAt,
    "updatedAt" to updatedAt
)
