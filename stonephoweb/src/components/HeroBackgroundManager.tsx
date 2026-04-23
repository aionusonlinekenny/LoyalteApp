import React, { useState, useEffect } from 'react';
import { Save, RotateCcw, Eye, Upload, Loader } from 'lucide-react';
import ImageUpload from './ImageUpload';

const HeroBackgroundManager: React.FC = () => {
  const [currentBackground, setCurrentBackground] = useState('');
  const [previewBackground, setPreviewBackground] = useState('');
  const [isPreviewMode, setIsPreviewMode] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [isSaving, setIsSaving] = useState(false);

  const defaultBackgrounds = [
    {
      name: 'Pho Bowl (Default)',
      url: 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&fit=crop',
      description: 'Classic pho bowl with steam'
    },
    {
      name: 'Vietnamese Restaurant',
      url: 'https://images.pexels.com/photos/1640774/pexels-photo-1640774.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&fit=crop',
      description: 'Cozy restaurant interior'
    },
    {
      name: 'Fresh Ingredients',
      url: 'https://images.pexels.com/photos/1633525/pexels-photo-1633525.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&fit=crop',
      description: 'Fresh Vietnamese herbs and vegetables'
    },
    {
      name: 'Cooking Process',
      url: 'https://images.pexels.com/photos/1640772/pexels-photo-1640772.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&fit=crop',
      description: 'Chef preparing Vietnamese dishes'
    },
    {
      name: 'Spring Rolls',
      url: 'https://images.pexels.com/photos/1410235/pexels-photo-1410235.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&fit=crop',
      description: 'Fresh Vietnamese spring rolls'
    },
    {
      name: 'Dark Restaurant',
      url: 'https://images.pexels.com/photos/941861/pexels-photo-941861.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&fit=crop',
      description: 'Elegant dark restaurant ambiance'
    }
  ];

  // Load current background
  useEffect(() => {
    const loadCurrentBackground = () => {
      // Load from localStorage
      const savedBackground = localStorage.getItem('heroBackground');
      if (savedBackground) {
        setCurrentBackground(savedBackground);
        setPreviewBackground(savedBackground);
      } else {
        const defaultBg = defaultBackgrounds[0].url;
        setCurrentBackground(defaultBg);
        setPreviewBackground(defaultBg);
        localStorage.setItem('heroBackground', defaultBg);
      }
    };

    loadCurrentBackground();
  }, []);

  // Upload background to server
  const uploadBackgroundToServer = async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('image', file);

    try {
      const apiUrl = '/api/upload-image.php';
      
      console.log('Uploading hero background to:', apiUrl);
      console.log('File info:', {
        name: file.name,
        size: file.size,
        type: file.type
      });
      
      const response = await fetch(apiUrl, {
        method: 'POST',
        body: formData,
        mode: 'cors'
      });

      console.log('Response status:', response.status);
      
      if (!response.ok) {
        const errorText = await response.text();
        console.error('Upload error response:', errorText);
        throw new Error(`Upload failed: ${response.status} - ${errorText}`);
      }

      const data = await response.json();
      console.log('Upload response:', data);
      
      if (!data.success) {
        console.error('Server error:', data);
        throw new Error(data.message || data.error || 'Upload failed');
      }
      
      return data.imageUrl;
    } catch (error) {
      console.error('Upload error:', error);
      throw error;
    }
  };

  // Handle file upload for custom background
  const handleCustomBackgroundUpload = async (imageUrl: string) => {
    // If it's a file upload (base64 or blob), we need to convert and upload
    if (imageUrl.startsWith('data:') || imageUrl.startsWith('blob:')) {
      try {
        setIsUploading(true);
        setUploadProgress(50);
        
        // Convert to file and upload
        const response = await fetch(imageUrl);
        const blob = await response.blob();
        const file = new File([blob], `hero-background-${Date.now()}.jpg`, { type: 'image/jpeg' });
        
        setUploadProgress(75);
        const uploadedUrl = await uploadBackgroundToServer(file);
        
        setUploadProgress(100);
        previewImage(uploadedUrl);
        
        setTimeout(() => {
          setIsUploading(false);
          setUploadProgress(0);
        }, 500);
        
      } catch (error) {
        setIsUploading(false);
        setUploadProgress(0);
        alert('Lỗi upload hình nền. Vui lòng thử lại!');
        console.error('Background upload error:', error);
      }
    } else {
      // It's already a URL, use directly
      previewImage(imageUrl);
    }
  };

  // Save background
  const saveBackground = async () => {
    setIsSaving(true);
    
    try {
      // Save to server - PRODUCTION HOSTING
      const response = await fetch('/api/hero-config.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          background: previewBackground
        })
      });

      if (!response.ok) {
        throw new Error(`Server error: ${response.status}`);
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.error || 'Failed to save background');
      }

      // Update local state
      setCurrentBackground(previewBackground);
      setIsPreviewMode(false);
      
      // Trigger custom event to update Hero component
      window.dispatchEvent(new CustomEvent('heroBackgroundChanged', {
        detail: { background: previewBackground }
      }));
      
      alert('Hero background updated successfully on server!');
      
    } catch (error) {
      console.error('Failed to save hero background:', error);
      alert('Error saving hero background. Please try again.');
    } finally {
      setIsSaving(false);
    }
  };

  // Reset to default
  const resetToDefault = async () => {
    setIsSaving(true);
    const defaultBg = defaultBackgrounds[0].url;
    
    try {
      // Save to server
      const response = await fetch('/api/hero-config.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          background: defaultBg
        })
      });

      if (!response.ok) {
        throw new Error(`Server error: ${response.status}`);
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.error || 'Failed to reset background');
      }

      // Update local state
      setPreviewBackground(defaultBg);
      setCurrentBackground(defaultBg);
      setIsPreviewMode(false);
      
      window.dispatchEvent(new CustomEvent('heroBackgroundChanged', {
        detail: { background: defaultBg }
      }));
      
      alert('Reset to default background on server!');
      
    } catch (error) {
      console.error('Failed to reset hero background:', error);
      alert('Error resetting hero background. Please try again.');
    } finally {
      setIsSaving(false);
    }
  };

  // Preview background
  const previewImage = (url: string) => {
    setPreviewBackground(url);
    setIsPreviewMode(true);
  };

  return (
    <div className="bg-white rounded-lg shadow-sm p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-6">Hero Background Management</h2>
      
      {/* Current Background Preview */}
      <div className="mb-8">
        <h3 className="font-medium text-gray-900 mb-4">Current Background</h3>
        <div className="relative">
          <div 
            className="w-full h-48 bg-cover bg-center rounded-lg shadow-lg"
            style={{ backgroundImage: `url(${isPreviewMode ? previewBackground : currentBackground})` }}
          >
            <div className="absolute inset-0 bg-black/40 rounded-lg flex items-center justify-center">
              <div className="text-white text-center">
                <h1 className="text-4xl font-bold mb-2">Stone Pho</h1>
                <p className="text-xl">Vietnamese Restaurant</p>
              </div>
            </div>
          </div>
          
          {isPreviewMode && (
            <div className="absolute top-2 right-2 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium">
              <Eye className="w-4 h-4 inline mr-1" />
              Preview Mode
            </div>
          )}
        </div>
      </div>

      {/* Upload Progress */}
      {isUploading && (
        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-center space-x-2 mb-2">
            <Loader className="w-4 h-4 animate-spin text-blue-600" />
            <span className="text-sm text-blue-800">Đang upload hình nền... {uploadProgress}%</span>
          </div>
          <div className="w-full bg-blue-200 rounded-full h-2">
            <div 
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${uploadProgress}%` }}
            ></div>
          </div>
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex gap-4 mb-8">
        <button
          onClick={saveBackground}
          disabled={!isPreviewMode || isSaving}
          className="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-semibold flex items-center space-x-2 transition-colors"
        >
          <Save className="w-5 h-5" />
          <span>{isSaving ? 'Saving...' : 'Save Background'}</span>
        </button>
        
        <button
          onClick={resetToDefault}
          disabled={isSaving}
          className="bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-lg font-semibold flex items-center space-x-2 transition-colors"
        >
          <RotateCcw className="w-5 h-5" />
          <span>{isSaving ? 'Resetting...' : 'Reset to Default'}</span>
        </button>
        
        {isPreviewMode && (
          <button
            onClick={() => {
              setPreviewBackground(currentBackground);
              setIsPreviewMode(false);
            }}
            className="border border-gray-300 text-gray-700 hover:bg-gray-50 px-6 py-3 rounded-lg font-semibold transition-colors"
          >
            Cancel Preview
          </button>
        )}
      </div>

      {/* Upload Custom Background */}
      <div className="mb-8 p-6 bg-gray-50 rounded-lg">
        <h3 className="font-medium text-gray-900 mb-4">Upload Custom Background</h3>
        <p className="text-sm text-gray-600 mb-4">
          Upload your own background image. Recommended size: 1920x1080px or larger.
        </p>
        
        <ImageUpload
          currentImage=""
          onImageChange={handleCustomBackgroundUpload}
          className="w-full"
        />
      </div>

      {/* Preset Backgrounds */}
      <div>
        <h3 className="font-medium text-gray-900 mb-4">Preset Backgrounds</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {defaultBackgrounds.map((bg, index) => (
            <div key={index} className="relative group">
              <div 
                className="w-full h-32 bg-cover bg-center rounded-lg shadow-md cursor-pointer transition-transform hover:scale-105"
                style={{ backgroundImage: `url(${bg.url})` }}
                onClick={() => previewImage(bg.url)}
              >
                <div className="absolute inset-0 bg-black/30 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <button className="bg-white/90 text-gray-900 px-4 py-2 rounded-lg font-medium">
                    <Eye className="w-4 h-4 inline mr-2" />
                    Preview
                  </button>
                </div>
              </div>
              
              <div className="mt-2">
                <h4 className="font-medium text-gray-900 text-sm">{bg.name}</h4>
                <p className="text-xs text-gray-500">{bg.description}</p>
              </div>
              
              {currentBackground === bg.url && (
                <div className="absolute top-2 right-2 bg-green-600 text-white px-2 py-1 rounded-full text-xs font-medium">
                  Current
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Instructions */}
      <div className="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h4 className="font-medium text-blue-900 mb-2">How to use:</h4>
        <ul className="text-sm text-blue-800 space-y-1">
          <li>• Click on any preset background to preview it</li>
          <li>• Upload your own custom background image</li>
          <li>• Click "Save Background" to apply changes</li>
          <li>• Changes will be visible immediately on the website</li>
          <li>• Use high-resolution images (1920x1080px+) for best quality</li>
        </ul>
      </div>
    </div>
  );
};

export default HeroBackgroundManager;