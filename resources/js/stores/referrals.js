import { defineStore } from 'pinia';
import axios from 'axios';

export const useReferralStore = defineStore('referrals', {
  state: () => ({
    summary: null,
    rewards: [],
    loading: false,
  }),

  actions: {
    async loadSummary() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/referrals/me');
        this.summary = data;
      } finally {
        this.loading = false;
      }
    },

    async loadRewards() {
      const { data } = await axios.get('/api/referrals/rewards');
      this.rewards = data.data || [];
    },
  },
});
