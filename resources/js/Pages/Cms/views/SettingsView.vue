<template>
  <div class="settings-view space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Pengaturan</h1>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat pengaturan..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadSettings"
    />

    <template v-else>
      <!-- Section 1: Domain & Branding -->
      <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Domain &amp; Branding
        </h2>
        <div class="space-y-4">
          <div>
            <label
              for="customDomain"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Custom Domain
            </label>
            <input
              id="customDomain"
              v-model="form.customDomain"
              type="text"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="contoh: undangan.domain.com"
            />
            <p class="mt-1 text-xs text-gray-500">
              Opsional. Masukkan domain kustom Anda.
            </p>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <label
                for="showWatermark"
                class="text-sm font-medium text-gray-700"
              >
                Tampilkan "Powered by" watermark
              </label>
              <p class="text-xs text-gray-500">
                Tampilkan atau sembunyikan branding di halaman undangan.
              </p>
            </div>
            <button
              id="showWatermark"
              type="button"
              role="switch"
              :aria-checked="form.showWatermark"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
              :class="form.showWatermark ? 'bg-emerald-600' : 'bg-gray-200'"
              @click="form.showWatermark = !form.showWatermark"
            >
              <span
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="form.showWatermark ? 'translate-x-5' : 'translate-x-0'"
              />
            </button>
          </div>
        </div>
      </div>

      <!-- Section 2: Keamanan -->
      <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Keamanan</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <label
                for="passwordProtected"
                class="text-sm font-medium text-gray-700"
              >
                Proteksi Password
              </label>
              <p class="text-xs text-gray-500">
                Tamu harus memasukkan password untuk melihat undangan.
              </p>
            </div>
            <button
              id="passwordProtected"
              type="button"
              role="switch"
              :aria-checked="form.passwordProtected"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
              :class="form.passwordProtected ? 'bg-emerald-600' : 'bg-gray-200'"
              @click="form.passwordProtected = !form.passwordProtected"
            >
              <span
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="
                  form.passwordProtected ? 'translate-x-5' : 'translate-x-0'
                "
              />
            </button>
          </div>

          <div v-if="form.passwordProtected">
            <label
              for="password"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Password
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="Masukkan password undangan"
            />
          </div>
        </div>
      </div>

      <!-- Section 3: Moderasi -->
      <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Moderasi</h2>
        <div class="flex items-center justify-between">
          <div>
            <label
              for="moderationEnabled"
              class="text-sm font-medium text-gray-700"
            >
              Aktifkan moderasi ucapan
            </label>
            <p class="text-xs text-gray-500">
              Ucapan tamu harus disetujui sebelum ditampilkan.
            </p>
          </div>
          <button
            id="moderationEnabled"
            type="button"
            role="switch"
            :aria-checked="form.moderationEnabled"
            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            :class="form.moderationEnabled ? 'bg-emerald-600' : 'bg-gray-200'"
            @click="form.moderationEnabled = !form.moderationEnabled"
          >
            <span
              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
              :class="
                form.moderationEnabled ? 'translate-x-5' : 'translate-x-0'
              "
            />
          </button>
        </div>
      </div>

      <!-- Section 4: Informasi Tambahan -->
      <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Informasi Tambahan
        </h2>
        <div class="space-y-4">
          <div>
            <label
              for="healthProtocol"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Protokol Kesehatan
            </label>
            <textarea
              id="healthProtocol"
              v-model="form.healthProtocol"
              rows="3"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="Tuliskan informasi protokol kesehatan untuk tamu..."
            />
          </div>

          <div>
            <label
              for="liveStreamUrl"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Live Streaming URL
            </label>
            <input
              id="liveStreamUrl"
              v-model="form.liveStreamUrl"
              type="text"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="contoh: https://youtube.com/live/..."
            />
          </div>

          <div>
            <label
              for="shippingAddress"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Alamat Pengiriman Hadiah Fisik
            </label>
            <textarea
              id="shippingAddress"
              v-model="form.shippingAddress"
              rows="3"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="Masukkan alamat lengkap untuk pengiriman hadiah fisik..."
            />
          </div>
        </div>
      </div>

      <!-- Section 5: SEO -->
      <div class="rounded-lg border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">SEO</h2>
        <div class="space-y-4">
          <div>
            <label
              for="seoTitle"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Meta Title
            </label>
            <input
              id="seoTitle"
              v-model="form.seoMeta.title"
              type="text"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="contoh: Pernikahan Budi & Ani"
            />
          </div>

          <div>
            <label
              for="seoDescription"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Meta Description
            </label>
            <textarea
              id="seoDescription"
              v-model="form.seoMeta.description"
              rows="2"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="contoh: Kami mengundang Anda untuk hadir di hari bahagia kami"
            />
          </div>

          <div>
            <label
              for="seoImage"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Meta Image URL
            </label>
            <input
              id="seoImage"
              v-model="form.seoMeta.image"
              type="text"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              placeholder="contoh: media/og-image.webp"
            />
          </div>
        </div>
      </div>

      <!-- Save button -->
      <div class="flex justify-end">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="saving"
          @click="handleSave"
        >
          <span v-if="saving">Menyimpan...</span>
          <span v-else>Simpan</span>
        </button>
      </div>

      <!-- Validation error -->
      <p v-if="validationError" class="text-sm text-red-600" role="alert">
        {{ validationError }}
      </p>

      <!-- Success message -->
      <p v-if="successMessage" class="text-sm text-green-600" role="status">
        {{ successMessage }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";

const store = useStore();

const form = ref({
  customDomain: "",
  showWatermark: true,
  passwordProtected: false,
  password: "",
  moderationEnabled: true,
  healthProtocol: "",
  liveStreamUrl: "",
  shippingAddress: "",
  seoMeta: {
    title: "",
    description: "",
    image: "",
  },
});

const saving = ref(false);
const validationError = ref("");
const successMessage = ref("");

const loading = computed(() => store.getters["settings/isLoading"]);
const error = computed(() => store.getters["settings/error"]);

function populateFromStore() {
  const data = store.getters["settings/settings"];
  if (data) {
    form.value.customDomain = data.customDomain || "";
    form.value.showWatermark =
      data.showWatermark !== undefined ? data.showWatermark : true;
    form.value.passwordProtected = data.passwordProtected || false;
    form.value.password = data.password || "";
    form.value.moderationEnabled =
      data.moderationEnabled !== undefined ? data.moderationEnabled : true;
    form.value.healthProtocol = data.healthProtocol || "";
    form.value.liveStreamUrl = data.liveStreamUrl || "";
    form.value.shippingAddress = data.shippingAddress || "";
    form.value.seoMeta = {
      title: data.seoMeta?.title || "",
      description: data.seoMeta?.description || "",
      image: data.seoMeta?.image || "",
    };
  }
}

async function loadSettings() {
  await store.dispatch("settings/fetchSettings");
  populateFromStore();
}

async function handleSave() {
  validationError.value = "";
  successMessage.value = "";

  saving.value = true;

  try {
    const payload = {
      customDomain: form.value.customDomain,
      showWatermark: form.value.showWatermark,
      passwordProtected: form.value.passwordProtected,
      password: form.value.password,
      moderationEnabled: form.value.moderationEnabled,
      healthProtocol: form.value.healthProtocol,
      liveStreamUrl: form.value.liveStreamUrl,
      shippingAddress: form.value.shippingAddress,
      seoMeta: { ...form.value.seoMeta },
    };

    await store.dispatch("settings/saveSettings", payload);
    successMessage.value = "Pengaturan berhasil disimpan.";

    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (err) {
    validationError.value = err.message || "Gagal menyimpan pengaturan.";
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadSettings();
});
</script>
