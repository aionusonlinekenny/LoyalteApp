import React, { useState, useRef } from 'react';
import { Upload, X, Image as ImageIcon, Link, Loader } from 'lucide-react';

interface ImageUploadProps {
  currentImage?: string;
  onImageChange: (imageUrl: string) => void;
  className?: string;
}

const ImageUpload: React.FC<ImageUploadProps> = ({ 
  currentImage, 
  onImageChange, 
  className = '' 
}) => {
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [dragActive, setDragActive] = useState(false);
  const [urlInput, setUrlInput] = useState('');
  const [showUrlInput, setShowUrlInput] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Upload file to server
  const uploadToServer = async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('image', file);

    try {
      // URL đúng - stonephovaldosta.com-redirect/ được redirect thành root
      const apiUrl = 'https://stonephovaldosta.com/api/upload-image.php';
      
      console.log('Uploading to:', apiUrl);
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
      console.log('Response headers:', response.headers);
      
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

  // Handle file selection
  const handleFileSelect = async (file: File) => {
    // Validate file
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
      alert('Vui lòng chọn file hình ảnh (JPG, PNG, GIF, WebP)');
      return;
    }

    if (file.size > 10 * 1024 * 1024) { // 10MB limit
      alert('File quá lớn! Vui lòng chọn file nhỏ hơn 10MB');
      return;
    }

    setIsUploading(true);
    setUploadProgress(0);

    try {
      // Simulate upload progress
      const progressInterval = setInterval(() => {
        setUploadProgress(prev => {
          if (prev >= 90) {
            clearInterval(progressInterval);
            return 90;
          }
          return prev + 10;
        });
      }, 200);

      // Upload to server
      const imageUrl = await uploadToServer(file);
      
      clearInterval(progressInterval);
      setUploadProgress(100);
      
      // Update parent component
      onImageChange(imageUrl);
      
      setTimeout(() => {
        setIsUploading(false);
        setUploadProgress(0);
      }, 500);

    } catch (error) {
      setIsUploading(false);
      setUploadProgress(0);
      alert('Lỗi upload hình ảnh. Vui lòng thử lại!');
    }
  };

  // Handle drag and drop
  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    const files = e.dataTransfer.files;
    if (files && files[0]) {
      handleFileSelect(files[0]);
    }
  };

  // Handle file input change
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files && files[0]) {
      handleFileSelect(files[0]);
    }
  };

  // Handle URL input
  const handleUrlSubmit = () => {
    if (urlInput.trim()) {
      onImageChange(urlInput.trim());
      setUrlInput('');
      setShowUrlInput(false);
    }
  };

  // Remove current image
  const handleRemoveImage = () => {
    onImageChange('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  return (
    <div className={`space-y-4 ${className}`}>
      {/* Current Image Preview */}
      {currentImage && !isUploading && (
        <div className="relative inline-block">
          <img
            src={currentImage}
            alt="Preview"
            className="w-32 h-24 object-cover rounded-lg border-2 border-gray-300 shadow-sm"
            onError={(e) => {
              (e.target as HTMLImageElement).src = '/placeholder-image.jpg';
            }}
          />
          <button
            type="button"
            onClick={handleRemoveImage}
            className="absolute -top-2 -right-2 bg-orange-500 hover:bg-orange-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs transition-colors shadow-lg"
          >
            <X className="w-3 h-3" />
          </button>
        </div>
      )}

      {/* Upload Progress */}
      {isUploading && (
        <div className="space-y-2">
          <div className="flex items-center space-x-2">
            <Loader className="w-4 h-4 animate-spin text-blue-600" />
            <span className="text-sm text-gray-600">Đang upload... {uploadProgress}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${uploadProgress}%` }}
            ></div>
          </div>
        </div>
      )}

      {/* Upload Options */}
      {!isUploading && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* File Upload */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Upload từ máy tính
            </label>
            <div
              className={`relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200 ${
                dragActive 
                  ? 'border-blue-500 bg-blue-50' 
                  : 'border-gray-300 hover:border-blue-400 hover:bg-blue-50'
              }`}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
              onClick={() => fileInputRef.current?.click()}
            >
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                onChange={handleInputChange}
                className="hidden"
              />
              
              <div className="space-y-2">
                <Upload className="mx-auto h-8 w-8 text-gray-400" />
                <div className="text-sm">
                  <span className="font-medium text-gray-900">Click để chọn</span>
                  <span className="text-gray-500"> hoặc kéo thả file vào đây</span>
                </div>
                <p className="text-xs text-gray-500">
                  JPG, PNG, GIF, WebP (tối đa 10MB)
                </p>
              </div>
            </div>
          </div>

          {/* URL Input */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Hoặc nhập URL hình ảnh
            </label>
            {!showUrlInput ? (
              <button
                type="button"
                onClick={() => setShowUrlInput(true)}
                className="w-full border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-all duration-200"
              >
                <Link className="mx-auto h-8 w-8 text-gray-400 mb-2" />
                <span className="text-sm font-medium text-gray-900">Nhập URL</span>
              </button>
            ) : (
              <div className="space-y-2">
                <input
                  type="url"
                  value={urlInput}
                  onChange={(e) => setUrlInput(e.target.value)}
                  placeholder="https://example.com/image.jpg"
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  onKeyPress={(e) => e.key === 'Enter' && handleUrlSubmit()}
                />
                <div className="flex space-x-2">
                  <button
                    type="button"
                    onClick={handleUrlSubmit}
                    className="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                  >
                    Thêm URL
                  </button>
                  <button
                    type="button"
                    onClick={() => {
                      setShowUrlInput(false);
                      setUrlInput('');
                    }}
                    className="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors"
                  >
                    Hủy
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Quick Image Suggestions */}
      {!isUploading && (
        <div className="border-t pt-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Hình ảnh gợi ý
          </label>
          <div className="flex flex-wrap gap-2">
            {[
              { name: 'Phở Đặc Biệt', url: 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=400&h=250&fit=crop' },
              { name: 'Phở Gà', url: 'https://images.pexels.com/photos/4518843/pexels-photo-4518843.jpeg?auto=compress&cs=tinysrgb&w=400&h=250&fit=crop' },
              { name: 'Gỏi Cuốn', url: 'https://images.pexels.com/photos/1410235/pexels-photo-1410235.jpeg?auto=compress&cs=tinysrgb&w=400&h=250&fit=crop' },
              { name: 'Trà Sữa', url: 'https://images.pexels.com/photos/1638280/pexels-photo-1638280.jpeg?auto=compress&cs=tinysrgb&w=400&h=250&fit=crop' }
            ].map((item, index) => (
              <button
                key={index}
                type="button"
                onClick={() => onImageChange(item.url)}
                className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full transition-colors"
              >
                {item.name}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default ImageUpload;