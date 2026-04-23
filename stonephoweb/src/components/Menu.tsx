import React, { useState, useEffect } from 'react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';

interface MenuProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Menu: React.FC<MenuProps> = ({ deviceInfo, forcedDevice }) => {
  const [activeCategory, setActiveCategory] = useState('pho');
  const [categories, setCategories] = useState<string[]>(['appetizers', 'pho', 'vermicelli', 'specialty', 'rice', 'beverages']);

  const currentDevice = forcedDevice || deviceInfo.deviceType;
  const isMobileView = currentDevice === 'mobile';
  const isTabletView = currentDevice === 'tablet';

  const defaultMenuItems = {
    appetizers: [
      { name: 'A1. Vietnamese Fried Egg Roll (2 rolls)', price: '$5.75', description: 'Egg wrap, ground pork...' },
      { name: 'A2. Spring Roll (2 rolls)', price: '$5.75', description: 'Rice paper, rice vermicelli...' },
    ],
    pho: [
      { name: 'SP1. Stone Pho Special', price: '$24.95', description: '48-hour broth...' },
    ],
    beverages: [
      { name: 'Thai Milk Tea', price: '$7.45', description: 'Traditional Thai milk tea.' },
    ],
  };

  const [menuItems, setMenuItems] = useState<any>(defaultMenuItems);

  useEffect(() => {
    const loadMenuFromServer = async () => {
      try {
        const menuResponse = await fetch('/api/load-menu.php');
        if (menuResponse.ok) {
          const menuData = await menuResponse.json();
          if (menuData.success && menuData.data && menuData.data.menuItems) {
            const categoriesResponse = await fetch('/api/load-categories.php');
            if (categoriesResponse.ok) {
              const categoriesData = await categoriesResponse.json();
              if (categoriesData.success && categoriesData.data && categoriesData.data.categories) {
                const items = menuData.data.menuItems;
                const cats = categoriesData.data.categories;

                const groupedItems: any = {};
                cats.forEach((cat: string) => {
                  groupedItems[cat] = items.filter((item: any) => item.category === cat);
                });

                setMenuItems(groupedItems);
                setCategories(cats);
                return;
              }
            }
          }
        }
        throw new Error('Failed to load from server');
      } catch (error) {
        console.warn('Failed to load menu from server, using defaults:', error);
        setMenuItems(defaultMenuItems);
        setCategories(['appetizers', 'pho', 'beverages']);
      }
    };

    loadMenuFromServer();
  }, []);

  const displayCategories = categories.map(cat => ({
    id: cat,
    name: cat === 'pho' ? 'Pho' :
          cat === 'appetizers' ? 'Appetizers' :
          cat === 'vermicelli' ? 'Rice Vermicelli' :
          cat === 'specialty' ? 'Chef\'s Specialties' :
          cat === 'rice' ? 'Rice Dishes' :
          cat === 'beverages' ? 'Beverages' :
          cat.charAt(0).toUpperCase() + cat.slice(1)
  }));

  return (
    <section
	  id="menu"
	  className="relative py-20 bg-cover bg-center"
	  style={{
		backgroundImage: "url('/uploads/menu-bg.png')", // ảnh nền bạn để trong /uploads
	  }}
	>
	  {/* Overlay để làm mờ / blend màu */}
	  <div className="absolute inset-0 bg-white/90"></div>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ScrollAnimatedSection animation="fadeInUp" className="text-center mb-16">
          <div>
            <h2 className={`font-bold text-gray-900 mb-4 ${isMobileView ? 'text-3xl' : 'text-4xl sm:text-5xl'}`}>
              Our Menu
            </h2>
            <p className={`text-gray-600 max-w-3xl mx-auto leading-relaxed ${isMobileView ? 'text-lg' : 'text-xl'}`}>
              Discover our authentic Vietnamese dishes, from traditional pho to flavorful appetizers and hearty main courses.
            </p>
          </div>
        </ScrollAnimatedSection>

        {/* Category Tabs */}
        <ScrollAnimatedSection animation="fadeInUp" delay={500} className="flex justify-center mb-12">
          <div>
            <div className={`bg-gray-100 rounded-full p-1 ${isMobileView ? 'flex-col space-y-1 w-full max-w-xs' : 'inline-flex'}`}>
              {displayCategories.map((category) => (
                <button
                  key={category.id}
                  onClick={() => setActiveCategory(category.id)}
                  className={`rounded-full font-semibold transition-all duration-300 hover:scale-105 ${
                    isMobileView ? 'px-4 py-2 text-sm w-full' : 'px-6 py-3'
                  } ${activeCategory === category.id ? 'bg-orange-600 text-white shadow-lg' : 'text-gray-600 hover:text-red-600'}`}
                >
                  {category.name}
                </button>
              ))}
            </div>
          </div>
        </ScrollAnimatedSection>

        {/* Menu Items */}
        <div className={`grid gap-6 ${isMobileView ? 'grid-cols-1' : isTabletView ? 'md:grid-cols-2' : 'md:grid-cols-2 lg:grid-cols-3'}`}>
          {menuItems[activeCategory as keyof typeof menuItems]?.map((item: any, index: number) => (
            <ScrollAnimatedSection key={index} animation="scaleIn" delay={index * 100}>
              <div className={`relative bg-gray-50 rounded-2xl hover:shadow-lg transition-all duration-300 hover:bg-white border border-transparent hover:border-red-200 overflow-hidden ${isMobileView ? 'p-4' : 'p-6'}`}>
                
                {/* ✅ Hình ảnh từ JSON */}
                <div className="mb-4 -mx-4 -mt-4 md:-mx-6 md:-mt-6">
                  <img
                    src={item.image ? item.image : "/uploads/menu/default-placeholder.jpg"}
                    alt={item.name}
                    className="w-full h-32 object-cover rounded-t-2xl"
                  />
                  
                </div>

                {/* Tên và giá */}
                <div className="flex justify-between items-start mb-3">
                  <h3 className={`font-bold text-gray-900 ${isMobileView ? 'text-lg' : 'text-xl'}`}>
                    {item.name}
                  </h3>
                  <span className={`font-bold text-red-600 ${isMobileView ? 'text-lg' : 'text-xl'}`}>
                    {item.price}
                  </span>
                </div>

                {/* Mô tả */}
                <p className={`text-gray-600 leading-relaxed ${isMobileView ? 'text-sm' : ''}`} style={{ whiteSpace: 'pre-line', minHeight: '4.5rem' }}>
                  {item.description}
                </p>

                {/* Nút đặt hàng */}
                {/*<div className="mt-4 flex gap-2">
                  <button 
                    onClick={() => window.open('https://www.clover.com/online-ordering/stone-pho-valdosta', '_blank')}
                    className={`bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-all duration-300 ${isMobileView ? 'px-3 py-1 text-xs flex-1' : 'px-4 py-2 text-sm flex-1'}`}>
                    Order Now
                  </button>
                  <button className={`border border-red-600 text-red-600 hover:bg-orange-600 hover:text-white rounded-lg font-medium transition-all duration-300 ${isMobileView ? 'px-3 py-1 text-xs' : 'px-4 py-2 text-sm'}`}>
                    ♡
                  </button>
                </div>*/}
              </div>
            </ScrollAnimatedSection>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Menu;
