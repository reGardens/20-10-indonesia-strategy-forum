<template>
  <div class="rounded-lg border border-gray-200 bg-white p-5">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <h2 class="text-lg font-semibold text-gray-900">
        Galeri Media
        <span v-if="media.length" class="text-sm font-normal text-gray-500">
          ({{ media.length }} item)
        </span>
      </h2>

      <div v-if="selectedIds.length" class="flex items-center gap-2">
        <span class="text-sm text-gray-500">
          {{ selectedIds.length }} dipilih
        </span>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
          @click="confirmBulkDelete"
        >
          Hapus Terpilih
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-if="!media.length"
      class="flex flex-col items-center justify-center py-12 text-gray-400"
    >
      <span class="text-4xl mb-2" aria-hidden="true">🖼️</span>
      <p class="text-sm">Belum ada media. Unggah foto atau video di atas.</p>
    </div>

    <!-- Grid -->
    <div
      v-else
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"
    >
      <div
        v-for="(item, index) in media"
        :key="item.id"
        class="group relative rounded-lg border overflow-hidden transition-shadow"
        :class="
          selectedIds.includes(item.id)
            ? 'border-emerald-500 ring-2 ring-emerald-200'
            : 'border-gray-200 hover:shadow-md'
        "
      >
        <!-- Checkbox -->
        <label
          class="absolute top-2 left-2 z-10 cursor-pointer"
          :for="'select-' + item.id"
        >
          <input
            :id="'select-' + item.id"
            type="checkbox"
            :checked="selectedIds.includes(item.id)"
            class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
            @change="toggleSelect(item.id)"
          />
          <span class="sr-only">Pilih {{ item.originalName }}</span>
        </label>

        <!-- Thumbnail -->
        <div class="aspect-square bg-gray-100 flex items-center justify-center">
          <img
            v-if="item.type === 'image'"
            :src="item.url || item.thumbnailUrl"
            :alt="item.originalName"
            class="h-full w-full object-cover"
            loading="lazy"
          />
          <span v-else class="text-3xl" aria-hidden="true">🎬</span>
        </div>

        <!-- Info -->
        <div class="p-2 space-y-0.5">
          <p
            class="text-xs font-medium text-gray-700 truncate"
            :title="item.originalName"
          >
            {{ item.originalName || item.fileName }}
          </p>
          <p class="text-xs text-gray-400">
            {{ formatSize(item.originalSize) }} →
            {{ formatSize(item.compressedSize) }}
          </p>
        </div>

        <!-- Actions -->
        <div
          class="absolute top-2 right-2 z-10 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <!-- Move up -->
          <button
            v-if="index > 0"
            type="button"
            class="rounded bg-white/90 p-1 text-xs shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            title="Pindah ke atas"
            @click="moveItem(index, index - 1)"
          >
            ↑
          </button>
          <!-- Move down -->
          <button
            v-if="index < media.length - 1"
            type="button"
            class="rounded bg-white/90 p-1 text-xs shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            title="Pindah ke bawah"
            @click="moveItem(index, index + 1)"
          >
            ↓
          </button>
          <!-- Delete -->
          <button
            type="button"
            class="rounded bg-red-500/90 p-1 text-xs text-white shadow hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
            title="Hapus"
            @click="confirmSingleDelete(item)"
          >
            ✕
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm dialog -->
    <ConfirmDialog
      :visible="showConfirm"
      :title="confirmTitle"
      :message="confirmMessage"
      confirm-text="Hapus"
      cancel-text="Batal"
      @confirm="handleConfirmDelete"
      @cancel="showConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useStore } from "vuex";
import ConfirmDialog from "@/Pages/Cms/components/shared/ConfirmDialog.vue";

const store = useStore();

const media = computed(() => store.getters["media/media"]);

const selectedIds = ref([]);
const showConfirm = ref(false);
const confirmTitle = ref("");
const confirmMessage = ref("");
const pendingDeleteAction = ref(null);

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx === -1) {
    selectedIds.value.push(id);
  } else {
    selectedIds.value.splice(idx, 1);
  }
}

function confirmSingleDelete(item) {
  confirmTitle.value = "Hapus Media";
  confirmMessage.value = `Apakah Anda yakin ingin menghapus "${item.originalName || item.fileName}"?`;
  pendingDeleteAction.value = async () => {
    await store.dispatch("media/deleteMedia", item.id);
    selectedIds.value = selectedIds.value.filter((sid) => sid !== item.id);
  };
  showConfirm.value = true;
}

function confirmBulkDelete() {
  confirmTitle.value = "Hapus Media Terpilih";
  confirmMessage.value = `Apakah Anda yakin ingin menghapus ${selectedIds.value.length} media yang dipilih?`;
  pendingDeleteAction.value = async () => {
    await store.dispatch("media/bulkDeleteMedia", [...selectedIds.value]);
    selectedIds.value = [];
  };
  showConfirm.value = true;
}

async function handleConfirmDelete() {
  showConfirm.value = false;
  if (pendingDeleteAction.value) {
    await pendingDeleteAction.value();
    pendingDeleteAction.value = null;
  }
}

async function moveItem(fromIndex, toIndex) {
  const items = [...media.value];
  const [moved] = items.splice(fromIndex, 1);
  items.splice(toIndex, 0, moved);

  const orderedItems = items.map((item, i) => ({
    id: item.id,
    sortOrder: i + 1,
  }));

  await store.dispatch("media/reorderMedia", orderedItems);
}

function formatSize(bytes) {
  if (!bytes && bytes !== 0) return "-";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
}
</script>
