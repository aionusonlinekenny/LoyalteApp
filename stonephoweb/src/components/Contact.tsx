import React, { useEffect, useState } from 'react';
import { MapPin, Phone, Clock, Mail, Instagram, Facebook, Twitter } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';


interface ContactProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Contact: React.FC<ContactProps> = ({ deviceInfo, forcedDevice }) => {
  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';
    // Google Reviews state
  const [reviews, setReviews] = useState<any[]>([]);
  const [currentSlide, setCurrentSlide] = useState(0);

  useEffect(() => {
    const loadReviews = async () => {
      try {
        const res = await fetch('/api/load-reviews.php');
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.data.reviews) {
            setReviews(data.data.reviews);
          }
        }
      } catch (err) {
        console.error("Failed to load reviews:", err);
      }
    };
    loadReviews();
  }, []);
  // Auto slide every 5s
	useEffect(() => {
	  if (reviews.length === 0) return;
	  const interval = setInterval(() => {
		setCurrentSlide(prev => (prev + 1) % Math.ceil(reviews.length / 6));
	  }, 5000);
	  return () => clearInterval(interval);
	}, [reviews]);
	// Chunk reviews into groups of 6
	const chunkedReviews: any[][] = [];
	for (let i = 0; i < reviews.length; i += 6) {
	  chunkedReviews.push(reviews.slice(i, i + 6));
	}
	
  return (
    <section id="contact" className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ScrollAnimatedSection animation="fadeInUp" className="text-center mb-16">
          <div>
            <h2 className={`font-bold text-gray-900 mb-4 ${
              isMobileView ? 'text-3xl' : 'text-4xl sm:text-5xl'
            }`}>
              Visit Us
            </h2>
            <p className={`text-gray-600 max-w-3xl mx-auto leading-relaxed ${
              isMobileView ? 'text-lg' : 'text-xl'
            }`}>
              Come experience the warmth and authentic flavors that make Stone Pho special.
            </p>
          </div>
        </ScrollAnimatedSection>

        <div className={`grid gap-16 ${
          isMobileView ? 'grid-cols-1' : 'lg:grid-cols-2'
        }`}>
          {/* Contact Information */}
          <ScrollAnimatedSection animation="fadeInLeft" delay={200} className="space-y-8">
          <div>
            <div className="flex items-start space-x-4">
              <div className={`bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 ${
                isMobileView ? 'w-10 h-10' : 'w-12 h-12'
              }`}>
                <MapPin className={`text-red-600 ${isMobileView ? 'w-5 h-5' : 'w-6 h-6'}`} />
              </div>
              <div>
                <h3 className={`font-bold text-gray-900 mb-2 ${
                  isMobileView ? 'text-lg' : 'text-xl'
                }`}>
                  Location
                </h3>
                <p className={`text-gray-600 leading-relaxed ${
                  isMobileView ? 'text-sm' : ''
                }`}>
                  1525 Baytree Rd, #M<br />
                  Valdosta, GA 31602
                </p>
              </div>
            </div>

            <div className="flex items-start space-x-4">
              <div className={`bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 ${
                isMobileView ? 'w-10 h-10' : 'w-12 h-12'
              }`}>
                <Phone className={`text-red-600 ${isMobileView ? 'w-5 h-5' : 'w-6 h-6'}`} />
              </div>
              <div>
                <h3 className={`font-bold text-gray-900 mb-2 ${
                  isMobileView ? 'text-lg' : 'text-xl'
                }`}>
                  Phone
                </h3>
                <p className={`text-gray-600 ${isMobileView ? 'text-sm' : ''}`}>
                  (229) 491-9905
                </p>
              </div>
            </div>

            <div className="flex items-start space-x-4">
              <div className={`bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 ${
                isMobileView ? 'w-10 h-10' : 'w-12 h-12'
              }`}>
                <Mail className={`text-red-600 ${isMobileView ? 'w-5 h-5' : 'w-6 h-6'}`} />
              </div>
              <div>
                <h3 className={`font-bold text-gray-900 mb-2 ${
                  isMobileView ? 'text-lg' : 'text-xl'
                }`}>
                  Email
                </h3>
                <p className={`text-gray-600 ${isMobileView ? 'text-sm' : ''}`}>
                  stonephovaldosta@gmail.com
                </p>
              </div>
            </div>

            <div className="flex items-start space-x-4">
              <div className={`bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 ${
                isMobileView ? 'w-10 h-10' : 'w-12 h-12'
              }`}>
                <Clock className={`text-red-600 ${isMobileView ? 'w-5 h-5' : 'w-6 h-6'}`} />
              </div>
              <div>
                <h3 className={`font-bold text-gray-900 mb-2 ${
                  isMobileView ? 'text-lg' : 'text-xl'
                }`}>
                  Hours
                </h3>
                <div className={`text-gray-600 space-y-1 ${
                  isMobileView ? 'text-sm' : ''
                }`}>
                  <p>Monday: 11:00 AM - 7:00 PM</p>
                  <p>Tuesday: WE CLOSED</p>
                  <p>Wednesday - Saturday: 11:00 AM - 8:45 PM</p>
                  <p>Sunday: 11:00 AM - 6:00 PM</p>
                </div>
              </div>
            </div>

            {/* Social Media */}
            <div className="pt-8">
              <h3 className={`font-bold text-gray-900 mb-4 ${
                isMobileView ? 'text-lg' : 'text-xl'
              }`}>
                Follow Us
              </h3>
              <div className="flex space-x-4">
                <a
                  href="#"
                  className={`bg-orange-100 rounded-full flex items-center justify-center hover:bg-orange-600 hover:text-white transition-all duration-300 ${
                    isMobileView ? 'w-10 h-10' : 'w-12 h-12'
                  }`}
                >
                  <Instagram className={isMobileView ? 'w-5 h-5' : 'w-6 h-6'} />
                </a>
                <a
                  href="#"
                  className={`bg-orange-100 rounded-full flex items-center justify-center hover:bg-orange-600 hover:text-white transition-all duration-300 ${
                    isMobileView ? 'w-10 h-10' : 'w-12 h-12'
                  }`}
                >
                  <Facebook className={isMobileView ? 'w-5 h-5' : 'w-6 h-6'} />
                </a>
                <a
                  href="#"
                  className={`bg-orange-100 rounded-full flex items-center justify-center hover:bg-orange-600 hover:text-white transition-all duration-300 ${
                    isMobileView ? 'w-10 h-10' : 'w-12 h-12'
                  }`}
                >
                  <Twitter className={isMobileView ? 'w-5 h-5' : 'w-6 h-6'} />
                </a>
              </div>
            </div>
          </div>
          </ScrollAnimatedSection>

          {/* Contact Form */}
          <ScrollAnimatedSection animation="fadeInRight" delay={400}>
            <div className={`bg-gray-50 rounded-2xl ${
              isMobileView ? 'p-6' : 'p-8'
            }`}>
              <h3 className={`font-bold text-gray-900 mb-6 ${
                isMobileView ? 'text-xl' : 'text-2xl'
              }`}>
                Get in Touch
              </h3>
              <form className="space-y-6">
                <div className={`grid gap-6 ${
                  isMobileView ? 'grid-cols-1' : 'md:grid-cols-2'
                }`}>
                  <div>
                    <label htmlFor="firstName" className={`block font-medium text-gray-700 mb-2 ${
                      isMobileView ? 'text-sm' : 'text-sm'
                    }`}>
                      First Name
                    </label>
                    <input
                      type="text"
                      id="firstName"
                      className={`w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 ${
                        isMobileView ? 'px-3 py-2 text-sm' : 'px-4 py-3'
                      }`}
                      placeholder="Your first name"
                    />
                  </div>
                  <div>
                    <label htmlFor="lastName" className={`block font-medium text-gray-700 mb-2 ${
                      isMobileView ? 'text-sm' : 'text-sm'
                    }`}>
                      Last Name
                    </label>
                    <input
                      type="text"
                      id="lastName"
                      className={`w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 ${
                        isMobileView ? 'px-3 py-2 text-sm' : 'px-4 py-3'
                      }`}
                      placeholder="Your last name"
                    />
                  </div>
                </div>
                <div>
                  <label htmlFor="email" className={`block font-medium text-gray-700 mb-2 ${
                    isMobileView ? 'text-sm' : 'text-sm'
                  }`}>
                    Email
                  </label>
                  <input
                    type="email"
                    id="email"
                    className={`w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 ${
                      isMobileView ? 'px-3 py-2 text-sm' : 'px-4 py-3'
                    }`}
                    placeholder="your@email.com"
                  />
                </div>
                <div>
                  <label htmlFor="message" className={`block font-medium text-gray-700 mb-2 ${
                    isMobileView ? 'text-sm' : 'text-sm'
                  }`}>
                    Message
                  </label>
                  <textarea
                    id="message"
                    rows={4}
                    className={`w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 ${
                      isMobileView ? 'px-3 py-2 text-sm' : 'px-4 py-3'
                    }`}
                    placeholder="Tell us how we can help you..."
                  ></textarea>
                </div>
                <button
                  type="submit"
                  className={`w-full bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 ${
                    isMobileView ? 'py-2 text-sm' : 'py-3'
                  }`}
                >
                  Send Message
                </button>
              </form>
            </div>
          </ScrollAnimatedSection>
        </div>

        {/* Google Map */}
<ScrollAnimatedSection animation="fadeInUp" delay={600} className="mt-16">
  <div className="bg-gray-50 rounded-2xl p-6 overflow-hidden">
    <h3
      className={`font-bold text-gray-900 mb-6 text-center ${
        isMobileView ? "text-xl" : "text-2xl"
      }`}
    >
      Find Us on Map
    </h3>
    <div className="relative">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12960886.322373796!2d-98.87333749304216!3d37.52492263122469!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x88ee67435e1c23b3%3A0xf9361cf365602dd0!2sStone%20Pho!5e0!3m2!1sen!2sus!4v1755644241074!5m2!1sen!2sus"
        width="100%"
        height={isMobileView ? "300" : "400"}
        style={{ border: 0 }}
        allowFullScreen
        loading="lazy"
        referrerPolicy="no-referrer-when-downgrade"
        className="rounded-xl shadow-lg relative z-0"
        title="Stone Pho Location"
      ></iframe>

      {/* Map overlay with address */}
      <div className="absolute top-4 left-4 bg-white/95 backdrop-blur-sm rounded-lg p-3 shadow-lg max-w-xs z-10">
        <div className="flex items-start space-x-2">
          <MapPin className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
          <div>
            <h4 className="font-semibold text-gray-900 text-sm">Stone Pho</h4>
            <p className="text-xs text-gray-600 leading-relaxed">
              1525 Baytree Rd, #M
              <br />
              Valdosta, GA 31602
            </p>
            <p className="text-xs text-red-600 font-medium mt-1">
              (229) 491-9905
            </p>
          </div>
        </div>
      </div>

      {/* Directions button */}
      <div className="absolute bottom-4 right-4 z-10">
        <button
          onClick={() =>
            window.open(
              "https://www.google.com/maps/dir//1525+Baytree+Rd+%23M,+Valdosta,+GA+31602",
              "_blank"
            )
          }
          className="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 transform hover:scale-105 shadow-lg text-sm"
        >
          Get Directions
        </button>
      </div>
    </div>

    {/* Quick info below map */}
    <div
      className={`grid gap-4 mt-6 ${
        isMobileView ? "grid-cols-1" : "md:grid-cols-3"
      }`}
    >
      <div className="text-center bg-white rounded-lg p-4">
        <div className="text-red-600 font-semibold text-sm mb-1">
          Distance from VSU
        </div>
        <div className="text-gray-900 font-bold">2.5 miles</div>
        <div className="text-gray-500 text-xs">5 min drive</div>
      </div>
      <div className="text-center bg-white rounded-lg p-4">
        <div className="text-red-600 font-semibold text-sm mb-1">Parking</div>
        <div className="text-gray-900 font-bold">Free</div>
        <div className="text-gray-500 text-xs">Ample space</div>
      </div>
      <div className="text-center bg-white rounded-lg p-4">
        <div className="text-red-600 font-semibold text-sm mb-1">
          Accessibility
        </div>
        <div className="text-gray-900 font-bold">Wheelchair</div>
        <div className="text-gray-500 text-xs">Accessible</div>
      </div>
    </div>
  </div>
</ScrollAnimatedSection>


        {/* Google Reviews Section */}
        <ScrollAnimatedSection animation="fadeInUp" delay={800} className="mt-16">
          <div className="bg-white rounded-2xl shadow-lg p-8">
            <div className="text-center mb-8">
              <h3 className={`font-bold text-gray-900 mb-4 ${
                isMobileView ? 'text-2xl' : 'text-3xl'
              }`}>
                What Our Customers Say
              </h3>
              <div className="flex items-center justify-center space-x-2 mb-4">
                <div className="flex space-x-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <svg
                      key={star}
                      className="w-6 h-6 text-yellow-400 fill-current"
                      viewBox="0 0 24 24"
                    >
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                  ))}
                </div>
                <span className="text-gray-600 font-medium">4.8 out of 5 stars</span>
              </div>
              <p className="text-gray-500">Based on 550+ Google Reviews</p>
            </div>

            {/* Reviews Slider */}
			<div className="relative overflow-hidden">
			  {chunkedReviews.map((group, index) => (
				<div
				  key={index}
				  className={`grid gap-6 transition-opacity duration-700 ${
					index === currentSlide
					  ? "grid-cols-1 md:grid-cols-2 lg:grid-cols-3 opacity-100 relative"
					  : "opacity-0 absolute"
				  }`}
				>
				  {group.map((review, idx) => (
					<div key={idx} className="bg-gray-50 rounded-xl p-6">
					  <div className="flex items-center mb-4">
						<div className={`w-12 h-12 ${review.bg} rounded-full flex items-center justify-center`}>
						  <span className={`${review.color} font-bold text-lg`}>{review.initial}</span>
						</div>
						<div className="ml-3">
						  <h4 className="font-semibold text-gray-900">{review.name}</h4>
						  <div className="flex space-x-1">
							{[1, 2, 3, 4, 5].map((star) => (
							  <svg key={star} className="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 24 24">
								<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
							  </svg>
							))}
						  </div>
						</div>
					  </div>
					  <p className="text-gray-600 text-sm leading-relaxed">
						"{review.text}"
					  </p>
					  <p className="text-gray-400 text-xs mt-3">{review.date}</p>
					</div>
				  ))}
				</div>
			  ))}

			  {/* Dots Navigation */}
			  <div className="flex justify-center mt-6 space-x-2">
				{chunkedReviews.map((_, idx) => (
				  <button
					key={idx}
					className={`w-3 h-3 rounded-full ${idx === currentSlide ? "bg-red-600" : "bg-gray-300"}`}
					onClick={() => setCurrentSlide(idx)}
				  />
				))}
			  </div>
			</div>


            {/* Call to Action */}
            <div className="text-center mt-8 pt-6 border-t border-gray-200">
              <p className="text-gray-600 mb-4">
                Have you dined with us? We'd love to hear about your experience!
              </p>
              <div className={`flex gap-4 justify-center ${
                isMobileView ? 'flex-col items-center' : 'flex-row'
              }`}>
                <a
                  href="https://www.google.com/search?q=stone+pho+valdosta+reviews"
                  target="_blank"
                  rel="noopener noreferrer"
                  className={`bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-300 transform hover:scale-105 ${
                    isMobileView ? 'px-6 py-2 text-sm w-full max-w-xs' : 'px-6 py-3'
                  }`}
                >
                  📝 Write a Review
                </a>
                <a
                  href="https://www.google.com/maps/place/Stone+Pho/@30.8324,-83.2784,17z"
                  target="_blank"
                  rel="noopener noreferrer"
                  className={`border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg font-medium transition-all duration-300 ${
                    isMobileView ? 'px-6 py-2 text-sm w-full max-w-xs' : 'px-6 py-3'
                  }`}
                >
                  ⭐ View All Reviews
                </a>
              </div>
            </div>
          </div>
        </ScrollAnimatedSection>
      </div>
    </section>
  );
};

export default Contact;