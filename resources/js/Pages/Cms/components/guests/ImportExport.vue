<template>
  <div class="import-export flex flex-wrap items-center gap-3">
    <!-- Import -->
    <div class="relative">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
        @click="showImportDialog = true"
      >
        <svg
          class="h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
          />
        </svg>
        Impor Excel
      </button>
    </div>

    <!-- Export -->
    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
      :disabled="exporting"
      @click="handleExport"
    >
      <svg
        class="h-4 w-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
        />
      </svg>
      <span v-if="exporting">Mengekspor...</span>
      <span v-else>Ekspor Excel</span>
    </button>

    <!-- Import Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showImportDialog"
          class="fixed inset-0 z-50 flex items-center justify-center"
          role="dialog"
          aria-modal="true"
          aria-label="Impor data tamu dari Excel"
          @keydown.escape="showImportDialog = false"
        >
          <div
            class="absolute inset-0 bg-black/50 transition-opacity"
            @click="showImportDialog = false"
          />
          <div
            class="relative z-10 w-full max-w-md rounded-xl bg-white p-6 shadow-xl mx-4"
          >
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Impor Data Tamu
            </h3>
            <p class="text-sm text-gray-600 mb-4">
              Unggah file Excel (.xlsx) dengan kolom: Nama, No. Telepon, Jumlah
              Tamu
            </p>

            <FileUploader accept=".xlsx,.xls" @files-selected="handleImport" />

            <p
              v-if="importError"
              class="mt-3 text-xs text-red-600"
              role="alert"
            >
              {{ importError }}
            </p>
            <p
              v-if="importSuccess"
              class="mt-3 text-xs text-green-600"
              role="status"
            >
              {{ importSuccess }}
            </p>

            <div class="mt-4 flex justify-end">
              <button
                type="button"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
                @click="closeImportDialog"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref } from "vue";
import FileUploader from "@/Pages/Cms/components/shared/FileUploader.vue";

const emit = defineEmits(["import", "export"]);

const showImportDialog = ref(false);
const importing = ref(false);
const exporting = ref(false);
const importError = ref("");
const importSuccess = ref("");

function closeImportDialog() {
  showImportDialog.value = false;
  importError.value = "";
  importSuccess.value = "";
}

async function handleImport(files) {
  if (!files || files.length === 0) return;

  importError.value = "";
  importSuccess.value = "";
  importing.value = true;

  try {
    emit("import", files[0]);
    importSuccess.value = "File berhasil diunggah. Memproses data...";
  } catch (err) {
    importError.value = err.message || "Gagal mengimpor data dari Excel";
  } finally {
    importing.value = false;
  }
}

function handleExport() {
  exporting.value = true;
  emit("export");
  // Parent will handle the async operation; reset after a short delay
  setTimeout(() => {
    exporting.value = false;
  }, 2000);
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
