<template>
  <section class="event-section px-4 py-16 md:py-24" data-aos="fade-up">
    <div class="mx-auto max-w-3xl text-center">
      <h2
        class="mb-8 text-2xl font-bold md:text-3xl"
        :style="{ color: primaryColor }"
      >
        Jadwal Acara
      </h2>

      <div v-if="events && events.length" class="space-y-8">
        <div
          v-for="event in events"
          :key="event.id"
          class="rounded-xl bg-white/80 p-6 shadow-md backdrop-blur-sm"
          data-aos="zoom-in"
        >
          <h3 class="text-xl font-bold" :style="{ color: primaryColor }">
            {{ event.name || "Acara" }}
          </h3>

          <p class="mt-2 text-gray-600">
            {{ formatEventDate(event.date) }}
          </p>
          <p class="text-gray-600">
            {{ formatTimeRange(event.startTime, event.endTime) }}
          </p>

          <p v-if="event.venueName" class="mt-3 font-medium text-gray-700">
            {{ event.venueName }}
          </p>
          <p v-if="event.address" class="text-sm text-gray-500">
            {{ event.address }}
          </p>

          <!-- Google Maps Embed -->
          <div
            v-if="event.latitude && event.longitude"
            class="mt-4 overflow-hidden rounded-lg"
          >
            <iframe
              :src="getMapEmbedUrl(event.latitude, event.longitude)"
              width="100%"
              height="250"
              style="border: 0"
              allowfullscreen
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              :title="`Peta lokasi ${event.venueName || 'acara'}`"
            ></iframe>
          </div>

          <!-- Add to Calendar -->
          <div class="mt-4 flex flex-wrap justify-center gap-2">
            <button
              class="inline-flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors hover:opacity-90"
              :style="{ backgroundColor: primaryColor }"
              @click="handleDownloadIcs(event)"
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
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
              Simpan ke Kalender
            </button>
            <a
              :href="getGoogleCalendarUrl(event)"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 rounded-lg border px-4 py-2 text-sm font-medium transition-colors hover:bg-gray-50"
              :style="{ borderColor: primaryColor, color: primaryColor }"
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
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                />
              </svg>
              Google Calendar
            </a>
          </div>
        </div>
      </div>

      <p v-else class="py-12 text-gray-400">Belum ada informasi acara</p>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { formatDate, formatTime } from "@/Utils/formatters.js";
import { useCalendarExport } from "@/Composables/useCalendarExport.js";

const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");

const { downloadIcs, generateGoogleCalendarUrl } = useCalendarExport();

function formatEventDate(date) {
  return formatDate(date) || "Tanggal belum ditentukan";
}

function formatTimeRange(startTime, endTime) {
  const start = formatTime(startTime, "");
  const end = formatTime(endTime, "");
  if (!start) return "";
  if (end) return `${start} - ${end} WIB`;
  return `${start} WIB`;
}

function getMapEmbedUrl(lat, lng) {
  return `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;
}

function handleDownloadIcs(event) {
  downloadIcs(event);
}

function getGoogleCalendarUrl(event) {
  return generateGoogleCalendarUrl(event);
}
</script>
