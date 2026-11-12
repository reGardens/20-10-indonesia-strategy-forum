<script setup>
/** Editorial member portal shell for /anggota. */
import { computed, onBeforeUnmount, onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import IsfHeader from "@/Components/Isf/IsfHeader.vue";
import { IsfFooter } from "@/Components/Isf";

const route = useRoute();
const prioritasQuery = computed(() => route.query.prioritas);
const MENU = [
    {
        key: "dashboard",
        label: "Dashboard",
        to: { path: "/anggota" },
        isActive: () => prioritasQuery.value === undefined,
    },
    { key: "pemantauan", label: "Pemantauan", disabled: true },
    { key: "anggota", label: "Anggota", disabled: true },
    {
        key: "alerts",
        label: "Alerts",
        to: { path: "/anggota", query: { prioritas: "semua" } },
        isActive: () => prioritasQuery.value === "semua",
    },
    {
        key: "prioritas-tinggi",
        label: "Prioritas Tinggi",
        to: { path: "/anggota", query: { prioritas: "merah" } },
        isActive: () => prioritasQuery.value === "merah",
        highlight: true,
    },
];
function itemClasses(item) {
    const active = item.isActive && item.isActive();
    return active
        ? "border-b-2 border-isf-red bg-white text-isf-red lg:border-b-0 lg:border-l-2 font-bold"
        : "border-b-2 border-transparent bg-white text-neutral-800 hover:border-isf-red hover:text-isf-red lg:border-b-0 lg:border-l-2";
}
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
            >Lewati ke konten utama</a
        >
        <!-- Reusable ISF Header with Edge-to-Edge full width -->
        <IsfHeader />

        <div
            class="isf-mobile-gutter flex w-full flex-1 flex-col gap-6 px-4 py-6 sm:px-6 md:px-8 lg:flex-row xl:px-12"
        >
            <nav
                aria-label="Menu anggota"
                class="border-b border-neutral-300 lg:w-56 lg:shrink-0 lg:border-b-0 lg:border-r"
            >
                <ul
                    class="isf-no-scrollbar flex gap-2 overflow-x-auto overflow-y-hidden font-ui text-[14px] font-medium lg:flex-col lg:overflow-visible lg:pr-4"
                >
                    <li v-for="item in MENU" :key="item.key" class="shrink-0">
                        <span
                            v-if="item.disabled"
                            aria-disabled="true"
                            class="flex cursor-not-allowed items-center gap-1 whitespace-nowrap px-3 py-2 text-neutral-500"
                            >{{ item.label }}
                            <span class="text-xs">(segera)</span></span
                        >
                        <router-link
                            v-else
                            :to="item.to"
                            active-class=""
                            exact-active-class=""
                            :aria-current="item.isActive() ? 'page' : undefined"
                            :class="itemClasses(item)"
                            class="flex items-center whitespace-nowrap px-3 py-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                            >{{ item.label }}</router-link
                        >
                    </li>
                </ul>
            </nav>
            <main
                id="konten-utama"
                tabindex="-1"
                class="min-w-0 flex-1 focus:outline-none"
            >
                <router-view />
            </main>
        </div>
        <IsfFooter />
    </div>
</template>
