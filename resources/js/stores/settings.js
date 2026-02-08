import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useSettingsStore = defineStore('settings', () => {
  const currency = ref('NGN');
  const currencySymbol = ref('₦');
  const locale = ref('en-NG');
  
  const currencies = {
    NGN: { symbol: '₦', name: 'Nigerian Naira', locale: 'en-NG' },
    USD: { symbol: '$', name: 'US Dollar', locale: 'en-US' },
    EUR: { symbol: '€', name: 'Euro', locale: 'en-EU' },
    GBP: { symbol: '£', name: 'British Pound', locale: 'en-GB' },
  };

  const setCurrency = (code) => {
    if (currencies[code]) {
      currency.value = code;
      currencySymbol.value = currencies[code].symbol;
      locale.value = currencies[code].locale;
      localStorage.setItem('currency', code);
    }
  };

  const formatCurrency = (amount) => {
    const numAmount = parseFloat(amount) || 0;
    return new Intl.NumberFormat(locale.value, {
      style: 'currency',
      currency: currency.value,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(numAmount);
  };

  const initSettings = () => {
    const savedCurrency = localStorage.getItem('currency');
    if (savedCurrency && currencies[savedCurrency]) {
      setCurrency(savedCurrency);
    } else {
      setCurrency('NGN'); // Default to Naira
    }
  };

  return {
    currency,
    currencySymbol,
    locale,
    currencies,
    setCurrency,
    formatCurrency,
    initSettings,
  };
});

