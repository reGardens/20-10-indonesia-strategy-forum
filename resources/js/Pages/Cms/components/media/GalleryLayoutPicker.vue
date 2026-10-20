<template>
  <div class="rounded-lg border border-gray-200 bg-white p-5">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Tata Letak Galeri</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <button
        v-for="option in layoutOptions"
        :key="option.value"
        type="button"
        class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        :class="
          selectedLayout === option.value
            ? 'border-emerald-500 bg-emerald-50'
            : 'border-gray-200 hover:border-gray-300 bg-white'
        "
        @click="selectLayout(option.value)"
      >
        <!-- Visual preview -->
        <div
          class="w-full aspect-video rounded bg-gray-100 p-2 flex items-end gap-1"
          aria-hidden="true"
        >
          <!-- Masonry preview -->
          <template v-if="option.value === 'masonry'">
            <div class="flex-1 flex flex-col gap-1">
              <div class="bg-indigo-300 rounded" style="height: 60%"></div>
              <div class="bg-emerald-200 rounded" style="height: 40%"></div>
            </div>
            <div class="flex-1 flex flex-col gap-1">
              <div class="bg-emerald-200 rounded" style="height: 40%"></div>
              <div class="bg-indigo-300 rounded" style="height: 60%"></div>
            </div>
            <div class="flex-1 flex flex-col gap-1">
              <div class="bg-indigo-300 rounded" style="height: 50%"></div>
              <div class="bg-emerald-200 rounded" style="height: 50%"></div>
            </div>
          </template>

          <!-- Slider preview -->
          <template v-else-if="option.value === 'slider'">
            <div class="w-full h-full flex items-center justify-center gap-1">
              <div class="w-2 h-full bg-gray-200 rounded opacity-50"></div>
              <div class="flex-1 h-full bg-indigo-300 rounded"></div>
              <div class="w-2 h-full bg-gray-200 rounded opacity-50"></div>
            </div>
          </template>

          <!-- Grid preview -->
          <template v-else>
            <div class="w-full h-full grid grid-cols-3 grid-rows-2 gap-1">
              <div class="bg-indigo-300 rounded"></div>
              <div class="bg-emerald-200 rounded"></div>
              <div class="bg-indigo-300 rounded"></div>
              <div class="bg-emerald-200 rounded"></div>
              <div class="bg-indigo-300 rounded"></div>
              <div class="bg-emerald-200 rounded"></div>
            </div>
          </template>
        </div>

        <span
          class="text-sm font-medium"
          :class="
            selectedLayout === option.value
              ? 'text-emerald-700'
              : 'text-gray-700'
          "
        >
          {{ option.label }}
        </span>
      </button>
    </div>

    <!-- Save status -->
    <p v-if="saveMessage" class="mt-3 text-sm text-green-600" role="status">
      {{ saveMessage }}
    </p>
    <p v-if="saveError" class="mt-3 text-sm text-red-600" role="alert">
      {{ saveError }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";

const store = useStore();

const layoutOptions = [
  { value: "masonry", label: "Masonry" },
  { value: "slider", label: "Slider" },
  { value: "grid", label: "Grid" },
];

const selectedLayout = ref("masonry");
const saveMessage = ref("");
const saveError = ref("");

const settings = computed(() => store.getters["settings/settings"]);

onMounted(async () => {
  if (!settings.value) {
    await store.dispatch("settings/fetchSettings");
  }
  if (settings.value?.galleryLayout) {
    selectedLayout.value = settings.value.galleryLayout;
  }
});

async function selectLayout(layout) {
  selectedLayout.value = layout;
  saveMessage.value = "";
  saveError.value = "";

  try {
    const currentSettings = settings.value || {};
    await store.dispatch("settings/saveSettings", {
      ...currentSettings,
      galleryLayout: layout,
      updatedAt: new Date().toISOString(),
    });
    saveMessage.value = "Tata letak galeri berhasil disimpan.";
    setTimeout(() => {
      saveMessage.value = "";
    }, 3000);
  } catch (err) {
    saveError.value = err.message || "Gagal menyimpan tata letak galeri.";
  }
}
</script>
