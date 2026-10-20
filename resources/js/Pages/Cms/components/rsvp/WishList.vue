<template>
  <div class="space-y-3">
    <div
      v-if="wishes.length === 0"
      class="rounded-lg border border-gray-200 bg-white p-5"
    >
      <p class="text-sm text-gray-500">Belum ada ucapan.</p>
    </div>

    <div
      v-for="wish in wishes"
      :key="wish.id"
      class="rounded-lg border border-gray-200 bg-white p-4 space-y-2"
    >
      <div
        class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2"
      >
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="text-sm font-semibold text-gray-900">
              {{ wish.guestName }}
            </p>
            <span
              :class="statusBadgeClass(wish.status)"
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
            >
              {{ statusLabel(wish.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">
            {{ wish.message }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            {{ formatTimestamp(wish.createdAt) }}
          </p>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
          <button
            v-if="wish.status !== 'approved'"
            type="button"
            class="text-sm text-green-600 hover:text-green-700 font-medium"
            @click="$emit('approve', wish.id)"
          >
            Setujui
          </button>
          <button
            v-if="wish.status !== 'hidden'"
            type="button"
            class="text-sm text-gray-500 hover:text-gray-700 font-medium"
            @click="$emit('hide', wish.id)"
          >
            Sembunyikan
          </button>
          <button
            type="button"
            class="text-sm text-red-600 hover:text-red-700 font-medium"
            @click="$emit('delete', wish.id)"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  wishes: {
    type: Array,
    required: true,
  },
});

defineEmits(["approve", "hide", "delete"]);

function statusBadgeClass(status) {
  switch (status) {
    case "approved":
      return "bg-green-100 text-green-700";
    case "hidden":
      return "bg-gray-100 text-gray-600";
    case "pending":
      return "bg-yellow-100 text-yellow-700";
    default:
      return "bg-gray-100 text-gray-600";
  }
}

function statusLabel(status) {
  switch (status) {
    case "approved":
      return "Disetujui";
    case "hidden":
      return "Disembunyikan";
    case "pending":
      return "Menunggu";
    default:
      return status || "Tidak diketahui";
  }
}

function formatTimestamp(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
</script>
