import React, { useState, useEffect } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { X, Star, Home, Gift, Clock, LogOut } from 'lucide-react';
import { AuthProvider, useAuth } from './AuthContext';
import {
  CustomerTier, LoyaltyTransaction, Reward, Redemption,
  tierInfo,
  subscribeToTransactions, subscribeToRewards, subscribeToRedemptions,
} from './customerService';

// ─── Types ────────────────────────────────────────────────────────────────────

type LoyaltyView = 'dashboard' | 'rewards' | 'history';

// ─── Shared helpers ───────────────────────────────────────────────────────────

function Spinner({ full = false }: { full?: boolean }) {
  return (
    <div className={`flex items-center justify-center ${full ? 'min-h-screen' : 'py-16'}`}>
      <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-yellow-500" />
    </div>
  );
}

const TIER_EMOJI: Record<CustomerTier, string> = {
  BRONZE: '🥉', SILVER: '🥈', GOLD: '🥇', PLATINUM: '💎',
};

function TierBadge({ tier }: { tier: CustomerTier }) {
  const info = tierInfo(tier);
  return (
    <span className="inline-flex items-center gap-1 font-bold rounded-full px-3 py-1 bg-white/20 text-white text-sm">
      {TIER_EMOJI[tier]} {info.label}
    </span>
  );
}

// ─── Navbar ───────────────────────────────────────────────────────────────────

function LoyaltyNavbar({
  view, setView, onClose,
}: {
  view: LoyaltyView;
  setView: (v: LoyaltyView) => void;
  onClose: () => void;
}) {
  const { signOut } = useAuth();

  const handleSignOut = async () => {
    await signOut();
  };

  const navItems: { key: LoyaltyView; label: string; icon: React.ReactNode }[] = [
    { key: 'dashboard', label: 'Home',    icon: <Home  className="w-4 h-4" /> },
    { key: 'rewards',   label: 'Rewards', icon: <Gift  className="w-4 h-4" /> },
    { key: 'history',   label: 'History', icon: <Clock className="w-4 h-4" /> },
  ];

  return (
    <nav className="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
      <div className="max-w-lg mx-auto px-4 h-14 flex items-center justify-between gap-2">
        {/* Brand */}
        <div className="flex items-center gap-1.5 flex-shrink-0">
          <Star className="w-5 h-5 text-yellow-500 fill-yellow-400" />
          <span className="font-extrabold text-gray-900 text-sm">Stone Pho Loyalty</span>
        </div>

        {/* Nav tabs */}
        <div className="flex items-center gap-0.5">
          {navItems.map(({ key, label, icon }) => (
            <button
              key={key}
              onClick={() => setView(key)}
              className={`flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                view === key
                  ? 'bg-yellow-100 text-yellow-700'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
              }`}
            >
              {icon} <span className="hidden sm:inline">{label}</span>
            </button>
          ))}
        </div>

        {/* Actions */}
        <div className="flex items-center gap-1 flex-shrink-0">
          <button
            onClick={handleSignOut}
            title="Sign out"
            className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
          >
            <LogOut className="w-4 h-4" />
          </button>
          <button
            onClick={onClose}
            title="Đóng loyalty"
            className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      </div>
    </nav>
  );
}

// ─── Login View ───────────────────────────────────────────────────────────────

function LoginView({ onClose }: { onClose: () => void }) {
  const { sendOtp, confirmOtp, otpSent, otpError, otpLoading, clearOtpState } = useAuth();
  const [phone, setPhone] = useState('');
  const [otp, setOtp]     = useState('');

  const handleSendOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    let normalized = phone.trim().replace(/[\s\-()]/g, '');
    if (!normalized.startsWith('+')) normalized = '+1' + normalized;
    await sendOtp(normalized);
  };

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    await confirmOtp(otp.trim());
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-yellow-50 to-white flex flex-col">
      {/* Top bar */}
      <div className="flex justify-end p-4">
        <button
          onClick={onClose}
          className="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
        >
          <X className="w-5 h-5" />
        </button>
      </div>

      {/* Content */}
      <div className="flex-1 flex items-center justify-center px-4 pb-8">
        <div className="w-full max-w-sm">
          {/* Header */}
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-4">
              <Star className="w-10 h-10 text-yellow-500 fill-yellow-400" />
            </div>
            <h1 className="text-3xl font-extrabold text-yellow-600">Stone Pho</h1>
            <p className="text-gray-500 mt-1 font-medium">Loyalty Program</p>
          </div>

          {/* Card */}
          <div className="bg-white rounded-2xl shadow-lg p-8">
            {!otpSent ? (
              <>
                <h2 className="text-xl font-bold mb-1">Đăng nhập</h2>
                <p className="text-sm text-gray-500 mb-6">
                  Nhập số điện thoại đã đăng ký tài khoản loyalty của bạn.
                </p>
                <form onSubmit={handleSendOtp} className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Số điện thoại
                    </label>
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder="+84 901 234 567"
                      required
                      className="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-yellow-400"
                    />
                    <p className="text-xs text-gray-400 mt-1">
                      Bao gồm mã quốc gia, ví dụ +84 (VN), +1 (US)
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
                    {otpLoading && <div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />}
                    {otpLoading ? 'Đang gửi…' : 'Gửi mã xác nhận'}
                  </button>
                </form>
              </>
            ) : (
              <>
                <h2 className="text-xl font-bold mb-1">Nhập mã xác nhận</h2>
                <p className="text-sm text-gray-500 mb-6">
                  Đã gửi mã 6 chữ số tới <strong>{phone}</strong>
                </p>
                <form onSubmit={handleVerifyOtp} className="space-y-4">
                  <input
                    type="text"
                    inputMode="numeric"
                    maxLength={6}
                    value={otp}
                    onChange={(e) => setOtp(e.target.value.replace(/\D/g, ''))}
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
                    {otpLoading && <div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />}
                    {otpLoading ? 'Đang xác nhận…' : 'Xác nhận & Đăng nhập'}
                  </button>

                  <button
                    type="button"
                    onClick={clearOtpState}
                    className="w-full text-gray-500 text-sm hover:text-gray-700 py-2"
                  >
                    ← Đổi số điện thoại
                  </button>
                </form>
              </>
            )}
          </div>

          {/* reCAPTCHA container required by Firebase Phone Auth */}
          <div id="recaptcha-container" />
        </div>
      </div>
    </div>
  );
}

// ─── Dashboard View ───────────────────────────────────────────────────────────

function DashboardView({ setView }: { setView: (v: LoyaltyView) => void }) {
  const { customer } = useAuth();
  const [recentTxs, setRecentTxs] = useState<LoyaltyTransaction[]>([]);

  useEffect(() => {
    if (!customer) return;
    return subscribeToTransactions(customer.id, (txs) => setRecentTxs(txs.slice(0, 5)));
  }, [customer]);

  if (!customer) return null;

  const nextTier = (): { label: string; need: number; total: number } | null => {
    if (customer.points < 500)  return { label: 'Silver',   need: 500  - customer.points, total: 500  };
    if (customer.points < 1000) return { label: 'Gold',     need: 1000 - customer.points, total: 500  };
    if (customer.points < 2500) return { label: 'Platinum', need: 2500 - customer.points, total: 1500 };
    return null;
  };
  const next = nextTier();
  const progressPct = next
    ? Math.min(100, ((next.total - next.need) / next.total) * 100)
    : 100;

  return (
    <main className="max-w-lg mx-auto px-4 py-6 space-y-4">
      {/* Welcome */}
      <div>
        <p className="text-gray-500 text-sm">Xin chào,</p>
        <h1 className="text-2xl font-extrabold">{customer.name}</h1>
      </div>

      {/* Points card */}
      <div className="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl p-6 text-white shadow-lg">
        <div className="flex justify-between items-start">
          <div>
            <p className="text-yellow-100 text-sm font-medium">Điểm tích lũy</p>
            <p className="text-5xl font-extrabold mt-1">{customer.points.toLocaleString()}</p>
            <p className="text-yellow-100 text-sm mt-1">điểm</p>
          </div>
          <TierBadge tier={customer.tier} />
        </div>

        {next ? (
          <div className="mt-4">
            <div className="flex justify-between text-sm text-yellow-100 mb-1">
              <span>Đến hạng {next.label}</span>
              <span>còn {next.need} điểm</span>
            </div>
            <div className="bg-yellow-300/40 rounded-full h-2">
              <div
                className="bg-white rounded-full h-2 transition-all duration-500"
                style={{ width: `${progressPct}%` }}
              />
            </div>
          </div>
        ) : (
          <div className="mt-4">
            <p className="text-yellow-100 text-sm">🏆 Bạn đã đạt hạng cao nhất!</p>
          </div>
        )}
      </div>

      {/* QR Code */}
      <div className="bg-white rounded-2xl shadow p-6 flex flex-col items-center gap-3">
        <h2 className="font-bold text-lg">Mã QR của bạn</h2>
        <p className="text-gray-500 text-sm text-center">
          Cho nhân viên quét mã này để tích điểm hoặc đổi thưởng
        </p>
        <div className="border-4 border-yellow-400 rounded-xl p-3 bg-white shadow-sm">
          <QRCodeSVG value={customer.qrCode} size={180} level="M" includeMargin={false} />
        </div>
        <p className="text-sm font-mono font-semibold text-gray-600 bg-gray-100 px-4 py-2 rounded-lg">
          {customer.memberId}
        </p>
      </div>

      {/* Recent transactions */}
      <div className="bg-white rounded-2xl shadow p-5">
        <div className="flex justify-between items-center mb-4">
          <h2 className="font-bold text-lg">Hoạt động gần đây</h2>
          <button
            onClick={() => setView('history')}
            className="text-yellow-600 text-sm font-medium hover:underline"
          >
            Xem tất cả
          </button>
        </div>
        {recentTxs.length === 0 ? (
          <p className="text-gray-400 text-sm text-center py-4">Chưa có giao dịch nào.</p>
        ) : (
          <ul className="divide-y divide-gray-100">
            {recentTxs.map((tx) => (
              <li key={tx.id} className="py-3 flex justify-between items-center">
                <div>
                  <p className="font-medium text-sm">{tx.description}</p>
                  <p className="text-xs text-gray-400">
                    {new Date(tx.createdAt).toLocaleDateString('vi-VN', {
                      day: 'numeric', month: 'short', year: 'numeric',
                    })}
                  </p>
                </div>
                <span className={`font-bold text-sm ${tx.type === 'EARNED' ? 'text-green-600' : 'text-red-500'}`}>
                  {tx.type === 'EARNED' ? '+' : '−'}{tx.points} pts
                </span>
              </li>
            ))}
          </ul>
        )}
      </div>

      {/* Rewards shortcut */}
      <button
        onClick={() => setView('rewards')}
        className="w-full bg-yellow-50 border border-yellow-200 rounded-2xl p-4 text-left hover:bg-yellow-100 transition-colors"
      >
        <div className="flex items-center justify-between">
          <div>
            <p className="font-bold text-yellow-700">Xem phần thưởng</p>
            <p className="text-sm text-yellow-600">Khám phá những phần thưởng bạn có thể đổi</p>
          </div>
          <Gift className="w-8 h-8 text-yellow-400" />
        </div>
      </button>
    </main>
  );
}

// ─── Rewards View ─────────────────────────────────────────────────────────────

const CATEGORY_EMOJI: Record<string, string> = {
  FOOD: '🍽', DRINK: '☕', DISCOUNT: '%', GENERAL: '🎁',
};

function RewardsView() {
  const { customer } = useAuth();
  const [rewards, setRewards]         = useState<Reward[]>([]);
  const [redemptions, setRedemptions] = useState<Redemption[]>([]);
  const [loading, setLoading]         = useState(true);

  useEffect(() => {
    return subscribeToRewards((r) => { setRewards(r); setLoading(false); });
  }, []);

  useEffect(() => {
    if (!customer) return;
    return subscribeToRedemptions(customer.id, setRedemptions);
  }, [customer]);

  if (!customer) return null;

  return (
    <main className="max-w-lg mx-auto px-4 py-6 space-y-5">
      {/* Points summary */}
      <div className="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-2xl p-5 text-white shadow">
        <p className="text-yellow-100 text-sm">Điểm hiện có</p>
        <p className="text-4xl font-extrabold">{customer.points.toLocaleString()}</p>
        <p className="text-yellow-100 text-sm mt-1">{customer.name}</p>
      </div>

      <h2 className="font-bold text-lg">Phần thưởng có thể đổi</h2>

      {loading ? (
        <Spinner />
      ) : rewards.length === 0 ? (
        <div className="bg-white rounded-2xl shadow p-8 text-center">
          <p className="text-gray-400">Hiện chưa có phần thưởng nào.</p>
        </div>
      ) : (
        <ul className="space-y-3">
          {rewards.map((reward) => {
            const canAfford = customer.points >= reward.pointsRequired;
            return (
              <li
                key={reward.id}
                className={`bg-white rounded-xl shadow-sm p-4 flex items-center gap-4 transition-opacity ${
                  !canAfford ? 'opacity-60' : ''
                }`}
              >
                <div className="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center text-2xl flex-shrink-0">
                  {CATEGORY_EMOJI[reward.category] ?? '🎁'}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-bold text-sm">{reward.name}</p>
                  <p className="text-xs text-gray-500 truncate">{reward.description}</p>
                  <p className={`text-xs font-semibold mt-1 ${canAfford ? 'text-yellow-600' : 'text-red-500'}`}>
                    {reward.pointsRequired} điểm
                    {!canAfford && ` · cần thêm ${reward.pointsRequired - customer.points} điểm`}
                  </p>
                </div>
                <div className={`text-xs font-bold px-3 py-1.5 rounded-lg flex-shrink-0 ${
                  canAfford ? 'bg-yellow-400 text-white' : 'bg-gray-200 text-gray-400'
                }`}>
                  {canAfford ? '✓ Có thể đổi' : 'Khóa'}
                </div>
              </li>
            );
          })}
        </ul>
      )}

      {redemptions.length > 0 && (
        <>
          <h2 className="font-bold text-lg pt-2">Lịch sử đổi thưởng</h2>
          <ul className="space-y-2">
            {redemptions.map((r) => (
              <li key={r.id} className="bg-white rounded-xl shadow-sm p-4 flex justify-between items-center">
                <div>
                  <p className="font-medium text-sm">{r.rewardName}</p>
                  <p className="text-xs text-gray-400">
                    {new Date(r.redeemedAt).toLocaleDateString('vi-VN', {
                      day: 'numeric', month: 'short', year: 'numeric',
                    })}
                  </p>
                </div>
                <span className="text-red-500 font-bold text-sm">−{r.pointsSpent} pts</span>
              </li>
            ))}
          </ul>
        </>
      )}

      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
        💡 Để đổi thưởng, hãy đến cửa hàng và cho nhân viên quét mã QR của bạn.
      </div>
    </main>
  );
}

// ─── History View ─────────────────────────────────────────────────────────────

function HistoryView() {
  const { customer } = useAuth();
  const [transactions, setTransactions] = useState<LoyaltyTransaction[]>([]);
  const [loading, setLoading]           = useState(true);

  useEffect(() => {
    if (!customer) return;
    return subscribeToTransactions(customer.id, (txs) => {
      setTransactions(txs);
      setLoading(false);
    });
  }, [customer]);

  if (!customer) return null;

  const typeBadge = (type: string) => {
    if (type === 'EARNED')   return { text: '+ Tích điểm',  cls: 'bg-green-100 text-green-700' };
    if (type === 'REDEEMED') return { text: '− Đổi thưởng', cls: 'bg-red-100 text-red-700'    };
    return                          { text: '± Điều chỉnh', cls: 'bg-blue-100 text-blue-700'  };
  };

  return (
    <main className="max-w-lg mx-auto px-4 py-6">
      <h1 className="text-2xl font-extrabold mb-1">Lịch sử giao dịch</h1>
      <p className="text-gray-500 text-sm mb-5">Tất cả hoạt động điểm của bạn</p>

      {loading ? (
        <Spinner />
      ) : transactions.length === 0 ? (
        <div className="bg-white rounded-2xl shadow p-8 text-center">
          <div className="text-5xl mb-3">📋</div>
          <p className="text-gray-500">Chưa có giao dịch nào.</p>
        </div>
      ) : (
        <ul className="space-y-3">
          {transactions.map((tx) => {
            const badge = typeBadge(tx.type);
            return (
              <li key={tx.id} className="bg-white rounded-xl shadow-sm p-4 flex justify-between items-start">
                <div className="flex-1">
                  <p className="font-semibold text-sm">{tx.description}</p>
                  <p className="text-xs text-gray-400 mt-1">
                    {new Date(tx.createdAt).toLocaleString('vi-VN', {
                      day: 'numeric', month: 'short', year: 'numeric',
                      hour: '2-digit', minute: '2-digit',
                    })}
                  </p>
                  <span className={`inline-block mt-2 text-xs font-semibold px-2 py-0.5 rounded-full ${badge.cls}`}>
                    {badge.text}
                  </span>
                </div>
                <span className={`font-extrabold text-lg ml-4 ${tx.type === 'EARNED' ? 'text-green-600' : 'text-red-500'}`}>
                  {tx.type === 'EARNED' ? '+' : '−'}{tx.points}
                </span>
              </li>
            );
          })}
        </ul>
      )}
    </main>
  );
}

// ─── Main router ──────────────────────────────────────────────────────────────

function LoyaltyContent({ onClose }: { onClose: () => void }) {
  const { user, customer, authLoading } = useAuth();
  const [view, setView] = useState<LoyaltyView>('dashboard');

  if (authLoading) return <Spinner full />;

  // Not logged in → show login
  if (!user || !customer) {
    return <LoginView onClose={onClose} />;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <LoyaltyNavbar view={view} setView={setView} onClose={onClose} />
      {view === 'dashboard' && <DashboardView setView={setView} />}
      {view === 'rewards'   && <RewardsView />}
      {view === 'history'   && <HistoryView />}
    </div>
  );
}

// ─── Entry point (wraps AuthProvider) ────────────────────────────────────────

export default function LoyaltySection({ onClose }: { onClose: () => void }) {
  return (
    <AuthProvider>
      <LoyaltyContent onClose={onClose} />
    </AuthProvider>
  );
}
