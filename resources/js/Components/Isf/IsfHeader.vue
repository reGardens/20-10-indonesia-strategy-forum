<script setup>
/**
 * IsfHeader — Reusable header component for Indonesia Strategy Forum.
 *
 * Implements:
 * - Brand identity color scheme (Navy #0B1220, Crimson Red #E10600, Crisp White #FFFFFF)
 * - Montserrat geometric bold typography for high authority and readability
 * - Edge-to-edge full width layout without side gaps
 * - Centered text layout without icon/logo in header
 * - 3 Layout Options:
 *     a. Single Row (Row 1): ISF | Indonesia Strategy Forum — inline, two sizes
 *     b. Two Rows  (Row 2): ISF (Extra large in Red) / INDONESIA STRATEGY FORUM below
 *     c. Row 3           : ISF | Indonesia Strategy Forum — inline, same size
 * - Scroll behaviour: sticky header, nav collapses, title fades to compact
 *   - NO Vue <Transition>; both compact & normal title stay in DOM, CSS opacity only
 *   - Hysteresis: collapse at >80px, expand at <=30px  → prevents oscillation
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
import { useIsfHeaderLayout } from "@/Composables/useIsfHeaderLayout";
import { getAnggota } from "@/api/services/isfPublicService";
import Input from "@/Components/UI/Input.vue";
import Label from "@/Components/UI/Label.vue";

const props = defineProps({
    /** Default layout option: 'single-row' | 'two-rows' | 'row-3' */
    defaultLayout: {
        type: String,
        default: "two-rows",
        validator: (v) => ["single-row", "two-rows", "row-3"].includes(v),
    },
    /** Whether to show the editorial layout switcher pill */
    showLayoutSwitcher: {
        type: Boolean,
        default: false,
    },
});

const route = useRoute();

const isMemberRoute = computed(() => Boolean(route?.path?.startsWith("/anggota")));
const memberUser = ref({ name: "" });

async function loadMemberUser() {
    if (isMemberRoute.value && !memberUser.value?.name) {
        try {
            const res = await getAnggota();
            if (res?.user) {
                memberUser.value = res.user;
            }
        } catch {
            // fallback if guest
        }
    }
}

const memberInitials = computed(() => {
    const name = memberUser.value?.name || (isMemberRoute.value ? "Anindya Putri" : "");
    return (
        name
            .split(" ")
            .filter(Boolean)
            .slice(0, 2)
            .map((p) => p[0].toUpperCase())
            .join("") || "AP"
    );
});

const currentDateStr = computed(() => {
    try {
        const now = new Date();
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        })
            .format(now)
            .toUpperCase();
    } catch {
        return "SENIN, 9 NOVEMBER 2026";
    }
});

// ─── Scroll state ────────────────────────────────────────────────────────────
const isScrolled = ref(false);
let scrollTicking = false;

// Layout-aware hysteresis thresholds:
// For tall Row 2 (~250px): collapse at 110px, expand at 30px
// For single-row Row 1 & Row 3 (~130-145px): collapse at 65px, expand at 20px
const collapseThreshold = computed(() => {
    return layout.value === "two-rows" ? 110 : 65;
});
const expandThreshold = computed(() => {
    return layout.value === "two-rows" ? 30 : 20;
});

function handleScroll() {
    if (typeof window === "undefined" || scrollTicking) return;
    scrollTicking = true;
    window.requestAnimationFrame(() => {
        const y = window.scrollY;
        if (!isScrolled.value && y > collapseThreshold.value) {
            isScrolled.value = true;
            menuOpen.value = false;
            searchOpen.value = false;
        } else if (isScrolled.value && y <= expandThreshold.value) {
            isScrolled.value = false;
        }
        scrollTicking = false;
    });
}

// ─── Layout ──────────────────────────────────────────────────────────────────
const { layout, setLayout } = useIsfHeaderLayout();

// ─── Header ref & dynamic spacer height ──────────────────────────────────────
const headerRef = ref(null);

function getInitialSpacerHeight(l) {
    if (l === "two-rows") return 255;
    if (l === "row-3") return 130;
    return 145; // single-row
}

const spacerHeight = ref(getInitialSpacerHeight(layout.value));

function updateSpacerHeight() {
    // Only update when headerRef is present and NOT scrolled
    if (headerRef.value && !isScrolled.value) {
        const h = headerRef.value.offsetHeight;
        if (h > 60) {
            spacerHeight.value = h;
        }
    }
}

// ─── UI state ────────────────────────────────────────────────────────────────
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
    { label: "Portal Anggota", to: "/anggota", group: "fitur" },
];

const NAV_GROUPS = computed(() => [
    NAV_LINKS.filter((l) => l.group === "rubrik"),
    NAV_LINKS.filter((l) => l.group === "fitur"),
]);

const focusRing =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

// ─── Actions ─────────────────────────────────────────────────────────────────
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
    menuToggle.value?.focus();
}

function handleGlobalKeydown(e) {
    const tag = e.target?.tagName?.toLowerCase();
    const isTyping = tag === "input" || tag === "textarea" || e.target?.isContentEditable;
    if ((e.key === "k" && (e.ctrlKey || e.metaKey)) || (e.key === "/" && !isTyping)) {
        e.preventDefault();
        if (!searchOpen.value) toggleSearch();
        else searchInput.value?.$el?.focus();
    } else if (e.key === "Escape") {
        if (searchOpen.value) { searchOpen.value = false; searchPending.value = false; }
        if (menuOpen.value) closeMenu();
    }
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(() => {
    window.addEventListener("keydown", handleGlobalKeydown);
    window.addEventListener("scroll", handleScroll, { passive: true });
    window.addEventListener("resize", updateSpacerHeight, { passive: true });
    nextTick(updateSpacerHeight);
    if (typeof document !== "undefined" && document.fonts?.ready) {
        document.fonts.ready.then(() => {
            nextTick(updateSpacerHeight);
        });
    }
    // Seed initial state without waiting for a scroll event
    handleScroll();
    loadMemberUser();
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleGlobalKeydown);
    window.removeEventListener("scroll", handleScroll);
    window.removeEventListener("resize", updateSpacerHeight);
});

watch(
    () => layout.value,
    (newLayout) => {
        spacerHeight.value = getInitialSpacerHeight(newLayout);
        if (!isScrolled.value) {
            nextTick(updateSpacerHeight);
        }
    },
);

watch(() => route?.fullPath, () => {
    menuOpen.value = false;
    loadMemberUser();
});

defineExpose({ layout, setLayout, toggleSearch, toggleMenu, closeMenu });
</script>

<template>
    <!--
        Placeholder div: memberi ruang tepat di bawah fixed header.
        Tingginya TIDAK pernah berubah saat scroll — hanya berubah saat layout berganti atau resize.
        Ini memastikan running text / konten tidak tertutup header sama sekali.
    -->
    <div
        aria-hidden="true"
        class="isf-header-spacer w-full shrink-0"
        :style="{
            height: `${spacerHeight}px`,
            transition: 'height 0.3s ease',
            overflowAnchor: 'none',
        }"
    ></div>

    <!--
        FIXED header — position: fixed, tidak ikut flow dokumen.
        Kedua title selalu di DOM, hanya opacity CSS yang berubah.
        Tidak ada DOM mutation saat scroll → tidak ada height change di flow → tidak ada oscillation.
    -->
    <header
        ref="headerRef"
        class="isf-header fixed top-0 left-0 right-0 z-50 w-full bg-isf-navy text-white"
        :class="{ 'isf-header--scrolled': isScrolled }"
        style="overflow-anchor: none;"
    >
        <!-- ═══ MASTHEAD ════════════════════════════════════════════════════ -->
        <div
            class="isf-masthead w-full flex items-center justify-between px-4 sm:px-6 md:px-8 xl:px-12"
            :class="{ 'isf-masthead--row2': layout === 'two-rows', 'isf-masthead--row3': layout === 'row-3' }"
        >

            <!-- Sisi Kiri -->
            <div
                class="isf-side isf-side--left hidden lg:flex flex-col items-start justify-center min-w-[220px] xl:min-w-[260px] text-left font-space-grotesk select-none shrink-0"
            >
                <span class="text-[11px] font-bold tracking-[0.12em] text-neutral-300 uppercase whitespace-nowrap">
                    {{ currentDateStr }}
                </span>
                <span class="text-[10px] tracking-[0.08em] text-neutral-400 mt-0.5 whitespace-nowrap">
                    Jakarta, Indonesia • Edisi Digital
                </span>
            </div>

            <!-- Tengah: wordmark — BOTH versions always rendered, CSS controls opacity -->
            <router-link
                to="/beranda"
                :class="['isf-wordmark group mx-auto flex items-center justify-center min-w-0 text-center leading-none no-underline', focusRing]"
            >
                <!--
                    COMPACT title (shown when scrolled):
                    ISF | Indonesia Strategy Forum — single line, small
                    opacity-0 → 1 when isScrolled via CSS class on <header>
                -->
                <div v-if="isScrolled" class="isf-title isf-title--compact inline-flex items-center justify-center gap-x-2.5 py-0.5 select-none whitespace-nowrap">
                    <span class="text-[1.3rem] sm:text-[1.5rem] font-bold text-isf-red font-space-grotesk leading-none shrink-0">ISF</span>
                    <span aria-hidden="true" class="h-3.5 sm:h-4 w-px bg-white/25 shrink-0"></span>
                    {{ " " }}
                    <span class="text-[11px] sm:text-[12.5px] font-bold text-white font-space-grotesk tracking-[1.5px] uppercase shrink-0" style="letter-spacing:1.5px">Indonesia Strategy Forum</span>
                </div>

                <!--
                    NORMAL title (shown when not scrolled):
                    Changes content based on layout prop — but no DOM swap on scroll
                    opacity-1 → 0 when isScrolled via CSS class on <header>
                -->
                <div v-else class="isf-title isf-title--normal flex items-center justify-center select-none">
                    <!-- Row 1: ISF (large) | Indonesia Strategy Forum (smaller, multiline) -->
                    <div
                        v-if="layout === 'single-row'"
                        class="inline-flex items-center justify-center gap-x-3.5 sm:gap-x-4 py-1"
                    >
                        <span class="text-[2.4rem] sm:text-[3rem] font-bold text-isf-red font-space-grotesk leading-none whitespace-nowrap shrink-0">ISF</span>
                        <span aria-hidden="true" class="h-8 sm:h-9 w-px bg-white/20 shrink-0"></span>
                        {{ " " }}
                        <div class="flex flex-col items-start justify-center">
                            <span
                                class="text-left text-[17px] leading-[19px] tracking-[2px] font-bold text-white font-space-grotesk select-none shrink-0"
                                style="width:156px; letter-spacing:2px"
                            >Indonesia Strategy Forum</span>
                            <span
                                v-if="isMemberRoute"
                                class="text-[9px] font-bold uppercase tracking-[0.16em] text-neutral-400 font-space-grotesk mt-0.5 select-none"
                            >Portal Anggota</span>
                        </div>
                    </div>

                    <!-- Row 3: ISF | Indonesia Strategy Forum — inline, same size (pada mobile sama persis dengan scrolled compact title) -->
                    <div
                        v-else-if="layout === 'row-3'"
                        class="inline-flex items-center justify-center gap-x-2.5 sm:gap-x-3 md:gap-x-3.5 xl:gap-x-4 py-0.5 sm:py-1 max-w-full select-none whitespace-nowrap"
                    >
                        <span class="text-[1.3rem] sm:text-[1.65rem] md:text-[2.1rem] xl:text-[2.6rem] font-bold text-isf-red font-space-grotesk leading-none shrink-0">ISF</span>
                        <span aria-hidden="true" class="h-3.5 sm:h-7 md:h-8 xl:h-9 w-px bg-white/25 sm:bg-white/20 shrink-0"></span>
                        {{ " " }}
                        <span
                            class="text-[11px] sm:text-[1.65rem] md:text-[2.1rem] xl:text-[2.6rem] font-bold text-white font-space-grotesk leading-none uppercase sm:normal-case tracking-[1.5px] sm:tracking-[1px] md:tracking-[1.5px] xl:tracking-[2px] shrink-0"
                            style="letter-spacing:1.5px"
                        >Indonesia Strategy Forum</span>
                    </div>

                    <!-- Row 2 (default): ISF besar di atas, Indonesia Strategy Forum di bawah -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center text-center py-1 sm:py-2"
                    >
                        <span class="text-[4.5rem] sm:text-[6rem] md:text-[8rem] font-anton text-isf-red uppercase leading-none whitespace-nowrap">ISF</span>
                        {{ " " }}
                        <span
                            class="text-[14px] capitalize text-white font-space-grotesk font-medium text-center whitespace-nowrap -mt-1.5 tracking-[2px] leading-[27px]"
                            style="letter-spacing:2px; line-height:27px"
                        >Indonesia Strategy Forum</span>
                        <span
                            v-if="isMemberRoute"
                            class="text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-400 font-space-grotesk mt-0.5 select-none"
                        >Portal Anggota</span>
                    </div>
                </div>
            </router-link>

            <!-- Sisi Kanan -->
            <div
                class="isf-side isf-side--right hidden lg:flex flex-col items-end justify-center min-w-[220px] xl:min-w-[260px] text-right font-space-grotesk select-none shrink-0"
            >
                <div class="flex items-center gap-x-1.5 text-[11px] font-semibold text-neutral-300 tracking-[0.04em] whitespace-nowrap">
                    <span>IHSG 7.450 <span class="text-emerald-400 font-bold">(+0.3%)</span></span>
                    <span class="text-neutral-500">•</span>
                    <span>USD/IDR 15.620</span>
                </div>
                <div class="flex items-center gap-x-1.5 text-[10px] tracking-[0.06em] text-neutral-400 mt-0.5 whitespace-nowrap">
                    <span>Indeks Stabilitas Kebijakan:</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        Moderat
                    </span>
                </div>
            </div>
        </div>

        <!-- Red accent line -->
        <div class="h-[2px] w-full bg-isf-red shrink-0"></div>

        <!-- ═══ NAVIGATION ROW ════════════════════════════════════════════════ -->
        <div class="isf-nav-row border-b border-neutral-800 bg-isf-navy overflow-hidden shrink-0">
            <div class="w-full flex items-center justify-between gap-3 px-4 sm:px-6 md:px-8 xl:px-12 py-1.5">

                <!-- Hamburger (kiri) -->
                <div class="flex items-center justify-start shrink-0 lg:min-w-[220px]">
                    <button
                        ref="menuToggle"
                        type="button"
                        :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                        :aria-expanded="menuOpen ? 'true' : 'false'"
                        :aria-controls="menuId"
                        :class="['inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white transition-colors hover:border-white/30 hover:bg-white/10 hover:text-isf-red', focusRing]"
                        @click="toggleMenu"
                        @keydown.esc="closeMenu"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                            <path v-if="menuOpen" d="M6 6l12 12M18 6 6 18" />
                            <path v-else d="M3 6h18M3 12h18M3 18h18" />
                        </svg>
                    </button>
                </div>

                <!-- Nav links (tengah) -->
                <nav aria-label="Navigasi utama" class="isf-no-scrollbar min-w-0 flex-1 overflow-x-auto overflow-y-hidden px-2 lg:overflow-visible flex items-center justify-center">
                    <ul class="flex items-center gap-x-4 sm:gap-x-5 xl:gap-x-6 py-1 font-space-grotesk text-[12px] sm:text-[13px] font-semibold xl:text-[14px] whitespace-nowrap">
                        <template v-for="(group, groupIndex) in NAV_GROUPS" :key="groupIndex">
                            <li v-if="groupIndex > 0 && group.length > 0" aria-hidden="true" class="mx-1 h-3.5 w-px bg-neutral-700 shrink-0"></li>
                            <li v-for="link in group" :key="link.label" class="shrink-0">
                                <router-link
                                    v-if="link.to.startsWith('/')"
                                    :to="link.to"
                                    :class="['inline-block whitespace-nowrap py-1 border-b-2 border-transparent transition-colors text-neutral-300 hover:text-white', focusRing]"
                                    active-class="!border-isf-red !text-white font-bold"
                                >{{ link.label }}</router-link>
                                <a
                                    v-else
                                    href="#"
                                    aria-disabled="true"
                                    title="Halaman belum tersedia"
                                    :class="['inline-block whitespace-nowrap py-1 border-b-2 border-transparent transition-colors text-neutral-300 hover:text-white', focusRing]"
                                    @click.prevent
                                >{{ link.label }}</a>
                            </li>
                        </template>
                    </ul>
                </nav>

                <!-- Portal Anggota / Login / Search / Member Profile (kanan) -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0 justify-end lg:min-w-[220px]">
                    <template v-if="isMemberRoute || memberUser?.name">
                        <button
                            type="button"
                            aria-label="Notifikasi"
                            title="Notifikasi"
                            :class="['inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white transition-colors hover:border-white/30 hover:bg-white/10 hover:text-isf-red', focusRing]"
                        >
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                                <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                            </svg>
                        </button>
                        <router-link
                            to="/anggota"
                            :class="['flex items-center gap-2 select-none group', focusRing]"
                            title="Portal Anggota"
                        >
                            <span
                                v-if="memberInitials"
                                class="inline-flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-xs bg-isf-red text-[11px] sm:text-xs font-bold text-white tracking-wider shadow-xs"
                                aria-hidden="true"
                            >{{ memberInitials }}</span>
                            <span
                                v-if="memberUser?.name || isMemberRoute"
                                class="hidden max-w-[120px] truncate text-[12px] font-space-grotesk font-semibold text-neutral-200 group-hover:text-white transition-colors sm:inline"
                            >{{ memberUser?.name || "Anindya Putri" }}</span>
                        </router-link>
                    </template>
                    <template v-else>
                        <a
                            href="/login"
                            :class="['hidden whitespace-nowrap font-space-grotesk text-[11px] md:text-[12px] font-bold uppercase tracking-[0.1em] rounded-md border border-white/30 px-2.5 py-1 text-white transition-all hover:border-isf-red hover:bg-isf-red hover:text-white sm:inline shadow-2xs', focusRing]"
                        >Login</a>
                    </template>

                    <button
                        type="button"
                        aria-label="Buka pencarian"
                        title="Buka pencarian (Ctrl+K atau /)"
                        :aria-expanded="searchOpen ? 'true' : 'false'"
                        :aria-controls="searchId"
                        :class="['inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white transition-colors hover:border-white/30 hover:bg-white/10 hover:text-isf-red', focusRing]"
                        @click="toggleSearch"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true" focusable="false">
                            <circle cx="11" cy="11" r="6.5" />
                            <path d="m16 16 4.5 4.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ HAMBURGER DRAWER ══════════════════════════════════════════════ -->
        <div
            v-show="menuOpen"
            :id="menuId"
            class="w-full border-b border-neutral-800 bg-isf-navy text-white shadow-md"
            @keydown.esc="closeMenu"
        >
            <nav aria-label="Menu lengkap" class="w-full px-4 py-4 sm:px-6 md:px-8 xl:px-12">
                <ul class="grid grid-cols-2 gap-x-6 gap-y-2 font-space-grotesk text-[14px] font-semibold sm:grid-cols-4">
                    <li v-for="link in NAV_LINKS" :key="link.label">
                        <router-link
                            v-if="link.to.startsWith('/')"
                            :to="link.to"
                            :class="['inline-block py-1 hover:text-isf-red transition-colors', focusRing]"
                            active-class="!text-isf-red"
                        >{{ link.label }}</router-link>
                        <a
                            v-else href="#" aria-disabled="true" title="Halaman belum tersedia"
                            :class="['inline-block py-1 hover:text-isf-red transition-colors', focusRing]"
                            @click.prevent
                        >{{ link.label }}</a>
                    </li>
                </ul>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-neutral-800 pt-3">
                    <div class="flex items-center gap-4">
                        <template v-if="isMemberRoute || memberUser?.name">
                            <router-link to="/anggota" :class="['flex items-center gap-2 font-space-grotesk text-[12px] font-bold text-white hover:text-isf-red transition-colors', focusRing]">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-xs bg-isf-red text-[10px] font-bold text-white">{{ memberInitials }}</span>
                                <span>{{ memberUser?.name || "Portal Anggota" }}</span>
                            </router-link>
                        </template>
                        <template v-else>
                            <router-link to="/anggota" :class="['font-space-grotesk text-[11px] font-bold uppercase tracking-[0.12em] hover:text-isf-red transition-colors', focusRing]">Portal Anggota</router-link>
                            <span class="text-neutral-500">|</span>
                            <a href="/login" :class="['font-space-grotesk text-[11px] font-bold uppercase tracking-[0.12em] hover:text-isf-red transition-colors', focusRing]">Login</a>
                        </template>
                    </div>
                </div>
            </nav>
        </div>

        <!-- ═══ SEARCH PANEL ══════════════════════════════════════════════════ -->
        <div
            v-show="searchOpen"
            :id="searchId"
            class="w-full border-b border-neutral-800 bg-isf-navy text-white"
        >
            <form role="search" class="w-full flex flex-wrap items-end gap-3 px-4 py-3 sm:px-6 md:px-8 xl:px-12" @submit.prevent="submitSearch">
                <div class="min-w-0 flex-1">
                    <Label for="isf-search-input" class="sr-only">Cari di Indonesia Strategy Forum</Label>
                    <Input
                        id="isf-search-input"
                        ref="searchInput"
                        type="search"
                        placeholder="Cari artikel, isu, atau tokoh..."
                        class="font-space-grotesk focus-visible:ring-isf-red !bg-isf-navy border-neutral-700 text-white placeholder:text-neutral-400"
                        style="background-color: #0B1220;"
                    />
                </div>
                <button type="submit" :class="['rounded-md bg-isf-red px-4 py-2 font-space-grotesk text-[11px] font-bold uppercase tracking-[0.12em] text-white transition-colors hover:bg-isf-red-dark shadow-2xs', focusRing]">Cari</button>
                <p v-if="searchPending" class="w-full text-sm font-space-grotesk text-neutral-400" aria-live="polite">Pencarian akan segera tersedia.</p>
            </form>
        </div>
    </header>
</template>

<style scoped>
/* ── Masthead: padding collapses when scrolled ───────────────────────────── */
.isf-masthead {
    transition: padding 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding-top: 0.875rem;
    padding-bottom: 0.875rem;
}

.isf-masthead--row2 {
    padding-top: 1.5rem;
    padding-bottom: 0.75rem;
}

/* When scrolled: minimal padding so the whole masthead collapses to ~40px */
.isf-header--scrolled .isf-masthead,
.isf-header--scrolled .isf-masthead--row2 {
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
}

/* ── Side widgets: fade out and collapse when scrolled ──────────────────── */
.isf-side {
    max-height: 50px;
    min-height: 0;
    overflow: hidden;
    transition: opacity 0.2s ease, max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.isf-header--scrolled .isf-side {
    opacity: 0;
    max-height: 0;
    min-height: 0;
    pointer-events: none;
}

/* On mobile in Row 3: title is already compact identical to scrolled, no scale jump */
@media (max-width: 639px) {
    .isf-masthead--row3 .isf-title {
        transform: none !important;
    }
}

/* ── Wordmark: stack both titles, only one visible at a time ────────────── */
.isf-wordmark {
    position: relative;
    /* Use a grid so both children occupy same cell */
    display: grid;
    place-items: center;
    min-height: 0;
}

.isf-title {
    grid-area: 1 / 1; /* Both in same grid cell */
    min-height: 0;
    will-change: opacity, transform, max-height;
}

/* Normal title: visible by default */
.isf-title--normal {
    opacity: 1;
    max-height: 70px; /* Tailored for Row 1 & Row 3 so max-height collapse starts immediately */
    min-height: 0;
    overflow: hidden;
    transform: scale(1);
    transition: opacity 0.2s ease, max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s ease;
}

/* Row 2 needs larger max-height for the 8rem Anton font */
.isf-masthead--row2 .isf-title--normal {
    max-height: 180px;
}

/* Compact title: hidden by default */
.isf-title--compact {
    opacity: 0;
    transform: scale(0.96);
    pointer-events: none;
    transition: opacity 0.25s ease 0.05s, transform 0.25s ease 0.05s;
}

/* When scrolled: swap visibility & collapse normal title so header shrinks */
.isf-header--scrolled .isf-title--normal {
    opacity: 0;
    max-height: 0 !important;
    min-height: 0;
    overflow: hidden;
    transform: scale(0.96);
    pointer-events: none;
}
.isf-header--scrolled .isf-title--compact {
    opacity: 1;
    transform: scale(1);
    pointer-events: auto;
}

/* ── Nav row: collapse when scrolled ────────────────────────────────────── */
.isf-nav-row {
    max-height: 3.25rem; /* ~52px matches nav row exactly */
    opacity: 1;
    transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
}
.isf-header--scrolled .isf-nav-row {
    max-height: 0;
    opacity: 0;
    pointer-events: none;
    border-color: transparent;
}

/* ── Scrollbar hide helper ───────────────────────────────────────────────── */
.isf-no-scrollbar {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.isf-no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
