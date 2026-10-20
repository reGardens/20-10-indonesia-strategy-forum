<template>
  <div class="dashboard-view space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>

    <!-- Loading state -->
    <LoadingIndicator v-if="loading" text="Memuat data dashboard..." />

    <!-- Error state -->
    <ErrorMessage
      v-else-if="error"
      :message="error"
      :retryable="true"
      @retry="loadDashboardData"
    />

    <template v-else>
      <!-- Stat cards row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Tamu"
          :value="totalGuests"
          icon="👥"
          color="bg-blue-100"
        />
        <StatCard
          title="Total RSVP"
          :value="totalRsvps"
          icon="📋"
          color="bg-green-100"
        />
        <StatCard
          title="Total Ucapan"
          :value="totalWishes"
          icon="💬"
          color="bg-purple-100"
        />
        <StatCard
          title="Total Hadiah"
          :value="totalGifts"
          icon="🎁"
          color="bg-yellow-100"
        />
      </div>

      <!-- Charts row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <RsvpSummary :summary="rsvpSummary" />
        <VisitorChart :visitors="visitors" />
      </div>

      <!-- Activity log -->
      <ActivityLog :activities="wishes" />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useStore } from "vuex";
import StatCard from "@/Pages/Cms/components/dashboard/StatCard.vue";
import RsvpSummary from "@/Pages/Cms/components/dashboard/RsvpSummary.vue";
import VisitorChart from "@/Pages/Cms/components/dashboard/VisitorChart.vue";
import ActivityLog from "@/Pages/Cms/components/dashboard/ActivityLog.vue";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";

const store = useStore();

// Computed state from store
const totalGuests = computed(() => store.getters["guests/guestCount"] || 0);
const totalRsvps = computed(() => store.getters["rsvp/totalRsvps"] || 0);
const totalWishes = computed(
  () => (store.getters["wishes/wishes"] || []).length,
);
const totalGifts = computed(() => store.getters["payments/totalGifts"] || 0);
const rsvpSummary = computed(
  () =>
    store.getters["rsvp/summary"] || { hadir: 0, tidakHadir: 0, mungkin: 0 },
);
const wishes = computed(() => store.getters["wishes/wishes"] || []);
const visitors = computed(() => []);

const loading = computed(() => {
  return (
    store.state.guests.loading ||
    store.state.rsvp.loading ||
    store.state.wishes.loading ||
    store.state.payments.loading
  );
});

const error = computed(() => {
  return (
    store.state.guests.error ||
    store.state.rsvp.error ||
    store.state.wishes.error ||
    store.state.payments.error
  );
});

async function loadDashboardData() {
  await Promise.all([
    store.dispatch("guests/fetchGuests"),
    store.dispatch("rsvp/fetchRsvps"),
    store.dispatch("rsvp/getSummary"),
    store.dispatch("wishes/fetchWishes"),
    store.dispatch("payments/fetchPayments"),
  ]);
}

onMounted(() => {
  loadDashboardData();
});
</script>
