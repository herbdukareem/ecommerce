<template>
  <MainLayout>
    <!-- Launching Soon Banner -->
    <LaunchingBanner />

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-primary/10 via-primary/5 to-transparent overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div class="animate-fade-in-left">
            <h1 class="text-5xl md:text-6xl font-bold text-primary mb-6 leading-tight">
              Discover Amazing Products
            </h1>
            <p class="text-xl text-secondary mb-8">
              Shop the latest trends with unbeatable prices and fast shipping. Your satisfaction is our priority.
            </p>
            <div class="flex flex-wrap gap-4">
              <Button variant="primary" size="lg" icon="shopping" @click="$router.push('/products')">
                Shop Now
              </Button>
              <Button variant="outline" size="lg" icon="tag" @click="$router.push('/products?sale=true')">
                View Deals
              </Button>
            </div>
          </div>
          <div class="animate-fade-in-right">
            <div class="relative">
              <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-primary-dark/20 rounded-3xl blur-3xl"></div>
              <div class="relative rounded-3xl shadow-material-5 w-full h-96 bg-gradient-to-br from-primary/20 to-primary-dark/20 flex items-center justify-center">
                <i class="mdi mdi-shopping text-9xl text-primary/30"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="py-16 bg-surface">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-1 text-center">
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mb-4">
                <i class="mdi mdi-truck-fast text-3xl text-success"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Free Shipping</h3>
              <p class="text-sm text-secondary">On orders over $50</p>
            </div>
          </Card>

          <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-2 text-center">
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full bg-info/10 flex items-center justify-center mb-4">
                <i class="mdi mdi-shield-check text-3xl text-info"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Secure Payment</h3>
              <p class="text-sm text-secondary">100% secure transactions</p>
            </div>
          </Card>

          <Card :elevation="2" hoverable animation="fade-in-up" class="stagger-3 text-center">
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mb-4">
                <i class="mdi mdi-refresh text-3xl text-warning"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Easy Returns</h3>
              <p class="text-sm text-secondary">30-day return policy</p>
            </div>
          </Card>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8 animate-fade-in-down">
          <div>
            <h2 class="text-3xl font-bold text-primary mb-2">Featured Products</h2>
            <p class="text-secondary">Handpicked items just for you</p>
          </div>
          <Button variant="ghost" icon="arrow-right" icon-right @click="$router.push('/products')">
            View All
          </Button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card
            v-for="(product, index) in featuredProducts"
            :key="product.id"
            :elevation="2"
            hoverable
            clickable
            animation="fade-in-up"
            :class="`stagger-${index + 1}`"
            @click="$router.push(`/products/${product.id}`)"
          >
            <div class="relative mb-4">
              <div class="w-full h-48 bg-gradient-to-br from-primary/10 to-primary-dark/10 rounded-lg flex items-center justify-center">
                <i class="mdi mdi-image text-5xl text-primary/30"></i>
              </div>
              <Badge
                v-if="product.discount"
                variant="danger"
                class="absolute top-2 right-2"
              >
                -{{ product.discount }}%
              </Badge>
            </div>
            <h3 class="font-semibold text-primary mb-2">{{ product.name }}</h3>
            <div class="flex items-center gap-2 mb-2">
              <div class="flex items-center gap-1 text-warning">
                <i class="mdi mdi-star text-sm"></i>
                <span class="text-sm font-medium">{{ product.rating }}</span>
              </div>
              <span class="text-xs text-secondary">({{ product.reviews }} reviews)</span>
            </div>
            <div class="flex items-center justify-between">
              <div>
                <span class="text-lg font-bold text-primary">${{ product.price }}</span>
                <span v-if="product.originalPrice" class="text-sm text-secondary line-through ml-2">
                  ${{ product.originalPrice }}
                </span>
              </div>
              <Button variant="primary" size="sm" icon="cart-plus" icon-only />
            </div>
          </Card>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="py-16 bg-surface">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 animate-fade-in-down">
          <h2 class="text-3xl font-bold text-primary mb-2">Shop by Category</h2>
          <p class="text-secondary">Explore our wide range of products</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <Card
            v-for="(category, index) in categories"
            :key="category.id"
            :elevation="2"
            hoverable
            clickable
            animation="fade-in-up"
            :class="`stagger-${index + 1}`"
            @click="$router.push(`/products?category=${category.slug}`)"
          >
            <div class="text-center">
              <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-3">
                <i :class="`mdi mdi-${category.icon} text-2xl text-primary`"></i>
              </div>
              <h3 class="text-sm font-semibold text-primary">{{ category.name }}</h3>
            </div>
          </Card>
        </div>
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import Badge from '../components/ui/Badge.vue';
import LaunchingBanner from '../components/ui/LaunchingBanner.vue';

// Mock data - replace with actual API calls
const featuredProducts = ref([
  { id: 1, name: 'Wireless Headphones', price: 79.99, originalPrice: 99.99, discount: 20, rating: 4.5, reviews: 128 },
  { id: 2, name: 'Smart Watch', price: 199.99, rating: 4.8, reviews: 256 },
  { id: 3, name: 'Laptop Stand', price: 49.99, originalPrice: 69.99, discount: 29, rating: 4.3, reviews: 89 },
  { id: 4, name: 'USB-C Hub', price: 39.99, rating: 4.6, reviews: 145 },
]);

const categories = ref([
  { id: 1, name: 'Electronics', slug: 'electronics', icon: 'laptop' },
  { id: 2, name: 'Fashion', slug: 'fashion', icon: 'hanger' },
  { id: 3, name: 'Home', slug: 'home', icon: 'home' },
  { id: 4, name: 'Sports', slug: 'sports', icon: 'basketball' },
  { id: 5, name: 'Books', slug: 'books', icon: 'book-open-variant' },
  { id: 6, name: 'Toys', slug: 'toys', icon: 'toy-brick' },
]);
</script>
