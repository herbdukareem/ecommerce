<template>
  <AdminLayout>
    <div class="space-y-6">
      <div
        v-if="canCreateNewsletter"
        class="rounded-lg border border-gray-200 bg-white p-5"
      >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Create Newsletter</h2>
            <p class="text-sm text-gray-600">Compose and send a rich email to all active subscribers.</p>
          </div>
          <span class="text-sm text-gray-500">{{ stats.active }} active recipients</span>
        </div>

        <div class="mt-5 space-y-4">
          <input
            v-model="campaign.subject"
            type="text"
            maxlength="150"
            placeholder="Newsletter subject"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
          />

          <div class="overflow-hidden rounded-lg border border-gray-300">
            <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 p-2">
              <button
                v-for="tool in editorTools"
                :key="tool.command"
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-700 transition-colors hover:bg-white hover:text-primary"
                :title="tool.label"
                @click="runEditorCommand(tool.command)"
              >
                <i :class="`mdi mdi-${tool.icon}`"></i>
              </button>
              <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-700 transition-colors hover:bg-white hover:text-primary"
                title="Link"
                @click="insertLink"
              >
                <i class="mdi mdi-link-variant"></i>
              </button>
            </div>

            <div
              ref="editorRef"
              class="min-h-56 bg-white px-4 py-3 text-sm leading-6 text-gray-900 outline-none"
              contenteditable="true"
              @input="syncEditor"
              @blur="syncEditor"
            ></div>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-gray-500">Emails are sent individually to active subscribers only.</p>
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-60"
              :disabled="sending || !campaign.subject.trim() || !campaign.body_html.trim() || stats.active < 1"
              @click="sendNewsletter"
            >
              <i class="mdi" :class="sending ? 'mdi-loading mdi-spin' : 'mdi-send'"></i>
              <span>{{ sending ? 'Sending...' : 'Send Newsletter' }}</span>
            </button>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Newsletter Subscribers</h1>
          <p class="text-sm text-gray-600 mt-1">Manage customers who opted in from the storefront newsletter form.</p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-60"
          :disabled="exporting"
          @click="exportSubscribers"
        >
          <i class="mdi" :class="exporting ? 'mdi-loading mdi-spin' : 'mdi-download'"></i>
          <span>{{ exporting ? 'Exporting...' : 'Export CSV' }}</span>
        </button>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Total</p>
          <p class="mt-2 text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Active</p>
          <p class="mt-2 text-2xl font-bold text-green-600">{{ stats.active }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Inactive</p>
          <p class="mt-2 text-2xl font-bold text-gray-600">{{ stats.inactive }}</p>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <div class="relative md:col-span-2">
            <i class="mdi mdi-magnify absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search email address..."
              class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              @input="debouncedFetch"
            />
          </div>
          <select
            v-model="filters.status"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            @change="fetchSubscribers"
          >
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
            @click="resetFilters"
          >
            <i class="mdi mdi-filter-remove-outline"></i>
            <span>Reset</span>
          </button>
        </div>
      </div>

      <div v-if="recentCampaigns.length" class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900">Recent Newsletters</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="campaignItem in recentCampaigns"
            :key="campaignItem.id"
            class="flex flex-col gap-1 rounded-lg border border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p class="font-medium text-gray-900">{{ campaignItem.subject }}</p>
              <p class="text-xs text-gray-500">
                Sent by {{ campaignItem.sender?.name || 'Admin' }} on {{ formatDate(campaignItem.sent_at) }}
              </p>
            </div>
            <span class="text-sm text-gray-600">{{ campaignItem.recipient_count }} recipients</span>
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div v-if="loading" class="p-10 text-center text-gray-500">
          <i class="mdi mdi-loading mdi-spin text-3xl text-primary"></i>
          <p class="mt-2 text-sm">Loading subscribers...</p>
        </div>

        <div v-else-if="subscribers.length === 0" class="p-10 text-center text-gray-500">
          <i class="mdi mdi-email-newsletter text-5xl text-gray-300"></i>
          <p class="mt-2 text-sm">No subscribers found.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
              <tr>
                <th class="px-5 py-3 text-left font-medium">Email</th>
                <th class="px-5 py-3 text-left font-medium">Status</th>
                <th class="px-5 py-3 text-left font-medium">Source</th>
                <th class="px-5 py-3 text-left font-medium">Subscribed</th>
                <th class="px-5 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="subscriber in subscribers" :key="subscriber.id" class="hover:bg-gray-50">
                <td class="px-5 py-4">
                  <p class="font-medium text-gray-900">{{ subscriber.email }}</p>
                  <p v-if="subscriber.unsubscribed_at" class="text-xs text-gray-500">
                    Unsubscribed {{ formatDate(subscriber.unsubscribed_at) }}
                  </p>
                </td>
                <td class="px-5 py-4">
                  <span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="subscriber.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                  >
                    {{ subscriber.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-5 py-4 text-gray-600">{{ subscriber.source || 'footer' }}</td>
                <td class="px-5 py-4 text-gray-600">{{ formatDate(subscriber.subscribed_at || subscriber.created_at) }}</td>
                <td class="px-5 py-4">
                  <div class="flex justify-end gap-2">
                    <button
                      v-if="canManageNewsletter"
                      type="button"
                      class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                      @click="toggleStatus(subscriber)"
                    >
                      {{ subscriber.is_active ? 'Deactivate' : 'Reactivate' }}
                    </button>
                    <button
                      v-if="canManageNewsletter"
                      type="button"
                      class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                      @click="deleteSubscriber(subscriber)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-gray-600">
            Showing {{ subscribers.length }} of {{ pagination.total || 0 }}
          </p>
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded-md border border-gray-300 px-3 py-2 text-sm disabled:opacity-50"
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              class="rounded-md border border-gray-300 px-3 py-2 text-sm disabled:opacity-50"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import { useAuthStore } from '../../stores/auth';

const toast = inject('toast');
const authStore = useAuthStore();
const loading = ref(false);
const exporting = ref(false);
const sending = ref(false);
const editorRef = ref(null);
const subscribers = ref([]);
const recentCampaigns = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});
const stats = ref({
  total: 0,
  active: 0,
  inactive: 0,
});
const filters = reactive({
  search: '',
  status: '',
  page: 1,
});
const campaign = reactive({
  subject: '',
  body_html: '',
});

let searchTimeout;

const isFullAdmin = computed(() => authStore.user?.roles?.some(role => ['Super Admin', 'Admin'].includes(role.name)));
const canCreateNewsletter = computed(() => isFullAdmin.value || authStore.can('newsletter.create'));
const canManageNewsletter = computed(() => isFullAdmin.value || authStore.can('newsletter.manage'));
const editorTools = [
  { command: 'bold', icon: 'format-bold', label: 'Bold' },
  { command: 'italic', icon: 'format-italic', label: 'Italic' },
  { command: 'underline', icon: 'format-underline', label: 'Underline' },
  { command: 'formatBlock:h2', icon: 'format-header-2', label: 'Heading' },
  { command: 'insertUnorderedList', icon: 'format-list-bulleted', label: 'Bulleted list' },
  { command: 'insertOrderedList', icon: 'format-list-numbered', label: 'Numbered list' },
  { command: 'removeFormat', icon: 'format-clear', label: 'Clear formatting' },
];

const fetchSubscribers = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/newsletter-subscribers', {
      params: {
        search: filters.search || undefined,
        status: filters.status || undefined,
        page: filters.page,
        per_page: pagination.value.per_page,
      },
    });

    subscribers.value = data.data || [];
    pagination.value = data.meta || pagination.value;
    stats.value = data.stats || stats.value;
    recentCampaigns.value = data.recent_campaigns || [];
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to load newsletter subscribers.');
  } finally {
    loading.value = false;
  }
};

const debouncedFetch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.page = 1;
    fetchSubscribers();
  }, 350);
};

const resetFilters = () => {
  filters.search = '';
  filters.status = '';
  filters.page = 1;
  fetchSubscribers();
};

const changePage = (page) => {
  if (!page || page === filters.page) return;
  filters.page = page;
  fetchSubscribers();
};

const toggleStatus = async (subscriber) => {
  try {
    const { data } = await axios.patch(`/api/admin/newsletter-subscribers/${subscriber.id}/status`, {
      is_active: !subscriber.is_active,
    });
    toast?.success(data.message || 'Subscriber updated.');
    await fetchSubscribers();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update subscriber.');
  }
};

const deleteSubscriber = async (subscriber) => {
  if (!confirm(`Delete ${subscriber.email} from newsletter subscribers?`)) return;

  try {
    const { data } = await axios.delete(`/api/admin/newsletter-subscribers/${subscriber.id}`);
    toast?.success(data.message || 'Subscriber deleted.');
    await fetchSubscribers();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to delete subscriber.');
  }
};

const exportSubscribers = async () => {
  exporting.value = true;
  try {
    const response = await axios.get('/api/admin/newsletter-subscribers/export', {
      responseType: 'blob',
    });
    const url = URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `newsletter-subscribers-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to export subscribers.');
  } finally {
    exporting.value = false;
  }
};

const syncEditor = () => {
  campaign.body_html = editorRef.value?.innerHTML || '';
};

const runEditorCommand = (command) => {
  editorRef.value?.focus();
  const [name, value] = command.split(':');
  document.execCommand(name, false, value || null);
  syncEditor();
};

const insertLink = () => {
  const url = window.prompt('Enter link URL');
  if (!url) return;

  editorRef.value?.focus();
  document.execCommand('createLink', false, url);
  syncEditor();
};

const resetComposer = () => {
  campaign.subject = '';
  campaign.body_html = '';
  if (editorRef.value) {
    editorRef.value.innerHTML = '';
  }
};

const sendNewsletter = async () => {
  syncEditor();

  if (!campaign.subject.trim() || !campaign.body_html.trim()) {
    toast?.warning('Enter a subject and newsletter content.');
    return;
  }

  if (!confirm(`Send this newsletter to ${stats.value.active} active subscribers?`)) {
    return;
  }

  sending.value = true;
  try {
    const { data } = await axios.post('/api/admin/newsletter-subscribers/send', {
      subject: campaign.subject,
      body_html: campaign.body_html,
    });

    toast?.success(data.message || 'Newsletter sent.');
    resetComposer();
    await fetchSubscribers();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to send newsletter.');
  } finally {
    sending.value = false;
  }
};

const formatDate = (value) => {
  if (!value) return '-';
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
};

onMounted(fetchSubscribers);
</script>
