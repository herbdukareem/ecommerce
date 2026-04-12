<template>
  <MainLayout>
    <section class="max-w-7xl mx-auto px-4 py-8 sm:py-10 space-y-6">
      <OrderHeader
        :order-id="order?.id || orderId"
        :placed-at="order?.placed_at || order?.created_at"
        :status="order?.status || 'pending'"
        :format-date="formatDate"
        :downloading-invoice="downloadingInvoice"
        @back="goBack"
        @download-invoice="downloadInvoice"
        @reorder="reorder"
        @contact-support="contactSupport"
      />

      <div class="fixed left-0 top-0 -z-10 pointer-events-none opacity-0">
        <OrderInvoiceTerminal
          v-if="order"
          ref="invoiceRef"
          :order="order"
          :format-currency="formatCurrency"
          :format-date="formatDate"
        />
      </div>

      <div v-if="ordersStore.loading" class="rounded-2xl border border-DEFAULT bg-white p-10 text-secondary text-center shadow-sm">
        Loading order details...
      </div>
      <div v-else-if="errorMessage" class="rounded-2xl border border-danger/30 bg-danger/5 p-10 text-danger text-center shadow-sm">
        {{ errorMessage }}
      </div>
      <div v-else-if="!order" class="rounded-2xl border border-DEFAULT bg-white p-10 text-secondary text-center shadow-sm">
        Order not found.
      </div>

      <template v-else>
        <OrderStatusBadges :order="order" />

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
          <div class="xl:col-span-8 space-y-6">
            <OrderItemsList :items="order.items || []" :format-currency="formatCurrency" />
            <DeliveryTimeline :order="order" :format-date="formatDate" />
          </div>

          <div class="xl:col-span-4 space-y-6">
            <OrderSummaryCard :order="order" :format-currency="formatCurrency" :format-date="formatDate" />

            <Card :elevation="1" class="p-5">
              <h3 class="text-sm font-semibold text-primary">Need to update this order?</h3>
              <p class="text-sm text-secondary mt-1">If your order has not entered shipping yet, you can cancel it below.</p>

              <Button
                v-if="canCancel"
                variant="danger"
                class="w-full mt-4"
                :loading="cancelling"
                icon="close-circle"
                @click="cancelOrder"
              >
                Cancel Order
              </Button>

              <p v-else class="text-xs text-secondary mt-4">
                Cancellation is no longer available for this order status.
              </p>
            </Card>
          </div>
        </div>
      </template>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, inject, nextTick, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MainLayout from '../components/layout/MainLayout.vue';
import Card from '../components/ui/Card.vue';
import Button from '../components/ui/Button.vue';
import OrderHeader from '../components/orders/OrderHeader.vue';
import OrderStatusBadges from '../components/orders/OrderStatusBadges.vue';
import OrderItemsList from '../components/orders/OrderItemsList.vue';
import DeliveryTimeline from '../components/orders/DeliveryTimeline.vue';
import OrderSummaryCard from '../components/orders/OrderSummaryCard.vue';
import OrderInvoiceTerminal from '../components/orders/OrderInvoiceTerminal.vue';
import { useOrdersStore } from '../stores/orders';
import { useSettingsStore } from '../stores/settings';

const route = useRoute();
const router = useRouter();
const toast = inject('toast');
const ordersStore = useOrdersStore();
const settingsStore = useSettingsStore();

const errorMessage = ref('');
const cancelling = ref(false);
const downloadingInvoice = ref(false);
const invoiceRef = ref(null);

const orderId = computed(() => route.params.id);
const order = computed(() => ordersStore.currentOrder);
const formatCurrency = settingsStore.formatCurrency;

const canCancel = computed(() => {
  const status = order.value?.status;
  return status === 'pending' || status === 'processing';
});

const formatDate = (value, withMeridiem = false) => {
  if (!value) return 'N/A';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return 'N/A';
  }

  if (withMeridiem) {
    return new Intl.DateTimeFormat(undefined, {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    }).format(date);
  }

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
};

const loadOrder = async () => {
  errorMessage.value = '';
  const result = await ordersStore.fetchOrder(orderId.value);
  if (!result.success) {
    errorMessage.value = result.error || 'Unable to load order.';
  }
};

const cancelOrder = async () => {
  cancelling.value = true;
  const result = await ordersStore.cancelOrder(orderId.value);
  cancelling.value = false;

  if (!result.success) {
    toast?.error(result.error || 'Unable to cancel order');
    return;
  }

  toast?.success(result.message || 'Order cancelled');
  await loadOrder();
};

const goBack = () => {
  router.push('/orders');
};

const loadScript = async (src, globalName) => {
  if (window[globalName]) {
    return true;
  }

  const existing = document.querySelector(`script[data-global="${globalName}"]`);
  if (existing) {
    return await new Promise((resolve) => {
      existing.addEventListener('load', () => resolve(!!window[globalName]), { once: true });
      existing.addEventListener('error', () => resolve(false), { once: true });
    });
  }

  return await new Promise((resolve) => {
    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.dataset.global = globalName;
    script.onload = () => resolve(!!window[globalName]);
    script.onerror = () => resolve(false);
    document.head.appendChild(script);
  });
};

const downloadInvoice = async () => {
  if (!order.value || downloadingInvoice.value) {
    return;
  }

  downloadingInvoice.value = true;

  try {
    const html2canvasReady = await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', 'html2canvas');
    const jspdfReady = await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', 'jspdf');

    if (!html2canvasReady || !jspdfReady) {
      toast?.error('Unable to load PDF tools. Please try again.');
      return;
    }

    await nextTick();

    const invoiceElement = invoiceRef.value?.$el || invoiceRef.value;
    if (!invoiceElement) {
      toast?.error('Invoice is not ready yet. Please try again.');
      return;
    }

    const canvas = await window.html2canvas(invoiceElement, {
      scale: 2,
      backgroundColor: '#0f172a',
      useCORS: true,
    });

    const imageData = canvas.toDataURL('image/png');
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ orientation: 'p', unit: 'pt', format: 'a4' });

    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const margin = 24;
    const imageWidth = pageWidth - (margin * 2);
    const imageHeight = (canvas.height * imageWidth) / canvas.width;

    let heightLeft = imageHeight;
    let position = margin;

    pdf.addImage(imageData, 'PNG', margin, position, imageWidth, imageHeight);
    heightLeft -= (pageHeight - (margin * 2));

    while (heightLeft > 0) {
      pdf.addPage();
      position = margin - (imageHeight - heightLeft);
      pdf.addImage(imageData, 'PNG', margin, position, imageWidth, imageHeight);
      heightLeft -= (pageHeight - (margin * 2));
    }

    pdf.save(`order-${order.value.id}-invoice.pdf`);
    toast?.success('Invoice PDF downloaded.');
  } catch (error) {
    toast?.error('Unable to generate invoice PDF.');
  } finally {
    downloadingInvoice.value = false;
  }
};

const reorder = () => {
  router.push('/products');
};

const contactSupport = () => {
  const subject = encodeURIComponent(`Support request for Order #${orderId.value}`);
  window.location.href = `mailto:support@ashlabtech.com?subject=${subject}`;
};

onMounted(loadOrder);
</script>
