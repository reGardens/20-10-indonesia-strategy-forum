<script setup>
/**
 * Public ISF shell with a Politico-style masthead (hamburger menu, centered
 * wordmark, Portal Anggota + search at the right), a centered Title Case nav
 * and client-only search.
 */
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { useRoute } from "vue-router";
import Input from "@/Components/UI/Input.vue";
import Label from "@/Components/UI/Label.vue";
import "@/assets/isf-theme.css";

const route = useRoute();
const searchOpen = ref(false);
const searchInput = ref(null);
const searchPending = ref(false);
const searchId = "isf-public-search";
const menuOpen = ref(false);
const menuId = "isf-menu-panel";
const menuToggle = ref(null);

const NAV_LINKS = [
    { label: "Beranda", to: "/beranda", group: "rubrik" },
    { label: "Politik", to: "#", group: "rubrik" },
    { label: "Hukum", to: "#", group: "rubrik" },
    { label: "Kebijakan Publik", to: "#", group: "rubrik" },
    { label: "Sosok", to: "/tokoh/budi-santoso", group: "rubrik" },
    { label: "Opini Publik", to: "#", group: "rubrik" },
    { label: "Survei", to: "#", group: "rubrik" },
    { label: "Anatomi Isu", to: "/isu/kuhp-baru-pelaku-usaha", group: "fitur" },
];

// Content sections | features, separated by a thin vertical bar in the nav.
const NAV_GROUPS = computed(() => [
    NAV_LINKS.filter((link) => link.group === "rubrik"),
    NAV_LINKS.filter((link) => link.group === "fitur"),
]);

const focusRing =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

async function toggleSearch() {
    searchOpen.value = !searchOpen.value;
    searchPending.value = false;
    if (searchOpen.value) {
        menuOpen.value = false;
        await nextTick();
        searchInput.value?.$el?.focus();
    }
}

function submitSearch() {
    searchPending.value = true;
}

function toggleMenu() {
    menuOpen.value = !menuOpen.value;
    if (menuOpen.value) searchOpen.value = false;
}

function closeMenu() {
    menuOpen.value = false;
    // Return focus to the hamburger so Esc inside the hidden panel does not drop it to <body>.
    menuToggle.value?.focus();
}

watch(
    () => route.fullPath,
    () => {
        menuOpen.value = false;
    },
);

function handleGlobalKeydown(e) {
    const targetTag = e.target?.tagName?.toLowerCase();
    const isTyping = targetTag === "input" || targetTag === "textarea" || e.target?.isContentEditable;

    if ((e.key === "k" && (e.ctrlKey || e.metaKey)) || (e.key === "/" && !isTyping)) {
        e.preventDefault();
        if (!searchOpen.value) {
            toggleSearch();
        } else {
            searchInput.value?.$el?.focus();
        }
    } else if (e.key === "Escape") {
        if (searchOpen.value) {
            searchOpen.value = false;
            searchPending.value = false;
        }
        if (menuOpen.value) {
            closeMenu();
        }
    }
}

let previousTitle = "";
onMounted(() => {
    previousTitle = document.title;
    if (route.meta.title) document.title = route.meta.title;
    window.addEventListener("keydown", handleGlobalKeydown);
});
watch(
    () => route.meta.title,
    (title) => {
        if (title && typeof document !== "undefined") document.title = title;
    },
);
onBeforeUnmount(() => {
    if (previousTitle) document.title = previousTitle;
    window.removeEventListener("keydown", handleGlobalKeydown);
});
</script>

<template>
    <div class="isf-theme flex min-h-screen flex-col bg-white text-neutral-900">
        <a
            href="#konten-utama"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-isf-red focus:px-4 focus:py-2 focus:text-white"
        >
            Lewati ke konten utama
        </a>

        <header class="bg-white text-neutral-900">
            <!-- Masthead row -->
            <div class="border-b border-neutral-300">
                <div
                    class="mx-auto grid w-full max-w-[1400px] grid-cols-[minmax(2.5rem,1fr)_auto_minmax(2.5rem,1fr)] items-center gap-3 px-4 py-3 sm:py-4 lg:px-6 lg:py-6"
                >
                    <div class="justify-self-start">
                        <button
                            ref="menuToggle"
                            type="button"
                            :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                            :aria-expanded="menuOpen ? 'true' : 'false'"
                            :aria-controls="menuId"
                            :class="[
                                'inline-flex h-10 w-10 items-center justify-center text-neutral-900 hover:text-isf-red',
                                focusRing,
                            ]"
                            @click="toggleMenu"
                            @keydown.esc="closeMenu"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                aria-hidden="true"
                            >
                                <path
                                    v-if="menuOpen"
                                    d="M6 6l12 12M18 6 6 18"
                                />
                                <path v-else d="M3 6h18M3 12h18M3 18h18" />
                            </svg>
                        </button>
                    </div>

                    <router-link
                        to="/beranda"
                        :class="[
                            'isf-wordmark mx-auto flex items-center justify-center min-w-0 text-center leading-none',
                            focusRing,
                        ]"
                    >
                        <span class="inline-flex items-stretch justify-center gap-x-2.5 sm:gap-x-3.5">
                            <img
                                src="/images/isf/logo-isf.jpeg"
                                alt="ISF Logo"
                                class="isf-masthead-icon w-auto self-stretch rounded-[7px] object-cover shrink-0 border border-neutral-300/80 shadow-xs"
                                width="50"
                                height="50"
                            />
                            <span class="sr-only">ISF </span>
                            <span class="isf-script text-neutral-950 text-[30px] sm:text-[38px] lg:text-[46px] xl:text-[50px] leading-none">
                                Indonesia Strategy Forum
                            </span>
                        </span>
                    </router-link>

                    <div class="flex items-center gap-4 justify-self-end">
                        <router-link
                            to="/anggota"
                            :class="[
                                'hidden whitespace-nowrap font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-900 hover:text-isf-red lg:inline',
                                focusRing,
                            ]"
                        >
                            Portal Anggota
                        </router-link>
                        <button
                            type="button"
                            aria-label="Buka pencarian"
                            title="Buka pencarian (Ctrl+K atau /)"
                            :aria-expanded="searchOpen ? 'true' : 'false'"
                            :aria-controls="searchId"
                            :class="[
                                'inline-flex h-10 w-10 shrink-0 items-center justify-center text-neutral-900 hover:text-isf-red',
                                focusRing,
                            ]"
                            @click="toggleSearch"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="6.5" />
                                <path d="m16 16 4.5 4.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hamburger menu panel -->
            <div
                v-show="menuOpen"
                :id="menuId"
                class="border-b border-neutral-300 bg-white"
                @keydown.esc="closeMenu"
            >
                <nav aria-label="Menu lengkap">
                    <div class="mx-auto max-w-[1400px] px-4 py-4 lg:px-6">
                        <ul
                            class="grid grid-cols-2 gap-x-6 gap-y-2 font-ui text-[15px] text-neutral-900 sm:grid-cols-4"
                        >
                            <li v-for="link in NAV_LINKS" :key="link.label">
                                <router-link
                                    v-if="link.to.startsWith('/')"
                                    :to="link.to"
                                    :class="[
                                        'inline-block py-1 hover:text-isf-red',
                                        focusRing,
                                    ]"
                                    active-class="!text-isf-red"
                                >
                                    {{ link.label }}
                                </router-link>
                                <a
                                    v-else
                                    href="#"
                                    aria-disabled="true"
                                    title="Halaman belum tersedia"
                                    :class="[
                                        'inline-block py-1 hover:text-isf-red',
                                        focusRing,
                                    ]"
                                    @click.prevent
                                >
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                        <div class="mt-3 border-t border-neutral-300 pt-3">
                            <router-link
                                to="/anggota"
                                :class="[
                                    'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-900 hover:text-isf-red',
                                    focusRing,
                                ]"
                            >
                                Portal Anggota
                            </router-link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Nav row -->
            <div class="border-b border-neutral-300">
                <nav
                    aria-label="Navigasi utama"
                    class="isf-no-scrollbar mx-auto max-w-[1400px] overflow-x-auto overflow-y-hidden px-4 lg:overflow-visible lg:px-6"
                >
                    <ul
                        class="mx-auto flex w-max items-center gap-x-4 py-2.5 font-ui text-[13px] font-normal xl:gap-x-5 xl:text-[15px]"
                    >
                        <template
                            v-for="(group, groupIndex) in NAV_GROUPS"
                            :key="groupIndex"
                        >
                            <li
                                v-if="groupIndex > 0"
                                aria-hidden="true"
                                class="mx-1 h-4 w-px bg-neutral-400"
                            ></li>
                            <li v-for="link in group" :key="link.label">
                                <router-link
                                    v-if="link.to.startsWith('/')"
                                    :to="link.to"
                                    :class="[
                                        'inline-block whitespace-nowrap py-0.5 text-neutral-900 hover:text-isf-red',
                                        focusRing,
                                    ]"
                                    active-class="!text-isf-red underline decoration-isf-red decoration-1 underline-offset-[6px]"
                                >
                                    {{ link.label }}
                                </router-link>
                                <a
                                    v-else
                                    href="#"
                                    aria-disabled="true"
                                    title="Halaman belum tersedia"
                                    :class="[
                                        'inline-block whitespace-nowrap py-0.5 text-neutral-900 hover:text-isf-red',
                                        focusRing,
                                    ]"
                                    @click.prevent
                                >
                                    {{ link.label }}
                                </a>
                            </li>
                        </template>
                    </ul>
                </nav>
            </div>

            <!-- Search panel -->
            <div
                v-show="searchOpen"
                :id="searchId"
                class="border-b border-neutral-300"
            >
                <form
                    role="search"
                    class="isf-mobile-gutter mx-auto flex max-w-[1400px] flex-wrap items-end gap-3 px-4 py-3 lg:px-6"
                    @submit.prevent="submitSearch"
                >
                    <div class="min-w-0 flex-1">
                        <Label for="isf-search-input" class="sr-only"
                            >Cari di Indonesia Strategy Forum</Label
                        >
                        <Input
                            id="isf-search-input"
                            ref="searchInput"
                            type="search"
                            placeholder="Cari artikel, isu, atau tokoh"
                        />
                    </div>
                    <button
                        type="submit"
                        :class="[
                            'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                            focusRing,
                        ]"
                    >
                        Cari
                    </button>
                    <p
                        v-if="searchPending"
                        class="w-full text-sm text-neutral-600"
                        aria-live="polite"
                    >
                        Pencarian akan segera tersedia.
                    </p>
                </form>
            </div>
        </header>

        <main id="konten-utama" tabindex="-1" class="flex-1 focus:outline-none">
            <router-view />
        </main>

        <footer class="mt-8 border-t border-neutral-800 bg-neutral-950 text-neutral-400">
            <!-- Row 1: Main nav links -->
            <div class="isf-mobile-gutter mx-auto max-w-[1400px] px-4 py-4 lg:px-6">
                <div class="flex flex-wrap items-center justify-between gap-y-2">
                    <nav aria-label="Footer utama" class="flex flex-wrap items-center gap-x-0 text-[11px]">
                        <router-link to="/tentang-isf"
                            class="px-2 py-1 text-neutral-400 hover:text-white transition-colors first:pl-0">
                            Tentang ISF
                        </router-link>
                        <span class="text-neutral-700 select-none">|</span>
                        <router-link to="/dewan-pakar"
                            class="px-2 py-1 text-neutral-400 hover:text-white transition-colors">
                            Dewan Pakar
                        </router-link>
                        <span class="text-neutral-700 select-none">|</span>
                        <router-link to="/event"
                            class="px-2 py-1 text-neutral-400 hover:text-white transition-colors">
                            Event
                        </router-link>
                        <span class="text-neutral-700 select-none">|</span>
                        <router-link to="/kontak"
                            class="px-2 py-1 text-neutral-400 hover:text-white transition-colors">
                            Kontak
                        </router-link>
                    </nav>
                    <p class="text-[11px] text-neutral-500">
                        © {{ new Date().getFullYear() }} Indonesia Strategy Forum
                    </p>
                </div>
            </div>
            <!-- Row 2: Legal links -->
            <div class="border-t border-neutral-800">
                <div class="isf-mobile-gutter mx-auto max-w-[1400px] px-4 py-3 lg:px-6">
                    <nav aria-label="Footer legal" class="flex flex-wrap items-center gap-x-0 text-[11px]">
                        <router-link to="/syarat-ketentuan"
                            class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors first:pl-0">
                            Syarat &amp; Ketentuan
                        </router-link>
                        <span class="text-neutral-700 select-none">|</span>
                        <router-link to="/privacy-policy"
                            class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors">
                            Privacy Policy
                        </router-link>
                        <span class="text-neutral-700 select-none">|</span>
                        <router-link to="/cookie-policy"
                            class="px-2 py-0.5 text-neutral-500 hover:text-white transition-colors">
                            Cookie Policy
                        </router-link>
                    </nav>
                </div>
            </div>
        </footer>
    </div>
</template>
