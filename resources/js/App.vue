<template>
  <div class="min-h-screen flex flex-col">
    <header class="bg-white shadow p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">E-commerce Platform</h1>
      <nav class="space-x-4">
        <RouterLink to="/">Home</RouterLink>
        <RouterLink to="/products">Products</RouterLink>
        <RouterLink to="/cart">Cart</RouterLink>
        <RouterLink to="/dashboard">Dashboard</RouterLink>
      </nav>
    </header>
    <main class="flex-1 p-4">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
// No additional logic needed in root component
</script>

<style scoped>
/* Add any global styles here if needed */
</style>