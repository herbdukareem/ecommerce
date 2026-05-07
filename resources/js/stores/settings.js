import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useSettingsStore = defineStore('settings', () => {
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

  const initSettings = async () => {
    loading.value = true;
    try {
      const { data } = await axios.get('/api/settings/currency');
      setCurrency(data);
    } catch (error) {
      setCurrency({ code: currency.value, symbol: currency.value, precision: currencyPrecision.value, locale: locale.value });
    } finally {
      loading.value = false;
    }
  };

  return {
    currency,
    currencySymbol,
    currencyPrecision,
    locale,
    loading,
    setCurrency,
    formatCurrency,
    initSettings,
  };
});
