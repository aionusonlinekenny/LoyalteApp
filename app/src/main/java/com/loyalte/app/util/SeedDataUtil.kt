package com.loyalte.app.util

import com.loyalte.app.domain.model.Customer
import com.loyalte.app.domain.model.CustomerTier
import com.loyalte.app.domain.model.LoyaltyTransaction
import com.loyalte.app.domain.model.Reward
import com.loyalte.app.domain.model.RewardCategory
import com.loyalte.app.domain.model.TransactionType
import com.loyalte.app.domain.repository.CustomerRepository
import com.loyalte.app.domain.repository.LoyaltyRepository
import com.loyalte.app.domain.repository.RewardRepository
import java.util.UUID
import java.util.concurrent.TimeUnit
import javax.inject.Inject

/**
 * Populates the database with demo data on first launch.
 * Safe to call multiple times — checks customer count first.
 *
 * To add more customers: extend the [customers] list below.
 * To change rewards: edit the [rewards] list.
 */
class SeedDataUtil @Inject constructor(
    private val customerRepository: CustomerRepository,
    private val loyaltyRepository: LoyaltyRepository,
    private val rewardRepository: RewardRepository
) {

    suspend fun seedIfEmpty() {
        if (customerRepository.getCustomerCount() > 0) return
        seedCustomers()
        seedRewards()
    }

    private suspend fun seedCustomers() {
        val now = System.currentTimeMillis()
        val day = TimeUnit.DAYS.toMillis(1)

        data class SeedCustomer(
            val seq: Int,
            val name: String,
            val phone: String,
            val points: Int,
            val email: String? = null
        )

        val seeds = listOf(
            SeedCustomer(1,  "John Smith",       "+14155551001", 450),
            SeedCustomer(2,  "Sarah Johnson",    "+14155551002", 750),
            SeedCustomer(3,  "Michael Chen",     "+14155551003", 1200),
            SeedCustomer(4,  "Emily Davis",      "+14155551004", 3000, "emily@example.com"),
            SeedCustomer(5,  "Robert Wilson",    "+14155551005", 850),
            SeedCustomer(6,  "Jennifer Martinez","+14155551006", 125),
            SeedCustomer(7,  "David Anderson",   "+14155551007", 1750, "david@example.com"),
            SeedCustomer(8,  "Lisa Thompson",    "+14155551008", 320),
            SeedCustomer(9,  "James Garcia",     "+14155551009", 2100),
            SeedCustomer(10, "Maria Rodriguez",  "+14155551010", 4200, "maria@example.com"),
            SeedCustomer(11, "William Brown",    "+14155551011", 680),
            SeedCustomer(12, "Jessica Taylor",   "+14155551012", 95)
        )

        seeds.forEach { seed ->
            val memberId = "LYL-%06d".format(seed.seq)
            val id = UUID.randomUUID().toString()
            val tier = CustomerTier.fromPoints(seed.points)
            val createdAt = now - (seed.seq * 7 * day)

            customerRepository.insertCustomer(
                Customer(
                    id = id,
                    memberId = memberId,
                    name = seed.name,
                    phone = seed.phone,
                    email = seed.email,
                    tier = tier,
                    points = seed.points,
                    // QR encodes the memberId — NOT the phone number
                    qrCode = memberId,
                    createdAt = createdAt,
                    updatedAt = createdAt
                )
            )

            seedTransactionsForCustomer(id, seed.points, createdAt, day)
        }
    }

    private suspend fun seedTransactionsForCustomer(
        customerId: String,
        currentPoints: Int,
        memberSince: Long,
        day: Long
    ) {
        val transactions = mutableListOf<LoyaltyTransaction>()
        var running = 0

        // Simulate 3-6 earn transactions over past weeks
        val earnAmounts = generateEarnHistory(currentPoints)
        earnAmounts.forEachIndexed { i, pts ->
            running += pts
            transactions.add(
                LoyaltyTransaction(
                    id = UUID.randomUUID().toString(),
                    customerId = customerId,
                    type = TransactionType.EARNED,
                    points = pts,
                    description = "Purchase reward",
                    createdAt = memberSince + (i * 5 * day)
                )
            )
        }

        // Add a redemption if the customer has enough history
        if (currentPoints >= 200 && earnAmounts.size >= 2) {
            transactions.add(
                LoyaltyTransaction(
                    id = UUID.randomUUID().toString(),
                    customerId = customerId,
                    type = TransactionType.REDEEMED,
                    points = 100,
                    description = "Redeemed: Free Coffee",
                    createdAt = memberSince + (3 * day)
                )
            )
        }

        loyaltyRepository.insertTransactions(transactions)
    }

    private fun generateEarnHistory(targetPoints: Int): List<Int> {
        val amounts = mutableListOf<Int>()
        var remaining = targetPoints + 100  // slightly over to account for possible redeem
        val increments = listOf(50, 75, 100, 150, 200, 250)
        while (remaining > 0) {
            val amt = increments.random().coerceAtMost(remaining)
            amounts.add(amt)
            remaining -= amt
        }
        return amounts.take(6)
    }

    private suspend fun seedRewards() {
        val now = System.currentTimeMillis()
        val rewards = listOf(
            Reward(UUID.randomUUID().toString(), "Free Coffee",          "Any regular-size coffee or hot drink",       100,  true, RewardCategory.DRINK,    now),
            Reward(UUID.randomUUID().toString(), "10% Discount",         "10% off your entire order",                  150,  true, RewardCategory.DISCOUNT, now),
            Reward(UUID.randomUUID().toString(), "Free Appetizer",       "Choose any appetizer from our menu",         250,  true, RewardCategory.FOOD,     now),
            Reward(UUID.randomUUID().toString(), "Free Drink Upgrade",   "Upgrade any drink to large, any flavour",    200,  true, RewardCategory.DRINK,    now),
            Reward(UUID.randomUUID().toString(), "Free Dessert",         "Any dessert item from our dessert menu",     300,  true, RewardCategory.FOOD,     now),
            Reward(UUID.randomUUID().toString(), "20% Discount",         "20% off your entire bill",                   400,  true, RewardCategory.DISCOUNT, now),
            Reward(UUID.randomUUID().toString(), "Free Main Course",     "One complimentary main course of your choice", 500, true, RewardCategory.FOOD,   now),
            Reward(UUID.randomUUID().toString(), "Birthday Cake Slice",  "A free slice of our signature cake",         1000, true, RewardCategory.FOOD,    now)
        )
        rewardRepository.insertRewards(rewards)
    }
}
