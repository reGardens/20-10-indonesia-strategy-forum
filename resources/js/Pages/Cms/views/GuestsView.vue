<template>
  <div class="guests-view space-y-6">
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
    >
      <h1 class="text-2xl font-bold text-gray-900">Daftar Tamu</h1>
      <div class="flex flex-wrap items-center gap-3">
        <ImportExport @import="handleImport" @export="handleExport" />
        <button
          v-if="!showForm"
          type="button"
          class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
          @click="openAddForm"
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
              d="M12 4v16m8-8H4"
            />
          </svg>
          Tambah Tamu
        </button>
      </div>
    </div>

    <!-- Search bar -->
    <div v-if="!showForm" class="relative">
      <label for="guest-search" class="sr-only">Cari tamu</label>
      <div
        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
      >
        <svg
          class="h-4 w-4 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
      <input
        id="guest-search"
        v-model="searchQuery"
        type="text"
        class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        placeholder="Cari berdasarkan nama atau nomor telepon..."
        @input="handleSearch"
      />
    </div>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat daftar tamu..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadData"
    />

    <template v-else>
      <!-- Guest Form (add/edit) -->
      <GuestForm
        v-if="showForm"
        :model-value="formData"
        :is-editing="isEditing"
        :saving="saving"
        @submit="handleSave"
        @cancel="closeForm"
      />

      <!-- Guest Table -->
      <div v-if="!showForm" class="rounded-lg border border-gray-200 bg-white">
        <GuestTable
          :guests="guests"
          @edit="openEditForm"
          @delete="confirmDelete"
        />
      </div>

      <!-- Guest count -->
      <p v-if="!showForm" class="text-sm text-gray-500">
        Total: {{ guestCount }} tamu
      </p>

      <!-- Validation / success messages -->
      <p v-if="validationError" class="text-sm text-red-600" role="alert">
        {{ validationError }}
      </p>
      <p v-if="successMessage" class="text-sm text-green-600" role="status">
        {{ successMessage }}
      </p>
    </template>

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      title="Hapus Tamu"
      :message="`Apakah Anda yakin ingin menghapus tamu '${guestToDelete?.name || ''}'?`"
      confirm-text="Hapus"
      cancel-text="Batal"
      @confirm="handleDelete"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import GuestTable from "@/Pages/Cms/components/guests/GuestTable.vue";
import GuestForm from "@/Pages/Cms/components/guests/GuestForm.vue";
import ImportExport from "@/Pages/Cms/components/guests/ImportExport.vue";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import ConfirmDialog from "@/Pages/Cms/components/shared/ConfirmDialog.vue";

const store = useStore();

const showForm = ref(false);
const isEditing = ref(false);
const editingGuestId = ref(null);
const saving = ref(false);
const validationError = ref("");
const successMessage = ref("");
const showDeleteDialog = ref(false);
const guestToDelete = ref(null);
const searchQuery = ref("");

let searchTimeout = null;

const formData = ref({
  name: "",
  phone: "",
  maxPax: 1,
});

const loading = computed(() => store.getters["guests/isLoading"]);
const error = computed(() => store.getters["guests/error"]);
const guests = computed(() => store.getters["guests/guests"]);
const guestCount = computed(() => store.getters["guests/guestCount"]);

function openAddForm() {
  isEditing.value = false;
  editingGuestId.value = null;
  formData.value = {
    name: "",
    phone: "",
    maxPax: 1,
  };
  showForm.value = true;
  clearMessages();
}

function openEditForm(guest) {
  isEditing.value = true;
  editingGuestId.value = guest.id;
  formData.value = {
    name: guest.name || "",
    phone: guest.phone || "",
    maxPax: guest.maxPax ?? 1,
  };
  showForm.value = true;
  clearMessages();
}

function closeForm() {
  showForm.value = false;
  isEditing.value = false;
  editingGuestId.value = null;
  clearMessages();
}

function clearMessages() {
  validationError.value = "";
  successMessage.value = "";
}

function showSuccess(message) {
  successMessage.value = message;
  setTimeout(() => {
    successMessage.value = "";
  }, 3000);
}

async function handleSave(data) {
  clearMessages();
  saving.value = true;

  try {
    if (isEditing.value && editingGuestId.value) {
      await store.dispatch("guests/updateGuest", {
        id: editingGuestId.value,
        data,
      });
      showSuccess("Data tamu berhasil diperbarui.");
    } else {
      await store.dispatch("guests/createGuest", data);
      showSuccess("Tamu berhasil ditambahkan.");
    }
    closeForm();
  } catch (err) {
    validationError.value = err.message || "Gagal menyimpan data tamu.";
  } finally {
    saving.value = false;
  }
}

function confirmDelete(guest) {
  guestToDelete.value = guest;
  showDeleteDialog.value = true;
}

async function handleDelete() {
  if (!guestToDelete.value) return;

  showDeleteDialog.value = false;
  clearMessages();

  try {
    await store.dispatch("guests/deleteGuest", guestToDelete.value.id);
    showSuccess("Tamu berhasil dihapus.");
  } catch (err) {
    validationError.value = err.message || "Gagal menghapus tamu.";
  } finally {
    guestToDelete.value = null;
  }
}

function handleSearch() {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    const query = searchQuery.value.trim();
    if (query) {
      store.dispatch("guests/searchGuests", query);
    } else {
      store.dispatch("guests/fetchGuests");
    }
  }, 300);
}

async function handleImport(file) {
  clearMessages();
  try {
    const imported = await store.dispatch("guests/importGuests", file);
    const count = Array.isArray(imported) ? imported.length : 0;
    showSuccess(`Berhasil mengimpor ${count} tamu dari Excel.`);
    // Refresh the full list
    await store.dispatch("guests/fetchGuests");
  } catch (err) {
    validationError.value = err.message || "Gagal mengimpor data dari Excel.";
  }
}

async function handleExport() {
  clearMessages();
  try {
    const data = await store.dispatch("guests/exportGuests");
    // Trigger download
    const blob = new Blob([data], {
      type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "daftar-tamu.xlsx";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    showSuccess("Data tamu berhasil diekspor.");
  } catch (err) {
    validationError.value = err.message || "Gagal mengekspor data tamu.";
  }
}

async function loadData() {
  await store.dispatch("guests/fetchGuests");
}

onMounted(() => {
  loadData();
});
</script>
