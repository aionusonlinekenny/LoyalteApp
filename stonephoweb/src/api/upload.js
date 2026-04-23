// Backend API endpoint for image upload
// File: /api/upload-image.js (for Node.js/Express)
// Hoặc /api/upload-image.php (for PHP)

/* 
=== NODE.JS EXPRESS VERSION ===
*/

const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const sharp = require('sharp'); // For image optimization
const router = express.Router();

// Create uploads directory if it doesn't exist
const uploadsDir = path.join(__dirname, '../public/uploads/menu-items');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

// Configure multer for file upload
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadsDir);
  },
  filename: (req, file, cb) => {
    // Generate unique filename
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    const ext = path.extname(file.originalname);
    cb(null, `menu-item-${uniqueSuffix}${ext}`);
  }
});

// File filter
const fileFilter = (req, file, cb) => {
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
  if (allowedTypes.includes(file.mimetype)) {
    cb(null, true);
  } else {
    cb(new Error('Invalid file type. Only JPG, PNG, GIF, WebP allowed.'), false);
  }
};

const upload = multer({
  storage: storage,
  fileFilter: fileFilter,
  limits: {
    fileSize: 10 * 1024 * 1024 // 10MB limit
  }
});

// Upload endpoint
router.post('/upload-image', upload.single('image'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' });
    }

    const originalPath = req.file.path;
    const optimizedPath = originalPath.replace(/\.[^/.]+$/, '-optimized.jpg');

    // Optimize image with Sharp
    await sharp(originalPath)
      .resize(800, 600, { 
        fit: 'inside',
        withoutEnlargement: true 
      })
      .jpeg({ 
        quality: 85,
        progressive: true 
      })
      .toFile(optimizedPath);

    // Delete original file
    fs.unlinkSync(originalPath);

    // Return the URL
    const imageUrl = `/uploads/menu-items/${path.basename(optimizedPath)}`;
    
    res.json({
      success: true,
      imageUrl: imageUrl,
      filename: path.basename(optimizedPath)
    });

  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ 
      error: 'Upload failed',
      message: error.message 
    });
  }
});

// Delete image endpoint
router.delete('/delete-image', (req, res) => {
  try {
    const { filename } = req.body;
    const filePath = path.join(uploadsDir, filename);
    
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
      res.json({ success: true, message: 'Image deleted' });
    } else {
      res.status(404).json({ error: 'Image not found' });
    }
  } catch (error) {
    res.status(500).json({ error: 'Delete failed' });
  }
});

module.exports = router;

/* 
=== PHP VERSION ===
File: /api/upload-image.php

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration
$uploadDir = '../public/uploads/menu-items/';
$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }

        $file = $_FILES['image'];
        
        // Validate file size
        if ($file['size'] > $maxFileSize) {
            throw new Exception('File too large. Maximum size is 10MB');
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, WebP allowed');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'menu-item-' . time() . '-' . rand(1000, 9999) . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save file');
        }

        // Optimize image (if GD extension is available)
        if (extension_loaded('gd')) {
            optimizeImage($filepath, $mimeType);
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'imageUrl' => '/uploads/menu-items/' . $filename,
            'filename' => $filename
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Upload failed',
            'message' => $e->getMessage()
        ]);
    }
}

function optimizeImage($filepath, $mimeType) {
    $maxWidth = 800;
    $maxHeight = 600;
    $quality = 85;

    // Get original dimensions
    list($width, $height) = getimagesize($filepath);

    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio < 1) {
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    // Create image resource
    switch ($mimeType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return; // Skip optimization for unsupported types
    }

    // Create new image
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize image
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save optimized image
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($destination, $filepath, $quality);
            break;
        case 'image/png':
            imagepng($destination, $filepath, 9);
            break;
        case 'image/gif':
            imagegif($destination, $filepath);
            break;
    }

    // Clean up
    imagedestroy($source);
    imagedestroy($destination);
}
?>
*/