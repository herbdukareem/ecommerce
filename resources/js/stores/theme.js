import { defineStore } from 'pinia';

export const useThemeStore = defineStore('theme', {
  state: () => ({
    currentTheme: localStorage.getItem('theme') || 'orange',
    themes: {
      blue: {
        name: 'Ocean Blue',
        primary: '#3B82F6',
        primaryDark: '#2563EB',
        primaryLight: '#60A5FA',
        secondary: '#8B5CF6',
        accent: '#10B981',
        danger: '#EF4444',
        warning: '#F59E0B',
        success: '#10B981',
        info: '#06B6D4',
        background: '#F9FAFB',
        surface: '#FFFFFF',
        text: '#111827',
        textSecondary: '#6B7280',
        border: '#E5E7EB',
      },
      purple: {
        name: 'Royal Purple',
        primary: '#8B5CF6',
        primaryDark: '#7C3AED',
        primaryLight: '#A78BFA',
        secondary: '#EC4899',
        accent: '#14B8A6',
        danger: '#EF4444',
        warning: '#F59E0B',
        success: '#10B981',
        info: '#06B6D4',
        background: '#F9FAFB',
        surface: '#FFFFFF',
        text: '#111827',
        textSecondary: '#6B7280',
        border: '#E5E7EB',
      },
      green: {
        name: 'Forest Green',
        primary: '#10B981',
        primaryDark: '#059669',
        primaryLight: '#34D399',
        secondary: '#3B82F6',
        accent: '#F59E0B',
        danger: '#EF4444',
        warning: '#F59E0B',
        success: '#10B981',
        info: '#06B6D4',
        background: '#F9FAFB',
        surface: '#FFFFFF',
        text: '#111827',
        textSecondary: '#6B7280',
        border: '#E5E7EB',
      },
      orange: {
        name: 'Sunset Orange',
        primary: '#F97316',
        primaryDark: '#EA580C',
        primaryLight: '#FB923C',
        secondary: '#8B5CF6',
        accent: '#10B981',
        danger: '#EF4444',
        warning: '#F59E0B',
        success: '#10B981',
        info: '#06B6D4',
        background: '#F9FAFB',
        surface: '#FFFFFF',
        text: '#111827',
        textSecondary: '#6B7280',
        border: '#E5E7EB',
      },
      dark: {
        name: 'Dark Mode',
        primary: '#3B82F6',
        primaryDark: '#2563EB',
        primaryLight: '#60A5FA',
        secondary: '#8B5CF6',
        accent: '#10B981',
        danger: '#EF4444',
        warning: '#F59E0B',
        success: '#10B981',
        info: '#06B6D4',
        background: '#111827',
        surface: '#1F2937',
        text: '#F9FAFB',
        textSecondary: '#9CA3AF',
        border: '#374151',
      },
    },
  }),

  getters: {
    theme: (state) => state.themes[state.currentTheme],
    isDark: (state) => state.currentTheme === 'dark',
    themeList: (state) => Object.keys(state.themes).map(key => ({
      key,
      name: state.themes[key].name,
      colors: state.themes[key],
    })),
  },

  actions: {
    setTheme(themeName) {
      if (this.themes[themeName]) {
        this.currentTheme = themeName;
        localStorage.setItem('theme', themeName);
        this.applyTheme();
      }
    },

    applyTheme() {
      const theme = this.theme;
      const root = document.documentElement;

      // Apply CSS variables for Tailwind
      root.style.setProperty('--color-primary', theme.primary);
      root.style.setProperty('--color-primary-dark', theme.primaryDark);
      root.style.setProperty('--color-primary-light', theme.primaryLight);
      root.style.setProperty('--color-secondary', theme.secondary);
      root.style.setProperty('--color-accent', theme.accent);
      root.style.setProperty('--color-danger', theme.danger);
      root.style.setProperty('--color-warning', theme.warning);
      root.style.setProperty('--color-success', theme.success);
      root.style.setProperty('--color-info', theme.info);
      root.style.setProperty('--color-background', theme.background);
      root.style.setProperty('--color-surface', theme.surface);
      root.style.setProperty('--color-text', theme.text);
      root.style.setProperty('--color-text-secondary', theme.textSecondary);
      root.style.setProperty('--color-border', theme.border);

      // Apply dark mode class for Tailwind
      if (this.isDark) {
        root.classList.add('dark');
      } else {
        root.classList.remove('dark');
      }

      // Set background color on body
      document.body.style.backgroundColor = theme.background;
      document.body.style.color = theme.text;
    },

    initTheme() {
      this.applyTheme();
    },
  },
});

