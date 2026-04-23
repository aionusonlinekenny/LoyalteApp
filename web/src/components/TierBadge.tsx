import { CustomerTier, tierInfo } from "@/lib/customerService";

interface Props {
  tier: CustomerTier;
  size?: "sm" | "md";
}

const TIER_EMOJI: Record<CustomerTier, string> = {
  BRONZE:   "🥉",
  SILVER:   "🥈",
  GOLD:     "🥇",
  PLATINUM: "💎",
};

export default function TierBadge({ tier, size = "md" }: Props) {
  const info = tierInfo(tier);
  return (
    <span
      className={`inline-flex items-center gap-1 font-bold rounded-full px-3 py-1 bg-white/20 text-white ${
        size === "sm" ? "text-xs" : "text-sm"
      }`}
    >
      {TIER_EMOJI[tier]} {info.label}
    </span>
  );
}
