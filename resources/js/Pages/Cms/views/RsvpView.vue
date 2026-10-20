<template>
  <div class="rsvp-view space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">RSVP & Ucapan</h1>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat data RSVP dan ucapan..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadData"
    />

    <template v-else>
      <!-- RSVP Summary + Export Section -->
      <section class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Data RSVP</h2>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div
            class="rounded-lg border border-gray-200 bg-white p-4 text-center"
          >
            <p class="text-2xl font-bold text-gray-900">
              {{ rsvpSummary.total }}
            </p>
            <p class="text-xs text-gray-500">Total RSVP</p>
          </div>
          <div
            class="rounded-lg border border-green-200 bg-green-50 p-4 text-center"
          >
            <p class="text-2xl font-bold text-green-700">
              {{ rsvpSummary.hadir }}
            </p>
            <p class="text-xs text-green-600">Hadir</p>
          </div>
          <div
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-center"
          >
            <p class="text-2xl font-bold text-red-700">
              {{ rsvpSummary.tidakHadir }}
            </p>
            <p class="text-xs text-red-600">Tidak Hadir</p>
          </div>
          <div
            class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-center"
          >
            <p class="text-2xl font-bold text-yellow-700">
              {{ rsvpSummary.mungkin }}
            </p>
            <p class="text-xs text-yellow-600">Mungkin</p>
          </div>
        </div>

        <!-- Export buttons -->
        <RsvpExporter :rsvps="rsvps" :wishes="allWishes" />
      </section>

      <!-- Wishes Moderation Section -->
      <section class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Moderasi Ucapan</h2>

        <!-- Filter tabs -->
        <WishModerator v-model="activeFilter" :wishes="allWishes" />

        <!-- Wish list -->
        <WishList
          :wishes="filteredWishes"
          @approve="handleApprove"
          @hide="handleHide"
          @delete="confirmDelete"
        />
      </section>

      <!-- Success message -->
      <p v-if="successMessage" class="text-sm text-green-600" role="status">
        {{ successMessage }}
      </p>
    </template>

    <!-- Confirm delete dialog -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      title="Hapus Ucapan"
      message="Apakah Anda yakin ingin menghapus ucapan ini? Tindakan ini tidak dapat dibatalkan."
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
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import ConfirmDialog from "@/Pages/Cms/components/shared/ConfirmDialog.vue";
import WishList from "@/Pages/Cms/components/rsvp/WishList.vue";
import WishModerator from "@/Pages/Cms/components/rsvp/WishModerator.vue";
import RsvpExporter from "@/Pages/Cms/components/rsvp/RsvpExporter.vue";

const store = useStore();

const activeFilter = ref("all");
const showDeleteDialog = ref(false);
const wishToDelete = ref(null);
const successMessage = ref("");

const loading = computed(
  () => store.getters["wishes/isLoading"] || store.getters["rsvp/isLoading"],
);
const error = computed(
  () => store.getters["wishes/error"] || store.getters["rsvp/error"],
);
const allWishes = computed(() => store.getters["wishes/wishes"]);
const rsvps = computed(() => store.getters["rsvp/rsvps"]);

const rsvpSummary = computed(() => {
  const items = rsvps.value;
  const summary = { total: items.length, hadir: 0, tidakHadir: 0, mungkin: 0 };
  for (const rsvp of items) {
    const status = (rsvp.status || "").toLowerCase();
    if (status === "hadir") summary.hadir++;
    else if (status === "tidak hadir") summary.tidakHadir++;
    else if (status === "mungkin") summary.mungkin++;
  }
  return summary;
});

const filteredWishes = computed(() => {
  if (activeFilter.value === "all") return allWishes.value;
  return allWishes.value.filter((w) => w.status === activeFilter.value);
});

async function loadData() {
  await Promise.all([
    store.dispatch("wishes/fetchWishes"),
    store.dispatch("rsvp/fetchRsvps"),
  ]);
}

async function handleApprove(id) {
  try {
    await store.dispatch("wishes/updateWishStatus", { id, status: "approved" });
    showSuccess("Ucapan berhasil disetujui");
  } catch {
    // Error handled by store
  }
}

async function handleHide(id) {
  try {
    await store.dispatch("wishes/updateWishStatus", { id, status: "hidden" });
    showSuccess("Ucapan berhasil disembunyikan");
  } catch {
    // Error handled by store
  }
}

function confirmDelete(id) {
  wishToDelete.value = id;
  showDeleteDialog.value = true;
}

async function handleDelete() {
  if (!wishToDelete.value) return;
  try {
    await store.dispatch("wishes/deleteWish", wishToDelete.value);
    showSuccess("Ucapan berhasil dihapus");
  } catch {
    // Error handled by store
  } finally {
    showDeleteDialog.value = false;
    wishToDelete.value = null;
  }
}

function showSuccess(message) {
  successMessage.value = message;
  setTimeout(() => {
    successMessage.value = "";
  }, 3000);
}

onMounted(() => {
  loadData();
});
</script>
