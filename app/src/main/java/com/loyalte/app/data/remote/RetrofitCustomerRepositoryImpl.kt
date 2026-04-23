package com.loyalte.app.data.remote

import com.loyalte.app.data.remote.api.LoyalteApiService
import com.loyalte.app.data.remote.api.dto.CreateCustomerRequest
import com.loyalte.app.data.remote.api.dto.CustomerDto
import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.CustomerTier
import com.loyalte.app.domain.repository.CustomerRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject

class RetrofitCustomerRepositoryImpl @Inject constructor(
    private val api: LoyalteApiService
) : CustomerRepository {

    override fun getAllCustomers(): Flow<List<Customer>> = flow {
        val response = api.getAllCustomers()
        emit(response.body()?.customers?.map { it.toDomain() } ?: emptyList())
    }

    override suspend fun getCustomerById(id: String): Customer? =
        api.getCustomerById(id).body()?.customer?.toDomain()

    override suspend fun getCustomerByPhone(phone: String): Customer? =
        api.getCustomerByPhone(phone).body()?.customer?.toDomain()

    override suspend fun getCustomerByQrCode(qrCode: String): Customer? =
        api.getCustomerByQr(qrCode).body()?.customer?.toDomain()

    override suspend fun getCustomerByMemberId(memberId: String): Customer? =
        api.getCustomerByQr(memberId).body()?.customer?.toDomain()

    override suspend fun insertCustomer(customer: Customer) {
        api.createCustomer(
            CreateCustomerRequest(
                name  = customer.name,
                phone = customer.phone,
                email = customer.email
            )
        )
    }

    override suspend fun updateCustomer(customer: Customer) {
        // Full customer updates are not exposed via the current API;
        // use updatePoints for point changes.
    }

    override suspend fun updatePoints(customerId: String, newPoints: Int) {
        // Points updates go through the transactions or points endpoint;
        // direct point-setting is handled by the earn/redeem flows.
    }

    override suspend fun getCustomerCount(): Int =
        api.getAllCustomers().body()?.customers?.size ?: 0

    override suspend fun deleteCustomer(id: String): Boolean =
        api.deleteCustomer(id).body()?.success == true
}

private fun CustomerDto.toDomain() = Customer(
    id        = id,
    memberId  = memberId,
    name      = name,
    phone     = phone,
    email     = email,
    tier      = CustomerTier.fromString(tier),
    points    = points,
    qrCode    = qrCode,
    createdAt = createdAt,
    updatedAt = updatedAt
)
