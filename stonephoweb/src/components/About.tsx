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

  return (
    <section
  id="about"
  className="relative py-20 bg-cover bg-center"
  style={{
    backgroundImage: "url('/uploads/ourstory-bg.png')",
  }}
>
  {/* Overlay để blend màu trắng từ top xuống bottom */}
  <div className="absolute inset-0 bg-gradient-to-b from-white/50 to-white/100"></div>
  
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ScrollAnimatedSection animation="fadeInUp" className="text-center mb-16">
          <div>
            <h2 className={`font-bold text-gray-900 mb-4 ${
              isMobileView ? 'text-3xl' : 'text-4xl sm:text-5xl'
            }`}>
              {t('about.heading')}
            </h2>
            <p className={`text-gray-600 max-w-3xl mx-auto leading-relaxed ${
              isMobileView ? 'text-lg' : 'text-xl'
            }`}>
              {t('about.subtitle')}
            </p>
          </div>
        </ScrollAnimatedSection>

        <div className={`grid gap-16 items-center mb-16 ${
          isMobileView ? 'grid-cols-1' : 'lg:grid-cols-2'
        }`}>
          <ScrollAnimatedSection animation="fadeInLeft" delay={200} className={isMobileView ? 'order-2' : ''}>
            <div>
              <img 
                src="https://stonephovaldosta.com/uploads/our-story.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop"
                alt="Pho preparation"
                className={`rounded-2xl shadow-2xl w-full object-cover hover:scale-105 transition-transform duration-500 ${
                  isMobileView ? 'h-64' : 'h-96'
                }`}
              />
            </div>
          </ScrollAnimatedSection>
          <ScrollAnimatedSection animation="fadeInRight" delay={400} className={`space-y-6 ${isMobileView ? 'order-1' : ''}`}>
            <div>
              <h3 className={`font-bold text-gray-900 ${
                isMobileView ? 'text-2xl' : 'text-3xl'
              }`}>
                {t('about.tradition.heading')}
              </h3>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-base' : 'text-lg'
              }`}>
                {t('about.tradition.p1')}
              </p>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-base' : 'text-lg'
              }`}>
                {t('about.tradition.p2')}
              </p>
            </div>
          </ScrollAnimatedSection>
        </div>

        <div className={`grid gap-8 ${
          isMobileView 
            ? 'grid-cols-1' 
            : isTabletView 
            ? 'md:grid-cols-2 lg:grid-cols-3' 
            : 'md:grid-cols-3'
        }`}>
          <ScrollAnimatedSection animation="scaleIn" delay={600}>
            <div className={`text-center bg-white rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 ${
              isMobileView ? 'p-6' : 'p-8'
            }`}>
              <div className={`bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 ${
                isMobileView ? 'w-12 h-12' : 'w-16 h-16'
              }`}>
                <Heart className={`text-red-600 ${isMobileView ? 'w-6 h-6' : 'w-8 h-8'}`} />
              </div>
              <h4 className={`font-bold text-gray-900 mb-4 ${
                isMobileView ? 'text-xl' : 'text-2xl'
              }`}>
                {t('about.cards.love.title')}
              </h4>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-sm' : ''
              }`}>
                {t('about.cards.love.body')}
              </p>
            </div>
          </ScrollAnimatedSection>

          <ScrollAnimatedSection animation="scaleIn" delay={800}>
            <div className={`text-center bg-white rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 ${
              isMobileView ? 'p-6' : 'p-8'
            }`}>
              <div className={`bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 ${
                isMobileView ? 'w-12 h-12' : 'w-16 h-16'
              }`}>
                <Award className={`text-red-600 ${isMobileView ? 'w-6 h-6' : 'w-8 h-8'}`} />
              </div>
              <h4 className={`font-bold text-gray-900 mb-4 ${
                isMobileView ? 'text-xl' : 'text-2xl'
              }`}>
                {t('about.cards.quality.title')}
              </h4>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-sm' : ''
              }`}>
                {t('about.cards.quality.body')}
              </p>
            </div>
          </ScrollAnimatedSection>

          <ScrollAnimatedSection animation="scaleIn" delay={1000}>
            <div className={`text-center bg-white rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 ${
              isMobileView ? 'p-6' : 'p-8'
            } ${isTabletView ? 'md:col-span-2 lg:col-span-1' : ''}`}>
              <div className={`bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 ${
                isMobileView ? 'w-12 h-12' : 'w-16 h-16'
              }`}>
                <Users className={`text-red-600 ${isMobileView ? 'w-6 h-6' : 'w-8 h-8'}`} />
              </div>
              <h4 className={`font-bold text-gray-900 mb-4 ${
                isMobileView ? 'text-xl' : 'text-2xl'
              }`}>
                {t('about.cards.community.title')}
              </h4>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-sm' : ''
              }`}>
                {t('about.cards.community.body')}
              </p>
            </div>
          </ScrollAnimatedSection>
        </div>
      </div>
    </section>
  );
};

export default About;