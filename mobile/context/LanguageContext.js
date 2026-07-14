import React, { createContext, useContext, useEffect, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import i18n from '../lib/i18n';

const LANG_KEY = 'msas_language';

export const LANGUAGES = [
  { code: 'en', name: 'English',          nativeName: 'English',      flag: '🇬🇧', ttsCode: 'en-NG' },
  { code: 'ha', name: 'Hausa',            nativeName: 'Hausa',        flag: '🟢',  ttsCode: 'ha'    },
  { code: 'yo', name: 'Yoruba',           nativeName: 'Yorùbá',       flag: '🟡',  ttsCode: 'yo'    },
  { code: 'ig', name: 'Igbo',             nativeName: 'Igbo',         flag: '🔵',  ttsCode: 'ig'    },
  { code: 'ff', name: 'Fulfulde (Fulani)',nativeName: 'Fulfulde',     flag: '🔴',  ttsCode: 'en-NG' }, // No TTS engine for ff; fall back to English voice
];

const LanguageContext = createContext(null);

export function LanguageProvider({ children }) {
  const [language, setLanguageState] = useState('en');
  const [accessibilityMode, setAccessibilityMode] = useState(false);
  const [voiceEnabled, setVoiceEnabled] = useState(true);
  const [textSize, setTextSize] = useState('normal'); // 'normal' | 'large' | 'xlarge'

  useEffect(() => {
    AsyncStorage.multiGet([LANG_KEY, 'msas_accessibility', 'msas_voice', 'msas_textsize']).then(pairs => {
      const stored = Object.fromEntries(pairs);
      if (stored[LANG_KEY]) {
        setLanguageState(stored[LANG_KEY]);
        i18n.changeLanguage(stored[LANG_KEY]);
      }
      if (stored['msas_accessibility'] === 'true') setAccessibilityMode(true);
      if (stored['msas_voice'] === 'false') setVoiceEnabled(false);
      if (stored['msas_textsize'])           setTextSize(stored['msas_textsize']);
    });
  }, []);

  const changeLanguage = async (code) => {
    setLanguageState(code);
    i18n.changeLanguage(code);
    await AsyncStorage.setItem(LANG_KEY, code);
  };

  const toggleAccessibility = async () => {
    const next = !accessibilityMode;
    setAccessibilityMode(next);
    await AsyncStorage.setItem('msas_accessibility', String(next));
  };

  const toggleVoice = async () => {
    const next = !voiceEnabled;
    setVoiceEnabled(next);
    await AsyncStorage.setItem('msas_voice', String(next));
  };

  const changeTextSize = async (size) => {
    setTextSize(size);
    await AsyncStorage.setItem('msas_textsize', size);
  };

  const currentLang = LANGUAGES.find(l => l.code === language) || LANGUAGES[0];

  return (
    <LanguageContext.Provider value={{
      language, currentLang, changeLanguage,
      accessibilityMode, toggleAccessibility,
      voiceEnabled, toggleVoice,
      textSize, changeTextSize,
    }}>
      {children}
    </LanguageContext.Provider>
  );
}

export const useLanguage = () => {
  const ctx = useContext(LanguageContext);
  if (!ctx) throw new Error('useLanguage must be inside LanguageProvider');
  return ctx;
};
