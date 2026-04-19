package com.loyalte.app.util

/**
 * Lightweight phone number validator.
 *
 * Strategy:
 *  - Strip all non-digit characters, then check for a valid length.
 *  - Store numbers in E.164 format (+[country code][subscriber number]).
 *  - For a production app replace with libphonenumber for full international support.
 *
 * QR code best practice note:
 *  Do NOT embed the phone number in the QR code. Use the memberId instead.
 *  This prevents exposing PII if a QR code is photographed or shared.
 */
object PhoneNumberValidator {

    /**
     * Strips all non-digit characters then prepends +1 for 10-digit North American numbers,
     * or preserves an existing country code for 11-digit strings starting with 1.
     */
    fun normalize(input: String): String {
        val digits = input.filter { it.isDigit() }
        return when {
            digits.length == 10 -> "+1$digits"
            digits.length == 11 && digits.startsWith("1") -> "+$digits"
            // For other regions, preserve as-is with + prefix
            digits.length in 7..15 -> "+$digits"
            else -> input.trim()
        }
    }

    /** Accepts E.164 format: + followed by 7–15 digits. */
    fun isValid(normalized: String): Boolean =
        normalized.matches(Regex("^\\+[1-9]\\d{6,14}$"))

    /** Format for display: +14155551234 → (415) 555-1234 */
    fun formatForDisplay(phone: String): String {
        val digits = phone.filter { it.isDigit() }
        return if (digits.length == 11 && digits.startsWith("1")) {
            val local = digits.substring(1)
            "(${local.substring(0, 3)}) ${local.substring(3, 6)}-${local.substring(6)}"
        } else {
            phone
        }
    }
}
