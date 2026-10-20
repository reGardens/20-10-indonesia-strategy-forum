<template>
  <section
    class="cover-section relative flex min-h-screen flex-col items-center justify-center px-4 text-center"
    data-aos="fade-up"
  >
    <div class="relative z-10">
      <p class="mb-2 text-sm uppercase tracking-[0.3em] opacity-80">
        Undangan Pernikahan
      </p>

      <h1 class="mb-4 text-4xl font-bold leading-tight md:text-6xl">
        {{ groomDisplayName }}
        <span class="mx-2 block text-5xl md:inline md:text-7xl">&amp;</span>
        {{ brideDisplayName }}
      </h1>

      <template v-if="guestName">
        <p class="mt-6 text-sm opacity-80">Kepada Yth:</p>
        <p class="text-lg font-semibold">{{ guestName }}</p>
      </template>
      <template v-else>
        <p class="mt-6 text-sm opacity-80">
          Anda diundang untuk hadir di hari bahagia kami
        </p>
      </template>

      <!-- Buka Undangan Button -->
      <div class="mt-8 relative z-20">
        <button
          type="button"
          class="buka-undangan-btn inline-flex items-center gap-2 rounded-full bg-amber-600 px-8 py-3.5 text-sm font-bold tracking-wider text-white uppercase shadow-lg transition-all duration-300 hover:scale-105 hover:bg-amber-500 hover:shadow-amber-600/25 active:scale-95 cursor-pointer"
          @click="openInvitation"
        >
          <span>💌 Buka Undangan</span>
        </button>
      </div>

    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from "vue";

const props = defineProps({
  couple: {
    type: Object,
    default: null,
  },
  guestName: {
    type: String,
    default: "",
  },
});

onMounted(() => {
  // Lock scroll when cover page is mounted
  document.documentElement.classList.add("invitation-locked");
  document.body.classList.add("invitation-locked");
});

onUnmounted(() => {
  // Clean up lock state when component unmounts
  document.documentElement.classList.remove("invitation-locked");
  document.body.classList.remove("invitation-locked");
});

function openInvitation() {
  document.documentElement.classList.remove("invitation-locked");
  document.body.classList.remove("invitation-locked");

  // Scroll to the next section dynamically
  setTimeout(() => {
    const nextSection = document.querySelector(".cover-section")?.nextElementSibling;
    if (nextSection) {
      nextSection.scrollIntoView({ behavior: "smooth" });
    } else {
      window.scrollTo({
        top: window.innerHeight,
        behavior: "smooth",
      });
    }
  }, 100);
}

const groomDisplayName = computed(
  () =>
    props.couple?.groom?.nickname ||
    props.couple?.groom?.fullName ||
    "Mempelai Pria",
);

const brideDisplayName = computed(
  () =>
    props.couple?.bride?.nickname ||
    props.couple?.bride?.fullName ||
    "Mempelai Wanita",
);
</script>
