<template>
  <section class="gallery-section px-4 py-16 md:py-24" data-aos="fade-up">
    <div class="mx-auto max-w-4xl text-center">
      <h2
        class="mb-8 text-2xl font-bold md:text-3xl"
        :style="{ color: primaryColor }"
      >
        Galeri
      </h2>

      <div v-if="mediaItems.length === 0" class="py-12 text-gray-400">
        Belum ada foto
      </div>

      <!-- Masonry Layout -->
      <div
        v-else-if="galleryLayout === 'masonry'"
        class="columns-2 gap-3 md:columns-3 md:gap-4"
      >
        <div
          v-for="item in mediaItems"
          :key="item.id"
          class="mb-3 break-inside-avoid overflow-hidden rounded-lg shadow-md md:mb-4"
          data-aos="zoom-in"
        >
          <template v-if="item.type === 'video'">
            <div v-if="isYouTubeUrl(item.url)" class="aspect-video">
              <iframe
                :src="getYouTubeEmbedUrl(item.url)"
                class="h-full w-full"
                frameborder="0"
                allow="
                  accelerometer;
                  autoplay;
                  clipboard-write;
                  encrypted-media;
                  gyroscope;
                  picture-in-picture;
                "
                allowfullscreen
                :title="item.originalName || 'Video'"
                loading="lazy"
              ></iframe>
            </div>
            <video
              v-else
              :src="item.url"
              controls
              class="w-full"
              preload="metadata"
            >
              Browser Anda tidak mendukung video.
            </video>
          </template>
          <img
            v-else
            :src="item.url || item.thumbnailUrl"
            :alt="item.originalName || 'Galeri'"
            class="w-full object-cover transition-transform duration-300 hover:scale-105"
            loading="lazy"
            @error="onImageError(item.id)"
          />
          <div
            v-if="!item.type || item.type !== 'video'"
            v-show="failedImages.has(item.id)"
            class="flex h-48 w-full items-center justify-center bg-gray-100 text-gray-400"
          >
            <svg
              class="h-12 w-12"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- Slider Layout -->
      <div v-else-if="galleryLayout === 'slider'" class="relative">
        <div class="overflow-hidden rounded-lg shadow-md">
          <template v-if="currentItem?.type === 'video'">
            <div v-if="isYouTubeUrl(currentItem.url)" class="aspect-video">
              <iframe
                :src="getYouTubeEmbedUrl(currentItem.url)"
                class="h-full w-full"
                frameborder="0"
                allow="
                  accelerometer;
                  autoplay;
                  clipboard-write;
                  encrypted-media;
                  gyroscope;
                  picture-in-picture;
                "
                allowfullscreen
                :title="currentItem.originalName || 'Video'"
                loading="lazy"
              ></iframe>
            </div>
            <video
              v-else
              :src="currentItem.url"
              controls
              class="w-full"
              preload="metadata"
            >
              Browser Anda tidak mendukung video.
            </video>
          </template>
          <img
            v-else-if="currentItem"
            :src="currentItem.url || currentItem.thumbnailUrl"
            :alt="currentItem.originalName || 'Galeri'"
            class="mx-auto max-h-[500px] w-full object-contain"
          />
        </div>

        <!-- Slider Controls -->
        <div
          v-if="mediaItems.length > 1"
          class="mt-4 flex items-center justify-center gap-4"
        >
          <button
            class="rounded-full p-2 text-gray-600 transition-colors hover:bg-gray-100"
            @click="prevSlide"
            aria-label="Foto sebelumnya"
          >
            <svg
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>
          <span class="text-sm text-gray-500">
            {{ currentIndex + 1 }} / {{ mediaItems.length }}
          </span>
          <button
            class="rounded-full p-2 text-gray-600 transition-colors hover:bg-gray-100"
            @click="nextSlide"
            aria-label="Foto berikutnya"
          >
            <svg
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Grid Layout (default) -->
      <div v-else class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
        <div
          v-for="item in mediaItems"
          :key="item.id"
          class="overflow-hidden rounded-lg shadow-md"
          data-aos="zoom-in"
        >
          <template v-if="item.type === 'video'">
            <div v-if="isYouTubeUrl(item.url)" class="aspect-video">
              <iframe
                :src="getYouTubeEmbedUrl(item.url)"
                class="h-full w-full"
                frameborder="0"
                allow="
                  accelerometer;
                  autoplay;
                  clipboard-write;
                  encrypted-media;
                  gyroscope;
                  picture-in-picture;
                "
                allowfullscreen
                :title="item.originalName || 'Video'"
                loading="lazy"
              ></iframe>
            </div>
            <video
              v-else
              :src="item.url"
              controls
              class="h-48 w-full object-cover"
              preload="metadata"
            >
              Browser Anda tidak mendukung video.
            </video>
          </template>
          <img
            v-else
            :src="item.url || item.thumbnailUrl"
            :alt="item.originalName || 'Galeri'"
            class="h-48 w-full object-cover transition-transform duration-300 hover:scale-110"
            loading="lazy"
            @error="onImageError(item.id)"
          />
          <div
            v-if="!item.type || item.type !== 'video'"
            v-show="failedImages.has(item.id)"
            class="flex h-48 w-full items-center justify-center bg-gray-100 text-gray-400"
          >
            <svg
              class="h-12 w-12"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  media: {
    type: Array,
    default: () => [],
  },
  galleryLayout: {
    type: String,
    default: "grid",
    validator: (v) => ["masonry", "slider", "grid"].includes(v),
  },
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");

const failedImages = ref(new Set());

function onImageError(itemId) {
  failedImages.value.add(itemId);
  failedImages.value = new Set(failedImages.value);
}

const mediaItems = computed(() =>
  (props.media || [])
    .filter((m) => m.type === "image" || m.type === "video")
    .sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0)),
);

// Slider state
const currentIndex = ref(0);

const currentItem = computed(
  () => mediaItems.value[currentIndex.value] || null,
);

function prevSlide() {
  if (mediaItems.value.length === 0) return;
  currentIndex.value =
    (currentIndex.value - 1 + mediaItems.value.length) %
    mediaItems.value.length;
}

function nextSlide() {
  if (mediaItems.value.length === 0) return;
  currentIndex.value = (currentIndex.value + 1) % mediaItems.value.length;
}

// YouTube helpers
function isYouTubeUrl(url) {
  if (!url) return false;
  return /(?:youtube\.com|youtu\.be)/.test(url);
}

function getYouTubeEmbedUrl(url) {
  if (!url) return "";
  const match = url.match(
    /(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/,
  );
  if (match && match[1]) {
    return `https://www.youtube.com/embed/${match[1]}`;
  }
  return url;
}
</script>
