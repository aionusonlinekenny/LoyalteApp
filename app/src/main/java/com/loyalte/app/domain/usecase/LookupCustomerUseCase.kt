package com.loyalte.app.domain.usecase

import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.util.PhoneNumberValidator
import javax.inject.Inject

class LookupCustomerUseCase @Inject constructor(
    private val customerRepository: CustomerRepository
) {

    sealed class Result {
        data class Found(val customer: Customer) : Result()
        object NotFound : Result()
        data class Error(val message: String) : Result()
    }

    suspend fun byPhone(rawPhone: String): Result {
        val normalized = PhoneNumberValidator.normalize(rawPhone)
        if (!PhoneNumberValidator.isValid(normalized)) {
            return Result.Error("Please enter a valid phone number")
        }
        val customer = customerRepository.getCustomerByPhone(normalized)
        return if (customer != null) Result.Found(customer) else Result.NotFound
    }

    suspend fun byQrCode(rawValue: String): Result {
        if (rawValue.isBlank()) return Result.Error("Empty QR code")
        // Try QR code field first, then fall back to memberId
        val customer = customerRepository.getCustomerByQrCode(rawValue)
            ?: customerRepository.getCustomerByMemberId(rawValue)
        return if (customer != null) Result.Found(customer) else Result.NotFound
    }
}
