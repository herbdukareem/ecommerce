<template>
  <section class="invoice-shell w-[900px] p-8 bg-slate-100 text-slate-800">
    <article class="receipt-card rounded-2xl border border-slate-200 bg-white shadow-md overflow-hidden">
      <header class="px-6 pt-6 pb-5 text-center border-b border-slate-100">
        <div class="mx-auto h-12 w-12 rounded-xl bg-primary text-white inline-flex items-center justify-center shadow-sm">
          <i class="mdi mdi-store text-2xl"></i>
        </div>
        <p class="mt-3 text-base font-semibold text-primary">Ashlab Commerce</p>
        <p class="text-xs text-slate-500">Order Payment Receipt</p>

        <div class="mt-4 flex items-center justify-center gap-2">
          <span class="h-8 w-8 rounded-full inline-flex items-center justify-center" :class="statusIconClass">
            <i :class="`mdi ${statusIcon} text-base`"></i>
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" :class="paymentBadgeClass">
            <i :class="`mdi ${statusIcon}`"></i>
            {{ humanize(order.payment_status || 'pending') }}
          </span>
        </div>
      </header>

      <div class="px-6 py-6 space-y-5">
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-center">
          <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Total Amount</p>
          <p class="mt-1 text-4xl font-bold text-primary">{{ formatCurrency(total) }}</p>
          <p class="mt-1 text-sm" :class="statusMessageClass">{{ statusMessage }}</p>
        </section>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] uppercase tracking-[0.12em] text-slate-500">Invoice Number</p>
            <p class="mt-1 text-sm font-semibold text-primary">INV-{{ order.id }}</p>
            <p class="text-xs text-slate-500">Order #{{ order.id }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] uppercase tracking-[0.12em] text-slate-500">Transaction Time</p>
            <p class="mt-1 text-sm font-semibold text-primary">{{ createdDateText }}</p>
            <p class="text-xs text-slate-500">{{ createdTimeText }}</p>
          </div>
        </div>

        <section class="space-y-2">
          <p class="text-sm font-semibold text-primary inline-flex items-center gap-1.5">
            <i class="mdi mdi-receipt-text-outline"></i>
            Receipt Details
          </p>
          <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-sm">
            <div class="row"><span>Order Number</span><span>#{{ order.id }}</span></div>
            <div class="row"><span>Receipt Number</span><span>INV-{{ order.id }}</span></div>
            <div class="row"><span>Payment Status</span><span>{{ humanize(order.payment_status || 'pending') }}</span></div>
            <div class="row"><span>Payment Method</span><span>{{ paymentMethod }}</span></div>
            <div class="row"><span>Payment Provider</span><span>{{ paymentProvider }}</span></div>
            <div class="row"><span>Customer Name</span><span>{{ customerName }}</span></div>
            <div class="row"><span>Customer Contact</span><span>{{ customerContact }}</span></div>
          </div>
        </section>

        <section class="space-y-2">
          <p class="text-sm font-semibold text-primary inline-flex items-center gap-1.5">
            <i class="mdi mdi-truck-delivery-outline"></i>
            Delivery Details
          </p>
          <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-sm">
            <div class="row"><span>City</span><span>{{ order.city_name || order.city?.name || 'N/A' }}</span></div>
            <div class="row"><span>Area</span><span>{{ order.area_name || order.area?.name || 'N/A' }}</span></div>
            <div class="row"><span>Dispatch Slot</span><span>{{ order.dispatch_time_label || order.dispatchTimeSlot?.label || 'N/A' }}</span></div>
            <div class="row"><span>Delivery Method</span><span>{{ order.delivery_snapshot?.method_name || order.shippingMethod?.name || 'N/A' }}</span></div>
            <div class="row"><span>Delivery Status</span><span>{{ humanize(order.delivery_status || 'pending_assignment') }}</span></div>
            <div class="row"><span>Tracking Code</span><span>{{ order.delivery_tracking_code || 'N/A' }}</span></div>
            <div class="row"><span>Number of Items</span><span>{{ itemsCount }}</span></div>
          </div>
        </section>

        <section class="space-y-2">
          <p class="text-sm font-semibold text-primary inline-flex items-center gap-1.5">
            <i class="mdi mdi-package-variant"></i>
            Item Summary
          </p>
          <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
            <div v-for="item in order.items || []" :key="item.id" class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img
                  :src="item.sku?.product?.image || placeholderImage"
                  :alt="item.sku?.product?.title || 'Product'"
                  class="h-11 w-11 rounded-lg border border-slate-200 object-cover"
                  @error="onImageError"
                />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-primary truncate">{{ item.sku?.product?.title || 'Product' }}</p>
                  <p class="text-xs text-slate-500">{{ item.quantity }} x {{ formatCurrency(item.price_snapshot || 0) }}</p>
                </div>
                <p class="text-sm font-semibold text-primary">{{ formatCurrency(itemSubtotal(item)) }}</p>
              </div>
            </div>
          </div>
        </section>

        <section class="space-y-2">
          <p class="text-sm font-semibold text-primary inline-flex items-center gap-1.5">
            <i class="mdi mdi-calculator"></i>
            Charges Breakdown
          </p>
          <div class="rounded-xl border border-slate-200 p-4 space-y-2 text-sm">
            <div class="line-item"><span>Subtotal</span><span>{{ formatCurrency(subtotal) }}</span></div>
            <div class="line-item"><span>Delivery Fee</span><span>{{ formatCurrency(deliveryFee) }}</span></div>
            <div class="line-item"><span>Discount</span><span>{{ formatCurrency(discount) }}</span></div>
            <div class="line-item"><span>Tax</span><span>{{ formatCurrency(tax) }}</span></div>
            <div class="border-t border-slate-200 pt-2 flex items-center justify-between">
              <span class="font-semibold text-primary">TOTAL</span>
              <span class="text-xl font-bold text-primary">{{ formatCurrency(total) }}</span>
            </div>
          </div>
        </section>

        <section class="space-y-2">
          <p class="text-sm font-semibold text-primary inline-flex items-center gap-1.5">
            <i class="mdi mdi-shield-check-outline"></i>
            References
          </p>
          <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-sm">
            <div class="row"><span>Transaction Reference</span><span>{{ transactionReference }}</span></div>
            <div class="row"><span>Payment Reference</span><span>{{ order.payment_reference || 'N/A' }}</span></div>
            <div class="row"><span>Generated At</span><span>{{ generatedAt }}</span></div>
            <div class="row"><span>Support</span><span>support@ashlabtech.com</span></div>
          </div>
          <p class="text-xs text-slate-500 pt-1">This is a computer-generated receipt.</p>
        </section>

      </div>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  formatCurrency: {
    type: Function,
    required: true,
  },
  formatDate: {
    type: Function,
    required: true,
  },
});

const placeholderImage = '/images/placeholders/product-placeholder.svg';

const toNumber = (value) => Number(value || 0);

const itemSubtotal = (item) => {
  if (item?.subtotal !== null && item?.subtotal !== undefined) {
    return toNumber(item.subtotal);
  }
  return toNumber(item?.price_snapshot) * toNumber(item?.quantity);
};

const subtotal = computed(() => {
  if (props.order.subtotal !== null && props.order.subtotal !== undefined) {
    return toNumber(props.order.subtotal);
  }
  return (props.order.items || []).reduce((sum, item) => sum + itemSubtotal(item), 0);
});

const deliveryFee = computed(() => toNumber(props.order.delivery_fee || props.order.shipping_cost));
const discount = computed(() => toNumber(props.order.discount || props.order.discount_amount));
const tax = computed(() => toNumber(props.order.tax));
const total = computed(() => {
  if (props.order.total !== null && props.order.total !== undefined) {
    return toNumber(props.order.total);
  }
  return Math.max(subtotal.value + deliveryFee.value + tax.value - discount.value, 0);
});

const itemsCount = computed(() => {
  return (props.order.items || []).reduce((sum, item) => sum + toNumber(item.quantity), 0);
});

const paymentMethod = computed(() => {
  const method = props.order.payment_mode || props.order.payments?.[0]?.method || 'N/A';
  return humanize(method);
});

const paymentProvider = computed(() => {
  const provider = props.order.payments?.[0]?.gateway_response?.provider || props.order.payments?.[0]?.provider || 'N/A';
  return humanize(provider);
});

const customerName = computed(() => {
  return props.order.shippingAddress?.full_name || props.order.shippingAddress?.name || props.order.user?.name || 'N/A';
});

const customerContact = computed(() => {
  return props.order.shippingAddress?.email || props.order.shippingAddress?.phone || props.order.user?.email || 'N/A';
});

const createdDateText = computed(() => {
  const value = props.order.placed_at || props.order.created_at;
  if (!value) return 'N/A';
  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value));
});

const createdTimeText = computed(() => {
  const value = props.order.placed_at || props.order.created_at;
  if (!value) return 'N/A';
  return new Intl.DateTimeFormat(undefined, {
    hour: 'numeric',
    minute: '2-digit',
  }).format(new Date(value));
});

const transactionReference = computed(() => {
  return props.order.payments?.[0]?.transaction_id || `TXN-${props.order.id || 'N/A'}`;
});

const generatedAt = computed(() => props.formatDate(new Date().toISOString(), true));

const statusIcon = computed(() => {
  const status = props.order.payment_status || 'pending';
  if (status === 'paid') return 'mdi-check-circle';
  if (status === 'failed') return 'mdi-alert-circle';
  if (status === 'refunded') return 'mdi-cash-refund';
  return 'mdi-clock-outline';
});

const paymentBadgeClass = computed(() => {
  const status = props.order.payment_status || 'pending';
  if (status === 'paid') return 'bg-emerald-100 text-emerald-800';
  if (status === 'failed') return 'bg-rose-100 text-rose-800';
  if (status === 'refunded') return 'bg-sky-100 text-sky-800';
  return 'bg-amber-100 text-amber-800';
});

const statusIconClass = computed(() => {
  const status = props.order.payment_status || 'pending';
  if (status === 'paid') return 'bg-emerald-100 text-emerald-700';
  if (status === 'failed') return 'bg-rose-100 text-rose-700';
  if (status === 'refunded') return 'bg-sky-100 text-sky-700';
  return 'bg-amber-100 text-amber-700';
});

const statusMessage = computed(() => {
  const status = props.order.payment_status || 'pending';
  if (status === 'paid') return 'Payment successful. Thanks for your purchase.';
  if (status === 'failed') return 'Payment was not completed successfully.';
  if (status === 'refunded') return 'Payment has been refunded.';
  return 'Payment is pending confirmation.';
});

const statusMessageClass = computed(() => {
  const status = props.order.payment_status || 'pending';
  if (status === 'paid') return 'text-emerald-700';
  if (status === 'failed') return 'text-rose-700';
  if (status === 'refunded') return 'text-sky-700';
  return 'text-amber-700';
});

const humanize = (value) => {
  if (!value) return 'N/A';
  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

const onImageError = (event) => {
  if (event?.target) {
    event.target.src = placeholderImage;
  }
};
</script>

<style scoped>
.row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.75rem;
  align-items: center;
  padding: 0.75rem 1rem;
}

.row > span:first-child {
  color: #64748b;
}

.row > span:last-child {
  color: #0f172a;
  font-weight: 500;
  text-align: right;
}

.line-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #475569;
}

.line-item > span:last-child {
  color: #0f172a;
  font-weight: 500;
}

@media print {
  .invoice-shell {
    background: #ffffff !important;
    padding: 0 !important;
  }

  .receipt-card {
    box-shadow: none !important;
    border-color: #e5e7eb !important;
  }
}
</style>
