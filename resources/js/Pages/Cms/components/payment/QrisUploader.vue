<template>
  <div class="rounded-lg border border-gray-200 bg-white p-5">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">QRIS Payment</h3>

    <!-- Preview of uploaded QRIS image -->
    <div v-if="qrisImageUrl" class="mb-4">
      <img
        :src="qrisImageUrl"
        alt="QRIS Payment Code"
        class="max-w-xs rounded-lg border border-gray-200 shadow-sm"
      />
      <button
        type="button"
        class="mt-2 text-sm text-red-600 hover:text-red-700 underline"
        @click="removeQris"
      >
        Hapus QRIS
      </button>
    </div>

    <!-- Upload area -->
    <FileUploader
      v-else
      accept="image/*"
      :max-size-m-b="5"
      @files-selected="handleFileSelected"
    />

    <!-- Status messages -->
    <p v-if="uploading" class="mt-2 text-sm text-gray-500">Mengunggah...</p>
    <p v-if="errorMessage" class="mt-2 text-sm text-red-600" role="alert">
      {{ errorMessage }}
    </p>
    <p v-if="successMessage" class="mt-2 text-sm text-green-600" role="status">
      {{ successMessage }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import FileUploader from "@/Pages/Cms/components/shared/FileUploader.vue";

const store = useStore();

const uploading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

const qrisImageUrl = computed(() => {
  const settings = store.getters["settings/settings"];
  return settings?.qrisImageUrl || "";
});

async function handleFileSelected(files) {
  if (!files || files.length === 0) return;

  const file = files[0];
  uploading.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    // Convert file to data URL for local storage
    const dataUrl = await fileToDataUrl(file);

    const settings = store.getters["settings/settings"] || {};
    await store.dispatch("settings/saveSettings", {
      ...settings,
      qrisImageUrl: dataUrl,
    });

    successMessage.value = "QRIS berhasil diunggah";
    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (err) {
    errorMessage.value = err.message || "Gagal mengunggah QRIS";
  } finally {
    uploading.value = false;
  }
}

async function removeQris() {
  try {
    const settings = store.getters["settings/settings"] || {};
    await store.dispatch("settings/saveSettings", {
      ...settings,
      qrisImageUrl: "",
    });
    successMessage.value = "QRIS berhasil dihapus";
    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (err) {
    errorMessage.value = err.message || "Gagal menghapus QRIS";
  }
}

function fileToDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error("Gagal membaca file"));
    reader.readAsDataURL(file);
  });
}

onMounted(() => {
  if (!store.getters["settings/settings"]) {
    store.dispatch("settings/fetchSettings");
  }
});
</script>
