<template>
  <section
    class="couple-section px-4 py-16 text-center md:py-24"
    data-aos="fade-up"
  >
    <div class="mx-auto max-w-3xl">
      <p class="mb-1 text-sm uppercase tracking-widest opacity-70">
        Bismillahirrahmanirrahim
      </p>
      <h2
        class="mb-8 text-2xl font-bold md:text-3xl"
        :style="{ color: primaryColor }"
      >
        Mempelai
      </h2>

      <div
        class="flex flex-col items-center gap-8 md:flex-row md:justify-center md:gap-16"
      >
        <!-- Groom -->
        <div class="flex-1" data-aos="fade-right">
          <div
            class="mx-auto mb-4 h-40 w-40 overflow-hidden rounded-full border-4"
            :style="{ borderColor: accentColor }"
          >
            <img
              v-if="couple?.groom?.photo && !groomImageError"
              :src="couple.groom.photo"
              :alt="groomName"
              class="h-full w-full object-cover"
              @error="groomImageError = true"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center bg-gray-100 text-4xl"
            >
              🤵
            </div>
          </div>
          <h3 class="text-xl font-bold" :style="{ color: primaryColor }">
            {{ groomName }}
          </h3>
          <p
            v-if="couple?.groom?.childOrder"
            class="mt-1 text-sm text-gray-600"
          >
            {{ couple.groom.childOrder }}
          </p>
          <p
            v-if="couple?.groom?.fatherName || couple?.groom?.motherName"
            class="mt-1 text-sm text-gray-500"
          >
            {{ couple?.groom?.fatherName }}
            <span v-if="couple?.groom?.fatherName && couple?.groom?.motherName">
              &amp;
            </span>
            {{ couple?.groom?.motherName }}
          </p>
          <a
            v-if="couple?.groom?.instagramUrl"
            :href="couple.groom.instagramUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-flex items-center gap-1 text-sm hover:underline"
            :style="{ color: accentColor }"
          >
            <svg
              class="h-4 w-4"
              fill="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"
              />
            </svg>
            Instagram
          </a>
        </div>

        <!-- Divider -->
        <div class="text-4xl font-bold" :style="{ color: accentColor }">
          &amp;
        </div>

        <!-- Bride -->
        <div class="flex-1" data-aos="fade-left">
          <div
            class="mx-auto mb-4 h-40 w-40 overflow-hidden rounded-full border-4"
            :style="{ borderColor: accentColor }"
          >
            <img
              v-if="couple?.bride?.photo && !brideImageError"
              :src="couple.bride.photo"
              :alt="brideName"
              class="h-full w-full object-cover"
              @error="brideImageError = true"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center bg-gray-100 text-4xl"
            >
              👰
            </div>
          </div>
          <h3 class="text-xl font-bold" :style="{ color: primaryColor }">
            {{ brideName }}
          </h3>
          <p
            v-if="couple?.bride?.childOrder"
            class="mt-1 text-sm text-gray-600"
          >
            {{ couple.bride.childOrder }}
          </p>
          <p
            v-if="couple?.bride?.fatherName || couple?.bride?.motherName"
            class="mt-1 text-sm text-gray-500"
          >
            {{ couple?.bride?.fatherName }}
            <span v-if="couple?.bride?.fatherName && couple?.bride?.motherName">
              &amp;
            </span>
            {{ couple?.bride?.motherName }}
          </p>
          <a
            v-if="couple?.bride?.instagramUrl"
            :href="couple.bride.instagramUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-flex items-center gap-1 text-sm hover:underline"
            :style="{ color: accentColor }"
          >
            <svg
              class="h-4 w-4"
              fill="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"
              />
            </svg>
            Instagram
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  couple: {
    type: Object,
    default: null,
  },
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const groomImageError = ref(false);
const brideImageError = ref(false);

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");
const accentColor = computed(() => props.themeColors?.accent || "#FFD700");

const groomName = computed(
  () =>
    props.couple?.groom?.fullName ||
    props.couple?.groom?.nickname ||
    "Mempelai Pria",
);

const brideName = computed(
  () =>
    props.couple?.bride?.fullName ||
    props.couple?.bride?.nickname ||
    "Mempelai Wanita",
);
</script>
