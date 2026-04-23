import React, { useState, useEffect } from 'react';
import { LogOut, Plus, Edit, Trash2, Save, X, Settings, Menu as MenuIcon, Power, PowerOff, Image as ImageIcon, Star } from 'lucide-react';
import ImageUpload from './ImageUpload';
import HeroBackgroundManager from './HeroBackgroundManager';
import LoyaltyAdminTab from './LoyaltyAdminTab';

interface MenuItem {
  id: string;
  name: string;
  price: string;
  description: string;
  category: string;
  image?: string;
}

interface GalleryItem {
  id: string;
  url: string;
  alt: string;
  title?: string;
  description?: string;
}

interface AdminPanelProps {
  onLogout: () => void;
}

const AdminPanel: React.FC<AdminPanelProps> = ({ onLogout }) => {
  const [activeTab, setActiveTab] = useState('menu');
  const [categories, setCategories] = useState<string[]>(['appetizers', 'pho', 'vermicelli', 'specialty', 'rice', 'beverages']);
  const [menuItems, setMenuItems] = useState<MenuItem[]>([]);
  const [editingItem, setEditingItem] = useState<string | null>(null);
  const [newItem, setNewItem] = useState<Partial<MenuItem>>({});
  const [isAddingItem, setIsAddingItem] = useState(false);
  const [newCategory, setNewCategory] = useState('');
  
  // Gallery states
  const [galleryItems, setGalleryItems] = useState<GalleryItem[]>([]);
  const [editingGalleryItem, setEditingGalleryItem] = useState<string | null>(null);
  const [newGalleryItem, setNewGalleryItem] = useState<Partial<GalleryItem>>({});
  const [isAddingGalleryItem, setIsAddingGalleryItem] = useState(false);

  // Maintenance mode - simple state without useEffect loop
  //const [maintenanceMode, setMaintenanceMode] = useState(() => {
  //const saved = localStorage.getItem('maintenanceMode');
  //return saved === 'true';
  //});
  // Maintenance mode
  const [maintenanceMode, setMaintenanceMode] = useState(false);
  const [loadingConfig, setLoadingConfig] = useState(true);
  
  // Default gallery items
  const defaultGalleryItems: GalleryItem[] = [
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
  ];

  // Default menu items
  const defaultMenuItems: MenuItem[] = [
    // Appetizers
    { id: '1', name: 'A1. Vietnamese Fried Egg Roll (2 rolls)', price: '$5.75', description: 'Egg wrap, ground pork, bean rice vermicelli, white onion, carrot, and fish sauce on the side.', category: 'appetizers' },
    { id: '2', name: 'A2. Spring Roll (2 rolls)', price: '$5.75', description: 'Rice paper, rice vermicelli, pork loin, shrimp, bean sprouts, lettuce, basil, and peanut sauce on the side.', category: 'appetizers' },
    { id: '3', name: 'A3. Fried Shrimp Roll (6 Rolls)', price: '$7.75', description: 'Egg wrapper, shrimp, spices, and sweet chili sauce on the side.', category: 'appetizers' },
    { id: '4', name: 'A4. Fried Calamari', price: '$7.75', description: 'Crispy calamari, soy sauce, onion, sesame seeds and ginger sauce.', category: 'appetizers' },
    { id: '5', name: 'A5. Fish Sauce Glazed Chicken Wings', price: '$9.95', description: 'Chicken wings, garlic, fish sauce, sesame seeds.', category: 'appetizers' },
    
    // Pho
    { id: '6', name: 'SP1. Stone Pho Special', price: '$24.95', description: '48-hour long-simmering bone beef broth with rare steak, brisket, tendon, and Vietnamese meatball.\nCHEF\'S CHOICE & SIGNATURE', category: 'pho' },
    { id: '7', name: 'P1. Pho Special', price: '$17.95', description: 'Steak, Brisket, Tendon & Vietnamese Meatball.\nClassic combination of all our best proteins.', category: 'pho' },
    { id: '8', name: 'P2. Steak, Brisket & Tendon', price: '$16.95', description: 'Classic combination with tender beef cuts.\nPerfect for those who love variety.', category: 'pho' },
    { id: '9', name: 'P3. Steak & Brisket', price: '$15.95', description: 'Perfect pairing of rare steak and slow-cooked brisket.\nA customer favorite combination.', category: 'pho' },
    { id: '10', name: 'P4. Steak & Tendon', price: '$15.95', description: 'Rare steak with tender beef tendon.\nGreat texture combination.', category: 'pho' },
    { id: '11', name: 'P5. Steak & Vietnamese Meat Ball', price: '$15.95', description: 'Rare steak with homemade Vietnamese meatballs.\nTraditional flavors combined.', category: 'pho' },
    { id: '12', name: 'P6. Steak', price: '$14.95', description: 'Classic rare beef pho.\nSimple and delicious.', category: 'pho' },
    { id: '13', name: 'P7. Brisket & Tendon', price: '$15.95', description: 'Slow-cooked brisket with tender tendon.\nRich and hearty combination.', category: 'pho' },
    { id: '14', name: 'P8. Brisket & Vietnamese Meatball', price: '$15.95', description: 'Hearty brisket with homemade meatballs.\nComfort food at its best.', category: 'pho' },
    { id: '15', name: 'P9. Vietnamese Meatball', price: '$14.95', description: 'Traditional Vietnamese beef meatballs.\nHomemade with authentic spices.', category: 'pho' },
    { id: '16', name: 'P10. Chicken Pho', price: '$14.95', description: 'Fresh chicken in aromatic broth.\nLighter option with great flavor.', category: 'pho' },
    { id: '17', name: 'P11. Plain Noodle', price: '$11.25', description: 'Rice noodles in rich beef broth.\nSimple and satisfying.', category: 'pho' },
    { id: '18', name: 'P12. Oxtail Pho', price: '$17.95', description: 'Tender oxtail braised for 4 hours.\nDeep, hearty flavor profile.', category: 'pho' },
    { id: '19', name: 'P13. Pho Short Ribs', price: '$16.95', description: 'Come with 3 bones, tender and juicy.\nFall-off-the-bone delicious.', category: 'pho' },
    { id: '20', name: 'P14. Pho Seafood', price: '$15.95', description: 'Fresh seafood in aromatic broth.\nOcean flavors meet Vietnamese tradition.', category: 'pho' },
    { id: '21', name: 'P15. Pho Tofu', price: '$14.95', description: 'Vegetarian option with fresh tofu.\nCHEF\'S CHOICE for plant-based diners.', category: 'pho' },
    
    // Vermicelli
    { id: '22', name: 'B1. Rice Vermicelli With Egg Rolls', price: '$11.25', description: 'Rice vermicelli with crispy egg rolls.\nServed with fresh herbs and vegetables.', category: 'vermicelli' },
    { id: '23', name: 'B2. Rice Vermicelli With Grilled Pork', price: '$15.95', description: 'Rice vermicelli with marinated grilled pork.\nTender and flavorful protein.', category: 'vermicelli' },
    { id: '24', name: 'B3. Rice Vermicelli With Egg Rolls & Grilled Pork', price: '$15.95', description: 'Combination of egg rolls and grilled pork.\nBest of both worlds over vermicelli.', category: 'vermicelli' },
    { id: '25', name: 'B4. Rice Vermicelli Special Combination', price: '$15.95', description: 'Special combination with multiple proteins.\nOur chef\'s recommended selection.', category: 'vermicelli' },
    { id: '26', name: 'B5. Rice Vermicelli With Grilled Shrimp', price: '$15.95', description: 'Rice vermicelli with fresh grilled shrimp.\nLight and healthy seafood option.', category: 'vermicelli' },
    
    // Specialty
    { id: '27', name: 'CS1. Hue\'s Style Noodle Soup', price: '$15.95', description: 'Thick rice vermicelli, onion, cilantro, brisket, pork loin, Vietnamese meatloaf.\nShrimp paste, lemongrass, beef broth.\nAuthentic Central Vietnamese flavors.', category: 'specialty' },
    { id: '28', name: 'CS2. Udon Soup With Pork & Crab', price: '$15.95', description: 'Udon noodles, onion, cilantro, shrimp, imitation crab, pork loin.\nRich pork and crab broth.\nFusion of Japanese and Vietnamese styles.', category: 'specialty' },
    { id: '29', name: 'CS3. Pho Stir Fry', price: '$16.95', description: 'Pho noodle, protein, carrot, broccoli, onion, bean sprouts and cabbage.\nSesame seeds (Beef/Chicken/Shrimp & Squid/Tofu).\nWok-fried Vietnamese style.', category: 'specialty' },
    { id: '30', name: 'CS4. BBQ Pork Udon - NEW DISH', price: '$15.95', description: 'Udon noodles, lettuce, cucumber, pickled carrots, sesame seeds.\nBBQ pork, fried egg.\nFresh and innovative combination.', category: 'specialty' },
    { id: '31', name: 'CS5. Sizzling Steak - NEW DISH', price: '$17.95', description: 'Served in a sizzling skillet with bánh mì, egg, pate.\nVietnamese meatloaf included.\nDramatic presentation, amazing flavors.', category: 'specialty' },
    { id: '32', name: 'CS6. Chicken Curry - CHEF\'S SPECIAL', price: '$15.95', description: 'Bread, curry, chicken, sweet potato, basil.\nSalt and pepper seasoning.\nComforting Vietnamese curry style.', category: 'specialty' },
    
    // Rice
    { id: '33', name: 'C1. The Combination', price: '$17.95', description: 'Rice, garnish, pickled carrots, onion, grilled pork chop.\nEgg, Vietnamese sausage included.\nComplete meal with multiple proteins.', category: 'rice' },
    { id: '34', name: 'C2. Grilled Pork', price: '$15.95', description: 'Rice, garnish, pickled carrots, onion, grilled pork chop.\nMarinated and grilled to perfection.\nClassic Vietnamese rice dish.', category: 'rice' },
    { id: '35', name: 'C3. Fried Rice', price: '$15.95', description: 'Rice, egg, peas, onion, carrot.\nChoice of Chicken/Seafood/Tofu available.\nWok-fried with authentic seasonings.', category: 'rice' },
    { id: '36', name: 'C4. Crispy Chicken Rice', price: '$15.95', description: 'Rice, garnish, pickled carrots, onion, crispy chicken.\nGolden fried to perfection.\nCrunchy and satisfying.', category: 'rice' },
    { id: '37', name: 'C5. BBQ Pork Rice', price: '$15.95', description: 'Rice, garnish, pickled carrots, onion, BBQ pork.\nSesame seeds for extra flavor.\nSweet and savory combination.', category: 'rice' },
    { id: '38', name: 'C6. Chicken Teriyaki', price: '$15.95', description: 'Rice, garnish, pickled carrots, grilled chicken.\nTangerine teriyaki sauce, sesame seeds.\nFusion of Vietnamese and Japanese flavors.', category: 'rice' },
    
    // Beverages
    { id: '39', name: 'Thai Milk Tea', price: '$7.45', description: 'Traditional Thai milk tea.\nTOP 3 BEVERAGE - Customer favorite.\nCreamy and perfectly sweetened.', category: 'beverages' },
    { id: '40', name: 'Baked Egg Cream Milk Tea', price: '$7.45', description: 'Signature baked egg cream milk tea.\nTOP 3 BEVERAGE - Unique and delicious.\nRich and creamy texture.', category: 'beverages' },
    { id: '41', name: 'Tropical Fruit Tea #1', price: '$6.45', description: 'Refreshing tropical fruit blend.\nTOP 3 BEVERAGE - Perfect for hot days.\nBurst of tropical flavors.', category: 'beverages' },
    { id: '42', name: 'Vietnamese Milk Coffee', price: '$7.45', description: 'Traditional Vietnamese coffee with condensed milk.\nStrong and sweet combination.\nAuthentic Vietnamese preparation.', category: 'beverages' },
    { id: '43', name: 'Vietnamese Black Coffee', price: '$6.45', description: 'Strong Vietnamese black coffee.\nBold and robust flavor.\nPerfect for coffee lovers.', category: 'beverages' },
    { id: '44', name: 'Peach Tea', price: '$6.45', description: 'Fresh peach flavored tea.\nSweet and fruity refreshment.\nLight and refreshing.', category: 'beverages' },
    { id: '45', name: 'Passion Fruit Tea', price: '$6.45', description: 'Tropical passion fruit tea.\nTart and exotic flavor.\nRefreshing tropical experience.', category: 'beverages' },
    { id: '46', name: 'Crystal Coconut', price: '$6.45', description: 'Refreshing coconut drink.\nNatural coconut flavor.\nHydrating and delicious.', category: 'beverages' },
    { id: '47', name: 'Atiso Tea', price: '$6.45', description: 'Traditional Vietnamese artichoke tea.\nUnique herbal flavor.\nHealthy and refreshing.', category: 'beverages' },
    { id: '48', name: 'Thai Tea Lemonade', price: '$7.45', description: 'Thai tea with fresh lemon.\nPerfect balance of sweet and tart.\nRefreshing twist on classic Thai tea.', category: 'beverages' },
    { id: '49', name: 'Coffee Ice Blended', price: '$7.45', description: 'Blended iced coffee drink.\nSmooth and creamy texture.\nPerfect coffee treat.', category: 'beverages' },
    { id: '50', name: 'Taro Ice Blended', price: '$7.45', description: 'Creamy taro blended drink.\nSweet purple root flavor.\nUnique and delicious.', category: 'beverages' },
    { id: '51', name: 'Chocolate Ice Blended', price: '$7.45', description: 'Rich chocolate blended drink.\nDecadent and satisfying.\nPerfect dessert beverage.', category: 'beverages' },
  ];

  // Load data from localStorage on mount
  useEffect(() => {
  const loadData = async () => {
    try {
      // Load menu items + categories từ server
      const menuRes = await fetch("/api/load-menu.php");
      if (menuRes.ok) {
        const menuData = await menuRes.json();
        if (menuData.success && menuData.data) {
          setMenuItems(menuData.data.menuItems || []);
          setCategories(menuData.data.categories || []);
        }
      }

      // Load gallery items từ server
      const galleryRes = await fetch("/api/load-gallery.php");
      if (galleryRes.ok) {
        const galleryData = await galleryRes.json();
        if (galleryData.success && galleryData.data) {
          setGalleryItems(galleryData.data.galleryItems || []);
        }
      }
	  // Load config (maintenanceMode)
		const configRes = await fetch("/api/load-config.php");
		if (configRes.ok) {
		  const configData = await configRes.json();
		  if (configData.success && configData.data) {
			setMaintenanceMode(configData.data.maintenanceMode === true);
		  }
		}
		setLoadingConfig(false);
    } catch (err) {
      console.error("Failed to load data:", err);
      // fallback localStorage nếu cần
    }
  };

  loadData();
}, []);


// Save menu items + categories
useEffect(() => {
  if (menuItems.length > 0 && categories.length > 0) {
    fetch("/api/save-menu.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        items: menuItems,
        categories: categories
      })
    }).catch(err => {
      console.error("❌ Failed to save menu:", err);
    });
  }
}, [menuItems, categories]);

// Save gallery items
useEffect(() => {
  if (galleryItems.length > 0) {
    fetch("/api/save-gallery.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ galleryItems })
    }).catch(err => {
      console.error("❌ Failed to save gallery:", err);
    });
  }
}, [galleryItems]);


  // Menu management functions
  const addCategory = () => {
    if (newCategory.trim() && !categories.includes(newCategory.toLowerCase())) {
      const updatedCategories = [...categories, newCategory.toLowerCase()];
      setCategories(updatedCategories);
      setNewCategory('');
    }
  };

  const deleteCategory = (categoryToDelete: string) => {
    if (categories.length > 1) {
      const updatedCategories = categories.filter(cat => cat !== categoryToDelete);
      setCategories(updatedCategories);
      
      // Move items from deleted category to first category
      const updatedItems = menuItems.map(item => 
        item.category === categoryToDelete 
          ? { ...item, category: updatedCategories[0] }
          : item
      );
      setMenuItems(updatedItems);
    }
  };

  const addMenuItem = () => {
    if (newItem.name && newItem.price && newItem.category) {
      const item: MenuItem = {
        id: Date.now().toString(),
        name: newItem.name,
        price: newItem.price,
        description: newItem.description || '',
        category: newItem.category,
        image: newItem.image || ''
      };
      setMenuItems([...menuItems, item]);
      setNewItem({});
      setIsAddingItem(false);
    }
  };

  const updateMenuItem = (id: string, updatedItem: Partial<MenuItem>) => {
    setMenuItems(menuItems.map(item => 
      item.id === id ? { ...item, ...updatedItem } : item
    ));
    setEditingItem(null);
  };

  const deleteMenuItem = (id: string) => {
    setMenuItems(menuItems.filter(item => item.id !== id));
  };

  // Gallery management functions
  const addGalleryItem = () => {
    if (newGalleryItem.url && newGalleryItem.alt) {
      const item: GalleryItem = {
        id: Date.now().toString(),
        url: newGalleryItem.url,
        alt: newGalleryItem.alt,
        title: newGalleryItem.title || '',
        description: newGalleryItem.description || ''
      };
      setGalleryItems([...galleryItems, item]);
      setNewGalleryItem({});
      setIsAddingGalleryItem(false);
    }
  };

  const updateGalleryItem = (id: string, updatedItem: Partial<GalleryItem>) => {
    setGalleryItems(galleryItems.map(item => 
      item.id === id ? { ...item, ...updatedItem } : item
    ));
    setEditingGalleryItem(null);
  };

  const deleteGalleryItem = (id: string) => {
    setGalleryItems(galleryItems.filter(item => item.id !== id));
  };

  // Maintenance mode toggle - simple function without loops
  //const handleMaintenanceToggle = () => {
    //const newMode = !maintenanceMode;
    //setMaintenanceMode(newMode);
    //localStorage.setItem('maintenanceMode', newMode.toString());
  //};
const handleMaintenanceToggle = async () => {
  const newMode = !maintenanceMode;
  setMaintenanceMode(newMode);

  await fetch("/api/save-config.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ maintenanceMode: newMode })
  });
};

  const getCategoryDisplayName = (category: string) => {
    const names: { [key: string]: string } = {
      'appetizers': 'Appetizers',
      'pho': 'Pho',
      'vermicelli': 'Rice Vermicelli',
      'specialty': 'Chef\'s Specialties',
      'rice': 'Rice Dishes',
      'beverages': 'Beverages'
    };
    return names[category] || category.charAt(0).toUpperCase() + category.slice(1);
  };
	if (loadingConfig) {
	  return (
		<div className="min-h-screen flex items-center justify-center">
		  <p className="text-gray-600">Loading Admin Panel...</p>
		</div>
	  );
	}
  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <img 
                src="/logo.png" 
                alt="Stone Pho Logo" 
                className="h-8 w-auto object-cover rounded-lg"
              />
              <h1 className="text-xl font-bold text-gray-900">Stone Pho Admin</h1>
            </div>
            <button
              onClick={onLogout}
              className="flex items-center space-x-2 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              <LogOut className="w-4 h-4" />
              <span>Logout</span>
            </button>
          </div>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <nav className="flex space-x-8">
            <button
              onClick={() => setActiveTab('menu')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'menu'
                  ? 'border-red-500 text-red-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <MenuIcon className="w-4 h-4 inline mr-2" />
              Menu Management
            </button>
            <button
              onClick={() => setActiveTab('gallery')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'gallery'
                  ? 'border-red-500 text-red-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <ImageIcon className="w-4 h-4 inline mr-2" />
              Gallery Management
            </button>
            <button
              onClick={() => setActiveTab('maintenance')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'maintenance'
                  ? 'border-red-500 text-red-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <Settings className="w-4 h-4 inline mr-2" />
              Maintenance Mode
            </button>
            <button
              onClick={() => setActiveTab('hero')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'hero'
                  ? 'border-red-500 text-red-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <ImageIcon className="w-4 h-4 inline mr-2" />
              Hero Background
            </button>
            <button
              onClick={() => setActiveTab('loyalty')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'loyalty'
                  ? 'border-red-500 text-red-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <Star className="w-4 h-4 inline mr-2" />
              Loyalty Program
            </button>
          </nav>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* MENU MANAGEMENT TAB */}
        {activeTab === 'menu' && (
          <div>
            {/* Categories Management */}
            <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">Categories</h2>
              <div className="flex flex-wrap gap-2 mb-4">
                {categories.map((category) => (
                  <div key={category} className="flex items-center bg-gray-100 rounded-full px-3 py-1">
                    <span className="text-sm font-medium text-gray-700">
                      {getCategoryDisplayName(category)}
                    </span>
                    {categories.length > 1 && (
                      <button
                        onClick={() => deleteCategory(category)}
                        className="ml-2 text-red-500 hover:text-red-700"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                ))}
              </div>
              <div className="flex gap-2">
                <input
                  type="text"
                  value={newCategory}
                  onChange={(e) => setNewCategory(e.target.value)}
                  placeholder="New category name"
                  className="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                />
                <button
                  onClick={addCategory}
                  className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                >
                  <Plus className="w-4 h-4" />
                  <span>Add Category</span>
                </button>
              </div>
            </div>

            {/* Menu Items by Category */}
            {categories.map((category) => (
              <div key={category} className="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div className="flex justify-between items-center mb-4">
                  <h2 className="text-lg font-semibold text-gray-900">
                    {getCategoryDisplayName(category)} ({menuItems.filter(item => item.category === category).length} items)
                  </h2>
                  <button
                    onClick={() => {
                      setNewItem({ category });
                      setIsAddingItem(true);
                    }}
                    className="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                  >
                    <Plus className="w-4 h-4" />
                    <span>Add Item</span>
                  </button>
                </div>

                {/* Add New Item Form */}
                {isAddingItem && newItem.category === category && (
                  <div className="bg-gray-50 rounded-lg p-4 mb-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                      <input
                        type="text"
                        placeholder="Item name"
                        value={newItem.name || ''}
                        onChange={(e) => setNewItem({ ...newItem, name: e.target.value })}
                        className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                      />
                      <input
                        type="text"
                        placeholder="Price (e.g., $12.95)"
                        value={newItem.price || ''}
                        onChange={(e) => setNewItem({ ...newItem, price: e.target.value })}
                        className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                      />
                    </div>
                    <textarea
                      placeholder="Description"
                      value={newItem.description || ''}
                      onChange={(e) => setNewItem({ ...newItem, description: e.target.value })}
                      rows={3}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent mb-4"
                    />
                    
                    {/* Image Upload */}
                    <div className="mb-4">
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Product Image
                      </label>
                      <ImageUpload
                        currentImage={newItem.image}
                        onImageChange={(imageUrl) => setNewItem({ ...newItem, image: imageUrl })}
                        className="w-full"
                      />
                    </div>
                    
                    <div className="flex gap-2">
                      <button
                        onClick={addMenuItem}
                        className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                      >
                        <Save className="w-4 h-4" />
                        <span>Save</span>
                      </button>
                      <button
                        onClick={() => {
                          setIsAddingItem(false);
                          setNewItem({});
                        }}
                        className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                      >
                        <X className="w-4 h-4" />
                        <span>Cancel</span>
                      </button>
                    </div>
                  </div>
                )}

                {/* Menu Items List */}
                <div className="space-y-4">
                  {menuItems
                    .filter(item => item.category === category)
                    .map((item) => (
                      <div key={item.id} className="border border-gray-200 rounded-lg p-4">
                        {editingItem === item.id ? (
                          <EditItemForm
                            item={item}
                            categories={categories}
                            onSave={(updatedItem) => updateMenuItem(item.id, updatedItem)}
                            onCancel={() => setEditingItem(null)}
                          />
                        ) : (
                          <div className="flex justify-between items-start">
                            <div className="flex-1">
                              {/* Display image if exists */}
                              {item.image && (
                                <div className="mb-3">
                                  <img
                                    src={item.image}
                                    alt={item.name}
                                    className="w-24 h-16 object-cover rounded-lg border border-gray-200"
                                    onError={(e) => {
                                      (e.target as HTMLImageElement).style.display = 'none';
                                    }}
                                  />
                                </div>
                              )}
                              <div className="flex justify-between items-start mb-2">
                                <h3 className="font-semibold text-gray-900">{item.name}</h3>
                                <span className="font-bold text-red-600">{item.price}</span>
                              </div>
                              <p className="text-gray-600 text-sm mb-2" style={{ whiteSpace: 'pre-line' }}>
                                {item.description}
                              </p>
                              <span className="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
                                {getCategoryDisplayName(item.category)}
                              </span>
                            </div>
                            <div className="flex space-x-2 ml-4">
                              <button
                                onClick={() => setEditingItem(item.id)}
                                className="text-blue-600 hover:text-blue-800"
                              >
                                <Edit className="w-4 h-4" />
                              </button>
                              <button
                                onClick={() => deleteMenuItem(item.id)}
                                className="text-red-600 hover:text-red-800"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            </div>
                          </div>
                        )}
                      </div>
                    ))}
                </div>
              </div>
            ))}
          </div>
        )}

        {/* GALLERY MANAGEMENT TAB */}
        {activeTab === 'gallery' && (
          <div>
            {/* Gallery Header */}
            <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
              <div className="flex justify-between items-center mb-4">
                <h2 className="text-lg font-semibold text-gray-900">
                  Gallery Images ({galleryItems.length} images)
                </h2>
                <button
                  onClick={() => setIsAddingGalleryItem(true)}
                  className="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                >
                  <Plus className="w-4 h-4" />
                  <span>Add Image</span>
                </button>
              </div>

              {/* Add New Gallery Item Form */}
              {isAddingGalleryItem && (
                <div className="bg-gray-50 rounded-lg p-4 mb-6">
                  <h3 className="font-medium text-gray-900 mb-4">Add New Gallery Image</h3>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Image Title
                      </label>
                      <input
                        type="text"
                        placeholder="e.g., Signature Pho"
                        value={newGalleryItem.title || ''}
                        onChange={(e) => setNewGalleryItem({ ...newGalleryItem, title: e.target.value })}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Alt Text (Required)
                      </label>
                      <input
                        type="text"
                        placeholder="e.g., Fresh pho bowl"
                        value={newGalleryItem.alt || ''}
                        onChange={(e) => setNewGalleryItem({ ...newGalleryItem, alt: e.target.value })}
                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        required
                      />
                    </div>
                  </div>

                  <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Description
                    </label>
                    <textarea
                      placeholder="Brief description of the image"
                      value={newGalleryItem.description || ''}
                      onChange={(e) => setNewGalleryItem({ ...newGalleryItem, description: e.target.value })}
                      rows={2}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    />
                  </div>

                  {/* Image Upload */}
                  <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Gallery Image
                    </label>
                    <ImageUpload
                      currentImage={newGalleryItem.url}
                      onImageChange={(imageUrl) => setNewGalleryItem({ ...newGalleryItem, url: imageUrl })}
                      className="w-full"
                    />
                  </div>
                  
                  <div className="flex gap-2">
                    <button
                      onClick={addGalleryItem}
                      disabled={!newGalleryItem.url || !newGalleryItem.alt}
                      className="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                    >
                      <Save className="w-4 h-4" />
                      <span>Save Image</span>
                    </button>
                    <button
                      onClick={() => {
                        setIsAddingGalleryItem(false);
                        setNewGalleryItem({});
                      }}
                      className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
                    >
                      <X className="w-4 h-4" />
                      <span>Cancel</span>
                    </button>
                  </div>
                </div>
              )}

              {/* Gallery Items Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {galleryItems.map((item) => (
                  <div key={item.id} className="border border-gray-200 rounded-lg overflow-hidden">
                    {editingGalleryItem === item.id ? (
                      <EditGalleryItemForm
                        item={item}
                        onSave={(updatedItem) => updateGalleryItem(item.id, updatedItem)}
                        onCancel={() => setEditingGalleryItem(null)}
                      />
                    ) : (
                      <>
                        {/* Image Preview */}
                        <div className="relative">
                          <img
                            src={item.url}
                            alt={item.alt}
                            className="w-full h-48 object-cover"
                            onError={(e) => {
                              (e.target as HTMLImageElement).src = '/placeholder-image.jpg';
                            }}
                          />
                          <div className="absolute top-2 right-2 flex space-x-1">
                            <button
                              onClick={() => setEditingGalleryItem(item.id)}
                              className="bg-blue-600 hover:bg-blue-700 text-white p-1 rounded"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => deleteGalleryItem(item.id)}
                              className="bg-orange-600 hover:bg-orange-700 text-white p-1 rounded"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </div>

                        {/* Image Info */}
                        <div className="p-4">
                          <h3 className="font-semibold text-gray-900 mb-1">
                            {item.title || 'Untitled'}
                          </h3>
                          <p className="text-sm text-gray-600 mb-2">
                            Alt: {item.alt}
                          </p>
                          {item.description && (
                            <p className="text-sm text-gray-500">
                              {item.description}
                            </p>
                          )}
                        </div>
                      </>
                    )}
                  </div>
                ))}
              </div>

              {galleryItems.length === 0 && (
                <div className="text-center py-12">
                  <div className="text-gray-400 mb-4">
                    <ImageIcon className="w-12 h-12 mx-auto" />
                  </div>
                  <h3 className="text-lg font-medium text-gray-900 mb-2">No Gallery Images</h3>
                  <p className="text-gray-500 mb-4">Add your first gallery image to get started.</p>
                  <button
                    onClick={() => setIsAddingGalleryItem(true)}
                    className="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg"
                  >
                    Add First Image
                  </button>
                </div>
              )}
            </div>
          </div>
        )}

        {/* MAINTENANCE MODE TAB */}
        {activeTab === 'maintenance' && (
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-6">Maintenance Mode</h2>
            
            <div className="space-y-6">
              {/* Current Status */}
              <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex items-center space-x-3">
                  {maintenanceMode ? (
                    <PowerOff className="w-6 h-6 text-red-600" />
                  ) : (
                    <Power className="w-6 h-6 text-green-600" />
                  )}
                  <div>
                    <h3 className="font-medium text-gray-900">Website Status</h3>
                    <p className="text-sm text-gray-600">
                      {maintenanceMode ? 'Maintenance Mode Active' : 'Website Live'}
                    </p>
                  </div>
                </div>
                <div className={`px-3 py-1 rounded-full text-sm font-medium ${
                  maintenanceMode 
                    ? 'bg-orange-100 text-red-800' 
                    : 'bg-green-100 text-green-800'
                }`}>
                  {maintenanceMode ? '🔴 Maintenance' : '🟢 Live'}
                </div>
              </div>

              {/* Toggle Button */}
              <div className="text-center">
                <button
                  onClick={handleMaintenanceToggle}
                  className={`px-8 py-4 rounded-lg font-semibold text-lg transition-all duration-300 transform hover:scale-105 ${
                    maintenanceMode
                      ? 'bg-green-600 hover:bg-green-700 text-white'
                      : 'bg-orange-600 hover:bg-orange-700 text-white'
                  }`}
                >
                  {maintenanceMode ? (
                    <>
                      <Power className="w-5 h-5 inline mr-2" />
                      🟢 Enable Website
                    </>
                  ) : (
                    <>
                      <PowerOff className="w-5 h-5 inline mr-2" />
                      🔴 Enable Maintenance
                    </>
                  )}
                </button>
              </div>

              {/* Information */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 className="font-medium text-blue-900 mb-2">How Maintenance Mode Works:</h4>
                <ul className="text-sm text-blue-800 space-y-1">
                  <li>• When enabled, customers will see a maintenance page</li>
                  <li>• They can still contact you and place orders via phone/links</li>
                  <li>• Admin panel remains accessible via #admin URL</li>
                  <li>• Use this when updating menu or fixing issues</li>
                </ul>
              </div>

              {maintenanceMode && (
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                  <h4 className="font-medium text-yellow-900 mb-2">⚠️ Maintenance Mode Active</h4>
                  <p className="text-sm text-yellow-800">
                    Your website is currently in maintenance mode. Customers will see the maintenance page 
                    with contact information and ordering links.
                  </p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* HERO BACKGROUND TAB */}
        {activeTab === 'hero' && (
          <HeroBackgroundManager />
        )}

        {/* LOYALTY PROGRAM TAB */}
        {activeTab === 'loyalty' && (
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-6">
              <Star className="w-5 h-5 inline mr-2 text-yellow-500" />
              Loyalty Program Management
            </h2>
            <LoyaltyAdminTab />
          </div>
        )}
      </div>
    </div>
  );
};

// Edit Gallery Item Form Component
const EditGalleryItemForm: React.FC<{
  item: GalleryItem;
  onSave: (item: Partial<GalleryItem>) => void;
  onCancel: () => void;
}> = ({ item, onSave, onCancel }) => {
  const [editedItem, setEditedItem] = useState<Partial<GalleryItem>>(item);

  return (
    <div className="p-4 space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Image Title
        </label>
        <input
          type="text"
          value={editedItem.title || ''}
          onChange={(e) => setEditedItem({ ...editedItem, title: e.target.value })}
          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Alt Text
        </label>
        <input
          type="text"
          value={editedItem.alt || ''}
          onChange={(e) => setEditedItem({ ...editedItem, alt: e.target.value })}
          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Description
        </label>
        <textarea
          value={editedItem.description || ''}
          onChange={(e) => setEditedItem({ ...editedItem, description: e.target.value })}
          rows={2}
          className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
        />
      </div>
      
      {/* Image Upload */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Gallery Image
        </label>
        <ImageUpload
          currentImage={editedItem.url}
          onImageChange={(imageUrl) => setEditedItem({ ...editedItem, url: imageUrl })}
          className="w-full"
        />
      </div>
      
      <div className="flex gap-2">
        <button
          onClick={() => onSave(editedItem)}
          className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
        >
          <Save className="w-4 h-4" />
          <span>Save</span>
        </button>
        <button
          onClick={onCancel}
          className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
        >
          <X className="w-4 h-4" />
          <span>Cancel</span>
        </button>
      </div>
    </div>
  );
};

// Edit Item Form Component
const EditItemForm: React.FC<{
  item: MenuItem;
  categories: string[];
  onSave: (item: Partial<MenuItem>) => void;
  onCancel: () => void;
}> = ({ item, categories, onSave, onCancel }) => {
  const [editedItem, setEditedItem] = useState<Partial<MenuItem>>(item);

  const getCategoryDisplayName = (category: string) => {
    const names: { [key: string]: string } = {
      'appetizers': 'Appetizers',
      'pho': 'Pho',
      'vermicelli': 'Rice Vermicelli',
      'specialty': 'Chef\'s Specialties',
      'rice': 'Rice Dishes',
      'beverages': 'Beverages'
    };
    return names[category] || category.charAt(0).toUpperCase() + category.slice(1);
  };

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          type="text"
          value={editedItem.name || ''}
          onChange={(e) => setEditedItem({ ...editedItem, name: e.target.value })}
          className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
        />
        <input
          type="text"
          value={editedItem.price || ''}
          onChange={(e) => setEditedItem({ ...editedItem, price: e.target.value })}
          className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
        />
      </div>
      <textarea
        value={editedItem.description || ''}
        onChange={(e) => setEditedItem({ ...editedItem, description: e.target.value })}
        rows={3}
        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
      />
      
      {/* Image Upload in Edit Form */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Product Image
        </label>
        <ImageUpload
          currentImage={editedItem.image}
          onImageChange={(imageUrl) => setEditedItem({ ...editedItem, image: imageUrl })}
          className="w-full"
        />
      </div>
      
      <select
        value={editedItem.category || ''}
        onChange={(e) => setEditedItem({ ...editedItem, category: e.target.value })}
        className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
      >
        {categories.map((category) => (
          <option key={category} value={category}>
            {getCategoryDisplayName(category)}
          </option>
        ))}
      </select>
      <div className="flex gap-2">
        <button
          onClick={() => onSave(editedItem)}
          className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
        >
          <Save className="w-4 h-4" />
          <span>Save</span>
        </button>
        <button
          onClick={onCancel}
          className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
        >
          <X className="w-4 h-4" />
          <span>Cancel</span>
        </button>
      </div>
    </div>
  );
};

export default AdminPanel;