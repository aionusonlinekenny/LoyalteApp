import React from 'react';
import { Monitor, Tablet, Smartphone } from 'lucide-react';
import { DeviceInfo } from '../hooks/useDeviceDetection';

interface DeviceSwitchProps {
  deviceInfo: DeviceInfo;
  onDeviceSwitch: (device: 'mobile' | 'tablet' | 'desktop') => void;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const DeviceSwitch: React.FC<DeviceSwitchProps> = ({ deviceInfo, onDeviceSwitch, forcedDevice }) => {
  return (
    <div className="fixed bottom-4 right-4 z-50 bg-white rounded-full shadow-lg border border-gray-200 p-2 flex space-x-2">
      <button
        onClick={() => onDeviceSwitch('mobile')}
        className={`p-2 rounded-full transition-all duration-200 ${
          (forcedDevice === 'mobile' || (!forcedDevice && deviceInfo.isMobile))
            ? 'bg-orange-600 text-white'
            : 'text-gray-600 hover:bg-gray-100'
        }`}
        title="Mobile View"
      >
        <Smartphone className="w-5 h-5" />
      </button>
      <button
        onClick={() => onDeviceSwitch('tablet')}
        className={`p-2 rounded-full transition-all duration-200 ${
          (forcedDevice === 'tablet' || (!forcedDevice && deviceInfo.isTablet))
            ? 'bg-orange-600 text-white'
            : 'text-gray-600 hover:bg-gray-100'
        }`}
        title="Tablet View"
      >
        <Tablet className="w-5 h-5" />
      </button>
      <button
        onClick={() => onDeviceSwitch('desktop')}
        className={`p-2 rounded-full transition-all duration-200 ${
          (forcedDevice === 'desktop' || (!forcedDevice && deviceInfo.isDesktop))
            ? 'bg-orange-600 text-white'
            : 'text-gray-600 hover:bg-gray-100'
        }`}
        title="Desktop View"
      >
        <Monitor className="w-5 h-5" />
      </button>
    </div>
  );
};

export default DeviceSwitch;