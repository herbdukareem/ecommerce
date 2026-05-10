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
            :class="activeTab === tab.id ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
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
          <Field label="Logo">
            <div class="flex items-center gap-4">
              <img
                v-if="logoPreview"
                :src="logoPreview"
                :alt="`${settings.site_name || 'Store'} logo preview`"
                class="h-16 w-16 rounded-lg border border-gray-200 object-contain bg-white"
              />
              <div v-else class="flex h-16 w-16 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-500">
                No logo
              </div>
              <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="form-input" @change="handleLogoChange" />
            </div>
            <p class="mt-2 text-xs text-gray-500">PNG, JPG, WEBP, or SVG up to 2MB.</p>
          </Field>
          <Field label="Contact Email">
            <input v-model="settings.site_email" type="email" class="form-input" />
          </Field>
          <Field label="Phone Number">
            <input v-model="settings.site_phone" type="text" class="form-input" />
          </Field>
          <Field label="WhatsApp Number">
            <input v-model="settings.site_whatsapp_number" type="text" class="form-input" placeholder="2348012345678" />
            <p class="mt-2 text-xs text-gray-500">Use international format without spaces or plus sign, for example 2348012345678.</p>
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

      <form v-if="activeTab === 'theme'" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
        <SectionHeader title="Theme Colors" description="These colors power buttons, links, highlights, and brand accents across admin and storefront pages." />
        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
          <Field label="Primary Color">
            <ColorInput v-model="settings.theme_primary_color" />
          </Field>
          <Field label="Secondary Color">
            <ColorInput v-model="settings.theme_secondary_color" />
          </Field>
          <Field label="Tertiary Color">
            <ColorInput v-model="settings.theme_tertiary_color" />
          </Field>
        </div>
        <div class="mt-6 rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-semibold text-gray-900">Brand preview</p>
          <div class="mt-4 flex flex-wrap items-center gap-3">
            <span class="rounded-lg px-4 py-2 text-sm font-semibold text-white" :style="{ backgroundColor: settings.theme_primary_color }">Primary action</span>
            <span class="rounded-lg px-4 py-2 text-sm font-semibold text-white" :style="{ backgroundColor: settings.theme_secondary_color }">Secondary signal</span>
            <span class="rounded-lg px-4 py-2 text-sm font-semibold text-white" :style="{ backgroundColor: settings.theme_tertiary_color }">Tertiary accent</span>
          </div>
        </div>
        <SaveBar :saving="saving" />
      </form>

      <form v-if="activeTab === 'homepage'" class="space-y-6" @submit.prevent="saveSettings">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <SectionHeader title="Top Flash Banner" description="Control the homepage announcement or site-wide promotion without editing code." />
          <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ToggleRow v-model="settings.homepage_flash_enabled" title="Show Flash Banner" description="Turn the announcement area on or off." class="lg:col-span-2 rounded-lg border border-gray-100 px-4" />
            <Field label="Display Location">
              <select v-model="settings.homepage_flash_location" class="form-input">
                <option value="home">Home page only</option>
                <option value="global">Top of every page</option>
                <option value="hidden">Hidden</option>
              </select>
            </Field>
            <Field label="Title">
              <input v-model="settings.homepage_flash_title" type="text" class="form-input" />
            </Field>
            <Field label="Message" class="lg:col-span-2">
              <textarea v-model="settings.homepage_flash_message" rows="3" class="form-input"></textarea>
            </Field>
            <Field label="Highlight Text">
              <input v-model="settings.homepage_flash_highlight" type="text" class="form-input" />
            </Field>
            <Field label="Button Label">
              <input v-model="settings.homepage_flash_button_label" type="text" class="form-input" />
            </Field>
            <Field label="Button Link">
              <input v-model="settings.homepage_flash_button_url" type="text" class="form-input" placeholder="/products" />
            </Field>
            <Field label="Countdown Target">
              <input v-model="settings.homepage_flash_countdown_target" type="datetime-local" class="form-input" />
              <label class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                <input v-model="settings.homepage_flash_countdown_enabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary/40" />
                Show countdown
              </label>
            </Field>
            <Field label="Background Color">
              <ColorInput v-model="settings.homepage_flash_background_color" />
            </Field>
            <Field label="Text Color">
              <ColorInput v-model="settings.homepage_flash_text_color" />
            </Field>
            <Field label="Optional Flash Image" class="lg:col-span-2">
              <ImageUpload :preview="flashImagePreview" label="Flash banner image" @change="handleFlashImageChange" />
            </Field>
          </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <SectionHeader title="Hero Section" description="Edit the main homepage headline, image, layout, and call-to-action buttons." />
          <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ToggleRow v-model="settings.homepage_hero_enabled" title="Show Hero Section" description="Turn the homepage hero on or off." class="lg:col-span-2 rounded-lg border border-gray-100 px-4" />
            <Field label="Layout Style">
              <select v-model="settings.homepage_hero_layout" class="form-input">
                <option value="image_right">Image right</option>
                <option value="image_left">Image left</option>
                <option value="centered">Centered text</option>
                <option value="full_bleed">Full image background</option>
              </select>
            </Field>
            <Field label="Background Style">
              <select v-model="settings.homepage_hero_background_style" class="form-input">
                <option value="soft">Soft brand tint</option>
                <option value="solid">Solid brand color</option>
                <option value="light">Light clean background</option>
                <option value="full_image">Use image as background</option>
              </select>
            </Field>
            <Field label="Eyebrow">
              <input v-model="settings.homepage_hero_eyebrow" type="text" class="form-input" />
            </Field>
            <Field label="Headline">
              <input v-model="settings.homepage_hero_headline" type="text" class="form-input" />
            </Field>
            <Field label="Subheadline" class="lg:col-span-2">
              <textarea v-model="settings.homepage_hero_subheadline" rows="3" class="form-input"></textarea>
            </Field>
            <Field label="Primary Button Label">
              <input v-model="settings.homepage_hero_primary_button_label" type="text" class="form-input" />
            </Field>
            <Field label="Primary Button Link">
              <input v-model="settings.homepage_hero_primary_button_url" type="text" class="form-input" placeholder="/products" />
            </Field>
            <Field label="Secondary Button Label">
              <input v-model="settings.homepage_hero_secondary_button_label" type="text" class="form-input" />
            </Field>
            <Field label="Secondary Button Link">
              <input v-model="settings.homepage_hero_secondary_button_url" type="text" class="form-input" placeholder="/products?sale=true" />
            </Field>
            <Field label="Hero Image" class="lg:col-span-2">
              <ImageUpload :preview="heroImagePreview" label="Hero image" @change="handleHeroImageChange" />
            </Field>
          </div>
        </section>

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
import { computed, h, onMounted, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import { useSettingsStore } from '../../stores/settings';

const activeTab = ref('general');
const saving = ref(false);
const testingMail = ref(false);
const testEmail = ref('');
const testMailMessage = ref('');
const testMailError = ref('');
const logoFile = ref(null);
const flashImageFile = ref(null);
const heroImageFile = ref(null);
const logoPreviewOverride = ref('');
const flashImagePreviewOverride = ref('');
const heroImagePreviewOverride = ref('');
const settingsStore = useSettingsStore();

const tabs = [
  { id: 'general', label: 'General', icon: 'mdi mdi-cog' },
  { id: 'theme', label: 'Theme', icon: 'mdi mdi-palette-outline' },
  { id: 'homepage', label: 'Homepage Content', icon: 'mdi mdi-home-edit-outline' },
  { id: 'currency', label: 'Currency', icon: 'mdi mdi-currency-usd' },
  { id: 'mail', label: 'Mail', icon: 'mdi mdi-email-outline' },
  { id: 'features', label: 'Features', icon: 'mdi mdi-toggle-switch' },
];

const settings = ref({
  site_name: '',
  site_description: '',
  site_email: '',
  site_phone: '',
  site_whatsapp_number: '',
  site_logo_path: '',
  site_logo_url: '',
  theme_primary_color: '#063f7c',
  theme_secondary_color: '#43b02a',
  theme_tertiary_color: '#f59e0b',
  homepage_flash_enabled: true,
  homepage_flash_location: 'home',
  homepage_flash_title: 'Launching Soon',
  homepage_flash_message: 'Fresh deals, fast delivery, and everyday essentials are ready for you.',
  homepage_flash_highlight: 'Up to 60% off',
  homepage_flash_button_label: 'Shop Deals',
  homepage_flash_button_url: '/products',
  homepage_flash_background_color: '#063f7c',
  homepage_flash_text_color: '#ffffff',
  homepage_flash_image_path: '',
  homepage_flash_image_url: '',
  homepage_flash_countdown_enabled: false,
  homepage_flash_countdown_target: '',
  homepage_hero_enabled: true,
  homepage_hero_layout: 'image_right',
  homepage_hero_eyebrow: 'Online Mart',
  homepage_hero_headline: 'Discover quality products for every need',
  homepage_hero_subheadline: 'Shop trusted items with clear prices, easy checkout, and reliable delivery updates.',
  homepage_hero_primary_button_label: 'Shop Now',
  homepage_hero_primary_button_url: '/products',
  homepage_hero_secondary_button_label: 'View Deals',
  homepage_hero_secondary_button_url: '/products?sale=true',
  homepage_hero_image_path: '',
  homepage_hero_image_url: '',
  homepage_hero_background_style: 'soft',
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

const logoPreview = computed(() => logoPreviewOverride.value || settings.value.site_logo_url || settings.value.site_logo_path || '');
const flashImagePreview = computed(() => flashImagePreviewOverride.value || settings.value.homepage_flash_image_url || settings.value.homepage_flash_image_path || '');
const heroImagePreview = computed(() => heroImagePreviewOverride.value || settings.value.homepage_hero_image_url || settings.value.homepage_hero_image_path || '');
const hasUpload = computed(() => Boolean(logoFile.value || flashImageFile.value || heroImageFile.value));

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
        class: 'rounded-lg bg-primary px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50',
      }, props.saving ? 'Saving...' : 'Save Changes'),
    ]);
  },
};

const ColorInput = {
  props: { modelValue: String },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const update = (value) => emit('update:modelValue', value);

    return () => h('div', { class: 'flex gap-2' }, [
      h('input', {
        type: 'color',
        value: props.modelValue,
        class: 'h-11 w-14 rounded-lg border border-gray-300 bg-white p-1',
        onInput: (event) => update(event.target.value),
      }),
      h('input', {
        type: 'text',
        value: props.modelValue,
        class: 'form-input uppercase',
        placeholder: '#063F7C',
        onInput: (event) => update(event.target.value),
      }),
    ]);
  },
};

const ImageUpload = {
  props: { preview: String, label: String },
  emits: ['change'],
  setup(props, { emit }) {
    return () => h('div', { class: 'flex flex-col gap-3 sm:flex-row sm:items-center' }, [
      props.preview
        ? h('img', {
            src: props.preview,
            alt: `${props.label} preview`,
            class: 'h-24 w-32 rounded-lg border border-gray-200 object-cover bg-white',
          })
        : h('div', { class: 'flex h-24 w-32 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-500' }, 'No image'),
      h('div', { class: 'flex-1' }, [
        h('input', {
          type: 'file',
          accept: 'image/png,image/jpeg,image/webp,image/svg+xml',
          class: 'form-input',
          onChange: (event) => emit('change', event),
        }),
        h('p', { class: 'mt-2 text-xs text-gray-500' }, 'PNG, JPG, WEBP, or SVG up to 4MB.'),
      ]),
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
        class: 'h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary/40',
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
    logoPreviewOverride.value = '';
    flashImagePreviewOverride.value = '';
    heroImagePreviewOverride.value = '';
  } catch (error) {
    console.error('Failed to fetch settings:', error);
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    await axios[hasUpload.value ? 'post' : 'put']('/api/admin/settings', settingsPayload());
    logoFile.value = null;
    flashImageFile.value = null;
    heroImageFile.value = null;
    settings.value.mail_sandbox_password = '';
    settings.value.mail_live_password = '';
    await fetchSettings();
    await settingsStore.initSettings();
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
  delete payload.site_logo_url;
  delete payload.homepage_flash_image_url;
  delete payload.homepage_hero_image_url;
  delete payload.mail_sandbox_password_configured;
  delete payload.mail_live_password_configured;

  if (!payload.mail_sandbox_password) {
    delete payload.mail_sandbox_password;
  }

  if (!payload.mail_live_password) {
    delete payload.mail_live_password;
  }

  if (!hasUpload.value) {
    return payload;
  }

  const formData = new FormData();
  Object.entries(payload).forEach(([key, value]) => {
    if (value !== undefined && value !== null) {
      formData.append(key, value);
    }
  });
  if (logoFile.value) {
    formData.append('site_logo', logoFile.value);
  }
  if (flashImageFile.value) {
    formData.append('homepage_flash_image', flashImageFile.value);
  }
  if (heroImageFile.value) {
    formData.append('homepage_hero_image', heroImageFile.value);
  }
  formData.append('_method', 'PUT');

  return formData;
};

const handleLogoChange = (event) => {
  const file = event.target.files?.[0] || null;
  logoFile.value = file;
  logoPreviewOverride.value = file ? URL.createObjectURL(file) : '';
};

const handleFlashImageChange = (event) => {
  const file = event.target.files?.[0] || null;
  flashImageFile.value = file;
  flashImagePreviewOverride.value = file ? URL.createObjectURL(file) : '';
};

const handleHeroImageChange = (event) => {
  const file = event.target.files?.[0] || null;
  heroImageFile.value = file;
  heroImagePreviewOverride.value = file ? URL.createObjectURL(file) : '';
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
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 18%, transparent);
}
</style>
