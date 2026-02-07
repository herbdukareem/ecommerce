<template>
  <div>
    <h2 class="text-2xl font-bold mb-4">Shopping Cart</h2>
    <div v-if="cart.items.length === 0" class="text-gray-600">Your cart is empty.</div>
    <div v-else>
      <table class="w-full mb-4">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2">Product</th>
            <th class="py-2">Quantity</th>
            <th class="py-2">Price</th>
            <th class="py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in cart.items" :key="item.id" class="border-b">
            <td class="py-2">
              {{ item.sku.product.title }} ({{ item.sku.sku_code }})
            </td>
            <td class="py-2">
              <input
                type="number"
                min="1"
                v-model.number="item.quantity"
                class="border p-1 w-16"
                @change="updateItem(item)"
              />
            </td>
            <td class="py-2">
              {{ item.unit_price_snapshot }}
            </td>
            <td class="py-2">
              <button
                class="text-red-600"
                @click="removeItem(item)"
              >Remove</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="flex justify-end mb-4">
        <router-link
          to="/checkout"
          class="bg-green-600 text-white px-4 py-2 rounded"
        >Proceed to Checkout</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useCartStore } from '../stores/cart';

const cart = useCartStore();

onMounted(() => {
  cart.loadCart();
});

async function updateItem(item) {
  await cart.updateItem(item.id, item.quantity);
}

async function removeItem(item) {
  await cart.removeItem(item.id);
}
</script>