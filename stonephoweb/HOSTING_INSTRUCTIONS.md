# 🚀 Stone Pho Website - Hosting Instructions

## 📁 Files to Upload to Your Hosting

After running `npm run build`, copy these files to your hosting:

### 📂 From `dist/` folder → Upload to `public_html/`:
```
dist/index.html          → public_html/index.html
dist/assets/             → public_html/assets/
dist/logo.png           → public_html/logo.png
```

### 📂 From project root → Upload to `public_html/`:
```
api/                     → public_html/api/
uploads/                 → public_html/uploads/
```

### 📂 From `hosting-files/` folder → Upload to `public_html/`:
```
hosting-files/.htaccess  → public_html/.htaccess
```

## 🔧 Final Structure on Your Hosting:

```
public_html/
├── index.html          # Main website file
├── assets/
│   ├── index-[hash].js # JavaScript bundle
│   └── index-[hash].css # CSS styles
├── logo.png           # Restaurant logo
├── api/
│   └── upload-image.php # Image upload API
├── uploads/
│   └── menu-items/    # Uploaded images folder
└── .htaccess          # Apache configuration
```

## ✅ What This Setup Includes:

### 🖼️ **Image Upload (Base64)**
- ✅ Upload images in admin panel
- ✅ Images stored as base64 in localStorage
- ✅ No server-side PHP needed
- ✅ Works on any hosting

### 🔐 **Admin Panel**
- ✅ Access via `yoursite.com#admin`
- ✅ Login: `admin` / `stonepho2024`
- ✅ Add/Edit/Delete menu items
- ✅ Category management
- ✅ Image upload for menu items

### 📱 **Website Features**
- ✅ Fully responsive design
- ✅ Order buttons (Clover & Delivery)
- ✅ Contact information
- ✅ Menu with categories
- ✅ Gallery section
- ✅ Contact form

### ⚡ **Performance**
- ✅ Fast loading
- ✅ Compressed assets
- ✅ Browser caching
- ✅ SEO optimized

## 🎯 **How to Deploy:**

1. **Build the project:**
   ```bash
   npm run build
   ```

2. **Upload files via FTP:**
   - Host: `ftp.stonephovaldosta.com`
   - Port: 21 (or 2222 for SFTP)
   - Upload `dist/` contents to `public_html/`
   - Upload `hosting-files/.htaccess` to `public_html/`

3. **Test the website:**
   - Visit `https://stonephovaldosta.com`
   - Test admin panel: `https://stonephovaldosta.com#admin`

## 🔒 **Security Notes:**

- ✅ Admin password can be changed in source code
- ✅ No sensitive data stored on server
- ✅ All data in browser localStorage
- ✅ HTTPS recommended for production

## 📞 **Support:**

If you need help:
1. Check browser console for errors (F12)
2. Verify all files uploaded correctly
3. Test admin panel functionality
4. Contact hosting support if needed

**🍜 Your Stone Pho website is ready to go live!** ✨