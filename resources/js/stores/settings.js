import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import axios from 'axios';

export const useSettingsStore = defineStore('settings', () => {
  const siteName = ref('Online Mart');
  const siteDescription = ref('Your one-stop shop for quality products, fast delivery, and everyday value.');
  const siteEmail = ref('');
  const sitePhone = ref('');
  const siteLogoUrl = ref('/images/online-mart-logo.png');
  const themePrimaryColor = ref('#063f7c');
  const themeSecondaryColor = ref('#43b02a');
  const themeTertiaryColor = ref('#f59e0b');
  const homepageFlash = ref({
    enabled: true,
    location: 'home',
    title: 'Launching Soon',
    message: 'Fresh deals, fast delivery, and everyday essentials are ready for you.',
    highlight: 'Up to 60% off',
    buttonLabel: 'Shop Deals',
    buttonUrl: '/products',
    backgroundColor: '#063f7c',
    textColor: '#ffffff',
    imageUrl: '',
    countdownEnabled: false,
    countdownTarget: '',
  });
  const homepageHero = ref({
    enabled: true,
    layout: 'image_right',
    eyebrow: 'Online Mart',
    headline: 'Discover quality products for every need',
    subheadline: 'Shop trusted items with clear prices, easy checkout, and reliable delivery updates.',
    primaryButtonLabel: 'Shop Now',
    primaryButtonUrl: '/products',
    secondaryButtonLabel: 'View Deals',
    secondaryButtonUrl: '/products?sale=true',
    imageUrl: '',
    backgroundStyle: 'soft',
  });
  const currency = ref('NGN');
  const currencySymbol = ref('NGN');
  const currencyPrecision = ref(2);
  const locale = ref('en-NG');
  const loading = ref(false);

  const formatCurrency = (amount) => {
    const value = Number(amount || 0);
    const formatted = new Intl.NumberFormat(locale.value, {
      minimumFractionDigits: currencyPrecision.value,
      maximumFractionDigits: currencyPrecision.value,
    }).format(value);

    return `${currencySymbol.value}${formatted}`;
  };

  const setCurrency = (settings) => {
    currency.value = settings?.code || settings?.currency || currency.value;
    currencySymbol.value = settings?.symbol || settings?.currency_symbol || currency.value;
    currencyPrecision.value = Number(settings?.precision ?? settings?.currency_precision ?? 2);
    locale.value = settings?.locale || settings?.currency_locale || locale.value;
  };

  const setBranding = (settings) => {
    siteName.value = settings?.site_name || siteName.value;
    siteDescription.value = settings?.site_description || siteDescription.value;
    siteEmail.value = settings?.site_email || '';
    sitePhone.value = settings?.site_phone || '';
    siteLogoUrl.value = settings?.site_logo_url || settings?.site_logo_path || siteLogoUrl.value;
    themePrimaryColor.value = settings?.theme_primary_color || themePrimaryColor.value;
    themeSecondaryColor.value = settings?.theme_secondary_color || themeSecondaryColor.value;
    themeTertiaryColor.value = settings?.theme_tertiary_color || themeTertiaryColor.value;
    applyBranding();
  };

  const setHomepageContent = (settings) => {
    homepageFlash.value = {
      enabled: toBool(settings?.homepage_flash_enabled, homepageFlash.value.enabled),
      location: valueOr(settings?.homepage_flash_location, homepageFlash.value.location),
      title: valueOr(settings?.homepage_flash_title, homepageFlash.value.title),
      message: valueOr(settings?.homepage_flash_message, homepageFlash.value.message),
      highlight: valueOr(settings?.homepage_flash_highlight, homepageFlash.value.highlight),
      buttonLabel: valueOr(settings?.homepage_flash_button_label, homepageFlash.value.buttonLabel),
      buttonUrl: valueOr(settings?.homepage_flash_button_url, homepageFlash.value.buttonUrl),
      backgroundColor: valueOr(settings?.homepage_flash_background_color, homepageFlash.value.backgroundColor),
      textColor: valueOr(settings?.homepage_flash_text_color, homepageFlash.value.textColor),
      imageUrl: settings?.homepage_flash_image_url || settings?.homepage_flash_image_path || '',
      countdownEnabled: toBool(settings?.homepage_flash_countdown_enabled, homepageFlash.value.countdownEnabled),
      countdownTarget: valueOr(settings?.homepage_flash_countdown_target, ''),
    };

    homepageHero.value = {
      enabled: toBool(settings?.homepage_hero_enabled, homepageHero.value.enabled),
      layout: valueOr(settings?.homepage_hero_layout, homepageHero.value.layout),
      eyebrow: valueOr(settings?.homepage_hero_eyebrow, homepageHero.value.eyebrow),
      headline: valueOr(settings?.homepage_hero_headline, homepageHero.value.headline),
      subheadline: valueOr(settings?.homepage_hero_subheadline, homepageHero.value.subheadline),
      primaryButtonLabel: valueOr(settings?.homepage_hero_primary_button_label, homepageHero.value.primaryButtonLabel),
      primaryButtonUrl: valueOr(settings?.homepage_hero_primary_button_url, homepageHero.value.primaryButtonUrl),
      secondaryButtonLabel: valueOr(settings?.homepage_hero_secondary_button_label, homepageHero.value.secondaryButtonLabel),
      secondaryButtonUrl: valueOr(settings?.homepage_hero_secondary_button_url, homepageHero.value.secondaryButtonUrl),
      imageUrl: settings?.homepage_hero_image_url || settings?.homepage_hero_image_path || '',
      backgroundStyle: valueOr(settings?.homepage_hero_background_style, homepageHero.value.backgroundStyle),
    };
  };

  const applyBranding = () => {
    const root = document.documentElement;
    root.style.setProperty('--color-primary', themePrimaryColor.value);
    root.style.setProperty('--color-primary-dark', shadeColor(themePrimaryColor.value, -16));
    root.style.setProperty('--color-primary-light', shadeColor(themePrimaryColor.value, 24));
    root.style.setProperty('--color-secondary', themeSecondaryColor.value);
    root.style.setProperty('--color-accent', themeTertiaryColor.value);

    document.title = siteName.value;
  };

  const shadeColor = (color, percent) => {
    const normalized = String(color || '').replace('#', '');
    if (!/^[0-9A-Fa-f]{6}$/.test(normalized)) return color;

    const amount = Math.round(2.55 * percent);
    const r = Math.max(0, Math.min(255, parseInt(normalized.slice(0, 2), 16) + amount));
    const g = Math.max(0, Math.min(255, parseInt(normalized.slice(2, 4), 16) + amount));
    const b = Math.max(0, Math.min(255, parseInt(normalized.slice(4, 6), 16) + amount));

    return `#${[r, g, b].map((value) => value.toString(16).padStart(2, '0')).join('')}`;
  };

  const toBool = (value, fallback = false) => {
    if (value === undefined || value === null || value === '') return fallback;
    return value === true || value === 1 || value === '1' || value === 'true';
  };

  const valueOr = (value, fallback) => (value === undefined || value === null ? fallback : value);

  const initSettings = async () => {
    loading.value = true;
    try {
      const { data } = await axios.get('/api/settings/public');
      setBranding(data);
      setHomepageContent(data);
      setCurrency(data?.currency || data);
    } catch (error) {
      setCurrency({ code: currency.value, symbol: currency.value, precision: currencyPrecision.value, locale: locale.value });
      setBranding({});
    } finally {
      loading.value = false;
    }
  };

  const brandInitials = computed(() => siteName.value
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || 'OM');

  return {
    siteName,
    siteDescription,
    siteEmail,
    sitePhone,
    siteLogoUrl,
    themePrimaryColor,
    themeSecondaryColor,
    themeTertiaryColor,
    homepageFlash,
    homepageHero,
    brandInitials,
    currency,
    currencySymbol,
    currencyPrecision,
    locale,
    loading,
    setBranding,
    setHomepageContent,
    setCurrency,
    applyBranding,
    formatCurrency,
    initSettings,
  };
});
