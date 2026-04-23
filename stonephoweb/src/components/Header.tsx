import React, { useState } from 'react';
import { Menu, X, Utensils, Star } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import { useScrollAnimation } from '../hooks/useScrollAnimation';

interface HeaderProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Header: React.FC<HeaderProps> = ({ deviceInfo, forcedDevice }) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { scrollY, isScrollingDown } = useScrollAnimation();
  const [headerSettings, setHeaderSettings] = useState({
    logo: '/logo.png',
    siteName: 'Stone Pho',
    showSiteName: false,
    menuItems: [
      { id: '1', name: 'Home', href: '#home', isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '2', name: 'About', href: '#about', isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '3', name: 'Menu', href: '#menu', isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '4', name: 'Order Online', href: 'https://www.clover.com/online-ordering/stone-pho-valdosta', isExternal: true, isButton: false, buttonStyle: 'primary' },
      { id: '5', name: 'Gallery', href: '#gallery', isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '6', name: 'Contact', href: '#contact', isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '7', name: 'Order Delivery', href: 'https://order.online/business/stone-pho-lp-14380597', isExternal: true, isButton: true, buttonStyle: 'success' }
    ]
  });
  
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';

  // Load header settings from localStorage
  React.useEffect(() => {
    const savedSettings = localStorage.getItem('headerSettings');
    if (savedSettings) {
      setHeaderSettings(JSON.parse(savedSettings));
    }
  }, []);

  const regularMenuItems = headerSettings.menuItems.filter(item => !item.isButton);
  const buttonMenuItems = headerSettings.menuItems.filter(item => item.isButton);

  // Header background opacity based on scroll
  const headerOpacity = Math.min(scrollY / 100, 0.95);
  const headerTransform = isScrollingDown && scrollY > 100 ? '-translate-y-full' : 'translate-y-0';

  return (
    <header 
      className={`fixed top-0 left-0 right-0 z-40 backdrop-blur-sm border-b border-gray-100 transition-all duration-300 ${headerTransform}`}
      style={{
        backgroundColor: `rgba(255, 255, 255, ${headerOpacity})`,
      }}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          {/* Logo */}
          <div className="flex items-center space-x-2">
            <img 
              src={headerSettings.logo} 
              alt={`${headerSettings.siteName} Logo`} 
              className={`object-cover rounded-lg ${
                isMobileView ? 'h-8 w-auto' : 'h-12 w-auto'
              }`}
            />
            {headerSettings.showSiteName && (
              <span className={`font-bold text-gray-900 ${
                isMobileView ? 'text-lg' : 'text-xl'
              }`}>
                {headerSettings.siteName}
              </span>
            )}
          </div>

          {/* Desktop Navigation */}
          <nav className={`${isMobileView ? 'hidden' : 'hidden md:flex'} items-center space-x-8`}>
            {regularMenuItems.map((item) => (
              <a
                key={item.id}
                href={item.href}
                target={item.isExternal ? '_blank' : '_self'}
                rel={item.isExternal ? 'noopener noreferrer' : undefined}
                className="text-white hover:text-red-600 transition-colors font-medium"
              >
                {item.name}
              </a>
            ))}
            {buttonMenuItems.map((item) => (
              <button
                key={item.id}
                onClick={() => item.isExternal ? window.open(item.href, '_blank') : window.location.href = item.href}
                className={`px-4 py-2 rounded-full font-medium transition-all duration-300 ${
                  item.buttonStyle === 'success' ? 'bg-green-600 hover:bg-green-700 text-white' :
                  item.buttonStyle === 'secondary' ? 'bg-gray-600 hover:bg-gray-700 text-white' :
                  'bg-orange-600 hover:bg-orange-700 text-white'
                }`}
              >
                {item.name}
              </button>
            ))}
            {/* Loyalty button — always visible */}
            <a
              href="#loyalty"
              className="flex items-center gap-1.5 px-4 py-2 rounded-full font-semibold bg-yellow-400 hover:bg-yellow-500 text-white transition-all duration-300 shadow-sm"
            >
              <Star className="w-4 h-4 fill-white" />
              Loyalty
            </a>
          </nav>

          {/* Mobile menu button */}
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className={`${isMobileView ? 'block' : 'md:hidden'} p-2 rounded-md text-white hover:text-red-600 hover:bg-gray-100`}
          >
            {isMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>

        {/* Mobile Navigation */}
        {isMenuOpen && (
          <div className={`${isMobileView ? 'block' : 'md:hidden'} py-4 border-t border-gray-100`}>
            <nav className="flex flex-col space-y-4">
              {regularMenuItems.map((item) => (
                <a
                  key={item.id}
                  href={item.href}
                  target={item.isExternal ? '_blank' : '_self'}
                  rel={item.isExternal ? 'noopener noreferrer' : undefined}
                  className="text-white hover:text-red-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {item.name}
                </a>
              ))}
              {buttonMenuItems.map((item) => (
                <button
                  key={item.id}
                  onClick={() => {
                    if (item.isExternal) {
                      window.open(item.href, '_blank');
                    } else {
                      window.location.href = item.href;
                    }
                    setIsMenuOpen(false);
                  }}
                  className={`text-left px-4 py-2 rounded-lg font-medium transition-all duration-300 ${
                    item.buttonStyle === 'success' ? 'bg-green-600 hover:bg-green-700 text-white' :
                    item.buttonStyle === 'secondary' ? 'bg-gray-600 hover:bg-gray-700 text-white' :
                    'bg-orange-600 hover:bg-orange-700 text-white'
                  }`}
                >
                  {item.name}
                </button>
              ))}
              {/* Loyalty button — mobile */}
              <a
                href="#loyalty"
                onClick={() => setIsMenuOpen(false)}
                className="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold bg-yellow-400 hover:bg-yellow-500 text-white transition-all duration-300"
              >
                <Star className="w-4 h-4 fill-white" />
                Loyalty — Tích điểm
              </a>
            </nav>
          </div>
        )}
      </div>
    </header>
  );
};

export default Header;