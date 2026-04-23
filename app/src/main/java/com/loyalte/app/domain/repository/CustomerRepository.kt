package com.loyalte.app.domain.repository

import com.loyalte.app.domain.model.Customer
import kotlinx.coroutines.flow.Flow

interface CustomerRepository {
    fun getAllCustomers(): Flow<List<Customer>>
    suspend fun getCustomerById(id: String): Customer?
    suspend fun getCustomerByPhone(phone: String): Customer?
    suspend fun getCustomerByQrCode(qrCode: String): Customer?
    suspend fun getCustomerByMemberId(memberId: String): Customer?
    suspend fun insertCustomer(customer: Customer)
    suspend fun updateCustomer(customer: Customer)
    suspend fun updatePoints(customerId: String, points: Int)
    suspend fun getCustomerCount(): Int
    suspend fun deleteCustomer(id: String): Boolean
}
