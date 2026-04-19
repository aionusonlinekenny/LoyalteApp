package com.loyalte.app.data.local.entity

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

/**
 * Room entity for customer records.
 * - [id] is an internal UUID, never exposed in QR codes.
 * - [memberId] is the human-readable ID embedded in QR codes (e.g. "LYL-000001").
 * - [phone] is stored in E.164 format (+1XXXXXXXXXX) and is UNIQUE to prevent duplicates.
 * - [qrCode] stores the value encoded in the physical QR — defaults to memberId.
 */
@Entity(
    tableName = "customers",
    indices = [
        Index(value = ["phone"], unique = true),
        Index(value = ["memberId"], unique = true),
        Index(value = ["qrCode"], unique = true)
    ]
)
data class CustomerEntity(
    @PrimaryKey val id: String,
    val memberId: String,
    val name: String,
    val phone: String,
    val email: String? = null,
    val tier: String = "BRONZE",
    val points: Int = 0,
    val qrCode: String,
    val createdAt: Long = System.currentTimeMillis(),
    val updatedAt: Long = System.currentTimeMillis()
)
