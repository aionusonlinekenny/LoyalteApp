import React from 'react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';
import { useLanguage } from '../contexts/LanguageContext';

interface FooterProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Footer: React.FC<FooterProps> = ({ deviceInfo, forcedDevice }) => {
  const { t } = useLanguage();
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';

  const links = [
    { href: '#home',    label: 'Home'    },
    { href: '#about',   label: 'About'   },
    { href: '#menu',    label: 'Menu'    },
    { href: '#gallery', label: 'Gallery' },
    { href: '#contact', label: 'Contact' },
  ];

  return (
    <footer style={{ backgroundColor: '#0A0908', borderTop: '1px solid #1E1C18' }}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <ScrollAnimatedSection animation="fadeInUp">
          <div className={`grid gap-10 ${isMobileView ? 'grid-cols-1' : 'md:grid-cols-4'}`}>

            {/* Brand */}
            <div className={isMobileView ? '' : 'md:col-span-2'}>
              <div className="flex items-center space-x-3 mb-5">
                <img
                  src="/logo.png"
                  alt="Stone Pho Logo"
                  className={`object-cover ${isMobileView ? 'h-9 w-auto' : 'h-11 w-auto'}`}
                />
                <span className="font-display text-lux-cream text-xl font-bold tracking-wide">
                  Stone Pho
                </span>
              </div>
              <p className={`text-lux-muted leading-relaxed mb-4 ${isMobileView ? 'text-sm' : 'text-sm'}`}>
                {t('footer.description')}
              </p>
              <p className={`text-lux-subtle ${isMobileView ? 'text-sm' : 'text-sm'}`}>
                1525 Baytree Rd, #M<br />Valdosta, GA 31602
              </p>
            </div>

            {/* Quick Links */}
            <div>
              <h3
                className="text-lux-cream font-semibold mb-5"
                style={{ fontSize: '0.7rem', fontWeight: 700, letterSpacing: '0.18em', textTransform: 'uppercase' }}
              >
                {t('footer.quickLinks')}
              </h3>
              <ul className="space-y-3">
                {links.map(l => (
                  <li key={l.href}>
                    <a
                      href={l.href}
                      className="text-lux-muted hover:text-lux-gold transition-colors"
                      style={{ fontSize: '0.85rem' }}
                    >
                      {l.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Contact */}
            <div>
              <h3
                className="text-lux-cream font-semibold mb-5"
                style={{ fontSize: '0.7rem', fontWeight: 700, letterSpacing: '0.18em', textTransform: 'uppercase' }}
              >
                {t('footer.contactLabel')}
              </h3>
              <ul className="space-y-2 text-lux-muted" style={{ fontSize: '0.85rem' }}>
                <li>(229) 491-9905</li>
                <li>stonephovaldosta@gmail.com</li>
                <li className="pt-3" style={{ borderTop: '1px solid #1E1C18', marginTop: '0.75rem' }}>
                  <strong className="text-lux-cream block mb-2" style={{ fontSize: '0.7rem', letterSpacing: '0.12em', textTransform: 'uppercase' }}>
                    {t('footer.hoursLabel')}
                  </strong>
                  <span className="text-lux-muted" style={{ lineHeight: 1.8, display: 'block' }}>
                    {t('footer.hoursData.mon')}<br />
                    {t('footer.hoursData.tue')}<br />
                    {t('footer.hoursData.wedSat')}<br />
                    {t('footer.hoursData.sun')}
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </ScrollAnimatedSection>

        {/* Bottom bar */}
        <ScrollAnimatedSection animation="fadeInUp" delay={200}>
          <div
            className={`mt-12 pt-8 flex justify-between items-center ${isMobileView ? 'flex-col space-y-3' : 'flex-col md:flex-row'}`}
            style={{ borderTop: '1px solid #1E1C18' }}
          >
            <p className="text-lux-subtle" style={{ fontSize: '0.75rem' }}>
              {t('footer.copyright')}
            </p>
            <p className="text-lux-subtle" style={{ fontSize: '0.75rem' }}>
              Made with{' '}
              <span style={{ color: '#C9A96E' }}>♥</span>
              {' '}{t('footer.tagline')}
            </p>
          </div>
        </ScrollAnimatedSection>
      </div>
    </footer>
  );
};

export default Footer;
