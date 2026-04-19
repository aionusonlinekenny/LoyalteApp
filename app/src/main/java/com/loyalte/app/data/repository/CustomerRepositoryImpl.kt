package com.loyalte.app.data.repository

import com.loyalte.app.data.local.dao.CustomerDao
import com.loyalte.app.data.local.entity.CustomerEntity
import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.CustomerTier
import com.loyalte.app.domain.repository.CustomerRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class CustomerRepositoryImpl @Inject constructor(
    private val customerDao: CustomerDao
) : CustomerRepository {

    override fun getAllCustomers(): Flow<List<Customer>> =
        customerDao.getAllCustomers().map { list -> list.map { it.toDomain() } }

    override suspend fun getCustomerById(id: String): Customer? =
        customerDao.getById(id)?.toDomain()

    override suspend fun getCustomerByPhone(phone: String): Customer? =
        customerDao.getByPhone(phone)?.toDomain()

    override suspend fun getCustomerByQrCode(qrCode: String): Customer? =
        customerDao.getByQrCode(qrCode)?.toDomain()

    override suspend fun getCustomerByMemberId(memberId: String): Customer? =
        customerDao.getByMemberId(memberId)?.toDomain()

    override suspend fun insertCustomer(customer: Customer) =
        customerDao.insert(customer.toEntity())

    override suspend fun updateCustomer(customer: Customer) =
        customerDao.update(customer.toEntity())

    override suspend fun updatePoints(customerId: String, points: Int) =
        customerDao.updatePoints(customerId, points)

    override suspend fun getCustomerCount(): Int =
        customerDao.getCount()
}

// ---- Mapper extensions ----

fun CustomerEntity.toDomain(): Customer = Customer(
    id = id,
    memberId = memberId,
    name = name,
    phone = phone,
    email = email,
    tier = CustomerTier.fromString(tier),
    points = points,
    qrCode = qrCode,
    createdAt = createdAt,
    updatedAt = updatedAt
)

fun Customer.toEntity(): CustomerEntity = CustomerEntity(
    id = id,
    memberId = memberId,
    name = name,
    phone = phone,
    email = email,
    tier = tier.name,
    points = points,
    qrCode = qrCode,
    createdAt = createdAt,
    updatedAt = updatedAt
)
