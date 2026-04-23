import React from 'react';
import { Utensils, Heart } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';

interface FooterProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Footer: React.FC<FooterProps> = ({ deviceInfo, forcedDevice }) => {
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';

  return (
    <footer
      className="relative text-black py-12 bg-cover bg-center"
      style={{
        backgroundImage: "url('/uploads/hero-bg.jpg')",
      }}
    >
      {/* Overlay gradient trắng ở trên → trong suốt ở dưới */}
      <div className="absolute inset-0 bg-gradient-to-b from-white/100 to-transparent"></div>

      {/* Nội dung Footer */}
      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ScrollAnimatedSection animation="fadeInUp">
          <div
            className={`grid gap-8 ${
              isMobileView ? 'grid-cols-1' : 'md:grid-cols-4'
            }`}
          >
            {/* Logo and Description */}
            <div className={isMobileView ? '' : 'md:col-span-2'}>
              <div className="flex items-center space-x-2 mb-4">
                <img
                  src="/logo.png"
                  alt="Stone Pho Logo"
                  className={`object-cover rounded-lg ${
                    isMobileView ? 'h-10 w-auto' : 'h-12 w-auto'
                  }`}
                />
              </div>
              <p
                className={`leading-relaxed mb-4 ${
                  isMobileView ? 'text-sm' : ''
                }`}
              >
                Authentic Vietnamese cuisine with 48-hour simmered broths and
                traditional recipes. Experience the true taste of Vietnam at
                Stone Pho.
              </p>
              <p className={`${isMobileView ? 'text-sm' : ''}`}>
                1525 Baytree Rd, #M
                <br />
                Valdosta, GA 31602
              </p>
            </div>

            {/* Quick Links */}
            <div>
              <h3
                className={`font-semibold mb-4 ${
                  isMobileView ? 'text-base' : 'text-lg'
                }`}
              >
                Quick Links
              </h3>
              <ul className="space-y-2">
                <li>
                  <a
                    href="#home"
                    className={`hover:text-red-600 transition-colors ${
                      isMobileView ? 'text-sm' : ''
                    }`}
                  >
                    Home
                  </a>
                </li>
                <li>
                  <a
                    href="#about"
                    className={`hover:text-red-600 transition-colors ${
                      isMobileView ? 'text-sm' : ''
                    }`}
                  >
                    About
                  </a>
                </li>
                <li>
                  <a
                    href="#menu"
                    className={`hover:text-red-600 transition-colors ${
                      isMobileView ? 'text-sm' : ''
                    }`}
                  >
                    Menu
                  </a>
                </li>
                <li>
                  <a
                    href="#gallery"
                    className={`hover:text-red-600 transition-colors ${
                      isMobileView ? 'text-sm' : ''
                    }`}
                  >
                    Gallery
                  </a>
                </li>
                <li>
                  <a
                    href="#contact"
                    className={`hover:text-red-600 transition-colors ${
                      isMobileView ? 'text-sm' : ''
                    }`}
                  >
                    Contact
                  </a>
                </li>
              </ul>
            </div>

            {/* Contact Info */}
            <div>
              <h3
                className={`font-semibold mb-4 ${
                  isMobileView ? 'text-base' : 'text-lg'
                }`}
              >
                Contact
              </h3>
              <ul
                className={`space-y-2 ${
                  isMobileView ? 'text-sm' : ''
                }`}
              >
                <li>(229) 491-9905</li>
                <li>stonephovaldosta@gmail.com</li>
                <li className="pt-2">
                  <strong className="text-black">Hours:</strong>
                  <br />
                  Mon: 11:00 AM - 7:00 PM
                  <br />
                  Tue: WE CLOSED
                  <br />
                  Wed - Sat: 11:00 AM - 8:45 PM
                  <br />
                  Sunday: 11:00 AM - 6:00 PM
                </li>
              </ul>
            </div>
          </div>
        </ScrollAnimatedSection>

        <ScrollAnimatedSection animation="fadeInUp" delay={200}>
          <div
            className={`border-t border-gray-300 mt-8 pt-8 flex justify-between items-center ${
              isMobileView
                ? 'flex-col space-y-4'
                : 'flex-col md:flex-row'
            }`}
          >
            <p className={`${isMobileView ? 'text-xs' : 'text-sm'}`}>
              © 2024 Stone Pho. All rights reserved.
            </p>
            <div
              className={`flex items-center space-x-1 ${
                isMobileView
                  ? 'text-xs mt-4 md:mt-0'
                  : 'text-sm mt-4 md:mt-0'
              }`}
            >
              <span>Made with</span>
              <Heart className="w-4 h-4 text-red-500" />
              <span>for pho lovers</span>
            </div>
          </div>
        </ScrollAnimatedSection>
      </div>
    </footer>
  );
};

export default Footer;
