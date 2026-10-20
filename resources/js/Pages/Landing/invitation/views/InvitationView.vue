<template>
  <div class="invitation-view w-full" :class="{ 'pt-[52px]': isDemo }">
    <!-- Sticky Demo Header -->
    <div
      v-if="isDemo"
      class="fixed top-0 left-0 right-0 z-[9999] flex items-center justify-between border-b border-white/10 bg-slate-950/80 px-4 py-3 backdrop-blur-md text-white shadow-lg md:px-8"
    >
      <div class="flex items-center gap-2">
        <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
        <span class="text-xs font-semibold tracking-wider uppercase text-white/90">Pratinjau Tema</span>
        <span class="hidden text-xs text-white/60 md:inline">| Mode Demo Superadmin</span>
      </div>
      <a
        :href="`/cms/templates/create?template=${slug.replace('demo-', '')}`"
        class="flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white transition-all hover:bg-emerald-500 hover:shadow-lg hover:shadow-emerald-600/20 active:scale-95"
      >
        <span>Pilih Template Ini</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12l5 5l10 -10" />
        </svg>
      </a>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="flex min-h-screen items-center justify-center bg-white"
    >
      <div class="text-center">
        <div
          class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-amber-600"
        ></div>
        <p class="text-sm text-gray-500">Memuat undangan...</p>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="flex min-h-screen items-center justify-center bg-white px-4"
    >
      <div class="max-w-md text-center">
        <div class="mb-4 text-5xl">💌</div>
        <h2 class="mb-2 text-xl font-semibold text-gray-800">
          Undangan Tidak Ditemukan
        </h2>
        <p class="mb-6 text-sm text-gray-500">
          {{ error }}
        </p>
        <button
          class="rounded-lg bg-amber-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-700"
          @click="loadData"
        >
          Coba Lagi
        </button>
      </div>
    </div>

    <!-- Template Render -->
    <component
      v-else-if="resolvedTemplate"
      :is="resolvedTemplate"
      :couple="couple"
      :events="events"
      :settings="settings"
      :media="media"
      :slug="slug"
      :template-record="activeTemplateRecord"
    />

    <!-- Template Not Found Fallback -->
    <div
      v-else
      class="flex min-h-screen items-center justify-center bg-white px-4"
    >
      <div class="max-w-md text-center">
        <div class="mb-4 text-5xl">🎨</div>
        <h2 class="mb-2 text-xl font-semibold text-gray-800">
          Template Tidak Tersedia
        </h2>
        <p class="text-sm text-gray-500">
          Template undangan belum dikonfigurasi. Silakan hubungi admin.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, shallowRef, computed } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";
import { coupleService } from "@/api/services/coupleService.js";
import { eventService } from "@/api/services/eventService.js";
import { settingsService } from "@/api/services/settingsService.js";
import { mediaService } from "@/api/services/mediaService.js";
import { weddingRegistryService } from "@/api/services/weddingRegistryService.js";
import { getTemplate } from "@/Pages/Landing/invitation/templates/index.js";
import UniversalTemplate from "@/Pages/Landing/invitation/templates/universal/UniversalTemplate.vue";

const route = useRoute();

const slug = ref(route.params.slug || "");
const loading = ref(true);
const error = ref(null);

const isDemo = computed(() => slug.value.startsWith("demo-"));

const couple = ref(null);
const events = ref([]);
const settings = ref(null);
const media = ref([]);
const resolvedTemplate = shallowRef(null);
const activeTemplateRecord = ref(null);

/**
 * Set or create a <meta> tag with the given property and content.
 */
function setMetaTag(property, content) {
  if (!content) return;
  let el = document.querySelector(`meta[property="${property}"]`);
  if (!el) {
    el = document.createElement("meta");
    el.setAttribute("property", property);
    document.head.appendChild(el);
  }
  el.setAttribute("content", content);
}

/**
 * Apply SEO meta tags from settings data.
 */
function applySeoMeta(settingsData, coupleData) {
  const seo = settingsData?.seoMeta || {};

  // Determine fallback title from couple names
  const groomName =
    coupleData?.groom?.nickname || coupleData?.groom?.fullName || "";
  const brideName =
    coupleData?.bride?.nickname || coupleData?.bride?.fullName || "";
  const fallbackTitle =
    groomName && brideName
      ? `Pernikahan ${groomName} & ${brideName}`
      : "Undangan Pernikahan";

  const title = seo.title || fallbackTitle;
  const description =
    seo.description || "Kami mengundang Anda untuk hadir di hari bahagia kami";
  const image = seo.image || "";

  // Set document title
  document.title = title;

  // Set Open Graph meta tags
  setMetaTag("og:title", title);
  setMetaTag("og:description", description);
  if (image) {
    setMetaTag("og:image", image);
  }
}

async function loadData() {
  loading.value = true;
  error.value = null;
  resolvedTemplate.value = null;

  // Validate slug against registry
  const slugExists = await weddingRegistryService.exists(slug.value);
  if (!slugExists) {
    error.value =
      "Undangan tidak ditemukan. Pastikan URL yang Anda akses sudah benar.";
    loading.value = false;
    return;
  }

  try {
    const [coupleData, eventsData, settingsData, mediaData] = await Promise.all(
      [
        coupleService.get(slug.value),
        eventService.getAll(slug.value),
        settingsService.get(slug.value),
        mediaService.getAll(slug.value),
      ],
    );

    couple.value = coupleData;
    events.value = eventsData;
    settings.value = settingsData;
    media.value = mediaData;

    // Apply SEO meta tags
    applySeoMeta(settingsData, coupleData);

    // Fetch template options from backend to check if template is custom
    let templatesList = [];
    try {
      const templatesRes = await axios.get("/api/templates");
      templatesList = templatesRes.data;
    } catch {}

    // Resolve template from registry
    const templateId = settingsData?.templateId || "batik-elegance";
    activeTemplateRecord.value = templatesList.find((t) => t.id === templateId) || null;
    const template = getTemplate(templateId);

    if (template && template.component) {
      try {
        const mod = await template.component();
        resolvedTemplate.value = mod.default || mod;
      } catch {
        // Fallback
        resolvedTemplate.value = UniversalTemplate;
      }
    } else {
      // It's a custom template! Resolve to UniversalTemplate
      resolvedTemplate.value = UniversalTemplate;
    }
  } catch (err) {
    error.value =
      err?.message || "Gagal memuat data undangan. Silakan coba lagi nanti.";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadData();
});
</script>
