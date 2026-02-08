import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import Home from '../views/Home.vue';
import Products from '../views/Products.vue';
import ProductDetail from '../views/ProductDetail.vue';
import Cart from '../views/Cart.vue';
import Checkout from '../views/Checkout.vue';
import Dashboard from '../views/Dashboard.vue';
import AdminDashboard from '../views/AdminDashboard.vue';
import Analytics from '../views/Analytics.vue';

// Admin Pages
import AdminLogin from '../views/admin/Login.vue';
import AdminVendors from '../views/admin/Vendors.vue';
import AdminVendorsManagement from '../views/admin/VendorsManagement.vue';
import AdminVendorDetail from '../views/admin/VendorDetail.vue';
import AdminProducts from '../views/admin/Products.vue';
import AdminProductsManagement from '../views/admin/ProductsManagement.vue';
import AdminProductDetail from '../views/admin/ProductDetail.vue';
import AdminCategories from '../views/admin/Categories.vue';
import AdminCategoriesManagement from '../views/admin/CategoriesManagement.vue';
import AdminZones from '../views/admin/Zones.vue';
import AdminSettingsManagement from '../views/admin/SettingsManagement.vue';
import AdminOrdersManagement from '../views/admin/OrdersManagement.vue';

const routes = [
  // Public routes
  { path: '/', name: 'Home', component: Home },
  { path: '/products', name: 'Products', component: Products },
  { path: '/products/:slug', name: 'ProductDetail', component: ProductDetail, props: true },
  { path: '/cart', name: 'Cart', component: Cart },
  { path: '/checkout', name: 'Checkout', component: Checkout },
  { path: '/dashboard', name: 'Dashboard', component: Dashboard },

  // Admin login (public)
  { path: '/admin/login', name: 'AdminLogin', component: AdminLogin },

  // Admin routes (protected)
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/analytics',
    name: 'Analytics',
    component: Analytics,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/vendors',
    name: 'AdminVendors',
    component: AdminVendorsManagement,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/vendors/:id',
    name: 'AdminVendorDetail',
    component: AdminVendorDetail,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/products',
    name: 'AdminProducts',
    component: AdminProductsManagement,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/products/:id',
    name: 'AdminProductDetail',
    component: AdminProductDetail,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/categories',
    name: 'AdminCategories',
    component: AdminCategoriesManagement,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/zones',
    name: 'AdminZones',
    component: AdminZones,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/settings',
    name: 'AdminSettings',
    component: AdminSettingsManagement,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/orders',
    name: 'AdminOrders',
    component: AdminOrdersManagement,
    meta: { requiresAdmin: true }
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard for admin routes
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // If route requires admin access
  if (to.meta.requiresAdmin) {
    // Fetch user if not already loaded
    if (!authStore.user && authStore.token) {
      await authStore.fetchUser();
    }

    // Check if user is admin
    if (!authStore.isAdmin) {
      next({ name: 'AdminLogin' });
      return;
    }
  }

  next();
});

export default router;