import React, { useState, useEffect } from 'react';
import { DeviceInfo } from '../hooks/useDeviceDetection';
import ScrollAnimatedSection from './ScrollAnimatedSection';
import { useLanguage } from '../contexts/LanguageContext';

interface MenuProps {
  deviceInfo: DeviceInfo;
  forcedDevice?: 'mobile' | 'tablet' | 'desktop' | null;
}

const Menu: React.FC<MenuProps> = ({ deviceInfo, forcedDevice }) => {
  const { t, lang } = useLanguage();
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
    name: t(`menu.categories.${cat}`) !== `menu.categories.${cat}`
      ? t(`menu.categories.${cat}`)
      : cat.charAt(0).toUpperCase() + cat.slice(1),
  }));

  return (
    <section
      id="menu"
      className="relative py-20 bg-cover bg-center"
      style={{ backgroundImage: "url('/uploads/menu-bg.png')" }}
    >
      {/* Dark overlay */}
      <div className="absolute inset-0" style={{ background: 'rgba(10,8,6,0.93)' }} />
      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ScrollAnimatedSection animation="fadeInUp" className="text-center mb-16">
          <div>
            <span style={{
              display: 'block',
              fontFamily: "'Inter',sans-serif",
              fontSize: '0.6rem',
              fontWeight: 700,
              letterSpacing: '0.25em',
              textTransform: 'uppercase',
              color: '#C9A96E',
              marginBottom: '1rem',
            }}>What We Serve</span>
            <h2
              className={`font-display text-lux-cream mb-4 ${isMobileView ? 'text-3xl' : 'text-4xl sm:text-5xl'}`}
              style={{ fontWeight: 700, lineHeight: 1 }}
            >
              {t('menu.heading')}
            </h2>
            <span className="gold-line" style={{ maxWidth: '60px', margin: '0 auto 1.25rem' }} />
            <p className={`text-lux-muted max-w-3xl mx-auto leading-relaxed ${isMobileView ? 'text-base' : 'text-lg'}`}>
              {t('menu.subtitle')}
            </p>
          </div>
        </ScrollAnimatedSection>

        {/* Category Tabs */}
        <ScrollAnimatedSection animation="fadeInUp" delay={500} className="flex justify-center mb-12">
          <div>
            <div className={`flex gap-1 ${isMobileView ? 'flex-col w-full max-w-xs' : 'flex-row'}`}
              style={{ border: '1px solid #2A2520', padding: '4px' }}
            >
              {displayCategories.map((category) => (
                <button
                  key={category.id}
                  onClick={() => setActiveCategory(category.id)}
                  className={`font-semibold transition-all duration-300 ${isMobileView ? 'px-4 py-2 text-sm w-full' : 'px-5 py-2.5 text-sm'}`}
                  style={{
                    background: activeCategory === category.id ? '#C9A96E' : 'transparent',
                    color: activeCategory === category.id ? '#0F0D0B' : '#7A6E64',
                    border: 'none',
                    cursor: 'pointer',
                    fontFamily: "'Inter',sans-serif",
                    fontSize: '0.72rem',
                    fontWeight: 600,
                    letterSpacing: '0.04em',
                    transition: 'all 0.2s ease',
                  }}
                  onMouseEnter={e => {
                    if (activeCategory !== category.id)
                      (e.currentTarget as HTMLElement).style.color = '#C9A96E';
                  }}
                  onMouseLeave={e => {
                    if (activeCategory !== category.id)
                      (e.currentTarget as HTMLElement).style.color = '#7A6E64';
                  }}
                >
                  {category.name}
                </button>
              ))}
            </div>
          </div>
        </ScrollAnimatedSection>

        {/* Menu Items */}
        <div className={`grid gap-5 ${isMobileView ? 'grid-cols-1' : isTabletView ? 'md:grid-cols-2' : 'md:grid-cols-2 lg:grid-cols-3'}`}>
          {menuItems[activeCategory as keyof typeof menuItems]?.map((item: any, index: number) => (
            <ScrollAnimatedSection key={index} animation="scaleIn" delay={index * 100}>
              <div
                className={`relative overflow-hidden transition-all duration-300 ${isMobileView ? 'p-0' : 'p-0'}`}
                style={{
                  background: '#1A1714',
                  border: '1px solid #2A2520',
                  transition: 'border-color 0.25s ease',
                }}
                onMouseEnter={e => (e.currentTarget.style.borderColor = '#C9A96E44')}
                onMouseLeave={e => (e.currentTarget.style.borderColor = '#2A2520')}
              >
                
                {/* Image */}
                <div style={{ overflow: 'hidden' }}>
                  <img
                    src={item.image ? item.image : "/uploads/menu/default-placeholder.jpg"}
                    alt={item.name}
                    className="w-full object-cover transition-transform duration-500 hover:scale-105"
                    style={{ height: '180px' }}
                  />
                </div>

                {/* Body */}
                <div style={{ padding: isMobileView ? '1rem' : '1.25rem' }}>
                  <div className="flex justify-between items-start mb-2 gap-2">
                    <h3
                      className="font-display text-lux-cream"
                      style={{ fontWeight: 700, fontSize: isMobileView ? '1.05rem' : '1.15rem', lineHeight: 1.2 }}
                    >
                      {lang === 'es' && item.name_es ? item.name_es : item.name}
                    </h3>
                    <span
                      className="font-display text-lux-gold flex-shrink-0"
                      style={{ fontWeight: 700, fontSize: isMobileView ? '1rem' : '1.1rem' }}
                    >
                      {item.price}
                    </span>
                  </div>
                  <p
                    className="text-lux-muted leading-relaxed"
                    style={{ fontSize: isMobileView ? '0.78rem' : '0.82rem', whiteSpace: 'pre-line', minHeight: '4rem' }}
                  >
                    {item.description}
                  </p>
                </div>
              </div>
            </ScrollAnimatedSection>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Menu;
