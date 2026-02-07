import { defineStore } from 'pinia';
import axios from 'axios';

export const useCatalogStore = defineStore('catalog', {
  state: () => ({
    products: [],
    pagination: {},
    facets: {},
    categories: [],
    attributes: [],
    loading: false,
    filters: {
      q: '',
      category_id: null,
      price_min: null,
      price_max: null,
      attributes: {},
      vendor_id: null,
      in_stock: false,
      sort: 'newest',
      page: 1,
      per_page: 24,
    },
  }),

  getters: {
    hasActiveFilters: (state) => {
      return !!(
        state.filters.q ||
        state.filters.category_id ||
        state.filters.price_min ||
        state.filters.price_max ||
        state.filters.vendor_id ||
        state.filters.in_stock ||
        Object.keys(state.filters.attributes).length > 0
      );
    },
  },

  actions: {
    async fetchProducts() {
      try {
        this.loading = true;
        const params = { ...this.filters };
        const { data } = await axios.get('/api/products', { params });
        this.products = data.data || [];
        this.pagination = data.meta || {};
        this.facets = data.facets || {};
      } catch (error) {
        console.error('Failed to fetch products:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchCategories() {
      try {
        const { data } = await axios.get('/api/categories');
        this.categories = data;
      } catch (error) {
        console.error('Failed to fetch categories:', error);
      }
    },

    async fetchAttributes() {
      try {
        const { data } = await axios.get('/api/attributes');
        this.attributes = data;
      } catch (error) {
        console.error('Failed to fetch attributes:', error);
      }
    },

    async searchProducts(query) {
      try {
        const { data } = await axios.get('/api/products/search', {
          params: { q: query },
        });
        return data;
      } catch (error) {
        console.error('Failed to search products:', error);
        return [];
      }
    },

    setFilter(name, value) {
      this.filters[name] = value;
      this.filters.page = 1; // Reset to first page when filter changes
    },

    setAttributeFilter(attributeName, values) {
      if (values && values.length > 0) {
        this.filters.attributes[attributeName] = values;
      } else {
        delete this.filters.attributes[attributeName];
      }
      this.filters.page = 1;
    },

    clearFilters() {
      this.filters = {
        q: '',
        category_id: null,
        price_min: null,
        price_max: null,
        attributes: {},
        vendor_id: null,
        in_stock: false,
        sort: 'newest',
        page: 1,
        per_page: 24,
      };
    },

    setPage(page) {
      this.filters.page = page;
    },

    setSort(sort) {
      this.filters.sort = sort;
      this.filters.page = 1;
    },
  },
});