import React, { useState, useEffect } from 'react';
import { useDeviceDetection } from './hooks/useDeviceDetection';
import DeviceSwitch from './components/DeviceSwitch';
import MaintenanceMode from './components/MaintenanceMode';
import AdminRoute from './components/AdminRoute';
import Header from './components/Header';
import Hero from './components/Hero';
import About from './components/About';
import Menu from './components/Menu';
import Gallery from './components/Gallery';
import Contact from './components/Contact';
import Footer from './components/Footer';
import LoyaltySection from './loyalty/LoyaltySection';

function App() {
  const deviceInfo = useDeviceDetection();
  const [forcedDevice, setForcedDevice] = useState<'mobile' | 'tablet' | 'desktop' | null>(null);
  const [showAdmin, setShowAdmin]     = useState(false);
  const [showLoyalty, setShowLoyalty] = useState(false);

  const [maintenanceMode, setMaintenanceMode] = useState(false);
  const [loadingConfig, setLoadingConfig] = useState(true);

  const handleDeviceSwitch = (device: 'mobile' | 'tablet' | 'desktop') => {
    setForcedDevice(device === deviceInfo.deviceType ? null : device);
  };

  // Check admin route
  useEffect(() => {
    const checkRoutes = () => {
      const path = window.location.pathname;
      const hash = window.location.hash;

      if (path.includes('/admin') || hash.includes('#admin')) {
        setShowAdmin(true);
        setShowLoyalty(false);
      } else if (hash.includes('#loyalty')) {
        setShowLoyalty(true);
        setShowAdmin(false);
      } else {
        setShowAdmin(false);
        setShowLoyalty(false);
      }
    };

    checkRoutes();
    window.addEventListener('hashchange', checkRoutes);
    window.addEventListener('popstate', checkRoutes);

    return () => {
      window.removeEventListener('hashchange', checkRoutes);
      window.removeEventListener('popstate', checkRoutes);
    };
  }, []);

  // Load config.json từ server
  useEffect(() => {
    const fetchConfig = async () => {
      try {
        const res = await fetch('/api/load-config.php');
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.data) {
            setMaintenanceMode(data.data.maintenanceMode || false);
          }
        }
      } catch (err) {
        console.error("Failed to load config:", err);
      } finally {
        setLoadingConfig(false);
      }
    };
    fetchConfig();
  }, []);

  if (loadingConfig) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-600">Loading website...</p>
      </div>
    );
  }

  // PRIORITY 1: Admin Panel
  if (showAdmin) {
    return <AdminRoute />;
  }

  // PRIORITY 2: Loyalty Section
  if (showLoyalty) {
    return (
      <LoyaltySection
        onClose={() => {
          window.location.hash = '';
          setShowLoyalty(false);
        }}
      />
    );
  }

  // PRIORITY 3: Maintenance Mode
  if (maintenanceMode) {
    return <MaintenanceMode />;
  }

  // PRIORITY 4: Normal Website
  return (
    <div className="min-h-screen">
      <Header deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <Hero deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <About deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <Menu deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <Gallery deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <Contact deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <Footer deviceInfo={deviceInfo} forcedDevice={forcedDevice} />
      <DeviceSwitch 
        deviceInfo={deviceInfo} 
        onDeviceSwitch={handleDeviceSwitch}
        forcedDevice={forcedDevice}
      />
    </div>
  );
}

export default App;
