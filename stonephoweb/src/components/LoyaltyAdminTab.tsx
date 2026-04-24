import React, { useState, useEffect, useCallback } from 'react';
import {
  Users, Receipt, Plus, Trash2, Search, RefreshCw,
  LogIn, Star, CheckCircle, Clock, XCircle, Lock, Mail,
  Settings, Zap, AlertCircle
} from 'lucide-react';

const API = '/loyalteapp/backend/api';

// ── Types ─────────────────────────────────────────────────────────────────────

interface Customer {
  id: string;
  member_id: string;
  name: string;
  phone: string;
  email?: string;
  tier: string;
  points: number;
}

interface CloverConfig {
  app_id: string;
  app_secret: string;
  access_token: string;
  merchant_id: string;
  environment: 'sandbox' | 'production';
  points_per_dollar: string;
  enabled: string;
}

interface CloverLog {
  id: number;
  payment_id: string;
  merchant_id: string;
  order_id: string | null;
  customer_name: string | null;
  phone: string | null;
  amount_cents: number;
  points_awarded: number;
  status: 'processed' | 'no_customer' | 'error';
  note: string | null;
  created_at: number;
}

interface ReceiptCode {
  id: string;
  code: string;
  points: number;
  expires_at: number;
  claimed_by: string | null;
  claimed_by_name?: string | null;
  claimed_at: number | null;
  created_at: number;
  note?: string | null;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const TIER_BADGE: Record<string, string> = {
  BRONZE:   'bg-amber-100 text-amber-700',
  SILVER:   'bg-slate-100 text-slate-600',
  GOLD:     'bg-yellow-100 text-yellow-700',
  PLATINUM: 'bg-purple-100 text-purple-700',
};

function fmtDate(ms: number) {
  return new Date(ms).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function authHeader(token: string) {
  return { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' };
}

// ── Mini login ────────────────────────────────────────────────────────────────

const LoyaltyLogin: React.FC<{ onSuccess: (token: string) => void }> = ({ onSuccess }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const res = await fetch(`${API}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });
      const data = await res.json();
      if (data.success && data.token) {
        localStorage.setItem('loyalteToken', data.token);
        localStorage.setItem('loyalteTokenExpiry', String(data.expires_at ?? Date.now() + 86400000));
        onSuccess(data.token);
      } else {
        setError(data.message || 'Login failed');
      }
    } catch {
      setError('Cannot connect to loyalty server');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-sm mx-auto mt-8 bg-gray-50 rounded-2xl p-8 shadow-sm border border-gray-200">
      <div className="text-center mb-6">
        <Star className="w-10 h-10 text-yellow-500 mx-auto mb-3 fill-yellow-400" />
        <h3 className="text-lg font-bold text-gray-900">Connect Loyalty System</h3>
        <p className="text-sm text-gray-500 mt-1">Sign in with your LoyalteApp staff account</p>
      </div>
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="relative">
          <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="email" value={email} onChange={e => setEmail(e.target.value)}
            placeholder="Staff email" required
            className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
          />
        </div>
        <div className="relative">
          <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="password" value={password} onChange={e => setPassword(e.target.value)}
            placeholder="Password" required
            className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
          />
        </div>
        {error && <p className="text-sm text-red-600 bg-red-50 rounded-lg p-3">{error}</p>}
        <button
          type="submit" disabled={loading}
          className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white py-2.5 rounded-lg font-medium text-sm flex items-center justify-center gap-2"
        >
          <LogIn className="w-4 h-4" />
          {loading ? 'Connecting...' : 'Connect'}
        </button>
      </form>
    </div>
  );
};

// ── Customer list ─────────────────────────────────────────────────────────────

const CustomerList: React.FC<{ token: string }> = ({ token }) => {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<Customer | null>(null);
  const [message, setMessage] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API}/customers`, { headers: authHeader(token) });
      const data = await res.json();
      setCustomers(data.customers ?? []);
    } catch {
      setMessage('Failed to load customers');
    } finally {
      setLoading(false);
    }
  }, [token]);

  useEffect(() => { load(); }, [load]);

  const filtered = customers.filter(c =>
    c.name.toLowerCase().includes(search.toLowerCase()) ||
    c.phone.includes(search) ||
    c.member_id.toLowerCase().includes(search.toLowerCase())
  );

  const handleDelete = async (c: Customer) => {
    try {
      const res = await fetch(`${API}/customers/${c.id}`, {
        method: 'DELETE',
        headers: authHeader(token),
      });
      const data = await res.json();
      if (data.success) {
        setMessage(`${c.name} deleted`);
        load();
      } else {
        setMessage(data.message || 'Delete failed');
      }
    } catch {
      setMessage('Delete failed');
    }
    setDeleteTarget(null);
  };

  return (
    <div>
      {message && (
        <div className="mb-4 p-3 bg-blue-50 text-blue-700 rounded-lg text-sm flex justify-between">
          {message}
          <button onClick={() => setMessage('')} className="ml-2 text-blue-400 hover:text-blue-600">✕</button>
        </div>
      )}

      {/* Confirm delete dialog */}
      {deleteTarget && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-2xl p-6 max-w-sm mx-4 shadow-xl">
            <h4 className="font-bold text-gray-900 mb-2">Delete Customer?</h4>
            <p className="text-sm text-gray-600 mb-4">
              Remove <strong>{deleteTarget.name}</strong> from the loyalty program? This cannot be undone.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => handleDelete(deleteTarget)}
                className="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm font-medium"
              >
                Delete
              </button>
              <button
                onClick={() => setDeleteTarget(null)}
                className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg text-sm font-medium"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="flex items-center gap-3 mb-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text" value={search} onChange={e => setSearch(e.target.value)}
            placeholder="Search name, phone, member ID…"
            className="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent"
          />
        </div>
        <button onClick={load} className="p-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg">
          <RefreshCw className="w-4 h-4" />
        </button>
      </div>

      {loading ? (
        <div className="text-center py-12 text-gray-500">Loading customers…</div>
      ) : filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-400">No customers found</div>
      ) : (
        <div className="space-y-2">
          <p className="text-xs text-gray-500 mb-2">{filtered.length} customer(s)</p>
          {filtered.map(c => (
            <div key={c.id} className="flex items-center justify-between p-3 border border-gray-200 rounded-xl hover:bg-gray-50">
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-0.5">
                  <span className="font-semibold text-gray-900 text-sm truncate">{c.name}</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${TIER_BADGE[c.tier] ?? TIER_BADGE.BRONZE}`}>
                    {c.tier}
                  </span>
                </div>
                <div className="flex items-center gap-3 text-xs text-gray-500">
                  <span>{c.phone}</span>
                  <span>{c.member_id}</span>
                </div>
              </div>
              <div className="flex items-center gap-3 ml-3">
                <span className="font-bold text-red-600 text-sm">{c.points.toLocaleString()} pts</span>
                <button
                  onClick={() => setDeleteTarget(c)}
                  className="text-gray-400 hover:text-red-600 transition-colors"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// ── Receipt codes ─────────────────────────────────────────────────────────────

const ReceiptCodes: React.FC<{ token: string }> = ({ token }) => {
  const [codes, setCodes] = useState<ReceiptCode[]>([]);
  const [loading, setLoading] = useState(true);
  const [generating, setGenerating] = useState(false);
  const [newCode, setNewCode] = useState<ReceiptCode | null>(null);
  const [form, setForm] = useState({ points: '', expiry_days: '28', note: '' });
  const [showForm, setShowForm] = useState(false);
  const [message, setMessage] = useState('');

  const now = Date.now();

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API}/receipt_codes`, { headers: authHeader(token) });
      const data = await res.json();
      setCodes(data.codes ?? []);
    } catch {
      setMessage('Failed to load codes');
    } finally {
      setLoading(false);
    }
  }, [token]);

  useEffect(() => { load(); }, [load]);

  const handleGenerate = async (e: React.FormEvent) => {
    e.preventDefault();
    setGenerating(true);
    try {
      const res = await fetch(`${API}/receipt_codes`, {
        method: 'POST',
        headers: authHeader(token),
        body: JSON.stringify({
          points: parseInt(form.points),
          expiry_days: parseInt(form.expiry_days),
          note: form.note.trim() || null,
        }),
      });
      const data = await res.json();
      if (data.success && data.receipt_code) {
        setNewCode(data.receipt_code);
        setForm({ points: '', expiry_days: '28', note: '' });
        setShowForm(false);
        load();
      } else {
        setMessage(data.message || 'Failed to generate code');
      }
    } catch {
      setMessage('Failed to generate code');
    } finally {
      setGenerating(false);
    }
  };

  const codeStatus = (rc: ReceiptCode) => {
    if (rc.claimed_by) return { label: 'Claimed', color: 'bg-green-100 text-green-700', icon: <CheckCircle className="w-3 h-3" /> };
    if (rc.expires_at < now) return { label: 'Expired', color: 'bg-red-100 text-red-700', icon: <XCircle className="w-3 h-3" /> };
    return { label: 'Available', color: 'bg-blue-100 text-blue-700', icon: <Clock className="w-3 h-3" /> };
  };

  return (
    <div>
      {message && (
        <div className="mb-4 p-3 bg-blue-50 text-blue-700 rounded-lg text-sm flex justify-between">
          {message}
          <button onClick={() => setMessage('')} className="ml-2">✕</button>
        </div>
      )}

      {/* New code result */}
      {newCode && (
        <div className="mb-6 p-5 bg-green-50 border border-green-200 rounded-2xl">
          <p className="text-sm font-semibold text-green-800 mb-2">✅ New Code Generated</p>
          <div className="flex items-center gap-3">
            <code className="text-xl font-bold text-green-900 bg-green-100 px-4 py-2 rounded-xl tracking-wide">
              {newCode.code}
            </code>
            <button
              onClick={() => { navigator.clipboard.writeText(newCode.code); setMessage('Code copied!'); }}
              className="text-xs bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700"
            >
              Copy
            </button>
          </div>
          <p className="text-xs text-green-700 mt-2">
            +{newCode.points} pts · Expires {fmtDate(newCode.expires_at)}
            {newCode.note && ` · ${newCode.note}`}
          </p>
          <button onClick={() => setNewCode(null)} className="mt-2 text-xs text-green-600 underline">Dismiss</button>
        </div>
      )}

      {/* Generate form toggle */}
      <div className="flex items-center justify-between mb-4">
        <h4 className="font-semibold text-gray-700 text-sm">Receipt Codes</h4>
        <div className="flex gap-2">
          <button onClick={load} className="p-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg">
            <RefreshCw className="w-4 h-4" />
          </button>
          <button
            onClick={() => setShowForm(!showForm)}
            className="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
          >
            <Plus className="w-4 h-4" />
            Generate Code
          </button>
        </div>
      </div>

      {/* Generate form */}
      {showForm && (
        <form onSubmit={handleGenerate} className="bg-gray-50 rounded-xl p-4 mb-4 space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Points *</label>
              <input
                type="number" min="1" value={form.points} required
                onChange={e => setForm({ ...form, points: e.target.value })}
                placeholder="e.g. 50"
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Expiry</label>
              <select
                value={form.expiry_days}
                onChange={e => setForm({ ...form, expiry_days: e.target.value })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500"
              >
                <option value="7">7 days</option>
                <option value="14">14 days</option>
                <option value="28">28 days</option>
                <option value="90">90 days</option>
              </select>
            </div>
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Note (optional)</label>
            <input
              type="text" value={form.note}
              onChange={e => setForm({ ...form, note: e.target.value })}
              placeholder="e.g. Happy Hour special"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500"
            />
          </div>
          <div className="flex gap-2">
            <button
              type="submit" disabled={generating}
              className="flex-1 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white py-2 rounded-lg text-sm font-medium"
            >
              {generating ? 'Generating…' : 'Generate'}
            </button>
            <button
              type="button" onClick={() => setShowForm(false)}
              className="px-4 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 rounded-lg text-sm font-medium"
            >
              Cancel
            </button>
          </div>
        </form>
      )}

      {/* Codes list */}
      {loading ? (
        <div className="text-center py-12 text-gray-500">Loading codes…</div>
      ) : codes.length === 0 ? (
        <div className="text-center py-12 text-gray-400">No codes yet</div>
      ) : (
        <div className="space-y-2">
          {codes.map(rc => {
            const status = codeStatus(rc);
            return (
              <div key={rc.id} className="p-3 border border-gray-200 rounded-xl">
                <div className="flex items-center justify-between mb-1">
                  <code className="font-bold text-gray-900 text-sm tracking-wide">{rc.code}</code>
                  <span className={`flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium ${status.color}`}>
                    {status.icon} {status.label}
                  </span>
                </div>
                <div className="flex items-center gap-4 text-xs text-gray-500">
                  <span>+{rc.points} pts</span>
                  <span>Exp: {fmtDate(rc.expires_at)}</span>
                  {rc.claimed_by_name && <span>By: {rc.claimed_by_name}</span>}
                  {rc.note && <span className="italic">{rc.note}</span>}
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

// ── Clover Settings ───────────────────────────────────────────────────────────

const CloverSettings: React.FC<{ token: string }> = ({ token }) => {
  const [cfg, setCfg] = useState<CloverConfig>({
    app_id: '', app_secret: '', access_token: '', merchant_id: '',
    environment: 'sandbox', points_per_dollar: '1', enabled: '0',
  });
  const [logs, setLogs] = useState<CloverLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [messageType, setMessageType] = useState<'ok' | 'err'>('ok');
  const [showLogs, setShowLogs] = useState(false);

  const loadConfig = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API}/clover/config`, { headers: authHeader(token) });
      const data = await res.json();
      if (data.success && data.config) {
        setCfg(prev => ({ ...prev, ...data.config }));
      }
    } catch {
      setMessage('Failed to load Clover config');
      setMessageType('err');
    } finally {
      setLoading(false);
    }
  }, [token]);

  const loadLogs = useCallback(async () => {
    try {
      const res = await fetch(`${API}/clover/logs?limit=30`, { headers: authHeader(token) });
      const data = await res.json();
      setLogs(data.logs ?? []);
    } catch {
      // ignore
    }
  }, [token]);

  useEffect(() => { loadConfig(); }, [loadConfig]);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      const payload: Partial<CloverConfig> = {
        app_id: cfg.app_id,
        merchant_id: cfg.merchant_id,
        environment: cfg.environment,
        points_per_dollar: cfg.points_per_dollar,
        enabled: cfg.enabled,
      };
      // Only send secrets if user typed real values (not masked placeholders)
      if (cfg.access_token && !cfg.access_token.startsWith('••••')) {
        payload.access_token = cfg.access_token;
      }
      if (cfg.app_secret && !cfg.app_secret.startsWith('••••')) {
        payload.app_secret = cfg.app_secret;
      }
      const res = await fetch(`${API}/clover/config`, {
        method: 'PUT',
        headers: authHeader(token),
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.success) {
        setMessage('Settings saved');
        setMessageType('ok');
        loadConfig();
      } else {
        setMessage(data.message || 'Save failed');
        setMessageType('err');
      }
    } catch {
      setMessage('Save failed');
      setMessageType('err');
    } finally {
      setSaving(false);
    }
  };

  const statusBadge = (s: CloverLog['status']) => {
    if (s === 'processed') return 'bg-green-100 text-green-700';
    if (s === 'no_customer') return 'bg-yellow-100 text-yellow-700';
    return 'bg-red-100 text-red-700';
  };

  if (loading) return <div className="text-center py-12 text-gray-500">Loading Clover config…</div>;

  return (
    <div className="space-y-6">
      {message && (
        <div className={`p-3 rounded-lg text-sm flex justify-between ${
          messageType === 'ok' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
        }`}>
          {message}
          <button onClick={() => setMessage('')} className="ml-2">✕</button>
        </div>
      )}

      {/* Webhook URL info */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div className="flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm font-semibold text-blue-900 mb-1">Clover Webhook URL</p>
            <code className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded block break-all">
              https://www.stonephovaldosta.com/loyalteapp/backend/api/clover/webhook
            </code>
            <p className="text-xs text-blue-700 mt-2">
              Paste this URL in your Clover Developer Dashboard → App → Webhooks.
              Subscribe to the <strong>payment.created</strong> event.
            </p>
          </div>
        </div>
      </div>

      {/* Config form */}
      <form onSubmit={handleSave} className="space-y-4">
        <h4 className="font-semibold text-gray-800 text-sm flex items-center gap-2">
          <Zap className="w-4 h-4 text-orange-500" /> Clover API Settings
        </h4>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">App ID</label>
            <input
              type="text" value={cfg.app_id}
              onChange={e => setCfg({ ...cfg, app_id: e.target.value })}
              placeholder="e.g. 2HCEACS0YWQZ8"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
            <input
              type="text" value={cfg.merchant_id}
              onChange={e => setCfg({ ...cfg, merchant_id: e.target.value })}
              placeholder="From Clover merchant dashboard"
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-600 mb-1">Access Token</label>
          <input
            type="password" value={cfg.access_token}
            onChange={e => setCfg({ ...cfg, access_token: e.target.value })}
            placeholder="Paste new token to update (leave blank to keep existing)"
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
          />
          <p className="text-xs text-gray-400 mt-1">
            Get from Clover Sandbox → Merchant Dashboard → Account &amp; Setup → API Tokens
          </p>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-600 mb-1">App Secret</label>
          <input
            type="password" value={cfg.app_secret}
            onChange={e => setCfg({ ...cfg, app_secret: e.target.value })}
            placeholder="Paste new secret to update"
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent"
          />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Environment</label>
            <select
              value={cfg.environment}
              onChange={e => setCfg({ ...cfg, environment: e.target.value as 'sandbox' | 'production' })}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
            >
              <option value="sandbox">Sandbox (testing)</option>
              <option value="production">Production (live)</option>
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Points per $1</label>
            <input
              type="number" min="1" max="100" value={cfg.points_per_dollar}
              onChange={e => setCfg({ ...cfg, points_per_dollar: e.target.value })}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Integration</label>
            <select
              value={cfg.enabled}
              onChange={e => setCfg({ ...cfg, enabled: e.target.value })}
              className={`w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 font-medium ${
                cfg.enabled === '1' ? 'border-green-400 text-green-700 bg-green-50' : 'border-gray-300 text-gray-600'
              }`}
            >
              <option value="0">Disabled</option>
              <option value="1">Enabled</option>
            </select>
          </div>
        </div>

        <button
          type="submit" disabled={saving}
          className="w-full bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white py-2.5 rounded-lg font-medium text-sm flex items-center justify-center gap-2"
        >
          <Settings className="w-4 h-4" />
          {saving ? 'Saving…' : 'Save Clover Settings'}
        </button>
      </form>

      {/* Payment Logs */}
      <div>
        <button
          onClick={() => { setShowLogs(!showLogs); if (!showLogs) loadLogs(); }}
          className="flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900"
        >
          <Receipt className="w-4 h-4" />
          {showLogs ? '▾' : '▸'} Payment Logs (last 30)
        </button>

        {showLogs && (
          <div className="mt-3 space-y-2">
            {logs.length === 0 ? (
              <p className="text-sm text-gray-400 py-4 text-center">No logs yet</p>
            ) : logs.map(log => (
              <div key={log.id} className="p-3 border border-gray-200 rounded-xl text-xs">
                <div className="flex items-center justify-between mb-1">
                  <code className="font-mono text-gray-700">{log.payment_id}</code>
                  <span className={`px-2 py-0.5 rounded-full font-medium ${statusBadge(log.status)}`}>
                    {log.status === 'processed' ? `+${log.points_awarded} pts` : log.status}
                  </span>
                </div>
                <div className="flex flex-wrap gap-x-4 gap-y-0.5 text-gray-500">
                  <span>${(log.amount_cents / 100).toFixed(2)}</span>
                  {log.customer_name && <span>{log.customer_name}</span>}
                  {log.phone && <span>{log.phone}</span>}
                  {log.note && <span className="italic">{log.note}</span>}
                  <span>{fmtDate(log.created_at)}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

// ── Main component ────────────────────────────────────────────────────────────

const LoyaltyAdminTab: React.FC = () => {
  const [token, setToken] = useState<string | null>(() => {
    const t = localStorage.getItem('loyalteToken');
    const exp = parseInt(localStorage.getItem('loyalteTokenExpiry') ?? '0');
    return t && Date.now() < exp ? t : null;
  });
  const [activeTab, setActiveTab] = useState<'customers' | 'codes' | 'clover'>('customers');

  const handleLogout = () => {
    localStorage.removeItem('loyalteToken');
    localStorage.removeItem('loyalteTokenExpiry');
    setToken(null);
  };

  if (!token) {
    return <LoyaltyLogin onSuccess={t => setToken(t)} />;
  }

  return (
    <div>
      {/* Sub-navigation */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex gap-2">
          <button
            onClick={() => setActiveTab('customers')}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              activeTab === 'customers'
                ? 'bg-red-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            <Users className="w-4 h-4" />
            Customers
          </button>
          <button
            onClick={() => setActiveTab('codes')}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              activeTab === 'codes'
                ? 'bg-red-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            <Receipt className="w-4 h-4" />
            Receipt Codes
          </button>
          <button
            onClick={() => setActiveTab('clover')}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              activeTab === 'clover'
                ? 'bg-orange-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            <Zap className="w-4 h-4" />
            Clover POS
          </button>
        </div>
        <button
          onClick={handleLogout}
          className="text-xs text-gray-400 hover:text-red-600 transition-colors"
        >
          Disconnect
        </button>
      </div>

      {activeTab === 'customers' && <CustomerList token={token} />}
      {activeTab === 'codes' && <ReceiptCodes token={token} />}
      {activeTab === 'clover' && <CloverSettings token={token} />}
    </div>
  );
};

export default LoyaltyAdminTab;
