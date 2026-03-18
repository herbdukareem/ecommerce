<template>
  <AdminLayout>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-primary mb-2">Shipping Zones</h1>
        <p class="text-secondary">Manage shipping zones, rules and methods from live API data</p>
      </div>
    </div>

    <LogisticsRulesBuilder />
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../components/admin/AdminLayout.vue';
import LogisticsRulesBuilder from '../../components/LogisticsRulesBuilder.vue';
</script>

