import React from 'react';
import { Wrench, Phone, Clock, Mail } from 'lucide-react';

const MaintenanceMode: React.FC = () => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-red-600 via-red-700 to-red-800 flex items-center justify-center px-4">
      <div className="max-w-2xl mx-auto text-center text-white">
        {/* Logo */}
        <div className="mb-8">
          <img 
            src="/logo.png" 
            alt="Stone Pho Logo" 
            className="w-20 h-20 rounded-full mx-auto mb-4 shadow-2xl border-4 border-white/20"
          />
          <h1 className="text-4xl md:text-6xl font-bold mb-2">Stone Pho</h1>
          <p className="text-xl md:text-2xl font-light text-red-200">Vietnamese Restaurant</p>
        </div>

        {/* Maintenance Icon */}
        <div className="mb-8">
          <div className="bg-white/10 backdrop-blur-sm rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6 animate-pulse">
            <Wrench className="w-12 h-12 text-white" />
          </div>
          <h2 className="text-3xl md:text-4xl font-bold mb-4">Website Under Maintenance</h2>
          <p className="text-lg md:text-xl text-red-100 leading-relaxed max-w-lg mx-auto">
            We're currently updating our website to serve you better. 
            Our restaurant is still open for dine-in, takeout, and delivery!
          </p>
        </div>

        {/* Contact Information */}
        <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-8 mb-8">
          <h3 className="text-2xl font-bold mb-6">We're Still Open!</h3>
          
          <div className="grid md:grid-cols-2 gap-6 text-left">
            <div className="flex items-start space-x-3">
              <Phone className="w-6 h-6 text-red-200 flex-shrink-0 mt-1" />
              <div>
                <h4 className="font-semibold mb-1">Call Us</h4>
                <a href="tel:+12293337468" className="text-red-200 hover:text-white transition-colors text-lg">
                  (229) 491-9905
                </a>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <Mail className="w-6 h-6 text-red-200 flex-shrink-0 mt-1" />
              <div>
                <h4 className="font-semibold mb-1">Location</h4>
                <p className="text-red-200">
                  1525 Baytree Rd, #M<br />
                  Valdosta, GA 31602
                </p>
              </div>
            </div>

            <div className="flex items-start space-x-3 md:col-span-2">
              <Clock className="w-6 h-6 text-red-200 flex-shrink-0 mt-1" />
              <div>
                <h4 className="font-semibold mb-1">Hours</h4>
                <div className="text-red-200 space-y-1">
                  <p>Monday: 11:00 AM - 7:00 PM</p>
                  <p>Tuesday: WE CLOSED</p>
                  <p>Wednesday - Saturday: 11:00 AM - 8:45 PM</p>
                  <p>Sunday: 11:00 AM - 6:00 PM</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <a
            href="tel:+12294919905"
            className="bg-white text-red-600 hover:bg-orange-50 px-8 py-4 rounded-full font-bold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
          >
            📞 Call to Order
          </a>
          <a
            href="https://www.clover.com/online-ordering/stone-pho-valdosta"
            target="_blank"
            rel="noopener noreferrer"
            className="bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-full font-bold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
          >
            🛒 Order Online
          </a>
          <a
            href="https://order.online/business/stone-pho-lp-14380597"
            target="_blank"
            rel="noopener noreferrer"
            className="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-full font-bold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
          >
            🚚 Order Delivery
          </a>
        </div>

        {/* Footer */}
        <div className="mt-12 pt-8 border-t border-white/20">
          <p className="text-red-200 text-sm">
            We'll be back online soon! Thank you for your patience.
          </p>
          <p className="text-red-300 text-xs mt-2">
            © 2022 Stone Pho. All rights reserved.
          </p>
        </div>

        {/* Admin Access */}
        <div className="mt-8">
          <a
            href="#admin"
            className="text-red-300 hover:text-white text-sm underline opacity-50 hover:opacity-100 transition-opacity"
          >
            Admin Access
          </a>
        </div>
      </div>
    </div>
  );
};

export default MaintenanceMode;