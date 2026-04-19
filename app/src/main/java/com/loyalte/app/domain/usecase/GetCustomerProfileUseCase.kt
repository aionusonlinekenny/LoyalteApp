package com.loyalte.app.domain.usecase

import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.LoyaltyRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

data class CustomerProfile(
    val customer: Customer,
    val recentTransactions: Flow<List<LoyaltyTransaction>>
)

class GetCustomerProfileUseCase @Inject constructor(
    private val customerRepository: CustomerRepository,
    private val loyaltyRepository: LoyaltyRepository
) {
    suspend operator fun invoke(customerId: String): CustomerProfile? {
        val customer = customerRepository.getCustomerById(customerId) ?: return null
        return CustomerProfile(
            customer = customer,
            recentTransactions = loyaltyRepository.getTransactionsByCustomer(customerId)
        )
    }
}
