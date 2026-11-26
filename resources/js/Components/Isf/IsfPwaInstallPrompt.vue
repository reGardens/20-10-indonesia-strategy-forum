<template>
  <transition
    enter-active-class="transition duration-300 ease-out transform"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in transform"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="shouldShow"
      class="fixed bottom-4 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 bg-[#0B1220] text-[#FBF9F4] border-t-2 border-[#E10600] rounded-lg shadow-2xl p-4 flex items-start gap-3.5 backdrop-blur-md bg-opacity-95"
      role="alert"
      aria-live="polite"
    >
      <div
        class="flex-shrink-0 w-11 h-11 rounded-md overflow-hidden bg-white/10 flex items-center justify-center p-1 border border-white/10"
      >
        <img
          src="/icons/icon-96x96.png"
          alt="ISF Icon"
          class="w-full h-full object-contain"
          width="44"
          height="44"
        />
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span
            class="inline-block w-1.5 h-1.5 rounded-full bg-[#E10600]"
          ></span>
          <h4
            class="text-xs font-semibold uppercase tracking-wider text-[#E10600]"
          >
            Aplikasi Web ISF
          </h4>
        </div>
        <p class="text-sm font-medium text-white mt-0.5 leading-snug">
          Pasang Indonesia Strategy Forum
        </p>
        <p class="text-xs text-white/70 mt-1 leading-relaxed">
          Dapatkan akses kilat ke analisis kebijakan, regulasi, dan
          arsip strategis langsung di beranda Anda.
        </p>

        <div class="flex items-center gap-2.5 mt-3">
          <button
            type="button"
            class="px-3 py-1.5 bg-[#E10600] hover:bg-[#c00500] active:scale-95 text-white text-xs font-semibold rounded transition-all shadow-sm"
            @click="handleInstall"
          >
            Pasang Sekarang
          </button>
          <button
            type="button"
            class="px-3 py-1.5 text-white/60 hover:text-white text-xs font-medium transition-colors"
            @click="handleDismiss"
          >
            Nanti Saja
          </button>
        </div>
      </div>

      <button
        type="button"
        class="text-white/40 hover:text-white p-1 -mr-1 -mt-1 transition-colors"
        aria-label="Tutup"
        @click="handleDismiss"
      >
        <svg
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useIsfPwa } from '@/Composables/useIsfPwa'
import { isNotificationPromptActive } from '@/Composables/useIsfPushNotification'

const { canInstall, installPwa, dismissInstallPrompt, initPwa } = useIsfPwa();
const isDismissed = ref(false);

const shouldShow = computed(() => {
  return canInstall.value && !isDismissed.value && !isNotificationPromptActive.value
})

onMounted(() => {
  initPwa();
});

const handleInstall = async () => {
  await installPwa();
};

const handleDismiss = () => {
  isDismissed.value = true;
  dismissInstallPrompt();
};
</script>
