# Stone Pho Admin Panel - User Guide

## 🔐 Accessing the Admin Panel

### Method 1: Direct URL
Add `#admin` to your website URL:
```
https://your-website.com#admin
```

### Method 2: Secret Link
Create a hidden link on your website (for staff only):
```html
<a href="#admin" style="display:none;">Admin</a>
```

## 🔑 Login Credentials

### Default Login:
- **Username**: `admin`
- **Password**: `stonepho2024`

### ⚠️ IMPORTANT: Change Default Password
For security, change the default password by modifying the code in `AdminLogin.tsx`:
```javascript
// Find this line and change the password:
if (credentials.username === 'admin' && credentials.password === 'YOUR_NEW_PASSWORD') {
```

## 📋 Admin Panel Features

### 🏷️ Category Management

#### Adding a New Category:
1. Click **"Add Category"** button
2. Enter category name (e.g., "Desserts", "Drinks")
3. Click **Save** button (✓)
4. New category appears in the list

#### Deleting a Category:
1. Find the category you want to delete
2. Click the **X** button next to category name
3. Confirm deletion in popup
4. **Note**: All items in deleted category move to the first category

#### Category Examples:
- Appetizers
- Pho
- Rice Dishes
- Beverages
- Desserts
- Lunch Specials

### 🍜 Menu Item Management

#### Adding a New Menu Item:
1. Select the category where you want to add the item
2. Click **"Add Item"** button
3. Fill in the form:
   - **Name**: Item name (e.g., "Pho Tai")
   - **Price**: Price with $ (e.g., "$12.95")
   - **Description**: Detailed description
   - **Category**: Select from dropdown
4. Click **"Save"** button
5. Item appears immediately on website

#### Editing an Existing Item:
1. Find the item you want to edit
2. Click **"Edit"** button (pencil icon)
3. Modify any field:
   - Change name, price, or description
   - Move to different category
4. Click **"Save"** to confirm changes
5. Click **"Cancel"** to discard changes

#### Deleting a Menu Item:
1. Find the item you want to delete
2. Click **"Delete"** button (trash icon)
3. Confirm deletion in popup
4. Item is permanently removed

## 📝 Best Practices for Menu Management

### Naming Convention:
- **Consistent Format**: "P1. Pho Special" or "A1. Spring Rolls"
- **Clear Names**: Use descriptive, customer-friendly names
- **Avoid Abbreviations**: Write out full names

### Pricing Format:
- **Always include $**: "$12.95" not "12.95"
- **Consistent Decimals**: Use .95 or .00 consistently
- **No Spaces**: "$12.95" not "$ 12.95"

### Description Guidelines:
- **Be Descriptive**: Include main ingredients
- **Mention Allergies**: Note common allergens
- **Highlight Specials**: Use "CHEF'S CHOICE" or "SIGNATURE"
- **Keep Concise**: 1-2 sentences maximum

### Category Organization:
- **Logical Order**: Appetizers → Main Dishes → Beverages
- **Customer Journey**: Order items as customers would browse
- **Seasonal Items**: Create temporary categories for specials

## 🔄 Data Management

### Automatic Saving:
- All changes save automatically to browser storage
- Changes appear immediately on main website
- No manual save required

### Data Backup:
Your menu data is stored locally. To backup:
1. Open browser developer tools (F12)
2. Go to **Application** → **Local Storage**
3. Copy `menuItems` and `menuCategories` data
4. Save to text file for backup

### Data Recovery:
If data is lost:
1. Use backup data from above
2. Or re-enter items manually
3. Default menu will reload if no saved data exists

## 🖥️ Admin Panel Interface

### Navigation:
- **Category Tabs**: Switch between menu categories
- **Add Buttons**: Green for categories, red for items
- **Edit Mode**: Forms appear inline for editing
- **Logout**: Red logout button in top-right

### Visual Indicators:
- **Active Category**: Highlighted in red
- **Edit Mode**: Gray background with form fields
- **Buttons**: Color-coded (Green=Add, Blue=Edit, Red=Delete)
- **Item Count**: Shows number of items per category

## 📱 Mobile Admin Access

The admin panel works on mobile devices:
- **Responsive Design**: Adapts to phone screens
- **Touch-Friendly**: Large buttons for easy tapping
- **Scrollable**: Easy navigation on small screens
- **Same Features**: Full functionality on mobile

## 🔒 Security Features

### Session Management:
- **Auto-Login**: Stays logged in until logout
- **Secure Storage**: Credentials stored securely
- **Session Timeout**: Logout required for security

### Access Control:
- **Password Protected**: Requires login credentials
- **Hidden Access**: No visible admin links on main site
- **Local Storage**: Data stored locally, not on server

## ⚠️ Important Notes

### Data Persistence:
- **Browser Storage**: Data saved in browser only
- **Device Specific**: Each device/browser has separate data
- **Clear Cache**: Clearing browser data will reset menu

### Multiple Admins:
- **Same Credentials**: All admins use same login
- **Simultaneous Access**: Multiple people can edit at once
- **Last Save Wins**: Most recent changes override previous ones

### Website Updates:
- **Immediate**: Changes appear instantly on website
- **No Refresh**: Customers see updates without page reload
- **Real-Time**: Live updates as you make changes

## 🛠️ Troubleshooting

### Can't Access Admin Panel:
- Check URL has `#admin` at the end
- Clear browser cache and cookies
- Try different browser
- Verify JavaScript is enabled

### Login Not Working:
- Check username/password spelling
- Ensure caps lock is off
- Try typing credentials manually
- Clear browser data and retry

### Changes Not Saving:
- Check browser console for errors (F12)
- Ensure localStorage is enabled
- Try different browser
- Refresh page and try again

### Items Not Appearing:
- Verify correct category is selected
- Check if item was actually saved
- Refresh main website page
- Clear browser cache

## 📞 Getting Help

### Common Solutions:
1. **Refresh the page** - Fixes most display issues
2. **Clear browser cache** - Resolves data conflicts
3. **Try different browser** - Eliminates browser-specific issues
4. **Check internet connection** - Ensures proper loading

### When to Contact Support:
- Persistent login issues
- Data loss or corruption
- Website not updating
- Technical errors in console

## 🎯 Quick Reference

### Daily Tasks:
- ✅ Update daily specials
- ✅ Adjust prices if needed
- ✅ Add seasonal items
- ✅ Remove sold-out items

### Weekly Tasks:
- ✅ Review all menu items
- ✅ Update descriptions
- ✅ Check pricing accuracy
- ✅ Backup menu data

### Monthly Tasks:
- ✅ Reorganize categories
- ✅ Add new menu items
- ✅ Remove discontinued items
- ✅ Update contact information

Remember: Always test changes on the main website to ensure they appear correctly for customers!