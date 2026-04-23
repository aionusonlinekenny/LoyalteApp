import React from 'react';
import { useState } from 'react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';

interface GalleryProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Gallery: React.FC<GalleryProps> = ({ deviceInfo, forcedDevice }) => {
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';
  const isTabletView = currentDevice === 'tablet';

  const [galleryImages, setGalleryImages] = useState([
    {
      id: '1',
      url: 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
      alt: 'Fresh pho bowl',
      title: 'Signature Pho',
      description: 'Our famous 48-hour simmered broth'
    },
    {
      id: '2',
      url: 'https://images.pexels.com/photos/1410235/pexels-photo-1410235.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
      alt: 'Vietnamese spring rolls',
      title: 'Fresh Spring Rolls',
      description: 'Hand-rolled with fresh herbs'
    },
    {
      id: '3',
      url: 'https://images.pexels.com/photos/1633525/pexels-photo-1633525.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
      alt: 'Vietnamese herbs and vegetables',
      title: 'Fresh Ingredients',
      description: 'Authentic Vietnamese herbs and vegetables'
    },
    {
      id: '4',
      url: 'https://images.pexels.com/photos/1640774/pexels-photo-1640774.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
      alt: 'Restaurant interior',
      title: 'Cozy Atmosphere',
      description: 'Warm and welcoming dining space'
    },
    {
      id: '5',
      url: 'https://images.pexels.com/photos/1640772/pexels-photo-1640772.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
      alt: 'Vietnamese banh mi',
      title: 'Banh Mi Sandwich',
      description: 'Crispy baguette with Vietnamese flavors'
    },
    {
      id: '6',
      url: 'https://images.pexels.com/photos/1640773/pexels-photo-1640773.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop',
      alt: 'Vietnamese noodle soup',
      title: 'Specialty Noodles',
      description: 'Various Vietnamese noodle dishes'
    },
  ]);

  // Load gallery images from localStorage (updated by admin)
  React.useEffect(() => {
    // Load gallery items from server for production hosting
    const loadGalleryFromServer = async () => {
      try {
        const response = await fetch('/api/load-gallery.php');
        if (response.ok) {
          const data = await response.json();
          if (data.success && data.data && data.data.galleryItems) {
            setGalleryImages(data.data.galleryItems);
            return;
          }
        }
        throw new Error('Failed to load from server');
      } catch (error) {
        console.warn('Failed to load gallery from server, using defaults:', error);
        // Keep default gallery images
      }
    };

    loadGalleryFromServer();
  }, []);

  return (
    <section id="gallery" className="py-20 bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ScrollAnimatedSection animation="fadeInUp" className="text-center mb-16">
          <div>
            <h2 className={`font-bold text-gray-900 mb-4 ${
              isMobileView ? 'text-3xl' : 'text-4xl sm:text-5xl'
            }`}>
              Gallery
            </h2>
            <p className={`text-gray-600 max-w-3xl mx-auto leading-relaxed ${
              isMobileView ? 'text-lg' : 'text-xl'
            }`}>
              Step into our world through these moments that capture the essence of Stone Pho.
            </p>
          </div>
        </ScrollAnimatedSection>

        <div className={`grid gap-6 ${
          isMobileView 
            ? 'grid-cols-1' 
            : isTabletView 
            ? 'grid-cols-2' 
            : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
        }`}>
          {galleryImages.map((image, index) => (
            <ScrollAnimatedSection key={index} animation="scaleIn" delay={index * 150}>
              <div className="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500">
                <img
                  src={image.url}
                  alt={image.alt}
                  className={`w-full object-cover group-hover:scale-110 transition-transform duration-500 ${
                    isMobileView ? 'h-64' : 'h-80'
                  }`}
                />
                <div className="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-all duration-300"></div>
                <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                  <div className="text-white text-center">
                    <p className={`font-semibold ${
                      isMobileView ? 'text-base' : 'text-lg'
                    }`}>
                      {image.title || image.alt}
                    </p>
                    {image.description && (
                      <p className="text-sm mt-1 opacity-90">{image.description}</p>
                    )}
                  </div>
                </div>
              </div>
            </ScrollAnimatedSection>
          ))}
        </div>

        <ScrollAnimatedSection animation="fadeInUp" delay={600} className="text-center mt-12">
          <div>
            <button className={`bg-orange-600 hover:bg-orange-700 text-white rounded-full font-semibold transition-all duration-300 transform hover:scale-105 ${
              isMobileView ? 'px-6 py-2 text-sm' : 'px-8 py-3'
            }`}>
              View More Photos
            </button>
          </div>
        </ScrollAnimatedSection>
      </div>
    </section>
  );
};

export default Gallery;