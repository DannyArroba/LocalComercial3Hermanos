import React, { createContext, useContext, useEffect, useState } from 'react';

const storageKey = 'tiendita-theme';
const ThemeContext = createContext({ theme: 'light', isDark: false, toggleTheme: () => {} });

function savedTheme() {
  try { return localStorage.getItem(storageKey) === 'navy' ? 'navy' : 'light'; }
  catch { return 'light'; }
}

function applyTheme(theme) {
  document.documentElement.dataset.theme = theme;
  document.documentElement.style.colorScheme = theme === 'navy' ? 'dark' : 'light';
}

// Set the document before React renders, including on a saved-theme reload.
const initialTheme = typeof document === 'undefined' ? 'light' : savedTheme();
if (typeof document !== 'undefined') applyTheme(initialTheme);

export function ThemeProvider({ children }) {
  const [theme, setTheme] = useState(initialTheme);
  useEffect(() => {
    applyTheme(theme);
    try { localStorage.setItem(storageKey, theme); } catch { /* Theme still works without storage. */ }
  }, [theme]);
  useEffect(() => {
    const synchronize = (event) => {
      if (event.key === storageKey) setTheme(event.newValue === 'navy' ? 'navy' : 'light');
    };
    window.addEventListener('storage', synchronize);
    return () => window.removeEventListener('storage', synchronize);
  }, []);
  const toggleTheme = () => setTheme((current) => current === 'navy' ? 'light' : 'navy');
  return <ThemeContext.Provider value={{ theme, isDark: theme === 'navy', toggleTheme }}>{children}</ThemeContext.Provider>;
}

export const useTheme = () => useContext(ThemeContext);
