<script setup>
/**
 * Public ISF shell with full-width edge-to-edge header (Montserrat typography,
 * identity colors, 2 layout options with interactive switcher) and responsive footer.
 */
import {
    onBeforeUnmount,
    onMounted,
    watch,
} from "vue";
import { useRoute } from "vue-router";
import IsfHeader from "@/Components/Isf/IsfHeader.vue";
import IsfFooter from "@/Components/Isf/IsfFooter.vue";

const route = useRoute();

let previousTitle = "";
onMounted(() => {
    previousTitle = document.title;
    if (route.meta.title) document.title = route.meta.title;
});
watch(
    () => route.meta.title,
    (title) => {
        if (title && typeof document !== "undefined") document.title = title;
    },
);
onBeforeUnmount(() => {
    if (previousTitle) document.title = previousTitle;
});
</script>

<template>
    <div class="isf-theme flex min-h-screen flex-col bg-white text-neutral-900" style="overflow-anchor: none;">
        <a
            href="#konten-utama"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-isf-red focus:px-4 focus:py-2 focus:text-white"
        >
            Lewati ke konten utama
        </a>

        <!-- Reusable ISF Header with Edge-to-Edge full width & 2 layout options -->
        <IsfHeader />

        <main id="konten-utama" tabindex="-1" class="flex-1 focus:outline-none">
            <router-view />
        </main>

        <IsfFooter />
    </div>
</template>
