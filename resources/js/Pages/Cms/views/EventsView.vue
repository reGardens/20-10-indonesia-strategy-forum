<template>
  <div class="events-view space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Acara</h1>
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
        Tambah Acara
      </button>
    </div>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat data acara..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadData"
    />

    <template v-else>
      <!-- Event Form (add/edit) -->
      <EventForm
        v-if="showForm"
        :model-value="formData"
        :is-editing="isEditing"
        :saving="saving"
        @submit="handleSave"
        @cancel="closeForm"
      />

      <!-- Event List -->
      <div v-if="events.length === 0 && !showForm" class="text-center py-12">
        <svg
          class="mx-auto h-12 w-12 text-gray-300"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
          />
        </svg>
        <p class="mt-2 text-sm text-gray-500">
          Belum ada acara. Klik "Tambah Acara" untuk memulai.
        </p>
      </div>

      <div v-else-if="!showForm" class="space-y-4">
        <div
          v-for="event in events"
          :key="event.id"
          class="rounded-lg border border-gray-200 bg-white p-5 hover:shadow-sm transition-shadow"
        >
          <div
            class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
          >
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ event.name }}
              </h3>
              <div class="mt-1 space-y-1">
                <p v-if="event.date" class="text-sm text-gray-600">
                  <span class="font-medium">Tanggal:</span>
                  {{ formatDate(event.date) }}
                </p>
                <p
                  v-if="event.startTime || event.endTime"
                  class="text-sm text-gray-600"
                >
                  <span class="font-medium">Waktu:</span>
                  {{ formatTimeRange(event.startTime, event.endTime) }}
                </p>
                <p v-if="event.venueName" class="text-sm text-gray-600">
                  <span class="font-medium">Tempat:</span> {{ event.venueName }}
                </p>
                <p v-if="event.address" class="text-sm text-gray-600">
                  <span class="font-medium">Alamat:</span> {{ event.address }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                type="button"
                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors"
                @click="openEditForm(event)"
              >
                Edit
              </button>
              <button
                type="button"
                class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                @click="confirmDelete(event)"
              >
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>

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
      title="Hapus Acara"
      :message="`Apakah Anda yakin ingin menghapus acara '${eventToDelete?.name || ''}'?`"
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
import EventForm from "@/Pages/Cms/components/events/EventForm.vue";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import ConfirmDialog from "@/Pages/Cms/components/shared/ConfirmDialog.vue";

const store = useStore();

const showForm = ref(false);
const isEditing = ref(false);
const editingEventId = ref(null);
const saving = ref(false);
const validationError = ref("");
const successMessage = ref("");
const showDeleteDialog = ref(false);
const eventToDelete = ref(null);

const formData = ref({
  name: "",
  date: "",
  startTime: "",
  endTime: "",
  venueName: "",
  address: "",
  latitude: "",
  longitude: "",
});

const loading = computed(() => store.getters["events/isLoading"]);
const error = computed(() => store.getters["events/error"]);
const events = computed(() => store.getters["events/events"]);

function formatDate(dateStr) {
  if (!dateStr) return "";
  try {
    const date = new Date(dateStr + "T00:00:00");
    return date.toLocaleDateString("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  } catch {
    return dateStr;
  }
}

function formatTimeRange(start, end) {
  if (start && end) return `${start} - ${end}`;
  if (start) return `${start}`;
  if (end) return `Sampai ${end}`;
  return "";
}

function openAddForm() {
  isEditing.value = false;
  editingEventId.value = null;
  formData.value = {
    name: "",
    date: "",
    startTime: "",
    endTime: "",
    venueName: "",
    address: "",
    latitude: "",
    longitude: "",
  };
  showForm.value = true;
  clearMessages();
}

function openEditForm(event) {
  isEditing.value = true;
  editingEventId.value = event.id;
  formData.value = {
    name: event.name || "",
    date: event.date || "",
    startTime: event.startTime || "",
    endTime: event.endTime || "",
    venueName: event.venueName || "",
    address: event.address || "",
    latitude: event.latitude ?? "",
    longitude: event.longitude ?? "",
  };
  showForm.value = true;
  clearMessages();
}

function closeForm() {
  showForm.value = false;
  isEditing.value = false;
  editingEventId.value = null;
  clearMessages();
}

function clearMessages() {
  validationError.value = "";
  successMessage.value = "";
}

async function handleSave(data) {
  clearMessages();
  saving.value = true;

  try {
    const isUpdate = isEditing.value && editingEventId.value;
    if (isUpdate) {
      await store.dispatch("events/updateEvent", {
        id: editingEventId.value,
        data,
      });
    } else {
      await store.dispatch("events/createEvent", data);
    }
    closeForm();
    successMessage.value = isUpdate
      ? "Acara berhasil diperbarui."
      : "Acara berhasil ditambahkan.";

    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (err) {
    validationError.value = err.message || "Gagal menyimpan acara.";
  } finally {
    saving.value = false;
  }
}

function confirmDelete(event) {
  eventToDelete.value = event;
  showDeleteDialog.value = true;
}

async function handleDelete() {
  if (!eventToDelete.value) return;

  showDeleteDialog.value = false;
  clearMessages();

  try {
    await store.dispatch("events/deleteEvent", eventToDelete.value.id);
    successMessage.value = "Acara berhasil dihapus.";
    setTimeout(() => {
      successMessage.value = "";
    }, 3000);
  } catch (err) {
    validationError.value = err.message || "Gagal menghapus acara.";
  } finally {
    eventToDelete.value = null;
  }
}

async function loadData() {
  await store.dispatch("events/fetchEvents");
}

onMounted(() => {
  loadData();
});
</script>
