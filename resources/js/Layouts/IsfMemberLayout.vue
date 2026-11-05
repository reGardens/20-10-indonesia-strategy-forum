<script setup>
/** Editorial member portal shell for /anggota. */
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { IconBell } from "@tabler/icons-vue";
import { getAnggota } from "@/api/services/isfPublicService";
import { IsfFooter } from "@/Components/Isf";

const route = useRoute();
const user = ref({ name: "" });
onMounted(async () => {
    try {
        user.value = (await getAnggota())?.user || { name: "" };
    } catch {
        user.value = { name: "" };
    }
});
const initials = computed(() =>
    (user.value.name || "")
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(""),
);
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
        ? "border-b-2 border-isf-red bg-white text-isf-red lg:border-b-0 lg:border-l-2"
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
    <div class="isf-theme flex min-h-screen flex-col bg-white text-neutral-900">
        <a
            href="#konten-utama"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-isf-red focus:px-4 focus:py-2 focus:text-white"
            >Lewati ke konten utama</a
        >
        <!-- NOTE: Keep header markup and classes in sync with resources/views/partials/isf-member-masthead.blade.php -->
        <header class="border-b border-neutral-300 bg-white">
            <div
                class="mx-auto flex sm:grid min-h-[4rem] sm:min-h-[4.5rem] lg:min-h-[5.5rem] w-full min-w-0 max-w-[1400px] items-center justify-between sm:grid-cols-[minmax(4.5rem,1fr)_auto_minmax(4.5rem,1fr)] gap-3 px-4 py-3 sm:py-4 lg:px-6"
            >
                <span aria-hidden="true" class="hidden sm:block"></span>
                <router-link
                    to="/beranda"
                    class="min-w-0 sm:mx-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                >
                    <!-- Mobile view (<640px): rata kiri, logo di kiri & teks tersusun di sampingnya -->
                    <div class="flex sm:hidden items-center justify-start gap-x-2 min-w-0">
                        <img
                            src="/images/isf/logo-isf.jpeg"
                            alt="ISF Logo"
                            class="isf-masthead-icon h-8 w-8 rounded-[6px] object-cover shrink-0 border border-neutral-300/80 shadow-xs"
                            width="32"
                            height="32"
                        />
                        <div class="flex flex-col items-start justify-center min-w-0">
                            <span class="sr-only">ISF </span>
                            <span
                                class="isf-script text-neutral-950 text-[20px] leading-tight select-none whitespace-nowrap"
                            >
                                Indonesia Strategy Forum
                            </span>
                            <span class="isf-byline mt-0.5 block text-left">Portal Anggota</span>
                        </div>
                    </div>

                    <!-- Desktop & Tablet view (>=640px): rata tengah (centered) -->
                    <div class="hidden sm:flex sm:flex-col sm:items-center sm:justify-center min-w-0 text-center leading-none">
                        <span
                            class="inline-flex items-center justify-center gap-x-2 sm:gap-x-3"
                        >
                            <img
                                src="/images/isf/logo-isf.jpeg"
                                alt="ISF Logo"
                                class="isf-masthead-icon sm:h-10 sm:w-10 md:h-11 md:w-11 lg:h-12 lg:w-12 xl:h-[50px] xl:w-[50px] rounded-[7px] object-cover shrink-0 border border-neutral-300/80 shadow-xs"
                                width="50"
                                height="50"
                            />
                            <span class="sr-only">ISF </span>
                            <span
                                class="isf-script text-neutral-950 sm:text-[32px] md:text-[36px] lg:text-[44px] xl:text-[50px] leading-tight sm:leading-none select-none whitespace-nowrap"
                            >
                                Indonesia Strategy Forum
                            </span>
                        </span>
                        <span class="isf-byline mt-1 block text-center">Portal Anggota</span>
                    </div>
                </router-link>
                <div class="flex items-center gap-2 sm:gap-3 shrink-0 sm:justify-self-end">
                    <button
                        type="button"
                        aria-label="Notifikasi"
                        class="inline-flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center text-neutral-700 hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                    >
                        <IconBell
                            class="h-4 w-4 sm:h-5 sm:w-5"
                            width="20"
                            height="20"
                            aria-hidden="true"
                            focusable="false"
                        />
                    </button>
                    <span
                        v-if="initials"
                        class="inline-flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center bg-isf-red text-xs sm:text-sm font-bold text-white tracking-wider"
                        aria-hidden="true"
                        :title="user.name"
                        >{{ initials }}</span
                    >
                    <span
                        class="hidden max-w-28 truncate text-sm font-medium sm:inline"
                        >{{ user.name }}</span
                    >
                </div>
            </div>
        </header>
        <div
            class="isf-mobile-gutter mx-auto flex w-full max-w-[1400px] flex-1 flex-col gap-6 px-4 py-6 lg:flex-row lg:px-6"
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
