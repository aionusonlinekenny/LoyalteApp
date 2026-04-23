import {
  collection,
  doc,
  getDocs,
  onSnapshot,
  query,
  where,
  orderBy,
  limit,
  Unsubscribe,
  DocumentData,
} from "firebase/firestore";
import { db } from "./firebase";

// ─── Types ────────────────────────────────────────────────────────────────────

export type CustomerTier = "BRONZE" | "SILVER" | "GOLD" | "PLATINUM";

export interface Customer {
  id: string;
  memberId: string;
  name: string;
  phone: string;
  email?: string;
  tier: CustomerTier;
  points: number;
  qrCode: string;
  createdAt: number;
  updatedAt: number;
}

export interface LoyaltyTransaction {
  id: string;
  customerId: string;
  type: "EARNED" | "REDEEMED" | "ADJUSTMENT";
  points: number;
  description: string;
  createdAt: number;
}

export interface Reward {
  id: string;
  name: string;
  description: string;
  pointsRequired: number;
  isActive: boolean;
  category: "FOOD" | "DRINK" | "DISCOUNT" | "GENERAL";
  createdAt: number;
}

export interface Redemption {
  id: string;
  customerId: string;
  rewardId: string;
  rewardName: string;
  pointsSpent: number;
  redeemedAt: number;
}

// ─── Tier helpers ─────────────────────────────────────────────────────────────

const TIER_CONFIG: Record<CustomerTier, { label: string; color: string; minPoints: number }> = {
  BRONZE:   { label: "Bronze",   color: "#CD7F32", minPoints: 0    },
  SILVER:   { label: "Silver",   color: "#A8A9AD", minPoints: 500  },
  GOLD:     { label: "Gold",     color: "#FFD700", minPoints: 1000 },
  PLATINUM: { label: "Platinum", color: "#8E8E93", minPoints: 2500 },
};

export function tierInfo(tier: CustomerTier) {
  return TIER_CONFIG[tier] ?? TIER_CONFIG.BRONZE;
}

export function tierFromPoints(points: number): CustomerTier {
  if (points >= 2500) return "PLATINUM";
  if (points >= 1000) return "GOLD";
  if (points >= 500)  return "SILVER";
  return "BRONZE";
}

// ─── Service functions ────────────────────────────────────────────────────────

function toCustomer(id: string, data: DocumentData): Customer {
  return {
    id,
    memberId:  data.memberId  ?? "",
    name:      data.name      ?? "",
    phone:     data.phone     ?? "",
    email:     data.email,
    tier:      (data.tier as CustomerTier) ?? "BRONZE",
    points:    data.points    ?? 0,
    qrCode:    data.qrCode    ?? data.memberId ?? id,
    createdAt: data.createdAt ?? 0,
    updatedAt: data.updatedAt ?? 0,
  };
}

/** Find the customer whose phone matches the Firebase Auth phone number. */
export async function findCustomerByPhone(phone: string): Promise<Customer | null> {
  const q = query(
    collection(db, "customers"),
    where("phone", "==", phone),
    limit(1)
  );
  const snap = await getDocs(q);
  if (snap.empty) return null;
  const d = snap.docs[0];
  return toCustomer(d.id, d.data());
}

/** Subscribe to real-time updates for a single customer document. */
export function subscribeToCustomer(
  customerId: string,
  onChange: (customer: Customer | null) => void
): Unsubscribe {
  return onSnapshot(doc(db, "customers", customerId), (snap) => {
    onChange(snap.exists() ? toCustomer(snap.id, snap.data()) : null);
  });
}

/** Subscribe to real-time transaction history for a customer. */
export function subscribeToTransactions(
  customerId: string,
  onChange: (txs: LoyaltyTransaction[]) => void
): Unsubscribe {
  const q = query(
    collection(db, "customers", customerId, "transactions"),
    orderBy("createdAt", "desc")
  );
  return onSnapshot(q, (snap) => {
    onChange(
      snap.docs.map((d) => ({
        id: d.id,
        customerId,
        type:        d.data().type        ?? "EARNED",
        points:      d.data().points      ?? 0,
        description: d.data().description ?? "",
        createdAt:   d.data().createdAt   ?? 0,
      }))
    );
  });
}

/** Subscribe to real-time active rewards list. */
export function subscribeToRewards(
  onChange: (rewards: Reward[]) => void
): Unsubscribe {
  const q = query(
    collection(db, "rewards"),
    where("isActive", "==", true),
    orderBy("pointsRequired", "asc")
  );
  return onSnapshot(q, (snap) => {
    onChange(
      snap.docs.map((d) => ({
        id:             d.id,
        name:           d.data().name           ?? "",
        description:    d.data().description    ?? "",
        pointsRequired: d.data().pointsRequired ?? 0,
        isActive:       d.data().isActive       ?? true,
        category:       d.data().category       ?? "GENERAL",
        createdAt:      d.data().createdAt      ?? 0,
      }))
    );
  });
}

/** Subscribe to real-time redemption history for a customer. */
export function subscribeToRedemptions(
  customerId: string,
  onChange: (redemptions: Redemption[]) => void
): Unsubscribe {
  const q = query(
    collection(db, "customers", customerId, "redemptions"),
    orderBy("redeemedAt", "desc")
  );
  return onSnapshot(q, (snap) => {
    onChange(
      snap.docs.map((d) => ({
        id:          d.id,
        customerId,
        rewardId:    d.data().rewardId    ?? "",
        rewardName:  d.data().rewardName  ?? "",
        pointsSpent: d.data().pointsSpent ?? 0,
        redeemedAt:  d.data().redeemedAt  ?? 0,
      }))
    );
  });
}
