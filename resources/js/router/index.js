import { createRouter, createWebHistory } from 'vue-router';

import Home from '../views/Home.vue';
import Products from '../views/Products.vue';
import ProductDetail from '../views/ProductDetail.vue';
import Cart from '../views/Cart.vue';
import Checkout from '../views/Checkout.vue';
import Dashboard from '../views/Dashboard.vue';

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/products', name: 'Products', component: Products },
  { path: '/products/:slug', name: 'ProductDetail', component: ProductDetail, props: true },
  { path: '/cart', name: 'Cart', component: Cart },
  { path: '/checkout', name: 'Checkout', component: Checkout },
  { path: '/dashboard', name: 'Dashboard', component: Dashboard },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;