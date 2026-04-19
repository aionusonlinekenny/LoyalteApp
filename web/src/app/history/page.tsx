"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import Navbar from "@/components/Navbar";
import LoadingSpinner from "@/components/LoadingSpinner";
import { LoyaltyTransaction, subscribeToTransactions } from "@/lib/customerService";

export default function HistoryPage() {
  const { user, customer, authLoading } = useAuth();
  const router = useRouter();
  const [transactions, setTransactions] = useState<LoyaltyTransaction[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!authLoading && !user) router.replace("/");
  }, [authLoading, user, router]);

  useEffect(() => {
    if (!customer) return;
    const unsub = subscribeToTransactions(customer.id, (txs) => {
      setTransactions(txs);
      setLoading(false);
    });
    return unsub;
  }, [customer]);

  if (authLoading || !customer) return <LoadingSpinner fullScreen />;

  const typeLabel = (type: string) => {
    if (type === "EARNED")     return { text: "+ Earned",   cls: "bg-green-100 text-green-700"  };
    if (type === "REDEEMED")   return { text: "− Redeemed", cls: "bg-red-100 text-red-700"      };
    return                            { text: "± Adjusted", cls: "bg-blue-100 text-blue-700"    };
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <main className="max-w-lg mx-auto px-4 py-6">
        <h1 className="text-2xl font-extrabold mb-1">Transaction History</h1>
        <p className="text-gray-500 text-sm mb-5">All your points activity</p>

        {loading ? (
          <LoadingSpinner />
        ) : transactions.length === 0 ? (
          <div className="bg-white rounded-2xl shadow p-8 text-center">
            <div className="text-4xl mb-3">📋</div>
            <p className="text-gray-500">No transactions yet.</p>
          </div>
        ) : (
          <ul className="space-y-3">
            {transactions.map((tx) => {
              const badge = typeLabel(tx.type);
              return (
                <li
                  key={tx.id}
                  className="bg-white rounded-xl shadow-sm p-4 flex justify-between items-start"
                >
                  <div className="flex-1">
                    <p className="font-semibold text-sm">{tx.description}</p>
                    <p className="text-xs text-gray-400 mt-1">
                      {new Date(tx.createdAt).toLocaleString("en-US", {
                        month: "short", day: "numeric", year: "numeric",
                        hour: "2-digit", minute: "2-digit",
                      })}
                    </p>
                    <span className={`inline-block mt-2 text-xs font-semibold px-2 py-0.5 rounded-full ${badge.cls}`}>
                      {badge.text}
                    </span>
                  </div>
                  <span
                    className={`font-extrabold text-lg ml-4 ${
                      tx.type === "EARNED" ? "text-green-600" : "text-red-500"
                    }`}
                  >
                    {tx.type === "EARNED" ? "+" : "−"}{tx.points}
                  </span>
                </li>
              );
            })}
          </ul>
        )}
      </main>
    </div>
  );
}
