import { defineStore } from 'pinia';
import axios from 'axios';

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    products: [],
    pagination: {},
    facets: {},
    filters: {
      q: '',
      category_id: null,
      price_min: null,
      price_max: null,
      attributes: {},
      rating_min: null,
      in_stock: false,
      sort: 'newest',
      page: 1,
      per_page: 24,
    },
  }),
  actions: {
    async fetchProducts() {
      const params = { ...this.filters };
      // Flatten attribute filters into query string (e.g. attributes[color]=red,blue)
      const { data } = await axios.get('/api/products', { params });
      this.products = data.data;
      this.pagination = data.meta;
      this.facets = data.facets || {};
    },
    setFilter(name, value) {
      this.filters[name] = value;
    },
  },
});