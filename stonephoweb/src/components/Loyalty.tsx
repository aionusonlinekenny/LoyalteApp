import React, { useState } from 'react';
import { Star, Gift, Phone, CheckCircle, AlertCircle, Loader, Award } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';

const API = '/loyalteapp/backend/api';

interface CustomerInfo {
  id: string;
  member_id: string;
  name: string;
  tier: string;
  points: number;
}

interface LoyaltyProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const TIER_COLORS: Record<string, string> = {
  BRONZE:   'from-amber-600 to-amber-400',
  SILVER:   'from-slate-400 to-slate-300',
  GOLD:     'from-yellow-500 to-yellow-300',
  PLATINUM: 'from-purple-400 to-purple-200',
};

const TIER_LABELS: Record<string, string> = {
  BRONZE: 'Bronze', SILVER: 'Silver', GOLD: 'Gold', PLATINUM: 'Platinum',
};

const Loyalty: React.FC<LoyaltyProps> = ({ deviceInfo, forcedDevice }) => {
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobile = currentDevice === 'mobile';

  const [activeTab, setActiveTab] = useState<'check' | 'claim'>('check');

  // ── Check Points tab ──────────────────────────────────────────────────────
  const [checkPhone, setCheckPhone] = useState('');
  const [checkLoading, setCheckLoading] = useState(false);
  const [checkResult, setCheckResult] = useState<CustomerInfo | null>(null);
  const [checkError, setCheckError] = useState('');

  const handleCheckPoints = async (e: React.FormEvent) => {
    e.preventDefault();
    setCheckLoading(true);
    setCheckError('');
    setCheckResult(null);
    try {
      const res = await fetch(`${API}/customers/lookup`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: checkPhone.trim() }),
      });
      const data = await res.json();
      if (data.success && data.customer) {
        setCheckResult(data.customer);
      } else {
        setCheckError(data.message || 'Phone number not found in our system.');
      }
    } catch {
      setCheckError('Could not connect to server. Please try again.');
    } finally {
      setCheckLoading(false);
    }
  };

  // ── Claim Code tab ────────────────────────────────────────────────────────
  const [claimPhone, setClaimPhone] = useState('');
  const [claimCode, setClaimCode] = useState('');
  const [claimLoading, setClaimLoading] = useState(false);
  const [claimSuccess, setClaimSuccess] = useState<{ points_added: number; new_points: number; tier: string } | null>(null);
  const [claimError, setClaimError] = useState('');

  const handleClaimCode = async (e: React.FormEvent) => {
    e.preventDefault();
    setClaimLoading(true);
    setClaimError('');
    setClaimSuccess(null);

    try {
      // Step 1: look up customer by phone
      const lookupRes = await fetch(`${API}/customers/lookup`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: claimPhone.trim() }),
      });
      const lookupData = await lookupRes.json();
      if (!lookupData.success || !lookupData.customer) {
        setClaimError(lookupData.message || 'Phone number not found. Please register first.');
        setClaimLoading(false);
        return;
      }

      // Step 2: claim the code
      const claimRes = await fetch(`${API}/receipt_codes/claim`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          code: claimCode.trim().toLowerCase(),
          customer_id: lookupData.customer.id,
        }),
      });
      const claimData = await claimRes.json();
      if (claimData.success) {
        setClaimSuccess(claimData);
        setClaimCode('');
      } else {
        setClaimError(claimData.message || 'Failed to claim code.');
      }
    } catch {
      setClaimError('Could not connect to server. Please try again.');
    } finally {
      setClaimLoading(false);
    }
  };

  return (
    <section id="loyalty" className="py-20 bg-gradient-to-b from-gray-900 to-gray-800">
      <div className={`max-w-7xl mx-auto px-4 ${isMobile ? '' : 'sm:px-6 lg:px-8'}`}>

        {/* Section header */}
        <div className="text-center mb-12">
          <div className="flex items-center justify-center space-x-2 mb-4">
            <Star className="w-8 h-8 text-yellow-400 fill-yellow-400" />
            <h2 className={`font-bold text-white ${isMobile ? 'text-3xl' : 'text-4xl'}`}>
              Loyalty Program
            </h2>
            <Star className="w-8 h-8 text-yellow-400 fill-yellow-400" />
          </div>
          <p className="text-gray-300 text-lg max-w-xl mx-auto">
            Earn points every visit, redeem for rewards. Check your balance or claim a receipt code below.
          </p>

          {/* Tier badges */}
          <div className={`flex ${isMobile ? 'flex-col gap-2' : 'flex-row gap-4'} justify-center mt-8`}>
            {[
              { tier: 'BRONZE',   label: 'Bronze',   pts: '0 pts',    emoji: '🥉' },
              { tier: 'SILVER',   label: 'Silver',   pts: '500 pts',  emoji: '🥈' },
              { tier: 'GOLD',     label: 'Gold',     pts: '1,000 pts', emoji: '🥇' },
              { tier: 'PLATINUM', label: 'Platinum', pts: '2,500 pts', emoji: '💎' },
            ].map(t => (
              <div
                key={t.tier}
                className={`bg-gradient-to-r ${TIER_COLORS[t.tier]} rounded-xl px-4 py-2 text-white font-semibold text-sm shadow-lg`}
              >
                {t.emoji} {t.label} — {t.pts}
              </div>
            ))}
          </div>
        </div>

        {/* Tab switcher */}
        <div className="flex justify-center mb-8">
          <div className="bg-gray-700 rounded-xl p-1 flex">
            <button
              onClick={() => setActiveTab('check')}
              className={`px-6 py-3 rounded-lg font-medium transition-all text-sm ${
                activeTab === 'check'
                  ? 'bg-red-600 text-white shadow'
                  : 'text-gray-300 hover:text-white'
              }`}
            >
              <Phone className="w-4 h-4 inline mr-2" />
              Check My Points
            </button>
            <button
              onClick={() => setActiveTab('claim')}
              className={`px-6 py-3 rounded-lg font-medium transition-all text-sm ${
                activeTab === 'claim'
                  ? 'bg-red-600 text-white shadow'
                  : 'text-gray-300 hover:text-white'
              }`}
            >
              <Gift className="w-4 h-4 inline mr-2" />
              Claim Receipt Code
            </button>
          </div>
        </div>

        {/* Card container */}
        <div className={`max-w-lg mx-auto bg-gray-800 rounded-2xl shadow-2xl p-8 border border-gray-700`}>

          {/* ── CHECK POINTS TAB ── */}
          {activeTab === 'check' && (
            <div>
              <h3 className="text-xl font-bold text-white mb-6 text-center">
                Check Your Points Balance
              </h3>
              <form onSubmit={handleCheckPoints} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Phone Number
                  </label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                      type="tel"
                      value={checkPhone}
                      onChange={e => setCheckPhone(e.target.value)}
                      placeholder="+1 (415) 555-1234"
                      required
                      className="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent placeholder-gray-500"
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={checkLoading}
                  className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-900 text-white py-3 rounded-xl font-semibold transition-all flex items-center justify-center gap-2"
                >
                  {checkLoading
                    ? <><Loader className="w-5 h-5 animate-spin" /> Checking...</>
                    : 'Check Balance'}
                </button>
              </form>

              {checkError && (
                <div className="mt-4 flex items-center gap-2 text-red-400 bg-red-900/30 rounded-xl p-4">
                  <AlertCircle className="w-5 h-5 shrink-0" />
                  <p className="text-sm">{checkError}</p>
                </div>
              )}

              {checkResult && (
                <div className={`mt-6 bg-gradient-to-br ${TIER_COLORS[checkResult.tier] ?? TIER_COLORS.BRONZE} rounded-2xl p-6 text-white shadow-xl`}>
                  <div className="flex items-center gap-3 mb-4">
                    <Award className="w-8 h-8" />
                    <div>
                      <p className="font-bold text-lg">{checkResult.name}</p>
                      <p className="text-sm opacity-80">{checkResult.member_id}</p>
                    </div>
                  </div>
                  <div className="flex justify-between items-end">
                    <div>
                      <p className="text-sm opacity-80 mb-1">Current Tier</p>
                      <p className="text-xl font-bold">{TIER_LABELS[checkResult.tier] ?? checkResult.tier}</p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm opacity-80 mb-1">Total Points</p>
                      <p className="text-3xl font-black">{checkResult.points.toLocaleString()}</p>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* ── CLAIM CODE TAB ── */}
          {activeTab === 'claim' && (
            <div>
              <h3 className="text-xl font-bold text-white mb-2 text-center">
                Claim Your Receipt Code
              </h3>
              <p className="text-gray-400 text-sm text-center mb-6">
                Enter the 3-word code from your receipt to earn bonus points.
              </p>
              <form onSubmit={handleClaimCode} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Your Phone Number
                  </label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                      type="tel"
                      value={claimPhone}
                      onChange={e => setClaimPhone(e.target.value)}
                      placeholder="+1 (415) 555-1234"
                      required
                      className="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent placeholder-gray-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Receipt Code
                  </label>
                  <input
                    type="text"
                    value={claimCode}
                    onChange={e => setClaimCode(e.target.value)}
                    placeholder="apple river gold"
                    required
                    className="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent placeholder-gray-500 font-mono"
                  />
                  <p className="mt-1 text-xs text-gray-500">3-word code printed on your receipt</p>
                </div>

                <button
                  type="submit"
                  disabled={claimLoading}
                  className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-900 text-white py-3 rounded-xl font-semibold transition-all flex items-center justify-center gap-2"
                >
                  {claimLoading
                    ? <><Loader className="w-5 h-5 animate-spin" /> Claiming...</>
                    : <><Gift className="w-5 h-5" /> Claim Points</>}
                </button>
              </form>

              {claimError && (
                <div className="mt-4 flex items-center gap-2 text-red-400 bg-red-900/30 rounded-xl p-4">
                  <AlertCircle className="w-5 h-5 shrink-0" />
                  <p className="text-sm">{claimError}</p>
                </div>
              )}

              {claimSuccess && (
                <div className="mt-6 bg-green-800/40 border border-green-500/50 rounded-2xl p-6 text-center">
                  <CheckCircle className="w-12 h-12 text-green-400 mx-auto mb-3" />
                  <p className="text-green-300 font-bold text-lg mb-1">
                    +{claimSuccess.points_added} Points Earned!
                  </p>
                  <p className="text-gray-300 text-sm">
                    New balance: <span className="font-bold text-white">{claimSuccess.new_points.toLocaleString()} pts</span>
                    {' '}· Tier: <span className="font-bold text-yellow-300">{TIER_LABELS[claimSuccess.tier] ?? claimSuccess.tier}</span>
                  </p>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Register CTA */}
        <p className="text-center text-gray-500 text-sm mt-8">
          Not a member yet? Ask our staff to register your phone number and start earning points today!
        </p>
      </div>
    </section>
  );
};

export default Loyalty;
