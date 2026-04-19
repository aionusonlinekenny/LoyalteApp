"use client";

import React, {
  createContext,
  useContext,
  useEffect,
  useState,
  useRef,
  ReactNode,
} from "react";
import {
  User,
  signOut as firebaseSignOut,
  onAuthStateChanged,
  RecaptchaVerifier,
  signInWithPhoneNumber,
  ConfirmationResult,
} from "firebase/auth";
import { auth } from "@/lib/firebase";
import { Customer, findCustomerByPhone } from "@/lib/customerService";

interface AuthContextType {
  user: User | null;
  customer: Customer | null;
  authLoading: boolean;
  // Phone OTP flow
  sendOtp: (phoneNumber: string) => Promise<void>;
  confirmOtp: (otp: string) => Promise<void>;
  signOut: () => Promise<void>;
  otpSent: boolean;
  otpError: string | null;
  otpLoading: boolean;
  clearOtpState: () => void;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser]           = useState<User | null>(null);
  const [customer, setCustomer]   = useState<Customer | null>(null);
  const [authLoading, setAuthLoading] = useState(true);
  const [otpSent, setOtpSent]     = useState(false);
  const [otpError, setOtpError]   = useState<string | null>(null);
  const [otpLoading, setOtpLoading] = useState(false);
  const confirmationRef = useRef<ConfirmationResult | null>(null);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      setUser(firebaseUser);
      if (firebaseUser?.phoneNumber) {
        const found = await findCustomerByPhone(firebaseUser.phoneNumber);
        setCustomer(found);
      } else {
        setCustomer(null);
      }
      setAuthLoading(false);
    });
    return unsubscribe;
  }, []);

  const sendOtp = async (phoneNumber: string) => {
    setOtpLoading(true);
    setOtpError(null);
    try {
      // reCAPTCHA verifier attached to the "recaptcha-container" div on the login page
      const verifier = new RecaptchaVerifier(auth, "recaptcha-container", {
        size: "invisible",
      });
      const confirmation = await signInWithPhoneNumber(auth, phoneNumber, verifier);
      confirmationRef.current = confirmation;
      setOtpSent(true);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : "Failed to send OTP";
      setOtpError(
        msg.includes("invalid-phone-number")
          ? "Invalid phone number format. Use +[country][number] e.g. +841234567890"
          : msg.includes("too-many-requests")
          ? "Too many attempts. Please wait a few minutes."
          : "Could not send verification code. Please try again."
      );
    } finally {
      setOtpLoading(false);
    }
  };

  const confirmOtp = async (otp: string) => {
    if (!confirmationRef.current) {
      setOtpError("Session expired. Please request a new code.");
      return;
    }
    setOtpLoading(true);
    setOtpError(null);
    try {
      await confirmationRef.current.confirm(otp);
      // onAuthStateChanged fires next and sets user + customer
    } catch (err: unknown) {
      setOtpError("Invalid verification code. Please try again.");
    } finally {
      setOtpLoading(false);
    }
  };

  const signOut = async () => {
    await firebaseSignOut(auth);
    setCustomer(null);
    setOtpSent(false);
  };

  const clearOtpState = () => {
    setOtpSent(false);
    setOtpError(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user, customer, authLoading,
        sendOtp, confirmOtp, signOut,
        otpSent, otpError, otpLoading,
        clearOtpState,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextType {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside <AuthProvider>");
  return ctx;
}
