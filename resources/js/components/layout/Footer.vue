<template>
  <footer class="bg-surface border-t border-DEFAULT mt-auto transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- About -->
        <div>
          <div class="flex items-center gap-2 mb-4">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center shadow-material-2">
              <i class="mdi mdi-shopping text-white text-xl"></i>
            </div>
            <span class="text-xl font-bold text-primary">ShopHub</span>
          </div>
          <p class="text-sm text-secondary mb-4">
            Your one-stop shop for all your needs. Quality products, great prices, and excellent service.
          </p>
          <div class="flex gap-3">
            <a href="#" class="w-9 h-9 rounded-lg bg-base hover:bg-primary hover:text-white text-primary flex items-center justify-center transition-all">
              <i class="mdi mdi-facebook"></i>
            </a>
            <a href="#" class="w-9 h-9 rounded-lg bg-base hover:bg-primary hover:text-white text-primary flex items-center justify-center transition-all">
              <i class="mdi mdi-twitter"></i>
            </a>
            <a href="#" class="w-9 h-9 rounded-lg bg-base hover:bg-primary hover:text-white text-primary flex items-center justify-center transition-all">
              <i class="mdi mdi-instagram"></i>
            </a>
            <a href="#" class="w-9 h-9 rounded-lg bg-base hover:bg-primary hover:text-white text-primary flex items-center justify-center transition-all">
              <i class="mdi mdi-linkedin"></i>
            </a>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h3 class="text-sm font-semibold text-primary mb-4 uppercase tracking-wider">Quick Links</h3>
          <ul class="space-y-2">
            <li>
              <router-link to="/about" class="text-sm text-secondary hover:text-primary transition-colors">
                About Us
              </router-link>
            </li>
            <li>
              <router-link to="/contact" class="text-sm text-secondary hover:text-primary transition-colors">
                Contact Us
              </router-link>
            </li>
            <li>
              <router-link to="/faq" class="text-sm text-secondary hover:text-primary transition-colors">
                FAQ
              </router-link>
            </li>
            <li>
              <router-link to="/shipping" class="text-sm text-secondary hover:text-primary transition-colors">
                Shipping Info
              </router-link>
            </li>
            <li>
              <router-link to="/returns" class="text-sm text-secondary hover:text-primary transition-colors">
                Returns & Refunds
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Customer Service -->
        <div>
          <h3 class="text-sm font-semibold text-primary mb-4 uppercase tracking-wider">Customer Service</h3>
          <ul class="space-y-2">
            <li>
              <router-link to="/dashboard" class="text-sm text-secondary hover:text-primary transition-colors">
                My Account
              </router-link>
            </li>
            <li>
              <router-link to="/orders" class="text-sm text-secondary hover:text-primary transition-colors">
                Track Order
              </router-link>
            </li>
            <li>
              <router-link to="/wishlist" class="text-sm text-secondary hover:text-primary transition-colors">
                Wishlist
              </router-link>
            </li>
            <li>
              <router-link to="/support" class="text-sm text-secondary hover:text-primary transition-colors">
                Support Center
              </router-link>
            </li>
            <li>
              <router-link to="/privacy" class="text-sm text-secondary hover:text-primary transition-colors">
                Privacy Policy
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Newsletter -->
        <div>
          <h3 class="text-sm font-semibold text-primary mb-4 uppercase tracking-wider">Newsletter</h3>
          <p class="text-sm text-secondary mb-4">
            Subscribe to get special offers, free giveaways, and updates.
          </p>
          <form @submit.prevent="subscribe" class="space-y-3">
            <input
              v-model="email"
              type="email"
              placeholder="Enter your email"
              class="w-full px-4 py-2.5 rounded-lg border border-DEFAULT bg-base text-primary placeholder-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
              required
            />
            <button
              type="submit"
              class="w-full px-4 py-2.5 rounded-lg bg-primary text-white font-medium hover:shadow-material-3 transition-all flex items-center justify-center gap-2"
            >
              <i class="mdi mdi-email-outline"></i>
              <span>Subscribe</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="mt-12 pt-8 border-t border-DEFAULT flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-secondary">
          © {{ currentYear }} ShopHub. All rights reserved.
        </p>
        <div class="flex items-center gap-6">
          <router-link to="/terms" class="text-sm text-secondary hover:text-primary transition-colors">
            Terms of Service
          </router-link>
          <router-link to="/privacy" class="text-sm text-secondary hover:text-primary transition-colors">
            Privacy Policy
          </router-link>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, computed } from 'vue';

const email = ref('');
const currentYear = computed(() => new Date().getFullYear());

const subscribe = () => {
  // Implement newsletter subscription
  console.log('Subscribing:', email.value);
  email.value = '';
};
</script>

