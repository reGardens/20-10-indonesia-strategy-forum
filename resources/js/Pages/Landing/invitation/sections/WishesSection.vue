<template>
  <section class="wishes-section px-4 py-16 md:py-24" data-aos="fade-up">
    <div class="mx-auto max-w-lg text-center">
      <h2
        class="mb-4 text-2xl font-bold md:text-3xl"
        :style="{ color: primaryColor }"
      >
        Ucapan &amp; Doa
      </h2>
      <p class="mb-8 text-gray-600">Kirimkan ucapan dan doa terbaik Anda</p>

      <!-- Wish Submission Form -->
      <form class="mb-8 space-y-3 text-left" @submit.prevent="handleSubmitWish">
        <div>
          <input
            v-model="wishForm.name"
            type="text"
            placeholder="Nama Anda"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            :class="{ 'border-red-400': wishErrors.name }"
          />
          <p v-if="wishErrors.name" class="mt-1 text-xs text-red-500">
            {{ wishErrors.name }}
          </p>
        </div>
        <div>
          <textarea
            v-model="wishForm.message"
            placeholder="Tulis ucapan dan doa Anda..."
            rows="3"
            class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
          ></textarea>
        </div>
        <button
          type="submit"
          class="w-full rounded-lg px-6 py-2.5 text-sm font-medium text-white transition-colors hover:opacity-90 disabled:opacity-50"
          :style="{ backgroundColor: primaryColor }"
          :disabled="wishSubmitting"
        >
          {{ wishSubmitting ? "Mengirim..." : "Kirim Ucapan" }}
        </button>
        <p v-if="wishSent" class="text-center text-sm text-green-600">
          Ucapan Anda telah terkirim! 🎉
        </p>
      </form>

      <!-- Wishes List -->
      <div v-if="approvedWishes.length" class="space-y-3 max-h-[280px] overflow-y-auto pr-1">
        <div
          v-for="wish in approvedWishes"
          :key="wish.id"
          class="rounded-lg bg-white/80 p-4 text-left shadow-sm backdrop-blur-sm"
          data-aos="fade-up"
        >
          <div class="flex items-start justify-between">
            <p class="text-sm font-semibold text-gray-800">
              {{ wish.guestName || "Anonim" }}
            </p>
            <span class="text-xs text-gray-400">
              {{ formatWishDate(wish.createdAt) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            {{ wish.message }}
          </p>
        </div>
      </div>

      <p v-else class="text-sm text-gray-400">
        Belum ada ucapan. Jadilah yang pertama!
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import { wishService } from "@/api/services/wishService.js";
import { settingsService } from "@/api/services/settingsService.js";

const props = defineProps({
  wishes: {
    type: Array,
    default: () => [],
  },
  themeColors: {
    type: Object,
    default: () => ({}),
  },
});

const primaryColor = computed(() => props.themeColors?.primary || "#8B4513");

// Filter only approved wishes and sort chronologically (oldest first)
const approvedWishes = computed(() =>
  (props.wishes || [])
    .filter((w) => w.status === "approved")
    .sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt)),
);

// Wish form state
const wishForm = reactive({
  name: "",
  message: "",
});
const wishErrors = reactive({
  name: "",
});
const wishSubmitting = ref(false);
const wishSent = ref(false);

async function handleSubmitWish() {
  wishErrors.name = "";
  wishSent.value = false;

  if (!wishForm.name || !wishForm.name.trim()) {
    wishErrors.name = "Nama wajib diisi";
    return;
  }
  if (!wishForm.message || !wishForm.message.trim()) {
    return;
  }

  wishSubmitting.value = true;
  try {
    const settings = await settingsService.get();
    const wishStatus = settings.moderationEnabled ? "pending" : "approved";

    await wishService.create({
      guestName: wishForm.name.trim(),
      message: wishForm.message.trim(),
      status: wishStatus,
    });

    wishForm.message = "";
    wishSent.value = true;
  } catch {
    // Silent error handling for guest-facing app
  } finally {
    wishSubmitting.value = false;
  }
}

function formatWishDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return "";
  return d.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}
</script>
