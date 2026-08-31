import React from 'react';
import { Heart, Award, Users } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';
import { useLanguage } from '../contexts/LanguageContext';

interface AboutProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const About: React.FC<AboutProps> = ({ deviceInfo, forcedDevice }) => {
  const { t } = useLanguage();
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';
  const isTabletView = currentDevice === 'tablet';

  const cardIcons = [
    { Icon: Heart,  key: 'love'      },
    { Icon: Award,  key: 'quality'   },
    { Icon: Users,  key: 'community' },
  ];

  return (
    <section
      id="about"
      className="relative py-20 bg-cover bg-center"
      style={{ backgroundImage: "url('/uploads/ourstory-bg.png')" }}
    >
      {/* Dark overlay */}
      <div className="absolute inset-0" style={{ background: 'linear-gradient(to bottom, rgba(15,13,11,0.75) 0%, rgba(15,13,11,0.92) 100%)' }} />

      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {/* Heading */}
        <ScrollAnimatedSection animation="fadeInUp" className="text-center mb-16">
          <div>
            <span style={{
              display: 'block',
              fontFamily: "'Inter', sans-serif",
              fontSize: '0.6rem',
              fontWeight: 700,
              letterSpacing: '0.25em',
              textTransform: 'uppercase',
              color: '#C9A96E',
              marginBottom: '1rem',
            }}>
              Our Story
            </span>
            <h2
              className={`font-display text-lux-cream mb-5 ${isMobileView ? 'text-3xl' : 'text-4xl sm:text-5xl'}`}
              style={{ fontWeight: 700, lineHeight: 1 }}
            >
              {t('about.heading')}
            </h2>
            <span className="gold-line" style={{ maxWidth: '60px', margin: '0 auto 1.5rem' }} />
            <p className={`text-lux-muted max-w-3xl mx-auto leading-relaxed ${isMobileView ? 'text-base' : 'text-lg'}`}>
              {t('about.subtitle')}
            </p>
          </div>
        </ScrollAnimatedSection>

        {/* Story image + text */}
        <div className={`grid gap-16 items-center mb-20 ${isMobileView ? 'grid-cols-1' : 'lg:grid-cols-2'}`}>
          <ScrollAnimatedSection animation="fadeInLeft" delay={200} className={isMobileView ? 'order-2' : ''}>
            <div style={{ position: 'relative' }}>
              <img
                src="https://stonephovaldosta.com/uploads/our-story.jpeg"
                alt="Pho preparation"
                className={`w-full object-cover hover:scale-105 transition-transform duration-500 ${isMobileView ? 'h-64' : 'h-96'}`}
                style={{ border: '1px solid #2A2520' }}
              />
              {/* Gold corner accent */}
              <div style={{
                position: 'absolute',
                top: '-8px', left: '-8px',
                width: '32px', height: '32px',
                borderTop: '2px solid #C9A96E',
                borderLeft: '2px solid #C9A96E',
              }} />
              <div style={{
                position: 'absolute',
                bottom: '-8px', right: '-8px',
                width: '32px', height: '32px',
                borderBottom: '2px solid #C9A96E',
                borderRight: '2px solid #C9A96E',
              }} />
            </div>
          </ScrollAnimatedSection>

          <ScrollAnimatedSection animation="fadeInRight" delay={400} className={`space-y-6 ${isMobileView ? 'order-1' : ''}`}>
            <h3
              className={`font-display text-lux-cream ${isMobileView ? 'text-2xl' : 'text-3xl'}`}
              style={{ fontWeight: 700, lineHeight: 1.1, marginBottom: '1.25rem' }}
            >
              {t('about.tradition.heading')}
            </h3>
            <p className={`text-lux-muted leading-relaxed ${isMobileView ? 'text-base' : 'text-lg'}`}>
              {t('about.tradition.p1')}
            </p>
            <p className={`text-lux-muted leading-relaxed ${isMobileView ? 'text-base' : 'text-lg'}`}>
              {t('about.tradition.p2')}
            </p>
          </ScrollAnimatedSection>
        </div>

        {/* Cards */}
        <div className={`grid gap-6 ${
          isMobileView ? 'grid-cols-1' :
          isTabletView ? 'md:grid-cols-2 lg:grid-cols-3' :
          'md:grid-cols-3'
        }`}>
          {cardIcons.map(({ Icon, key }, i) => (
            <ScrollAnimatedSection key={key} animation="scaleIn" delay={600 + i * 200}>
              <div
                className={`text-center hover:scale-105 transition-all duration-300 ${
                  isTabletView && i === 2 ? 'md:col-span-2 lg:col-span-1' : ''
                } ${isMobileView ? 'p-6' : 'p-8'}`}
                style={{
                  background: '#1A1714',
                  border: '1px solid #2A2520',
                  transition: 'all 0.3s ease',
                }}
                onMouseEnter={e => (e.currentTarget.style.borderColor = '#C9A96E44')}
                onMouseLeave={e => (e.currentTarget.style.borderColor = '#2A2520')}
              >
                <div
                  className={`flex items-center justify-center mx-auto mb-6 ${isMobileView ? 'w-12 h-12' : 'w-14 h-14'}`}
                  style={{ background: 'rgba(201,169,110,0.1)', border: '1px solid rgba(201,169,110,0.2)' }}
                >
                  <Icon className={`text-lux-gold ${isMobileView ? 'w-5 h-5' : 'w-6 h-6'}`} />
                </div>
                <h4
                  className={`font-display text-lux-cream mb-3 ${isMobileView ? 'text-xl' : 'text-2xl'}`}
                  style={{ fontWeight: 700 }}
                >
                  {t(`about.cards.${key}.title`)}
                </h4>
                <p className={`text-lux-muted leading-relaxed ${isMobileView ? 'text-sm' : 'text-sm'}`}>
                  {t(`about.cards.${key}.body`)}
                </p>
              </div>
            </ScrollAnimatedSection>
          ))}
        </div>

      </div>
    </section>
  );
};

export default About;
