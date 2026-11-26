<template>
    <div>
        <!-- Floating Editorial Permission Banner (Shown once for new readers) -->
        <transition
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div
                v-if="showBanner"
                class="fixed bottom-4 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 bg-[#0B1220] text-[#FBF9F4] border-t-2 border-[#E10600] rounded-lg shadow-2xl p-4 flex items-start gap-3.5 backdrop-blur-md bg-opacity-95"
                role="alert"
                aria-live="polite"
            >
                <div
                    class="flex-shrink-0 w-10 h-10 rounded-full bg-[#E10600]/15 text-[#E10600] flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white leading-snug">
                        Aktifkan Notifikasi Prioritas
                    </p>
                    <p class="text-xs text-white/70 mt-1 leading-relaxed">
                        Dapatkan peringatan dini isu regulasi kritis, breaking
                        news, dan pembaruan analisis tokoh langsung ke peramban
                        Anda.
                    </p>

                    <div class="flex items-center gap-2.5 mt-3">
                        <button
                            type="button"
                            :disabled="isLoading"
                            class="px-3 py-1.5 bg-[#E10600] hover:bg-[#c00500] active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded transition-all shadow-sm flex items-center gap-1.5"
                            @click="handleSubscribe"
                        >
                            <svg
                                v-if="isLoading"
                                class="animate-spin w-3.5 h-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span>{{
                                isLoading
                                    ? "Menghubungkan..."
                                    : "Aktifkan Notifikasi"
                            }}</span>
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useIsfPushNotification, isNotificationPromptActive } from "@/Composables/useIsfPushNotification";

const {
    isSupported,
    permissionState,
    isSubscribed,
    isLoading,
    initPush,
    subscribe,
    unsubscribe,
    dismissPrompt,
    isDismissedRecently,
} = useIsfPushNotification();

const dismissed = ref(false);

onMounted(async () => {
    await initPush();
});

const showBanner = computed(() => {
    if (!isSupported.value) return false;
    if (isSubscribed.value) return false;
    if (
        permissionState.value === "denied" ||
        permissionState.value === "granted"
    )
        return false;
    if (dismissed.value) return false;
    return !isDismissedRecently();
});

watch(showBanner, (val) => {
    isNotificationPromptActive.value = !!val;
}, { immediate: true });

const handleSubscribe = async () => {
    const result = await subscribe();
    if (
        result.success ||
        (typeof Notification !== "undefined" &&
            Notification.permission === "granted")
    ) {
        dismissed.value = true;
        dismissPrompt();
        isNotificationPromptActive.value = false;
    }
};

const handleDismiss = () => {
    dismissed.value = true;
    dismissPrompt();
    isNotificationPromptActive.value = false;
};
</script>
