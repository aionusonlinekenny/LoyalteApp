import React, { useState, useCallback } from 'react';
import { LogOut, Gift, Star, ChevronRight, CheckCircle, AlertCircle, Loader, Lock } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';

const API = '/loyalteapp/backend/api';

interface CustomerInfo {
  id: string;
  member_id: string;
  name: string;
  tier: string;
  points: number;
  email?: string;
}

interface Reward {
  id: string;
  name: string;
  description?: string;
  points_required: number;
  image_url?: string;
  emoji?: string;
}

interface PortalProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const TIER_COLORS: Record<string, { bg: string; text: string; badge: string }> = {
  BRONZE:   { bg: 'from-amber-700 to-amber-500',   text: 'text-amber-700',   badge: 'bg-amber-100 text-amber-800 border-amber-300' },
  SILVER:   { bg: 'from-slate-500 to-slate-400',   text: 'text-slate-600',   badge: 'bg-slate-100 text-slate-700 border-slate-300' },
  GOLD:     { bg: 'from-yellow-500 to-yellow-300', text: 'text-yellow-700',  badge: 'bg-yellow-50 text-yellow-800 border-yellow-300' },
  PLATINUM: { bg: 'from-purple-500 to-purple-300', text: 'text-purple-700',  badge: 'bg-purple-50 text-purple-800 border-purple-300' },
};

const TIER_THRESHOLDS: Record<string, { min: number; max: number; next?: string }> = {
  BRONZE:   { min: 0,    max: 500,  next: 'SILVER' },
  SILVER:   { min: 500,  max: 1000, next: 'GOLD' },
  GOLD:     { min: 1000, max: 2500, next: 'PLATINUM' },
  PLATINUM: { min: 2500, max: 2500 },
};

function tierProgress(tier: string, points: number): number {
  const t = TIER_THRESHOLDS[tier];
  if (!t || !t.next) return 100;
  const range = t.max - t.min;
  if (range <= 0) return 100;
  return Math.min(100, Math.round(((points - t.min) / range) * 100));
}

function tierNextLabel(tier: string, points: number): string {
  const t = TIER_THRESHOLDS[tier];
  if (!t || !t.next) return 'Max tier reached';
  const remaining = t.max - points;
  if (remaining <= 0) return `${t.next} unlocked!`;
  return `${remaining} pts to ${t.next}`;
}

// ── PIN Input ─────────────────────────────────────────────────────────────────
function PinInput({
  value,
  onChange,
  label,
  autoFocus,
}: {
  value: string;
  onChange: (v: string) => void;
  label: string;
  autoFocus?: boolean;
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      <input
        type="password"
        inputMode="numeric"
        pattern="\d{4}"
        maxLength={4}
        autoFocus={autoFocus}
        value={value}
        onChange={(e) => onChange(e.target.value.replace(/\D/g, '').slice(0, 4))}
        placeholder="••••"
        className="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-2xl tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-red-400"
      />
    </div>
  );
}

// ── Login Form ────────────────────────────────────────────────────────────────
function LoginForm({
  onLogin,
}: {
  onLogin: (customer: CustomerInfo, rewards: Reward[], phone: string, pin: string) => void;
}) {
  const [phone, setPhone]     = useState('');
  const [pin, setPin]         = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError]     = useState('');

  const handleSubmit = useCallback(
    async (e: React.FormEvent) => {
      e.preventDefault();
      setError('');
      const digits = phone.replace(/\D/g, '');
      if (digits.length < 10) { setError('Enter a valid 10-digit phone number'); return; }
      if (pin.length !== 4)   { setError('PIN must be exactly 4 digits'); return; }
      setLoading(true);
      try {
        const res = await fetch(`${API}/customers/portal_login`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ phone: digits, pin }),
        });
        const data = await res.json();
        if (data.success) {
          onLogin(data.customer, data.rewards ?? [], digits, pin);
        } else {
          setError(data.message || 'Login failed. Check your phone number and PIN.');
        }
      } catch {
        setError('Connection error. Please try again.');
      } finally {
        setLoading(false);
      }
    },
    [phone, pin, onLogin]
  );

  return (
    <div className="min-h-[420px] flex items-center justify-center py-8 px-4">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
            <Lock className="w-8 h-8 text-red-600" />
          </div>
          <h2 className="text-2xl font-bold text-gray-900">My Account</h2>
          <p className="text-gray-500 mt-1 text-sm">Sign in with your phone number and PIN</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input
              type="tel"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="(555) 555-5555"
              className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-400"
            />
          </div>

          <PinInput label="4-Digit PIN" value={pin} onChange={setPin} />

          {error && (
            <div className="flex items-start gap-2 text-red-600 text-sm bg-red-50 rounded-lg p-3">
              <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
              <span>{error}</span>
            </div>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white font-semibold py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            {loading ? <Loader className="w-5 h-5 animate-spin" /> : null}
            {loading ? 'Signing in…' : 'Sign In'}
          </button>

          <p className="text-center text-xs text-gray-400">
            Don't have a PIN yet?{' '}
            <a href="#loyalty" className="text-red-600 hover:underline font-medium">
              Create one in Check My Points
            </a>
          </p>
        </form>
      </div>
    </div>
  );
}

// ── Account Card (left panel) ─────────────────────────────────────────────────
function AccountCard({
  customer,
  onLogout,
  isMobile,
}: {
  customer: CustomerInfo;
  onLogout: () => void;
  isMobile: boolean;
}) {
  const tier    = customer.tier || 'BRONZE';
  const colors  = TIER_COLORS[tier] ?? TIER_COLORS['BRONZE'];
  const progress = tierProgress(tier, customer.points);
  const initial  = customer.name?.trim()[0]?.toUpperCase() ?? '?';

  return (
    <div className={`flex flex-col gap-4 ${isMobile ? '' : 'h-full'}`}>
      {/* Avatar + name */}
      <div className={`rounded-2xl bg-gradient-to-br ${colors.bg} p-6 text-white text-center shadow-lg`}>
        <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 text-4xl font-bold mb-3">
          {initial}
        </div>
        <h3 className="text-xl font-bold">{customer.name}</h3>
        <p className="text-white/80 text-sm mt-1">{customer.member_id}</p>

        <span className={`inline-block mt-2 px-3 py-0.5 rounded-full text-xs font-bold border ${colors.badge} bg-white/90`}>
          {tier}
        </span>
      </div>

      {/* Points */}
      <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <div className="flex items-center justify-between mb-1">
          <span className="text-gray-500 text-sm font-medium">Points Balance</span>
          <Star className={`w-4 h-4 ${colors.text}`} fill="currentColor" />
        </div>
        <p className={`text-4xl font-extrabold ${colors.text}`}>{customer.points.toLocaleString()}</p>
        <p className="text-gray-400 text-xs mt-1">loyalty points</p>

        {/* Tier progress bar */}
        {tier !== 'PLATINUM' && (
          <div className="mt-4">
            <div className="flex justify-between text-xs text-gray-400 mb-1">
              <span>{tier}</span>
              <span>{tierNextLabel(tier, customer.points)}</span>
            </div>
            <div className="w-full bg-gray-100 rounded-full h-2">
              <div
                className={`h-2 rounded-full bg-gradient-to-r ${colors.bg} transition-all duration-700`}
                style={{ width: `${progress}%` }}
              />
            </div>
          </div>
        )}
        {tier === 'PLATINUM' && (
          <p className="mt-3 text-xs text-purple-600 font-medium">🏆 You've reached our highest tier!</p>
        )}
      </div>

      {/* Member info */}
      {customer.email && (
        <div className="rounded-2xl bg-white border border-gray-100 shadow-sm px-5 py-4">
          <p className="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Email</p>
          <p className="text-gray-700 text-sm truncate">{customer.email}</p>
        </div>
      )}

      {/* Logout */}
      <button
        onClick={onLogout}
        className="mt-auto flex items-center justify-center gap-2 w-full border border-gray-200 rounded-xl py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors text-sm font-medium"
      >
        <LogOut className="w-4 h-4" />
        Sign Out
      </button>
    </div>
  );
}

// ── Reward Card ───────────────────────────────────────────────────────────────
function RewardCard({
  reward,
  customerPoints,
  onRedeem,
}: {
  reward: Reward;
  customerPoints: number;
  onRedeem: (reward: Reward) => void;
}) {
  const canRedeem = customerPoints >= reward.points_required;

  return (
    <div className={`rounded-2xl border overflow-hidden shadow-sm flex flex-col transition-all duration-200 ${
      canRedeem ? 'border-gray-200 hover:shadow-md' : 'border-gray-100 opacity-70'
    } bg-white`}>
      {/* Image / Emoji */}
      <div className="h-36 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center overflow-hidden">
        {reward.image_url ? (
          <img
            src={reward.image_url}
            alt={reward.name}
            className="w-full h-full object-cover"
            onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
          />
        ) : (
          <span className="text-5xl">{reward.emoji || '🎁'}</span>
        )}
      </div>

      {/* Content */}
      <div className="p-4 flex flex-col flex-1 gap-2">
        <h4 className="font-bold text-gray-900 leading-tight">{reward.name}</h4>
        {reward.description && (
          <p className="text-gray-500 text-xs leading-snug line-clamp-2">{reward.description}</p>
        )}
        <div className="flex items-center gap-1 mt-auto pt-2">
          <Star className="w-4 h-4 text-yellow-500" fill="currentColor" />
          <span className="text-sm font-semibold text-gray-700">
            {reward.points_required.toLocaleString()} pts
          </span>
        </div>
        <button
          disabled={!canRedeem}
          onClick={() => onRedeem(reward)}
          className={`w-full py-2.5 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-1 ${
            canRedeem
              ? 'bg-red-600 hover:bg-red-700 text-white'
              : 'bg-gray-100 text-gray-400 cursor-not-allowed'
          }`}
        >
          {canRedeem ? (
            <>
              <Gift className="w-4 h-4" />
              Redeem
            </>
          ) : (
            `Need ${(reward.points_required - customerPoints).toLocaleString()} more pts`
          )}
        </button>
      </div>
    </div>
  );
}

// ── Confirm Redeem Modal ──────────────────────────────────────────────────────
function ConfirmRedeemModal({
  reward,
  customer,
  phone,
  pin,
  onSuccess,
  onClose,
}: {
  reward: Reward;
  customer: CustomerInfo;
  phone: string;
  pin: string;
  onSuccess: (newPoints: number, tier: string) => void;
  onClose: () => void;
}) {
  const [loading, setLoading]   = useState(false);
  const [error, setError]       = useState('');
  const [confirmed, setConfirmed] = useState(false);

  const handleConfirm = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await fetch(`${API}/kiosk/redeem`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone, reward_id: reward.id, pin }),
      });
      const data = await res.json();
      if (data.success) {
        setConfirmed(true);
        setTimeout(() => {
          onSuccess(data.new_points ?? 0, data.tier ?? customer.tier);
          onClose();
        }, 2000);
      } else {
        setError(data.message || 'Redemption failed. Please try again.');
      }
    } catch {
      setError('Connection error. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [phone, reward, pin, onSuccess, onClose, customer.tier]);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
        {confirmed ? (
          <div className="text-center py-4">
            <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-3" />
            <h3 className="text-xl font-bold text-gray-900 mb-1">Redeemed!</h3>
            <p className="text-gray-500 text-sm">Enjoy your <strong>{reward.name}</strong>. Show this to our staff.</p>
          </div>
        ) : (
          <>
            <div className="text-center mb-6">
              <div className="text-4xl mb-2">{reward.emoji || '🎁'}</div>
              <h3 className="text-xl font-bold text-gray-900">Confirm Redemption</h3>
              <p className="text-gray-500 text-sm mt-1">
                Use <strong>{reward.points_required.toLocaleString()} pts</strong> for{' '}
                <strong>{reward.name}</strong>?
              </p>
              <p className="text-gray-400 text-xs mt-2">
                Your balance after:{' '}
                <span className="font-semibold text-gray-700">
                  {(customer.points - reward.points_required).toLocaleString()} pts
                </span>
              </p>
            </div>

            {error && (
              <div className="flex items-start gap-2 text-red-600 text-sm bg-red-50 rounded-lg p-3 mb-4">
                <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>{error}</span>
              </div>
            )}

            <div className="flex gap-3">
              <button
                onClick={onClose}
                disabled={loading}
                className="flex-1 border border-gray-200 rounded-xl py-3 text-gray-600 hover:bg-gray-50 font-medium text-sm transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleConfirm}
                disabled={loading}
                className="flex-1 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white rounded-xl py-3 font-semibold text-sm transition-colors flex items-center justify-center gap-2"
              >
                {loading ? <Loader className="w-4 h-4 animate-spin" /> : <ChevronRight className="w-4 h-4" />}
                {loading ? 'Processing…' : 'Confirm'}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

// ── Main CustomerPortal ───────────────────────────────────────────────────────
const CustomerPortal: React.FC<PortalProps> = ({ deviceInfo, forcedDevice }) => {
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobile      = currentDevice === 'mobile';

  const [customer, setCustomer]     = useState<CustomerInfo | null>(null);
  const [rewards, setRewards]       = useState<Reward[]>([]);
  const [phone, setPhone]           = useState('');
  const [pin, setPin]               = useState('');
  const [redeemTarget, setRedeemTarget] = useState<Reward | null>(null);

  const handleLogin = useCallback(
    (c: CustomerInfo, r: Reward[], ph: string, p: string) => {
      setCustomer(c);
      setRewards(r);
      setPhone(ph);
      setPin(p);
    },
    []
  );

  const handleLogout = useCallback(() => {
    setCustomer(null);
    setRewards([]);
    setPhone('');
    setPin('');
    setRedeemTarget(null);
  }, []);

  const handleRedeemSuccess = useCallback((newPoints: number, tier: string) => {
    setCustomer((prev) => prev ? { ...prev, points: newPoints, tier } : prev);
  }, []);

  return (
    <section id="my-account" className="py-16 bg-gray-50 border-t border-gray-100">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section header */}
        <div className="text-center mb-10">
          <h2 className={`font-extrabold text-gray-900 mb-2 ${isMobile ? 'text-2xl' : 'text-3xl'}`}>
            My Rewards Account
          </h2>
          <p className="text-gray-500 text-sm">
            Sign in to view your points and redeem rewards
          </p>
        </div>

        {!customer ? (
          // ── Login ──
          <div className="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <LoginForm onLogin={handleLogin} />
          </div>
        ) : (
          // ── Dashboard ──
          <div className={`flex ${isMobile ? 'flex-col' : 'flex-row items-start'} gap-6`}>
            {/* Left: Account card — 40% */}
            <div className={isMobile ? 'w-full' : 'w-[40%] flex-shrink-0'}>
              <AccountCard customer={customer} onLogout={handleLogout} isMobile={isMobile} />
            </div>

            {/* Right: Rewards — 60% */}
            <div className={isMobile ? 'w-full' : 'flex-1'}>
              <div className="mb-4 flex items-center justify-between">
                <h3 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                  <Gift className="w-5 h-5 text-red-500" />
                  Available Rewards
                </h3>
                <span className="text-xs text-gray-400">
                  {rewards.filter((r) => customer.points >= r.points_required).length} redeemable
                </span>
              </div>

              {rewards.length === 0 ? (
                <div className="text-center py-16 text-gray-400">
                  <Gift className="w-12 h-12 mx-auto mb-3 opacity-30" />
                  <p className="font-medium">No rewards available right now.</p>
                  <p className="text-sm mt-1">Check back soon!</p>
                </div>
              ) : (
                <div className={`grid gap-4 ${
                  isMobile ? 'grid-cols-1' : 'grid-cols-2 xl:grid-cols-3'
                }`}>
                  {rewards.map((r) => (
                    <RewardCard
                      key={r.id}
                      reward={r}
                      customerPoints={customer.points}
                      onRedeem={setRedeemTarget}
                    />
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Confirm modal */}
      {redeemTarget && customer && (
        <ConfirmRedeemModal
          reward={redeemTarget}
          customer={customer}
          phone={phone}
          pin={pin}
          onSuccess={handleRedeemSuccess}
          onClose={() => setRedeemTarget(null)}
        />
      )}
    </section>
  );
};

export default CustomerPortal;
