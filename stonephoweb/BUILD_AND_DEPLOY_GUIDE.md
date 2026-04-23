# Stone Pho Website - Build & Deploy Guide

## 📋 Prerequisites

Before you begin, make sure you have:
- **Node.js** (version 16 or higher) - Download from https://nodejs.org/
- **Git** (optional but recommended) - Download from https://git-scm.com/
- A **web hosting service** (Netlify, Vercel, GitHub Pages, etc.)

## 🛠️ Building the Project

### Step 1: Download the Source Code
1. Download all project files to your computer
2. Extract them to a folder (e.g., `stone-pho-website`)

### Step 2: Install Dependencies
Open terminal/command prompt in the project folder and run:
```bash
npm install
```

### Step 3: Test Locally (Optional)
To test the website on your computer:
```bash
npm run dev
```
Visit `http://localhost:5173` to see your website.

### Step 4: Build for Production
Create the production files:
```bash
npm run build
```
This creates a `dist` folder with all the files needed for hosting.

## 🌐 Deployment Options

### Option 1: Netlify (Recommended - Free)

1. **Sign up** at https://netlify.com/
2. **Drag and drop** the `dist` folder to Netlify dashboard
3. **Get your URL** (e.g., `https://your-site-name.netlify.app`)
4. **Custom domain** (optional): Add your own domain in Site Settings

### Option 2: Vercel (Free)

1. **Sign up** at https://vercel.com/
2. **Import project** from GitHub or upload files
3. **Deploy** automatically
4. **Get your URL** (e.g., `https://your-site-name.vercel.app`)

### Option 3: GitHub Pages (Free)

1. **Create GitHub account** at https://github.com/
2. **Create new repository** named `stone-pho-website`
3. **Upload** the `dist` folder contents
4. **Enable GitHub Pages** in repository settings
5. **Access** at `https://yourusername.github.io/stone-pho-website`

### Option 4: Traditional Web Hosting

1. **Choose a hosting provider** (GoDaddy, Bluehost, HostGator, etc.)
2. **Upload** the `dist` folder contents via FTP/File Manager
3. **Point domain** to the uploaded files

## 📁 File Structure After Build

```
dist/
├── index.html          # Main website file
├── assets/
│   ├── index-[hash].js # JavaScript bundle
│   └── index-[hash].css # CSS styles
└── _redirects          # Routing configuration
```

## 🔧 Configuration Files

### Environment Variables (Optional)
Create `.env` file in root directory for custom settings:
```
VITE_RESTAURANT_NAME=Stone Pho
VITE_PHONE_NUMBER=(229) 333-7468
VITE_ADDRESS=1525 Baytree Rd, #M, Valdosta, GA 31602
```

### Custom Domain Setup
1. **Purchase domain** from registrar (GoDaddy, Namecheap, etc.)
2. **Point DNS** to your hosting provider
3. **Configure** in hosting dashboard
4. **Enable HTTPS** (usually automatic)

## 🚀 Going Live Checklist

- [ ] Test all links work correctly
- [ ] Verify phone numbers dial correctly on mobile
- [ ] Check ordering links (Clover & DoorDash)
- [ ] Test admin panel functionality
- [ ] Ensure responsive design on all devices
- [ ] Verify contact information is correct
- [ ] Test form submissions
- [ ] Check loading speed
- [ ] Confirm SEO meta tags

## 📱 Mobile Optimization

The website is fully responsive and includes:
- Mobile-friendly navigation
- Touch-optimized buttons
- Readable text on small screens
- Fast loading on mobile networks

## 🔍 SEO Features Included

- Meta descriptions and titles
- Structured data for restaurant
- Mobile-friendly design
- Fast loading times
- Proper heading structure
- Alt text for images

## 🛡️ Security Considerations

- Admin authentication (change default password)
- HTTPS enabled (through hosting provider)
- No sensitive data in frontend code
- Secure form handling

## 📊 Analytics Setup (Optional)

Add Google Analytics:
1. Create account at https://analytics.google.com/
2. Get tracking ID
3. Add to `index.html` in `<head>` section:
```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_TRACKING_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_TRACKING_ID');
</script>
```

## 🔄 Updates and Maintenance

### To Update Content:
1. Use the admin panel (see ADMIN_GUIDE.md)
2. Or modify source files and rebuild

### To Update Code:
1. Make changes to source files
2. Run `npm run build`
3. Upload new `dist` folder to hosting

### Backup Strategy:
- Keep source code backed up
- Export menu data from admin panel regularly
- Save hosting account credentials securely

## 🆘 Troubleshooting

### Common Issues:

**Build Fails:**
- Check Node.js version (16+)
- Delete `node_modules` and run `npm install` again
- Check for syntax errors in code

**Site Not Loading:**
- Verify all files uploaded correctly
- Check hosting provider status
- Ensure `index.html` is in root directory

**Admin Panel Not Working:**
- Clear browser cache
- Check browser console for errors
- Verify localStorage is enabled

**Mobile Issues:**
- Test on actual devices
- Use browser developer tools
- Check viewport meta tag

### Getting Help:
- Check hosting provider documentation
- Contact hosting support
- Review browser console errors
- Test on different browsers

## 💰 Cost Breakdown

### Free Options:
- **Netlify/Vercel**: Free tier available
- **GitHub Pages**: Completely free
- **Domain**: $10-15/year (optional)

### Paid Options:
- **Shared Hosting**: $3-10/month
- **VPS Hosting**: $5-20/month
- **Premium Features**: Varies by provider

## 📞 Support

For technical issues with the website code:
- Check browser console for errors
- Review this documentation
- Test in different browsers
- Contact your hosting provider for server issues

Remember to keep your admin credentials secure and regularly backup your menu data!