<template>
  <div class="rounded-xl bg-white shadow-sm border border-gray-100 p-5">
    <h3 class="text-base font-semibold text-gray-900 mb-4">
      Aktivitas Terbaru
    </h3>

    <div v-if="sortedActivities.length === 0" class="text-center py-8">
      <p class="text-sm text-gray-400">Belum ada aktivitas</p>
    </div>

    <ul v-else class="space-y-3" role="list">
      <li
        v-for="activity in sortedActivities"
        :key="activity.id"
        class="flex items-start gap-3 pb-3 border-b border-gray-50 last:border-0 last:pb-0"
      >
        <span
          class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-sm"
          aria-hidden="true"
        >
          💬
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-gray-900">
            <span class="font-medium">{{
              activity.guestName || "Anonim"
            }}</span>
          </p>
          <p class="text-sm text-gray-500 truncate">{{ activity.message }}</p>
          <p class="text-xs text-gray-400 mt-0.5">
            {{ formatDate(activity.createdAt) }}
          </p>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  activities: {
    type: Array,
    default: () => [],
  },
});

const sortedActivities = computed(() => {
  return [...props.activities].sort((a, b) => {
    const dateA = new Date(a.createdAt || 0);
    const dateB = new Date(b.createdAt || 0);
    return dateB - dateA;
  });
});

function formatDate(dateStr) {
  if (!dateStr) return "";
  try {
    const date = new Date(dateStr);
    return date.toLocaleDateString("id-ID", {
      day: "numeric",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return dateStr;
  }
}
</script>
