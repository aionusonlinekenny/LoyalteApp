"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import LoadingSpinner from "@/components/LoadingSpinner";

export default function LoginPage() {
  const { user, customer, authLoading, sendOtp, confirmOtp, otpSent, otpError, otpLoading, clearOtpState } = useAuth();
  const router = useRouter();

  const [phone, setPhone] = useState("");
  const [otp, setOtp]     = useState("");

  // Redirect authenticated customers to dashboard
  useEffect(() => {
    if (!authLoading && user) {
      router.replace(customer ? "/dashboard" : "/not-found-customer");
    }
  }, [authLoading, user, customer, router]);

  if (authLoading) return <LoadingSpinner fullScreen />;

  const handleSendOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    let normalized = phone.trim().replace(/\s|-/g, "");
    if (!normalized.startsWith("+")) normalized = "+1" + normalized;
    await sendOtp(normalized);
  };

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    await confirmOtp(otp.trim());
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-b from-yellow-50 to-white px-4">
      <div className="w-full max-w-sm">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="text-6xl mb-3">⭐</div>
          <h1 className="text-3xl font-extrabold text-yellow-600">LoyalteApp</h1>
          <p className="text-gray-500 mt-1">Customer Portal</p>
        </div>

        {/* Card */}
        <div className="bg-white rounded-2xl shadow-lg p-8">
          {!otpSent ? (
            <>
              <h2 className="text-xl font-bold mb-1">Sign In</h2>
              <p className="text-sm text-gray-500 mb-6">
                Enter the phone number linked to your loyalty account.
              </p>
              <form onSubmit={handleSendOtp} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number
                  </label>
                  <input
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="+1 (415) 555-1234"
                    required
                    className="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-yellow-400"
                  />
                  <p className="text-xs text-gray-400 mt-1">
                    Include country code, e.g. +1 for US, +84 for Vietnam
                  </p>
                </div>

                {otpError && (
                  <div className="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                    {otpError}
                  </div>
                )}

                <button
                  type="submit"
                  disabled={otpLoading || !phone}
                  className="w-full bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition-colors flex items-center justify-center gap-2"
                >
                  {otpLoading ? <LoadingSpinner size="sm" /> : null}
                  {otpLoading ? "Sending…" : "Send Verification Code"}
                </button>
              </form>
            </>
          ) : (
            <>
              <h2 className="text-xl font-bold mb-1">Verify Code</h2>
              <p className="text-sm text-gray-500 mb-6">
                Enter the 6-digit code sent to <strong>{phone}</strong>
              </p>
              <form onSubmit={handleVerifyOtp} className="space-y-4">
                <input
                  type="text"
                  inputMode="numeric"
                  maxLength={6}
                  value={otp}
                  onChange={(e) => setOtp(e.target.value.replace(/\D/g, ""))}
                  placeholder="123456"
                  required
                  className="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-2xl font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-yellow-400"
                />

                {otpError && (
                  <div className="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                    {otpError}
                  </div>
                )}

                <button
                  type="submit"
                  disabled={otpLoading || otp.length < 6}
                  className="w-full bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition-colors flex items-center justify-center gap-2"
                >
                  {otpLoading ? <LoadingSpinner size="sm" /> : null}
                  {otpLoading ? "Verifying…" : "Verify & Sign In"}
                </button>

                <button
                  type="button"
                  onClick={clearOtpState}
                  className="w-full text-gray-500 text-sm hover:text-gray-700 py-2"
                >
                  ← Change phone number
                </button>
              </form>
            </>
          )}
        </div>

        {/* Invisible reCAPTCHA container required by Firebase Phone Auth */}
        <div id="recaptcha-container" />
      </div>
    </div>
  );
}
