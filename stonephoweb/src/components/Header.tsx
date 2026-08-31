import React, { useState } from 'react';
import { Menu, X } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import { useScrollAnimation } from '../hooks/useScrollAnimation';
import { useLanguage } from '../contexts/LanguageContext';
import LanguageSwitcher from './LanguageSwitcher';

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
      { id: '1', name: 'Home',         href: '#home',     isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '2', name: 'About',        href: '#about',    isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '3', name: 'Menu',         href: '#menu',     isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '4', name: 'Order Online', href: 'https://www.clover.com/online-ordering/stone-pho-valdosta', isExternal: true, isButton: false, buttonStyle: 'primary' },
      { id: '5', name: 'Gallery',      href: '#gallery',  isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '6', name: 'Contact',      href: '#contact',  isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '8', name: '⭐ Rewards',   href: '#loyalty',  isExternal: false, isButton: false, buttonStyle: 'primary' },
      { id: '9', name: '👤 My Account',href: '#my-account',isExternal:false, isButton: false, buttonStyle: 'primary' },
      { id: '7', name: 'Order Delivery',href:'https://order.online/business/stone-pho-lp-14380597',isExternal:true,isButton:true,buttonStyle:'success'},
    ],
  });

  const { t } = useLanguage();
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';

  const navKeyMap: Record<string, string> = {
    '1': 'nav.home', '2': 'nav.about',     '3': 'nav.menu',
    '4': 'nav.orderOnline', '5': 'nav.gallery', '6': 'nav.contact',
    '7': 'nav.orderDelivery', '8': 'nav.rewards', '9': 'nav.myAccount',
  };
  const navName = (item: { id: string; name: string }) =>
    navKeyMap[item.id] ? t(navKeyMap[item.id]) : item.name;

  React.useEffect(() => {
    const saved = localStorage.getItem('headerSettings');
    if (saved) setHeaderSettings(JSON.parse(saved));
  }, []);

  const regularMenuItems = headerSettings.menuItems.filter(i => !i.isButton);
  const buttonMenuItems  = headerSettings.menuItems.filter(i =>  i.isButton);

  const isScrolled = scrollY > 60;
  const headerTransform = isScrollingDown && scrollY > 100 ? '-translate-y-full' : 'translate-y-0';

  /* Bg: fully transparent → dark lux on scroll */
  const bgAlpha = Math.min(scrollY / 80, 0.97);

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-40 backdrop-blur-sm transition-all duration-300 ${headerTransform}`}
      style={{
        backgroundColor: `rgba(15,13,11,${bgAlpha})`,
        borderBottom: isScrolled ? '1px solid #2A2520' : '1px solid transparent',
      }}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center h-16 gap-4">

          {/* Logo */}
          <div className="flex items-center space-x-2 flex-shrink-0">
            <img
              src={headerSettings.logo}
              alt={`${headerSettings.siteName} Logo`}
              className={`object-cover rounded-lg ${isMobileView ? 'h-8 w-auto' : 'h-11 w-auto'}`}
            />
            {headerSettings.showSiteName && (
              <span
                className={`font-display font-bold text-lux-cream ${isMobileView ? 'text-lg' : 'text-xl'}`}
              >
                {headerSettings.siteName}
              </span>
            )}
          </div>

          {/* Desktop nav */}
          <nav className={`${isMobileView ? 'hidden' : 'hidden md:flex'} items-center gap-4 text-sm flex-1 justify-end overflow-hidden`}>
            {regularMenuItems.map(item => (
              <a
                key={item.id}
                href={item.href}
                target={item.isExternal ? '_blank' : '_self'}
                rel={item.isExternal ? 'noopener noreferrer' : undefined}
                className="text-lux-muted hover:text-lux-gold transition-colors font-medium whitespace-nowrap"
                style={{ fontSize: '0.8rem', letterSpacing: '0.01em' }}
              >
                {navName(item)}
              </a>
            ))}
            <LanguageSwitcher />
            {buttonMenuItems.map(item => (
              <button
                key={item.id}
                onClick={() => item.isExternal ? window.open(item.href, '_blank') : (window.location.href = item.href)}
                style={{
                  padding: '0.5rem 1.25rem',
                  border: '1px solid #C9A96E',
                  color: '#C9A96E',
                  background: 'transparent',
                  fontFamily: "'Inter', sans-serif",
                  fontSize: '0.65rem',
                  fontWeight: 700,
                  letterSpacing: '0.15em',
                  textTransform: 'uppercase',
                  cursor: 'pointer',
                  whiteSpace: 'nowrap',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={e => {
                  (e.currentTarget as HTMLElement).style.background = '#C9A96E';
                  (e.currentTarget as HTMLElement).style.color = '#0F0D0B';
                }}
                onMouseLeave={e => {
                  (e.currentTarget as HTMLElement).style.background = 'transparent';
                  (e.currentTarget as HTMLElement).style.color = '#C9A96E';
                }}
              >
                {navName(item)}
              </button>
            ))}
          </nav>

          {/* Mobile hamburger */}
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className={`${isMobileView ? 'block' : 'md:hidden'} p-2 rounded-md text-lux-muted hover:text-lux-gold ml-auto`}
          >
            {isMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>

        {/* Mobile menu */}
        {isMenuOpen && (
          <div
            className={`${isMobileView ? 'block' : 'md:hidden'} py-5 border-t border-lux-border`}
            style={{ backgroundColor: 'rgba(15,13,11,0.98)' }}
          >
            <nav className="flex flex-col space-y-4 px-2">
              {regularMenuItems.map(item => (
                <a
                  key={item.id}
                  href={item.href}
                  target={item.isExternal ? '_blank' : '_self'}
                  rel={item.isExternal ? 'noopener noreferrer' : undefined}
                  className="text-lux-muted hover:text-lux-gold transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {navName(item)}
                </a>
              ))}
              <div className="pt-1"><LanguageSwitcher /></div>
              {buttonMenuItems.map(item => (
                <button
                  key={item.id}
                  onClick={() => {
                    item.isExternal ? window.open(item.href, '_blank') : (window.location.href = item.href);
                    setIsMenuOpen(false);
                  }}
                  style={{
                    textAlign: 'left',
                    padding: '0.7rem 1.25rem',
                    border: '1px solid #C9A96E',
                    color: '#C9A96E',
                    background: 'transparent',
                    fontFamily: "'Inter', sans-serif",
                    fontSize: '0.7rem',
                    fontWeight: 700,
                    letterSpacing: '0.15em',
                    textTransform: 'uppercase',
                    cursor: 'pointer',
                  }}
                >
                  {item.name}
                </button>
              ))}
            </nav>
          </div>
        )}
      </div>
    </header>
  );
};

export default Header;
