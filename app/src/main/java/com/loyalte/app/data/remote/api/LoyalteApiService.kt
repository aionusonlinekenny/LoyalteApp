package com.loyalte.app.data.remote.api

import com.loyalte.app.data.remote.api.dto.*
import retrofit2.Response
import retrofit2.http.*

interface LoyalteApiService {

    // ── Auth ──────────────────────────────────────────────────────────────────

    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @POST("auth/logout")
    suspend fun logout(): Response<Unit>

    // ── Customers ─────────────────────────────────────────────────────────────

    @GET("customers")
    suspend fun getAllCustomers(): Response<CustomersResponse>

    @GET("customers")
    suspend fun getCustomerByPhone(
        @Query("phone") phone: String
    ): Response<CustomerResponse>

    @GET("customers")
    suspend fun getCustomerByQr(
        @Query("qr") qr: String
    ): Response<CustomerResponse>

    @GET("customers/{id}")
    suspend fun getCustomerById(
        @Path("id") id: String
    ): Response<CustomerResponse>

    @POST("customers")
    suspend fun createCustomer(
        @Body body: CreateCustomerRequest
    ): Response<CustomerResponse>

    @PUT("customers/{id}/points")
    suspend fun updatePoints(
        @Path("id") id: String,
        @Body body: AddPointsRequest
    ): Response<CustomerResponse>

    // ── Transactions ──────────────────────────────────────────────────────────

    @GET("transactions")
    suspend fun getTransactions(
        @Query("customer_id") customerId: String,
        @Query("limit") limit: Int = 50,
        @Query("offset") offset: Int = 0
    ): Response<TransactionsResponse>

    @POST("transactions")
    suspend fun earnPoints(
        @Body body: EarnPointsRequest
    ): Response<TransactionsResponse>

    // ── Rewards ───────────────────────────────────────────────────────────────

    @GET("rewards")
    suspend fun getActiveRewards(): Response<RewardsResponse>

    // ── Redemptions ───────────────────────────────────────────────────────────

    @GET("redemptions")
    suspend fun getRedemptions(
        @Query("customer_id") customerId: String
    ): Response<RedemptionsResponse>

    @POST("redemptions")
    suspend fun redeemReward(
        @Body body: RedeemRequest
    ): Response<RedeemResponse>
}
