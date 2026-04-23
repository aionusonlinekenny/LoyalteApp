import React from 'react';
import { Heart, Award, Users } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';

interface AboutProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const About: React.FC<AboutProps> = ({ deviceInfo, forcedDevice }) => {
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
              Our Story
            </h2>
            <p className={`text-gray-600 max-w-3xl mx-auto leading-relaxed ${
              isMobileView ? 'text-lg' : 'text-xl'
            }`}>
              Founded with a passion for authentic Vietnamese cuisine, Stone Pho 
              has been serving traditional flavors and bringing families together since our doors first opened.
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
                Authentic Vietnamese Tradition
              </h3>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-base' : 'text-lg'
              }`}>
                Every bowl at Stone Pho is a testament to our commitment to authenticity. We use traditional 
                recipes passed down through generations, ensuring that each spoonful delivers the true taste 
                of Vietnam with fresh ingredients and time-honored cooking methods.
              </p>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-base' : 'text-lg'
              }`}>
                Our skilled chefs prepare each broth with care, simmering bones and spices for hours to 
                achieve the perfect depth of flavor that makes our pho truly exceptional.
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
                Made with Love
              </h4>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-sm' : ''
              }`}>
                Every dish is prepared with care and attention to detail, ensuring the perfect 
                balance of flavors and authentic Vietnamese taste.
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
                Premium Quality
              </h4>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-sm' : ''
              }`}>
                We source only the freshest ingredients and highest quality meats, ensuring exceptional 
                taste in every bowl we serve.
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
                Community Focus
              </h4>
              <p className={`text-gray-600 leading-relaxed ${
                isMobileView ? 'text-sm' : ''
              }`}>
                More than just a restaurant, we're a gathering place where friendships 
                are formed and memories are made.
              </p>
            </div>
          </ScrollAnimatedSection>
        </div>
      </div>
    </section>
  );
};

export default About;