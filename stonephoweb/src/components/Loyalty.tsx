import React, { useState, useRef, useEffect, useCallback } from 'react';
import { Star, Gift, Phone, CheckCircle, AlertCircle, Loader, Award, Camera, X, ScanLine } from 'lucide-react';
import { BrowserMultiFormatReader, IScannerControls } from '@zxing/browser';
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

// Extract a bare ID from a scanned value that may be a URL
function extractId(raw: string): string {
  const trimmed = raw.trim();
  try {
    const url = new URL(trimmed);
    const parts = url.pathname.split('/').filter(Boolean);
    if (parts.length > 0) return parts[parts.length - 1].toUpperCase();
  } catch { /* not a URL */ }
  return trimmed.toUpperCase();
}

// ── Camera scanner hook (@zxing/browser — Code128 + QR, all browsers) ────────
function useCameraScanner(onDetected: (value: string) => void) {
  const [scanning, setScanning]   = useState(false);
  const [camError, setCamError]   = useState('');
  const videoRef    = useRef<HTMLVideoElement>(null);
  const controlsRef = useRef<IScannerControls | null>(null);

  const stopScan = useCallback(() => {
    controlsRef.current?.stop();
    controlsRef.current = null;
    setScanning(false);
  }, []);

  const startScan = useCallback(() => {
    setCamError('');
    setScanning(true);
  }, []);

  useEffect(() => {
    if (!scanning || !videoRef.current) return;
    const reader = new BrowserMultiFormatReader();
    reader
      .decodeFromConstraints(
        { video: { facingMode: { ideal: 'environment' } } },
        videoRef.current,
        (result, _err, controls) => {
          if (result) {
            onDetected(extractId(result.getText()));
            controls.stop();
            controlsRef.current = null;
            setScanning(false);
          }
        }
      )
      .then(controls => { controlsRef.current = controls; })
      .catch(() => {
        setCamError('Camera access denied. Please allow camera access and try again.');
        setScanning(false);
      });

    return () => { controlsRef.current?.stop(); };
  }, [scanning, onDetected]);

  useEffect(() => () => stopScan(), [stopScan]);

  return { scanning, camError, startScan, stopScan, videoRef };
}

// ── Camera modal overlay ──────────────────────────────────────────────────────
function CameraModal({ scanning, videoRef, onClose }: {
  scanning: boolean;
  videoRef: React.RefObject<HTMLVideoElement>;
  onClose: () => void;
}) {
  if (!scanning) return null;
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
      <div className="relative w-full max-w-sm bg-black rounded-2xl overflow-hidden">
        <video
          ref={videoRef}
          muted
          playsInline
          className="w-full aspect-square object-cover"
        />
        {/* Scan frame guide */}
        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div className="w-64 h-32 border-2 border-red-500 rounded-lg" />
        </div>
        <p className="absolute bottom-14 w-full text-center text-white text-sm px-4">
          Point the barcode on your receipt inside the frame
        </p>
        <button
          onClick={onClose}
          className="absolute top-3 right-3 bg-black/60 rounded-full p-1.5 text-white hover:bg-black"
        >
          <X className="w-5 h-5" />
        </button>
      </div>
    </div>
  );
}

// ── Main component ────────────────────────────────────────────────────────────
const Loyalty: React.FC<LoyaltyProps> = ({ deviceInfo, forcedDevice }) => {
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobile = currentDevice === 'mobile';

  const [activeTab, setActiveTab] = useState<'check' | 'claim' | 'payment'>('check');

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

  // ── Claim by Payment ID tab ───────────────────────────────────────────────
  const [payPhone, setPayPhone]       = useState('');
  const [paymentId, setPaymentId]     = useState('');
  const [payLoading, setPayLoading]   = useState(false);
  const [paySuccess, setPaySuccess]   = useState<{ points_added: number; new_points: number; tier: string; amount: string } | null>(null);
  const [payError, setPayError]       = useState('');

  const handleScanDetected = useCallback((value: string) => {
    setPaymentId(value);
  }, []);

  const { scanning, camError, startScan, stopScan, videoRef } = useCameraScanner(handleScanDetected);

  const handleClaimPayment = async (e: React.FormEvent) => {
    e.preventDefault();
    setPayLoading(true);
    setPayError('');
    setPaySuccess(null);

    try {
      const res = await fetch(`${API}/receipt_codes/claim_payment`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: payPhone.trim(), payment_id: paymentId.trim() }),
      });
      const data = await res.json();
      if (data.success) {
        setPaySuccess(data);
        setPaymentId('');
      } else {
        setPayError(data.message || 'Failed to claim points.');
      }
    } catch {
      setPayError('Could not connect to server. Please try again.');
    } finally {
      setPayLoading(false);
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
            Earn points every visit, redeem for rewards. Check your balance or claim points below.
          </p>

          {/* Tier badges */}
          <div className={`flex ${isMobile ? 'flex-col gap-2' : 'flex-row gap-4'} justify-center mt-8`}>
            {[
              { tier: 'BRONZE',   label: 'Bronze',   pts: '0 pts',     emoji: '🥉' },
              { tier: 'SILVER',   label: 'Silver',   pts: '500 pts',   emoji: '🥈' },
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
          <div className={`bg-gray-700 rounded-xl p-1 flex ${isMobile ? 'flex-col w-full max-w-sm gap-1' : ''}`}>
            <button
              onClick={() => setActiveTab('check')}
              className={`px-5 py-3 rounded-lg font-medium transition-all text-sm ${
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
              className={`px-5 py-3 rounded-lg font-medium transition-all text-sm ${
                activeTab === 'claim'
                  ? 'bg-red-600 text-white shadow'
                  : 'text-gray-300 hover:text-white'
              }`}
            >
              <Gift className="w-4 h-4 inline mr-2" />
              Claim Code
            </button>
            <button
              onClick={() => setActiveTab('payment')}
              className={`px-5 py-3 rounded-lg font-medium transition-all text-sm ${
                activeTab === 'payment'
                  ? 'bg-red-600 text-white shadow'
                  : 'text-gray-300 hover:text-white'
              }`}
            >
              <ScanLine className="w-4 h-4 inline mr-2" />
              Claim by Receipt
            </button>
          </div>
        </div>

        {/* Card container */}
        <div className="max-w-lg mx-auto bg-gray-800 rounded-2xl shadow-2xl p-8 border border-gray-700">

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

          {/* ── CLAIM BY PAYMENT ID TAB ── */}
          {activeTab === 'payment' && (
            <div>
              <h3 className="text-xl font-bold text-white mb-2 text-center">
                Claim by Receipt Payment ID
              </h3>
              <p className="text-gray-400 text-sm text-center mb-6">
                Enter your Payment ID from the receipt — or scan the barcode/QR code on it.
                Points are calculated from your payment amount.
              </p>

              <form onSubmit={handleClaimPayment} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Your Phone Number
                  </label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                      type="tel"
                      value={payPhone}
                      onChange={e => setPayPhone(e.target.value)}
                      placeholder="+1 (415) 555-1234"
                      required
                      className="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent placeholder-gray-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Payment ID
                  </label>
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={paymentId}
                      onChange={e => setPaymentId(e.target.value.toUpperCase())}
                      placeholder="e.g. 9M9M1573VZFER"
                      required
                      className="flex-1 px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent placeholder-gray-500 font-mono tracking-wider"
                    />
                    <button
                      type="button"
                      onClick={startScan}
                      className="px-4 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-xl transition-all flex items-center gap-1.5 shrink-0"
                      title="Scan receipt barcode with camera"
                    >
                      <Camera className="w-5 h-5" />
                      <span className="text-sm font-medium hidden sm:inline">Scan</span>
                    </button>
                  </div>
                  <p className="mt-1 text-xs text-gray-500">
                    Payment ID is printed on your Clover receipt (alphanumeric, e.g. 9M9M1573VZFER)
                  </p>
                  {camError && (
                    <p className="mt-1 text-xs text-amber-400">{camError}</p>
                  )}
                  {paymentId && (
                    <p className="mt-1 text-xs text-green-400 font-mono">Scanned: {paymentId}</p>
                  )}
                </div>

                <button
                  type="submit"
                  disabled={payLoading}
                  className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-900 text-white py-3 rounded-xl font-semibold transition-all flex items-center justify-center gap-2"
                >
                  {payLoading
                    ? <><Loader className="w-5 h-5 animate-spin" /> Claiming...</>
                    : <><ScanLine className="w-5 h-5" /> Claim Points</>}
                </button>
              </form>

              {payError && (
                <div className="mt-4 flex items-center gap-2 text-red-400 bg-red-900/30 rounded-xl p-4">
                  <AlertCircle className="w-5 h-5 shrink-0" />
                  <p className="text-sm">{payError}</p>
                </div>
              )}

              {paySuccess && (
                <div className="mt-6 bg-green-800/40 border border-green-500/50 rounded-2xl p-6 text-center">
                  <CheckCircle className="w-12 h-12 text-green-400 mx-auto mb-3" />
                  <p className="text-green-300 font-bold text-lg mb-1">
                    +{paySuccess.points_added} Points Earned!
                  </p>
                  <p className="text-gray-400 text-sm mb-2">
                    Payment amount: <span className="text-white font-semibold">${paySuccess.amount}</span>
                  </p>
                  <p className="text-gray-300 text-sm">
                    New balance: <span className="font-bold text-white">{paySuccess.new_points.toLocaleString()} pts</span>
                    {' '}· Tier: <span className="font-bold text-yellow-300">{TIER_LABELS[paySuccess.tier] ?? paySuccess.tier}</span>
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

      {/* Camera overlay */}
      <CameraModal scanning={scanning} videoRef={videoRef} onClose={stopScan} />
    </section>
  );
};

export default Loyalty;
