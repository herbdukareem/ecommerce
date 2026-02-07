<template>
  <div>
    <h2 class="text-2xl font-bold mb-4">Checkout</h2>
    <div class="mb-4">
      <h3 class="font-semibold mb-2">Shipping Address</h3>
      <form @submit.prevent="getQuotes" class="space-y-2">
        <input v-model="address.country" placeholder="Country" class="border p-2 w-full" />
        <input v-model="address.state" placeholder="State" class="border p-2 w-full" />
        <input v-model="address.city" placeholder="City" class="border p-2 w-full" />
        <input v-model="address.zip" placeholder="Zip" class="border p-2 w-full" />
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Get Shipping Quotes</button>
      </form>
    </div>
    <div v-if="quotes.length" class="mb-4">
      <h3 class="font-semibold mb-2">Shipping Methods</h3>
      <ul class="space-y-2">
        <li v-for="quote in quotes" :key="quote.method" class="flex items-center">
          <input
            type="radio"
            name="shipping"
            :value="quote"
            v-model="selectedQuote"
            class="mr-2"
          />
          {{ quote.method }} - ₦{{ quote.amount }}
        </li>
      </ul>
    </div>
    <div v-if="selectedQuote" class="mb-4">
      <h3 class="font-semibold mb-2">Payment Method</h3>
      <select v-model="paymentMethod" class="border p-2 w-full">
        <option value="card">Card</option>
        <option value="paypal">PayPal</option>
      </select>
    </div>
    <div v-if="selectedQuote" class="flex justify-end">
      <button class="bg-green-600 text-white px-4 py-2 rounded" @click="placeOrder">Place Order</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useCheckoutStore } from '../stores/checkout';
import { useRouter } from 'vue-router';

const checkout = useCheckoutStore();
const router = useRouter();

const address = ref({ country: '', state: '', city: '', zip: '' });
const quotes = ref([]);
const selectedQuote = ref(null);
const paymentMethod = ref('card');

async function getQuotes() {
  await checkout.quoteShipping(address.value);
  quotes.value = checkout.quotes;
}

async function placeOrder() {
  // For this stub we'll just simulate an order placement. In reality you'd choose or create an address record and pass payment method.
  await checkout.placeOrder(1, paymentMethod.value);
  alert('Order placed');
  router.push('/');
}
</script>