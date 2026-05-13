import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const Home = () => import('../views/Home.vue');
const Products = () => import('../views/Products.vue');
const ProductDetail = () => import('../views/ProductDetail.vue');
const Cart = () => import('../views/Cart.vue');
const Checkout = () => import('../views/Checkout.vue');
const Login = () => import('../views/Login.vue');
const Register = () => import('../views/Register.vue');
const Account = () => import('../views/Account.vue');
const Referrals = () => import('../views/Referrals.vue');
const ForgotPassword = () => import('../views/ForgotPassword.vue');
const Orders = () => import('../views/Orders.vue');
const OrderDetail = () => import('../views/OrderDetail.vue');
const Addresses = () => import('../views/Addresses.vue');
const StaticPage = () => import('../views/StaticPage.vue');
const Dashboard = () => import('../views/Dashboard.vue');
const AdminDashboard = () => import('../views/AdminDashboard.vue');
const Analytics = () => import('../views/Analytics.vue');

// Admin Pages
const AdminLogin = () => import('../views/admin/Login.vue');
const AdminVendors = () => import('../views/admin/Vendors.vue');
const AdminVendorsManagement = () => import('../views/admin/VendorsManagement.vue');
const AdminVendorDetail = () => import('../views/admin/VendorDetail.vue');
const AdminProducts = () => import('../views/admin/Products.vue');
const AdminProductsManagement = () => import('../views/admin/ProductsManagement.vue');
const AdminProductDetail = () => import('../views/admin/ProductDetail.vue');
const AdminCategories = () => import('../views/admin/Categories.vue');
const AdminCategoriesManagement = () => import('../views/admin/CategoriesManagement.vue');
const AdminZones = () => import('../views/admin/Zones.vue');
const AdminSettingsManagement = () => import('../views/admin/SettingsManagement.vue');
const AdminOrdersManagement = () => import('../views/admin/OrdersManagement.vue');
const AdminPaymentGateways = () => import('../views/admin/PaymentGateways.vue');
const AdminDeliveryPartners = () => import('../views/admin/DeliveryPartners.vue');
const AdminDispatchRiders = () => import('../views/admin/DispatchRiders.vue');
const AdminOrderDetail = () => import('../views/admin/OrderDetail.vue');
const AdminDispatchTimeSlots = () => import('../views/admin/DispatchTimeSlots.vue');
const AdminOperationCities = () => import('../views/admin/OperationCities.vue');
const AdminOperationAreas = () => import('../views/admin/OperationAreas.vue');
const AdminCreateOrder = () => import('../views/admin/CreateOrder.vue');
const AdminInventoryManagement = () => import('../views/admin/InventoryManagement.vue');
const AdminInventoryLedger = () => import('../views/admin/InventoryLedger.vue');
const AdminExpiryAlerts = () => import('../views/admin/ExpiryAlerts.vue');
const AdminProfitMargins = () => import('../views/admin/ProfitMargins.vue');
const AdminRolesPermissions = () => import('../views/admin/RolesPermissions.vue');
const AdminNewsletterSubscribers = () => import('../views/admin/NewsletterSubscribers.vue');
const AdminReferrals = () => import('../views/admin/Referrals.vue');

const routes = [
  // Public routes
  { path: '/', name: 'Home', component: Home },
  { path: '/products', name: 'Products', component: Products },
  { path: '/products/:slug', name: 'ProductDetail', component: ProductDetail, props: true },
  { path: '/cart', name: 'Cart', component: Cart },
  { path: '/checkout', name: 'Checkout', component: Checkout, meta: { requiresAuth: true } },
  { path: '/login', name: 'Login', component: Login, meta: { guestOnly: true } },
  { path: '/register', name: 'Register', component: Register, meta: { guestOnly: true } },
  { path: '/forgot-password', name: 'ForgotPassword', component: ForgotPassword, meta: { guestOnly: true } },
  { path: '/account', name: 'Account', component: Account, meta: { requiresAuth: true } },
  { path: '/account/referrals', name: 'Referrals', component: Referrals, meta: { requiresAuth: true } },
  { path: '/orders', name: 'Orders', component: Orders, meta: { requiresAuth: true } },
  { path: '/orders/:id', name: 'OrderDetail', component: OrderDetail, props: true, meta: { requiresAuth: true } },
  { path: '/addresses', name: 'Addresses', component: Addresses, meta: { requiresAuth: true } },
  { path: '/about', name: 'About', component: StaticPage, meta: { pageKey: 'about' } },
  { path: '/contact', name: 'Contact', component: StaticPage, meta: { pageKey: 'contact' } },
  { path: '/faq', name: 'Faq', component: StaticPage, meta: { pageKey: 'faq' } },
  { path: '/shipping', name: 'ShippingInfo', component: StaticPage, meta: { pageKey: 'shipping' } },
  { path: '/returns', name: 'ReturnsRefunds', component: StaticPage, meta: { pageKey: 'returns' } },
  { path: '/support', name: 'Support', component: StaticPage, meta: { pageKey: 'support' } },
  { path: '/privacy', name: 'Privacy', component: StaticPage, meta: { pageKey: 'privacy' } },
  { path: '/terms', name: 'Terms', component: StaticPage, meta: { pageKey: 'terms' } },
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
  {
    path: '/admin/orders/create',
    name: 'AdminCreateOrder',
    component: AdminCreateOrder,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/orders/:id',
    name: 'AdminOrderDetail',
    component: AdminOrderDetail,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/payment-gateways',
    name: 'AdminPaymentGateways',
    component: AdminPaymentGateways,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/delivery-partners',
    name: 'AdminDeliveryPartners',
    component: AdminDeliveryPartners,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/dispatch-riders',
    name: 'AdminDispatchRiders',
    component: AdminDispatchRiders,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/dispatch-time-slots',
    name: 'AdminDispatchTimeSlots',
    component: AdminDispatchTimeSlots,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/operation-cities',
    name: 'AdminOperationCities',
    component: AdminOperationCities,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/operation-areas',
    name: 'AdminOperationAreas',
    component: AdminOperationAreas,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/inventory',
    name: 'AdminInventory',
    component: AdminInventoryManagement,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/inventory-ledger',
    name: 'AdminInventoryLedger',
    component: AdminInventoryLedger,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/expiry-alerts',
    name: 'AdminExpiryAlerts',
    component: AdminExpiryAlerts,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/profit-margins',
    name: 'AdminProfitMargins',
    component: AdminProfitMargins,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/roles-permissions',
    name: 'AdminRolesPermissions',
    component: AdminRolesPermissions,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/newsletter-subscribers',
    name: 'AdminNewsletterSubscribers',
    component: AdminNewsletterSubscribers,
    meta: { requiresAdmin: true }
  },
  {
    path: '/admin/referrals',
    name: 'AdminReferrals',
    component: AdminReferrals,
    meta: { requiresAdmin: true }
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }

    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' };
    }

    return { top: 0 };
  },
});

// Navigation guard for admin routes
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  if (!authStore.user && authStore.token) {
    await authStore.fetchUser();
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    next({ name: 'Account' });
    return;
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } });
    return;
  }

  // If route requires admin access
  if (to.meta.requiresAdmin) {
    // Check if user is admin
    if (!authStore.isAdmin) {
      next({ name: 'AdminLogin' });
      return;
    }
  }

  next();
});

export default router;
