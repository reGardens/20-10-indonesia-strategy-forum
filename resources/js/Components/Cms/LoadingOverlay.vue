<script setup>
/**
 * LoadingOverlay — Full-screen loading indicator.
 * Ditampilkan saat form sedang processing.
 */
defineProps({
    show: { type: Boolean, default: false },
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
            >
                <div class="flex flex-col items-center gap-3">
                    <!-- Spinner -->
                    <div
                        class="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin"
                    />
                    <p class="text-white text-sm font-medium">Memproses...</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
