"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import Navbar from "@/components/Navbar";
import LoadingSpinner from "@/components/LoadingSpinner";
import { Reward, Redemption, subscribeToRewards, subscribeToRedemptions } from "@/lib/customerService";

const CATEGORY_EMOJI: Record<string, string> = {
  FOOD: "🍽", DRINK: "☕", DISCOUNT: "%", GENERAL: "🎁",
};

export default function RewardsPage() {
  const { user, customer, authLoading } = useAuth();
  const router = useRouter();
  const [rewards, setRewards]         = useState<Reward[]>([]);
  const [redemptions, setRedemptions] = useState<Redemption[]>([]);
  const [loading, setLoading]         = useState(true);

  useEffect(() => {
    if (!authLoading && !user) router.replace("/");
  }, [authLoading, user, router]);

  useEffect(() => {
    const unsub1 = subscribeToRewards((r) => { setRewards(r); setLoading(false); });
    return unsub1;
  }, []);

  useEffect(() => {
    if (!customer) return;
    const unsub2 = subscribeToRedemptions(customer.id, setRedemptions);
    return unsub2;
  }, [customer]);

  if (authLoading || !customer) return <LoadingSpinner fullScreen />;

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar />
      <main className="max-w-lg mx-auto px-4 py-6 space-y-5">

        {/* Points header */}
        <div className="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-2xl p-5 text-white shadow">
          <p className="text-yellow-100 text-sm">Available Points</p>
          <p className="text-4xl font-extrabold">{customer.points.toLocaleString()}</p>
          <p className="text-yellow-100 text-sm mt-1">pts — {customer.name}</p>
        </div>

        <h2 className="font-bold text-lg">Available Rewards</h2>

        {loading ? (
          <LoadingSpinner />
        ) : rewards.length === 0 ? (
          <div className="bg-white rounded-2xl shadow p-8 text-center">
            <p className="text-gray-400">No rewards available right now.</p>
          </div>
        ) : (
          <ul className="space-y-3">
            {rewards.map((reward) => {
              const canAfford = customer.points >= reward.pointsRequired;
              return (
                <li
                  key={reward.id}
                  className={`bg-white rounded-xl shadow-sm p-4 flex items-center gap-4 ${
                    !canAfford ? "opacity-60" : ""
                  }`}
                >
                  {/* Category icon */}
                  <div className="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center text-2xl flex-shrink-0">
                    {CATEGORY_EMOJI[reward.category] ?? "🎁"}
                  </div>

                  <div className="flex-1 min-w-0">
                    <p className="font-bold text-sm">{reward.name}</p>
                    <p className="text-xs text-gray-500 truncate">{reward.description}</p>
                    <p className={`text-xs font-semibold mt-1 ${canAfford ? "text-yellow-600" : "text-red-500"}`}>
                      {reward.pointsRequired} pts required
                      {!canAfford && ` · Need ${reward.pointsRequired - customer.points} more`}
                    </p>
                  </div>

                  <div
                    className={`text-xs font-bold px-3 py-1.5 rounded-lg flex-shrink-0 ${
                      canAfford
                        ? "bg-yellow-400 text-white"
                        : "bg-gray-200 text-gray-400"
                    }`}
                  >
                    {canAfford ? "Available!" : "Locked"}
                  </div>
                </li>
              );
            })}
          </ul>
        )}

        {/* Redemption history */}
        {redemptions.length > 0 && (
          <>
            <h2 className="font-bold text-lg pt-2">Redemption History</h2>
            <ul className="space-y-2">
              {redemptions.map((r) => (
                <li key={r.id} className="bg-white rounded-xl shadow-sm p-4 flex justify-between items-center">
                  <div>
                    <p className="font-medium text-sm">{r.rewardName}</p>
                    <p className="text-xs text-gray-400">
                      {new Date(r.redeemedAt).toLocaleDateString("en-US", {
                        month: "short", day: "numeric", year: "numeric",
                      })}
                    </p>
                  </div>
                  <span className="text-red-500 font-bold text-sm">−{r.pointsSpent} pts</span>
                </li>
              ))}
            </ul>
          </>
        )}

        {/* Note: redemptions happen at the store */}
        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
          💡 To redeem a reward, visit our store and show your QR code to the staff.
        </div>
      </main>
    </div>
  );
}
