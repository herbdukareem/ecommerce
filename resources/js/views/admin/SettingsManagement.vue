<template>
  <AdminLayout>
    <div class="space-y-6 pb-8">
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Store configuration</p>
        <h1 class="mt-1 text-3xl font-bold text-gray-900">Settings</h1>
        <p class="mt-1 text-sm text-gray-600">Manage store identity, currency, email delivery, and feature toggles.</p>
      </div>

      <div class="border-b border-gray-200">
        <nav class="flex flex-wrap gap-6">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="activeTab = tab.id"
            :class="activeTab === tab.id ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
            class="border-b-2 px-1 py-4 text-sm font-medium transition-colors"
          >
            <i :class="tab.icon" class="mr-2"></i>
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <form v-if="activeTab === 'general'" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
        <SectionHeader title="General Settings" description="Public store identity and business contact details." />
        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
          <Field label="Site Name">
            <input v-model="settings.site_name" type="text" class="form-input" />
          </Field>
          <Field label="Contact Email">
            <input v-model="settings.site_email" type="email" class="form-input" />
          </Field>
          <Field label="Phone Number">
            <input v-model="settings.site_phone" type="text" class="form-input" />
          </Field>
          <Field label="Tax Rate (%)">
            <input v-model.number="settings.tax_rate" type="number" step="0.01" class="form-input" />
          </Field>
          <Field label="Site Description" class="lg:col-span-2">
            <textarea v-model="settings.site_description" rows="3" class="form-input"></textarea>
          </Field>
        </div>
        <SaveBar :saving="saving" />
      </form>

      <form v-if="activeTab === 'currency'" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
        <SectionHeader title="Currency Settings" description="Controls how money values appear across the storefront, admin, receipts, and emails." />
        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
          <Field label="Currency">
            <select v-model="settings.currency" class="form-input">
              <option value="NGN">Nigerian Naira (NGN)</option>
              <option value="USD">US Dollar (USD)</option>
              <option value="EUR">Euro (EUR)</option>
              <option value="GBP">British Pound (GBP)</option>
              <option value="ZAR">South African Rand (ZAR)</option>
              <option value="KES">Kenyan Shilling (KES)</option>
              <option value="GHS">Ghanaian Cedi (GHS)</option>
            </select>
          </Field>
          <Field label="Currency Symbol">
            <input v-model="settings.currency_symbol" type="text" class="form-input" />
          </Field>
          <Field label="Locale">
            <input v-model="settings.currency_locale" type="text" class="form-input" placeholder="en-NG" />
          </Field>
        </div>
        <div class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
          Preview: <strong>{{ settings.currency_symbol }}1,000.00</strong>
        </div>
        <SaveBar :saving="saving" />
      </form>

      <form v-if="activeTab === 'mail'" class="space-y-6" @submit.prevent="saveSettings">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <SectionHeader title="Mail Mode" description="Auto uses sandbox outside production and live in production. Use manual modes when you need to force a specific environment." />
          <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <Field label="Active Mode">
              <select v-model="settings.mail_mode" class="form-input">
                <option value="auto">Auto (local sandbox, production live)</option>
                <option value="sandbox">Force Sandbox</option>
                <option value="live">Force Live</option>
              </select>
            </Field>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 lg:col-span-2">
              <p class="text-sm font-semibold text-gray-900">Currently resolves to {{ humanize(settings.active_mail_mode || 'sandbox') }}</p>
              <p class="mt-1 text-sm text-gray-600">Use sandbox for local tests. Use live only when the sending domain has been verified by your provider.</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
          <MailPanel
            title="Sandbox Mail"
            description="Best for local testing. Use log mailer or Mailtrap sandbox SMTP."
            prefix="mail_sandbox"
            :settings="settings"
          />
          <MailPanel
            title="Live Mail"
            description="Production email. Sender domain must be verified by your SMTP provider."
            prefix="mail_live"
            :settings="settings"
          />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <SectionHeader title="Send Test Email" description="Save your settings first, then send a test through the currently active mail mode." />
          <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            <input v-model="testEmail" type="email" class="form-input flex-1" placeholder="admin@example.com" />
            <button
              type="button"
              :disabled="testingMail || !testEmail"
              class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 disabled:opacity-50"
              @click="sendTestMail"
            >
              <i v-if="testingMail" class="mdi mdi-loading mdi-spin mr-2"></i>
              {{ testingMail ? 'Sending...' : 'Send Test' }}
            </button>
          </div>
          <p v-if="testMailMessage" class="mt-3 text-sm text-green-700">{{ testMailMessage }}</p>
          <p v-if="testMailError" class="mt-3 text-sm text-red-700">{{ testMailError }}</p>
        </div>

        <SaveBar :saving="saving" />
      </form>

      <form v-if="activeTab === 'features'" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
        <SectionHeader title="Feature Toggles" description="Turn customer-facing store features on or off." />
        <div class="mt-5 divide-y divide-gray-200">
          <ToggleRow v-model="settings.enable_reviews" title="Product Reviews" description="Allow customers to review products." />
          <ToggleRow v-model="settings.enable_wishlist" title="Wishlist" description="Enable wishlist functionality." />
          <ToggleRow v-model="settings.enable_coupons" title="Coupons & Discounts" description="Enable coupon code functionality." />
        </div>
        <SaveBar :saving="saving" />
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { h, onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';

const activeTab = ref('general');
const saving = ref(false);
const testingMail = ref(false);
const testEmail = ref('');
const testMailMessage = ref('');
const testMailError = ref('');

const tabs = [
  { id: 'general', label: 'General', icon: 'mdi mdi-cog' },
  { id: 'currency', label: 'Currency', icon: 'mdi mdi-currency-usd' },
  { id: 'mail', label: 'Mail', icon: 'mdi mdi-email-outline' },
  { id: 'features', label: 'Features', icon: 'mdi mdi-toggle-switch' },
];

const settings = ref({
  site_name: '',
  site_description: '',
  site_email: '',
  site_phone: '',
  currency: 'NGN',
  currency_symbol: 'NGN ',
  currency_locale: 'en-NG',
  tax_rate: 7.5,
  enable_reviews: true,
  enable_wishlist: true,
  enable_coupons: true,
  mail_mode: 'auto',
  active_mail_mode: 'sandbox',
  mail_sandbox_mailer: 'log',
  mail_sandbox_host: 'sandbox.smtp.mailtrap.io',
  mail_sandbox_port: 2525,
  mail_sandbox_username: '',
  mail_sandbox_password: '',
  mail_sandbox_encryption: 'tls',
  mail_sandbox_from_address: '',
  mail_sandbox_from_name: '',
  mail_sandbox_password_configured: false,
  mail_live_mailer: 'smtp',
  mail_live_host: 'live.smtp.mailtrap.io',
  mail_live_port: 587,
  mail_live_username: '',
  mail_live_password: '',
  mail_live_encryption: 'tls',
  mail_live_from_address: '',
  mail_live_from_name: '',
  mail_live_password_configured: false,
});

const SectionHeader = {
  props: { title: String, description: String },
  setup(props) {
    return () => h('div', [
      h('h2', { class: 'text-lg font-semibold text-gray-900' }, props.title),
      h('p', { class: 'mt-1 text-sm text-gray-600' }, props.description),
    ]);
  },
};

const Field = {
  props: { label: String },
  setup(props, { slots, attrs }) {
    return () => h('div', attrs, [
      h('label', { class: 'mb-2 block text-sm font-medium text-gray-700' }, props.label),
      slots.default?.(),
    ]);
  },
};

const SaveBar = {
  props: { saving: Boolean },
  setup(props) {
    return () => h('div', { class: 'mt-6 flex justify-end' }, [
      h('button', {
        type: 'submit',
        disabled: props.saving,
        class: 'rounded-lg bg-orange-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700 disabled:opacity-50',
      }, props.saving ? 'Saving...' : 'Save Changes'),
    ]);
  },
};

const ToggleRow = {
  props: { modelValue: Boolean, title: String, description: String },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('div', { class: 'flex items-center justify-between gap-4 py-4' }, [
      h('div', [
        h('h3', { class: 'font-medium text-gray-900' }, props.title),
        h('p', { class: 'text-sm text-gray-500' }, props.description),
      ]),
      h('input', {
        type: 'checkbox',
        checked: props.modelValue,
        class: 'h-5 w-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500',
        onChange: (event) => emit('update:modelValue', event.target.checked),
      }),
    ]);
  },
};

const MailPanel = {
  props: { title: String, description: String, prefix: String, settings: Object },
  setup(props) {
    const key = (suffix) => `${props.prefix}_${suffix}`;
    return () => h('section', { class: 'rounded-xl border border-gray-200 bg-white p-6 shadow-sm' }, [
      h('h2', { class: 'text-lg font-semibold text-gray-900' }, props.title),
      h('p', { class: 'mt-1 text-sm text-gray-600' }, props.description),
      h('div', { class: 'mt-5 grid grid-cols-1 gap-4' }, [
        h(Field, { label: 'Mailer' }, () => h('select', {
          class: 'form-input',
          value: props.settings[key('mailer')],
          onChange: (event) => { props.settings[key('mailer')] = event.target.value; },
        }, [
          h('option', { value: 'log' }, 'Log'),
          h('option', { value: 'smtp' }, 'SMTP'),
        ])),
        h(Field, { label: 'Host' }, () => h('input', {
          class: 'form-input',
          value: props.settings[key('host')],
          onInput: (event) => { props.settings[key('host')] = event.target.value; },
          placeholder: props.prefix === 'mail_live' ? 'live.smtp.mailtrap.io' : 'sandbox.smtp.mailtrap.io',
        })),
        h('div', { class: 'grid grid-cols-1 gap-4 sm:grid-cols-2' }, [
          h(Field, { label: 'Port' }, () => h('input', {
            class: 'form-input',
            type: 'number',
            value: props.settings[key('port')],
            onInput: (event) => { props.settings[key('port')] = Number(event.target.value || 0); },
          })),
          h(Field, { label: 'Encryption' }, () => h('select', {
            class: 'form-input',
            value: props.settings[key('encryption')],
            onChange: (event) => { props.settings[key('encryption')] = event.target.value; },
          }, [
            h('option', { value: 'tls' }, 'TLS'),
            h('option', { value: 'ssl' }, 'SSL'),
            h('option', { value: 'null' }, 'None'),
          ])),
        ]),
        h(Field, { label: 'Username' }, () => h('input', {
          class: 'form-input',
          value: props.settings[key('username')],
          onInput: (event) => { props.settings[key('username')] = event.target.value; },
        })),
        h(Field, { label: props.settings[key('password_configured')] ? 'Password / API Token (configured)' : 'Password / API Token' }, () => h('input', {
          class: 'form-input',
          type: 'password',
          value: props.settings[key('password')],
          onInput: (event) => { props.settings[key('password')] = event.target.value; },
          placeholder: props.settings[key('password_configured')] ? 'Leave blank to keep existing password' : '',
        })),
        h(Field, { label: 'From Address' }, () => h('input', {
          class: 'form-input',
          type: 'email',
          value: props.settings[key('from_address')],
          onInput: (event) => { props.settings[key('from_address')] = event.target.value; },
        })),
        h(Field, { label: 'From Name' }, () => h('input', {
          class: 'form-input',
          value: props.settings[key('from_name')],
          onInput: (event) => { props.settings[key('from_name')] = event.target.value; },
        })),
      ]),
    ]);
  },
};

const parseSettingValue = (value) => {
  if (value === 'true') return true;
  if (value === 'false') return false;
  if (value !== '' && value !== null && !Number.isNaN(Number(value)) && String(value).match(/^\d+(\.\d+)?$/)) {
    return Number(value);
  }
  return value;
};

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/admin/settings');
    const entries = Array.isArray(data)
      ? data.map((setting) => [setting.key, setting.value])
      : Object.entries(data || {});

    entries.forEach(([key, value]) => {
      if (key in settings.value && value !== null) {
        settings.value[key] = parseSettingValue(value);
      }
    });
  } catch (error) {
    console.error('Failed to fetch settings:', error);
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    await axios.put('/api/admin/settings', settingsPayload());
    settings.value.mail_sandbox_password = '';
    settings.value.mail_live_password = '';
    await fetchSettings();
    alert('Settings saved successfully.');
  } catch (error) {
    console.error('Failed to save settings:', error);
    alert(error.response?.data?.message || 'Failed to save settings. Please try again.');
  } finally {
    saving.value = false;
  }
};

const settingsPayload = () => {
  const payload = { ...settings.value };

  delete payload.active_mail_mode;
  delete payload.mail_sandbox_password_configured;
  delete payload.mail_live_password_configured;

  if (!payload.mail_sandbox_password) {
    delete payload.mail_sandbox_password;
  }

  if (!payload.mail_live_password) {
    delete payload.mail_live_password;
  }

  return payload;
};

const sendTestMail = async () => {
  testMailMessage.value = '';
  testMailError.value = '';
  testingMail.value = true;
  try {
    const { data } = await axios.post('/api/admin/settings/mail/test', { email: testEmail.value });
    testMailMessage.value = `${data.message} Active mode: ${humanize(data.active_mode || settings.value.active_mail_mode)}.`;
  } catch (error) {
    testMailError.value = error.response?.data?.message || 'Unable to send test email.';
  } finally {
    testingMail.value = false;
  }
};

const humanize = (value) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

onMounted(fetchSettings);
</script>

<style scoped>
.form-input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
  padding: 0.625rem 0.875rem;
  color: #111827;
  outline: none;
}

.form-input:focus {
  border-color: #f97316;
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.18);
}
</style>
