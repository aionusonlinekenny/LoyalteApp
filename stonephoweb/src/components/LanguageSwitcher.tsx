import React from 'react';
import { useLanguage } from '../contexts/LanguageContext';

const LanguageSwitcher: React.FC = () => {
  const { lang, setLang } = useLanguage();

  return (
    <div className="flex items-center border border-current rounded-full overflow-hidden text-xs font-semibold">
      <button
        onClick={() => setLang('en')}
        className={`px-2 py-1 transition-colors ${
          lang === 'en' ? 'bg-red-600 text-white' : 'hover:bg-gray-100'
        }`}
      >
        EN
      </button>
      <button
        onClick={() => setLang('es')}
        className={`px-2 py-1 transition-colors ${
          lang === 'es' ? 'bg-red-600 text-white' : 'hover:bg-gray-100'
        }`}
      >
        ES
      </button>
    </div>
  );
};

export default LanguageSwitcher;
