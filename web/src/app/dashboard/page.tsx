"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { QRCodeSVG } from "qrcode.react";
import { useAuth } from "@/context/AuthContext";
import Navbar from "@/components/Navbar";
import LoadingSpinner from "@/components/LoadingSpinner";
import TierBadge from "@/components/TierBadge";
import {
  LoyaltyTransaction,
  subscribeToTransactions,
} from "@/lib/customerService";

export default function DashboardPage() {
  const { user, customer, authLoading } = useAuth();
  const router = useRouter();
  const [recentTxs, setRecentTxs] = useState<LoyaltyTransaction[]>([]);

  useEffect(() => {
    if (!authLoading && !user) router.replace("/");
  }, [authLoading, user, router]);

  useEffect(() => {
    if (!customer) return;
    const unsub = subscribeToTransactions(customer.id, (txs) =>
      setRecentTxs(txs.slice(0, 5))
    );
    return unsub;
  }, [customer]);

  if (authLoading || !customer) return <LoadingSpinner fullScreen />;

  const nextTierPoints = () => {
    if (customer.points < 500)  return { label: "Silver", need: 500  - customer.points };
    if (customer.points < 1000) return { label: "Gold",   need: 1000 - customer.points };
    if (customer.points < 2500) return { label: "Platinum", need: 2500 - customer.points };
    return null;
  };
  const next = nextTierPoints();

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <main className="max-w-lg mx-auto px-4 py-6 space-y-4">

        {/* Welcome */}
        <div>
          <p className="text-gray-500 text-sm">Welcome back,</p>
          <h1 className="text-2xl font-extrabold">{customer.name}</h1>
        </div>

        {/* Points card */}
        <div className="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl p-6 text-white shadow-lg">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-yellow-100 text-sm font-medium">Your Points</p>
              <p className="text-5xl font-extrabold mt-1">{customer.points.toLocaleString()}</p>
              <p className="text-yellow-100 text-sm mt-1">points</p>
            </div>
            <TierBadge tier={customer.tier} />
          </div>

          {next && (
            <div className="mt-4">
              <div className="flex justify-between text-sm text-yellow-100 mb-1">
                <span>Progress to {next.label}</span>
                <span>{next.need} pts needed</span>
              </div>
              <div className="bg-yellow-300/40 rounded-full h-2">
                <div
                  className="bg-white rounded-full h-2 transition-all"
                  style={{
                    width: `${Math.min(
                      100,
                      (customer.points / (customer.points + next.need)) * 100
                    )}%`,
                  }}
                />
              </div>
            </div>
          )}
        </div>

        {/* QR Code — customer shows this to staff */}
        <div className="bg-white rounded-2xl shadow p-6 flex flex-col items-center gap-3">
          <h2 className="font-bold text-lg">Your Member QR Code</h2>
          <p className="text-gray-500 text-sm text-center">
            Show this to our staff to collect or redeem points
          </p>
          <div className="border-4 border-yellow-400 rounded-xl p-3 bg-white">
            <QRCodeSVG
              value={customer.qrCode}
              size={180}
              level="M"
              includeMargin={false}
            />
          </div>
          <p className="text-sm font-mono font-semibold text-gray-600 bg-gray-100 px-4 py-2 rounded-lg">
            {customer.memberId}
          </p>
        </div>

        {/* Recent transactions */}
        <div className="bg-white rounded-2xl shadow p-5">
          <div className="flex justify-between items-center mb-4">
            <h2 className="font-bold text-lg">Recent Activity</h2>
            <button
              onClick={() => router.push("/history")}
              className="text-yellow-600 text-sm font-medium hover:underline"
            >
              See all
            </button>
          </div>
          {recentTxs.length === 0 ? (
            <p className="text-gray-400 text-sm">No transactions yet.</p>
          ) : (
            <ul className="divide-y divide-gray-100">
              {recentTxs.map((tx) => (
                <li key={tx.id} className="py-3 flex justify-between items-center">
                  <div>
                    <p className="font-medium text-sm">{tx.description}</p>
                    <p className="text-xs text-gray-400">
                      {new Date(tx.createdAt).toLocaleDateString("en-US", {
                        month: "short", day: "numeric", year: "numeric",
                      })}
                    </p>
                  </div>
                  <span
                    className={`font-bold text-sm ${
                      tx.type === "EARNED" ? "text-green-600" : "text-red-500"
                    }`}
                  >
                    {tx.type === "EARNED" ? "+" : "−"}{tx.points} pts
                  </span>
                </li>
              ))}
            </ul>
          )}
        </div>
      </main>
    </div>
  );
}
