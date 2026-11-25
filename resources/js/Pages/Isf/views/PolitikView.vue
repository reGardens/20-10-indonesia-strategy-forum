<script setup>
/**
 * PolitikView.vue — Halaman Landing Rubrik Politik ISF (/politik/ dan sub-kategori).
 *
 * Mengimplementasikan:
 * - Header Hero bergaya editorial navy konsisten dengan rubrik ISF:
 *   Breadcrumb, kicker badge, serif heading, dan deskripsi rubrik.
 * - Filter Bar di LUAR background hitam / dipindahkan ke bawah header:
 *   CTA filter lengkap (Semua Artikel, Sorotan, Eksekutif, Legislatif, Dinamika Partai, Pemilu Pilkada, Kegiatan Istana).
 *   Filter tanggal (full date YYYY-MM-DD) dan kotak pencarian (search) berdasarkan kategori yang dipilih.
 * - Desain Dual Layout:
 *   1. Kategori 'Sorotan': Layout Multimedia & Video Investigatif (Featured 16:9 + Playlist Queue + grid).
 *   2. Kategori Lainnya (Semua Artikel, Eksekutif, Legislatif, Dinamika Partai, Pemilu Pilkada, Kegiatan Istana):
 *      Layout 4-kolom bergaris seperti section Politik di Beranda dengan penulis between sejajar tanggal,
 *      serta pagination "klik more" (Load More).
 * - Widget Khusus 1: Peta Kursi Parlemen DPR RI (2024-2029).
 * - Widget Khusus 2: Profil Tokoh Politik pilihan dengan tautan aktif ke rubrik Sosok.
 */
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    IconArrowLeft,
    IconClock,
    IconUser,
    IconArrowRight,
    IconChevronRight,
    IconBuildingBank,
    IconSearch,
    IconCalendar,
    IconX,
    IconChevronDown,
    IconPlayerPlay,
} from "@tabler/icons-vue";
import {
    ImagePlaceholder,
    IsfLink,
    PetaKursiParlemen,
    SectionHeading,
} from "@/Components/Isf";
import Pagination from "@/Components/UI/Pagination.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getPolitik } from "@/api/services/isfPublicService";

const route = useRoute();
const router = useRouter();

const sectionPad = "px-3 lg:px-4 xl:px-[30px]";
const linkClass =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

// Daftar CTA filter lengkap sesuai kebutuhan rubrik Politik ISF
const filterTabs = [
    { slug: null, name: "Semua Artikel" },
    { slug: "sorotan", name: "Sorotan" },
    { slug: "eksekutif", name: "Eksekutif" },
    { slug: "legislatif", name: "Legislatif" },
    { slug: "dinamika-partai", name: "Dinamika Partai" },
    { slug: "pemilu-pilkada", name: "Pemilu Pilkada" },
    { slug: "kegiatan-istana", name: "Kegiatan Istana" },
];

// Subkategori dari URL parameter (jika ada)
const currentSubkategori = computed(() => {
    return route.params.subkategori || null;
});

// Search & Date filter state
const searchQuery = ref(route.query.q || "");
const selectedDate = ref(route.query.date || "");
let searchDebounce = null;

const currentPage = ref(1);
const loading = ref(false);
const loadingMore = ref(false);
const error = ref(null);
const data = ref(null);
const accumulatedArticles = ref([]);

const rubrik = computed(
    () =>
        data.value?.rubrik || {
            title: "Politik",
            label: "Politik",
            description:
                "Dinamika politik nasional, parlemen, partai, pemilu, dan lembaga negara.",
            subkategori: [
                {
                    slug: "sorotan",
                    name: "Sorotan",
                    description:
                        "Isu dan laporan khusus sorotan politik nasional.",
                },
                {
                    slug: "eksekutif",
                    name: "Eksekutif",
                    description:
                        "Kebijakan kementerian, kabinet, dan lembaga eksekutif.",
                },
                {
                    slug: "legislatif",
                    name: "Legislatif",
                    description:
                        "Kinerja legislasi, rapat komisi, dan dinamika parlemen DPR RI.",
                },
                {
                    slug: "dinamika-partai",
                    name: "Dinamika Partai",
                    description:
                        "Perkembangan internal, suksesi, dan peta koalisi partai politik.",
                },
                {
                    slug: "pemilu-pilkada",
                    name: "Pemilu Pilkada",
                    description:
                        "Isu dinamika Pemilu, Pilpres, Pilkada, dan kepemiluan.",
                },
                {
                    slug: "kegiatan-istana",
                    name: "Kegiatan Istana",
                    description:
                        "Agenda, arahan strategis, dan kegiatan kepresidenan.",
                },
            ],
        },
);

function isTabActive(tabSlug) {
    if (!tabSlug && !currentSubkategori.value) return true;
    if (!tabSlug || !currentSubkategori.value) return false;
    const cur = currentSubkategori.value;
    if (tabSlug === cur) return true;
    if (tabSlug === "pemilu-pilkada" && cur === "pemilu") return true;
    if (tabSlug === "legislatif" && (cur === "parlemen" || cur === "dpr"))
        return true;
    if (
        tabSlug === "eksekutif" &&
        (cur === "pemerintahan" || cur === "presiden")
    )
        return true;
    if (tabSlug === "dinamika-partai" && cur === "partai") return true;
    return false;
}

const activeSubkategoriName = computed(() => {
    if (!currentSubkategori.value) return null;
    const cur = currentSubkategori.value;
    const found = filterTabs.find(
        (s) =>
            s.slug === cur ||
            (s.slug === "pemilu-pilkada" && cur === "pemilu") ||
            (s.slug === "legislatif" &&
                (cur === "parlemen" || cur === "dpr")) ||
            (s.slug === "eksekutif" &&
                (cur === "pemerintahan" || cur === "presiden")) ||
            (s.slug === "dinamika-partai" && cur === "partai"),
    );
    return found?.name || currentSubkategori.value;
});

const headline = computed(() => data.value?.headline || null);
const meta = computed(
    () =>
        data.value?.meta || {
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 12,
        },
);
const petaKursi = computed(() => data.value?.petaKursi || {});
const tokohPolitik = computed(() => data.value?.tokohPolitik || []);

// Gabungan artikel untuk tampilan 4 kolom (termasuk headline jika ada agar urutan tetap komprehensif)
const allListArticles = computed(() => {
    const isDuplicate = (item) => {
        return accumulatedArticles.value.some(
            (a) =>
                (a.id != null && item.id != null && a.id === item.id) ||
                (a.slug && item.slug && a.slug === item.slug),
        );
    };

    if (
        headline.value &&
        currentPage.value === 1 &&
        !isDuplicate(headline.value)
    ) {
        return [headline.value, ...accumulatedArticles.value];
    }
    return accumulatedArticles.value;
});

async function fetchData(page = 1) {
    loading.value = true;
    error.value = null;

    try {
        const res = await getPolitik(currentSubkategori.value, page, 12, {
            q: searchQuery.value || null,
            date: selectedDate.value || null,
        });
        data.value = res;
        accumulatedArticles.value = res.articles || [];
    } catch (err) {
        error.value = err;
    } finally {
        loading.value = false;
    }
}

function navigateSubkategori(slug) {
    currentPage.value = 1;
    accumulatedArticles.value = [];
    const query = { ...route.query };
    delete query.page;
    if (!slug) {
        router.push({ path: "/politik", query });
    } else {
        router.push({ path: `/politik/${slug}`, query });
    }
}

function onSearchInput(e) {
    searchQuery.value = e.target.value;
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        currentPage.value = 1;
        const query = { ...route.query };
        delete query.page;
        if (searchQuery.value) {
            query.q = searchQuery.value;
        } else {
            delete query.q;
        }
        router.push({ query });
        fetchData(1);
    }, 400);
}

function onDateChange() {
    currentPage.value = 1;
    const query = { ...route.query };
    delete query.page;
    if (selectedDate.value) {
        query.date = selectedDate.value;
    } else {
        delete query.date;
    }
    router.push({ query });
    fetchData(1);
}

function resetFilters() {
    searchQuery.value = "";
    selectedDate.value = "";
    currentPage.value = 1;
    const query = { ...route.query };
    delete query.q;
    delete query.date;
    delete query.page;
    router.push({ query });
    fetchData(1);
}

function onPageChange(page) {
    currentPage.value = page;
    const query = { ...route.query };
    if (page > 1) {
        query.page = String(page);
    } else {
        delete query.page;
    }
    router.push({ query });
    fetchData(page);
    if (typeof window !== "undefined") {
        const el = document.getElementById("daftar-artikel-politik-heading");
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "start" });
        } else {
            window.scrollTo({ top: 380, behavior: "smooth" });
        }
    }
}

// Reset dan reload saat subkategori atau route query berubah
watch(
    () => [
        route.params.subkategori,
        route.query.q,
        route.query.date,
        route.query.page,
    ],
    () => {
        searchQuery.value = route.query.q || "";
        selectedDate.value = route.query.date || "";
        currentPage.value = Math.max(1, parseInt(route.query.page) || 1);
        fetchData(currentPage.value);
    },
    { immediate: true },
);

// Update document title
watch(
    [() => rubrik.value?.title, activeSubkategoriName],
    ([title, subName]) => {
        if (typeof document !== "undefined") {
            const pageTitle = subName
                ? `${subName} · Politik · Indonesia Strategy Forum`
                : `${title || "Politik"} · Indonesia Strategy Forum`;
            document.title = pageTitle;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-20"
    >
        <!-- ═══ 1. HEADER HERO RUBRIK POLITIK (Gaya Editorial Navy) ═══ -->
        <header
            class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-8 sm:pb-10"
        >
            <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
                <!-- Breadcrumb Nav -->
                <nav
                    aria-label="Breadcrumb"
                    class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-4 select-none min-w-0"
                >
                    <router-link
                        to="/beranda"
                        class="whitespace-nowrap shrink-0 hover:text-white transition-colors"
                        >Beranda</router-link
                    >
                    <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
                    <router-link
                        to="/politik"
                        :class="[
                            'whitespace-nowrap shrink-0 transition-colors',
                            !currentSubkategori
                                ? 'text-white font-bold'
                                : 'hover:text-white',
                        ]"
                    >
                        Politik
                    </router-link>
                    <template v-if="activeSubkategoriName">
                        <IconChevronRight
                            class="w-3.5 h-3.5 opacity-60 shrink-0"
                        />
                        <span
                            class="text-white font-bold whitespace-nowrap shrink-0"
                            >{{ activeSubkategoriName }}</span
                        >
                    </template>
                </nav>

                <!-- Rubrik Title & Deskripsi + Filter Pills -->
                <div>
                    <div
                        class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1.5"
                    >
                        <span
                            class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"
                        ></span>
                        <span>RUBRIK DINAMIKA POLITIK & PARLEMEN</span>
                    </div>
                    <h1
                        class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-none text-white"
                    >
                        POLITIK
                    </h1>
                    <p
                        class="text-xs sm:text-sm md:text-base text-neutral-300 mt-2 max-w-3xl leading-relaxed"
                    >
                        {{ rubrik.description }}
                    </p>

                    <!-- Filter Pills Kategori Politik (Di dalam hero header navy di bawah deskripsi) -->
                    <div
                        class="mt-6 flex items-center gap-2 overflow-x-auto isf-no-scrollbar -mx-1 px-1 py-1"
                        role="tablist"
                        aria-label="Filter Subkategori Politik"
                    >
                        <button
                            v-for="tab in filterTabs"
                            :key="tab.name"
                            type="button"
                            @click="navigateSubkategori(tab.slug)"
                            :class="[
                                'px-4 py-2 rounded text-xs font-space-grotesk font-bold transition-all shrink-0 cursor-pointer',
                                isTabActive(tab.slug)
                                    ? 'bg-isf-red text-white'
                                    : 'bg-[#1a2333] text-neutral-300 hover:bg-[#253247] hover:text-white border border-neutral-700/60',
                            ]"
                        >
                            {{ tab.name }}
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══ 2. FILTER & SEARCH BAR (SEMUA KONTROL DI SEBELAH KANAN TANPA DIVIDER) ═══ -->
        <div class="bg-white py-3 sm:py-4">
            <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2.5 sm:gap-3"
                >
                    <!-- Date Filter and Reset Button -->
                    <div class="flex items-center justify-end gap-2 order-2 sm:order-1">
                        <div
                            class="flex items-center gap-1.5 text-xs text-neutral-600 bg-neutral-50 px-2.5 py-1.5 rounded-lg border border-neutral-200"
                        >
                            <IconCalendar
                                class="w-3.5 h-3.5 text-neutral-500 shrink-0"
                            />
                            <span
                                class="font-medium text-[11px] text-neutral-500 hidden sm:inline"
                                >Tanggal:</span
                            >
                            <input
                                v-model="selectedDate"
                                type="date"
                                aria-label="Filter tanggal publikasi"
                                @change="onDateChange"
                                class="bg-transparent text-xs font-space-grotesk text-neutral-800 outline-none cursor-pointer"
                            />
                        </div>

                        <!-- Reset Button -->
                        <button
                            v-if="searchQuery || selectedDate"
                            type="button"
                            @click="resetFilters"
                            class="text-xs text-isf-red hover:underline font-medium px-2 py-1 cursor-pointer"
                        >
                            Reset
                        </button>
                    </div>

                    <!-- Search Input (Full width di mobile, rapi di kanan di desktop) -->
                    <div class="relative w-full sm:w-64 md:w-72 order-1 sm:order-2">
                        <IconSearch
                            class="w-3.5 h-3.5 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                        />
                        <input
                            :value="searchQuery"
                            type="text"
                            placeholder="Cari artikel politik..."
                            aria-label="Cari artikel politik"
                            @input="onSearchInput"
                            class="w-full h-8 pl-8 pr-7 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 placeholder-neutral-400 outline-none focus:bg-white focus:border-isf-red focus:ring-1 focus:ring-isf-red transition-all"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="
                                searchQuery = '';
                                onSearchInput({ target: { value: '' } });
                            "
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 p-0.5 cursor-pointer"
                            aria-label="Hapus kata kunci"
                        >
                            <IconX class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ 3. SKELETON LOADER ═══ -->
        <main
            v-if="loading && accumulatedArticles.length === 0"
            class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-0 space-y-8 animate-pulse"
            aria-busy="true"
        >
            <!-- Kasus A Skeleton: Khusus Subkategori 'Sorotan' (Navy Investigatif Skeleton) -->
            <div
                v-if="currentSubkategori === 'sorotan'"
                class="bg-isf-navy rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-4 sm:p-6 space-y-6"
            >
                <div
                    class="border-b border-neutral-800 pb-4 flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
                        <div class="h-4 w-48 bg-neutral-700 rounded"></div>
                    </div>
                    <div class="h-3 w-32 bg-neutral-800 rounded"></div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 aspect-video bg-neutral-800 rounded-md"></div>
                    <div class="lg:col-span-4 space-y-3">
                        <div v-for="n in 3" :key="n" class="flex gap-3">
                            <div class="w-24 aspect-video bg-neutral-800 rounded shrink-0"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-full bg-neutral-700 rounded"></div>
                                <div class="h-3 w-1/2 bg-neutral-800 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kasus B Skeleton: Grid 4 Kolom (4x3 Cards) -->
            <div
                v-else
                class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
            >
                <div
                    class="border-b border-neutral-200 px-4 py-3 sm:px-6 sm:py-4"
                >
                    <div class="h-4 w-36 bg-neutral-200 rounded"></div>
                </div>
                <div class="p-4 sm:p-6 lg:p-8">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
                    >
                        <div
                            v-for="n in 4"
                            :key="n"
                            class="p-4 bg-white rounded-xl border border-neutral-200 space-y-3"
                        >
                            <div
                                class="aspect-[4/3] bg-neutral-200 rounded"
                            ></div>
                            <div class="h-3 w-16 bg-neutral-200 rounded"></div>
                            <div
                                class="h-5 w-full bg-neutral-300 rounded"
                            ></div>
                            <div class="h-3 w-4/5 bg-neutral-200 rounded"></div>
                            <div
                                class="pt-3 border-t border-neutral-100 flex items-center justify-between"
                            >
                                <div
                                    class="h-3 w-24 bg-neutral-200 rounded"
                                ></div>
                                <div
                                    class="h-3 w-16 bg-neutral-200 rounded"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skeleton Widget Navy Peta Kursi -->
            <div
                class="bg-isf-navy rounded-lg border border-neutral-800 shadow-sm overflow-hidden p-4 sm:p-6 space-y-6"
            >
                <div
                    class="border-b border-neutral-800 pb-4 flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-isf-red rounded-xs"></div>
                        <div class="h-4 w-44 bg-neutral-700 rounded"></div>
                    </div>
                    <div class="h-3 w-32 bg-neutral-800 rounded"></div>
                </div>
                <div class="h-36 w-full bg-neutral-800/60 rounded"></div>
            </div>
        </main>

        <!-- Error state -->
        <div
            v-else-if="error && accumulatedArticles.length === 0"
            class="mx-auto max-w-3xl py-16 px-4"
        >
            <ErrorMessage
                message="Gagal memuat konten rubrik Politik. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="fetchData(1)"
            />
        </div>

        <!-- ═══ 4. CONTENT SECTIONS ═══ -->
        <main
            v-else
            class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 mt-0 space-y-8 sm:space-y-12"
        >
            <!-- ═══ KASUS A: LAYOUT MULTIMEDIA & VIDEO INVESTIGATIF (UNTUK FILTER 'SOROTAN') ═══ -->
            <section
                v-if="currentSubkategori === 'sorotan'"
                aria-labelledby="sorotan-investigatif-heading"
                class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
            >
                <!-- Section Header -->
                <div
                    class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"
                        ></span>
                        <span
                            class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red"
                        >
                            Laporan Khusus & Investigasi
                        </span>
                        <span class="text-neutral-600">•</span>
                        <h2
                            id="sorotan-investigatif-heading"
                            class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider"
                        >
                            Multimedia & Video Investigatif Politik
                        </h2>
                    </div>
                    <span class="text-[11px] font-mono text-neutral-400">
                        Liputan Mendalam & Analisis Strategis
                    </span>
                </div>

                <!-- Layout Grid: 8 Cols Featured di Kiri + 4 Cols Playlist di Kanan -->
                <div
                    class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-neutral-800"
                >
                    <!-- Featured Story (Kiri) -->
                    <article
                        v-if="allListArticles.length > 0"
                        class="lg:col-span-7 xl:col-span-8 p-4 sm:p-6 lg:p-7"
                    >
                        <IsfLink
                            :to="allListArticles[0].to || ('/artikel/' + allListArticles[0].slug)"
                            :class="[linkClass, 'group block']"
                        >
                            <div
                                class="relative overflow-hidden aspect-video bg-neutral-900 rounded-md shadow-sm"
                            >
                                <img
                                    v-if="allListArticles[0].imageUrl"
                                    :src="allListArticles[0].imageUrl"
                                    :alt="allListArticles[0].title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center bg-neutral-800 text-neutral-400"
                                >
                                    <IconBuildingBank class="w-12 h-12" />
                                </div>
                                <!-- Play Icon Overlay -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex items-center justify-center"
                                >
                                    <div
                                        class="w-14 h-14 rounded-full bg-isf-red text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform"
                                    >
                                        <IconPlayerPlay
                                            class="w-6 h-6 fill-current translate-x-0.5"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="absolute top-3 left-3 bg-isf-red text-white text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded font-mono"
                                >
                                    SOROTAN UTAMA
                                </div>
                            </div>

                            <span
                                class="isf-kicker text-isf-red mt-3.5 block font-bold text-xs uppercase tracking-wider"
                            >
                                {{ allListArticles[0].category }} ·
                                {{
                                    allListArticles[0].subcategory ||
                                    "Investigasi"
                                }}
                            </span>
                            <h3
                                class="font-headline mt-1.5 text-xl sm:text-2xl lg:text-3xl font-bold leading-tight text-white group-hover:text-red-400 transition-colors"
                            >
                                {{ allListArticles[0].title }}
                            </h3>
                            <p
                                v-if="allListArticles[0].summary"
                                class="mt-2 font-serif text-xs sm:text-sm text-neutral-300 leading-relaxed line-clamp-3"
                            >
                                {{ allListArticles[0].summary }}
                            </p>
                            <div
                                class="mt-3.5 flex items-center justify-between text-xs text-neutral-400 font-mono"
                            >
                                <span class="font-medium text-neutral-300">{{
                                    allListArticles[0].author ||
                                    "Tim Riset ISF"
                                }}</span>
                                <time
                                    v-if="allListArticles[0].publishedAt"
                                    :datetime="allListArticles[0].publishedAt"
                                >
                                    {{
                                        formatDate(
                                            allListArticles[0].publishedAt,
                                        )
                                    }}
                                </time>
                            </div>
                        </IsfLink>
                    </article>

                    <!-- Playlist Queue List (Kanan) -->
                    <div
                        class="lg:col-span-5 xl:col-span-4 p-4 sm:p-6 flex flex-col divide-y divide-neutral-800"
                    >
                        <p
                            class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 font-mono pb-3"
                        >
                            Daftar Laporan Terkait
                        </p>
                        <article
                            v-for="item in allListArticles.slice(1, 5)"
                            :key="item.slug || item.id"
                            class="py-3.5 first:pt-2 last:pb-2"
                        >
                            <IsfLink
                                :to="item.to || ('/artikel/' + item.slug)"
                                :class="[
                                    linkClass,
                                    'group flex items-start gap-3',
                                ]"
                            >
                                <div
                                    class="relative w-28 shrink-0 aspect-video rounded overflow-hidden bg-neutral-900"
                                >
                                    <img
                                        v-if="item.imageUrl"
                                        :src="item.imageUrl"
                                        :alt="item.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute inset-0 bg-black/40 flex items-center justify-center"
                                    >
                                        <IconPlayerPlay
                                            class="w-4 h-4 text-white fill-current opacity-80"
                                        />
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="font-space-grotesk text-xs sm:text-sm font-bold leading-snug text-white group-hover:text-red-400 transition-colors line-clamp-2"
                                    >
                                        {{ item.title }}
                                    </h4>
                                    <time
                                        v-if="item.publishedAt"
                                        :datetime="item.publishedAt"
                                        class="text-[10px] text-neutral-400 font-mono mt-1 block"
                                    >
                                        {{ formatDate(item.publishedAt) }}
                                    </time>
                                </div>
                            </IsfLink>
                        </article>
                        <div
                            v-if="allListArticles.length <= 1"
                            class="py-8 text-center text-xs text-neutral-400 font-mono"
                        >
                            Belum ada arsip sorotan tambahan.
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ SECTION: DAFTAR ARTIKEL POLITIK (GRID 12 CARDS 4x3 + PAGINATION) ═══ -->
            <section
                aria-labelledby="daftar-artikel-politik-heading"
                class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
            >
                <div class="border-b border-neutral-200">
                    <SectionHeading
                        id="daftar-artikel-politik-heading"
                        :title="
                            activeSubkategoriName
                                ? `Daftar Artikel ${activeSubkategoriName}`
                                : 'Daftar Artikel Politik'
                        "
                        size="lg"
                        :class="sectionPad"
                    >
                        <template #eyebrow>
                            {{ meta.total }} Total Artikel{{
                                activeSubkategoriName
                                    ? ` (${activeSubkategoriName})`
                                    : ""
                            }}
                        </template>
                    </SectionHeading>
                </div>

                <!-- Empty state jika tidak ada artikel -->
                <div
                    v-if="allListArticles.length === 0"
                    class="py-16 text-center"
                    :class="sectionPad"
                >
                    <p class="text-neutral-500 text-sm font-space-grotesk">
                        Belum ada artikel yang cocok dengan filter atau
                        pencarian ini.
                    </p>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="mt-3 text-xs font-bold text-isf-red hover:underline cursor-pointer"
                    >
                        Bersihkan filter pencarian
                    </button>
                </div>

                <!-- 4 Kolom Artikel (Grid 12 Cards 4x3) -->
                <div v-else class="p-4 sm:p-6 lg:p-8">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
                    >
                        <article
                            v-for="(item, idx) in allListArticles"
                            :key="item.slug || item.id || idx"
                            class="group flex flex-col justify-between bg-white hover:bg-neutral-50/80 rounded-xl border border-neutral-200 overflow-hidden transition-all duration-300 hover:shadow-md hover:border-isf-red/40"
                        >
                            <IsfLink
                                :to="item.to"
                                class="block relative aspect-[4/3] overflow-hidden bg-neutral-200"
                            >
                                <ImagePlaceholder
                                    :label="item.imageAlt || item.title"
                                    :src="item.imageUrl || ''"
                                    aspect="card"
                                    bare
                                    :priority="idx === 0"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                            </IsfLink>
                            <div
                                class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3"
                            >
                                <div>
                                    <span
                                        class="isf-kicker text-isf-red block font-bold text-[11px] sm:text-xs"
                                    >
                                        {{ item.category
                                        }}{{
                                            item.subcategory
                                                ? ` · ${item.subcategory}`
                                                : ""
                                        }}
                                    </span>
                                    <h3
                                        class="isf-headline mt-1.5 text-[15px] sm:text-[16px] leading-[1.3] group-hover:text-isf-red transition-colors line-clamp-2"
                                    >
                                        <IsfLink :to="item.to">
                                            {{ item.title }}
                                        </IsfLink>
                                    </h3>
                                    <p
                                        v-if="item.summary"
                                        class="mt-1.5 font-serif text-[12px] sm:text-[13px] leading-[1.4] text-neutral-600 line-clamp-2"
                                    >
                                        {{ item.summary }}
                                    </p>
                                </div>
                                <div
                                    class="isf-byline pt-3 border-t border-neutral-100 flex items-center justify-between text-[11px] sm:text-xs text-neutral-500 mt-auto"
                                >
                                    <span
                                        class="truncate font-medium text-neutral-700 max-w-[55%]"
                                    >
                                        {{ item.author || "Tim Redaksi ISF" }}
                                    </span>
                                    <time
                                        v-if="item.publishedAt"
                                        :datetime="item.publishedAt"
                                        class="shrink-0 text-neutral-500"
                                    >
                                        {{ formatDate(item.publishedAt) }}
                                    </time>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="meta.lastPage > 1"
                    :class="[
                        'py-5 sm:py-6 flex justify-center border-t border-neutral-200',
                        sectionPad,
                    ]"
                >
                    <Pagination
                        :current-page="meta.currentPage"
                        :total-pages="meta.lastPage"
                        @update:current-page="onPageChange"
                    />
                </div>
            </section>

            <!-- ═══ SECTION: WIDGET PETA KURSI PARLEMEN (NAVY) ═════════════════ -->
            <section
                v-if="petaKursi?.items?.length"
                aria-labelledby="peta-kursi-parlemen-title"
                class="bg-isf-navy text-white rounded-lg border border-neutral-800 shadow-sm overflow-hidden"
            >
                <div
                    class="border-b border-neutral-800 px-4 py-3 sm:px-6 sm:py-4 flex flex-wrap items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"
                        ></span>
                        <span
                            class="text-xs font-mono font-bold tracking-widest uppercase text-isf-red"
                        >
                            Data Dinamika Legislasi
                        </span>
                        <span class="text-neutral-600">•</span>
                        <h2
                            id="peta-kursi-parlemen-title"
                            class="font-space-grotesk text-sm sm:text-base font-bold text-white uppercase tracking-wider"
                        >
                            Peta Kursi Parlemen
                        </h2>
                    </div>
                    <span class="text-[11px] font-mono text-neutral-400">
                        Sumber: KPU & Sekretariat Parlemen RI
                    </span>
                </div>

                <div :class="[sectionPad, 'py-3 sm:py-5']">
                    <PetaKursiParlemen :initial-data="petaKursi" />
                </div>
            </section>

            <!-- ═══ SECTION: WIDGET PROFIL TOKOH POLITIK ═══════════════ -->
            <section
                v-if="tokohPolitik.length > 0"
                aria-labelledby="tokoh-politik-title"
                class="bg-white rounded-lg border border-neutral-300 shadow-xs overflow-hidden"
            >
                <div class="border-b border-neutral-200">
                    <SectionHeading
                        id="tokoh-politik-title"
                        title="Profil Tokoh Politik"
                        size="lg"
                        :class="sectionPad"
                    >
                        <template #eyebrow>Aktor & Pembuat Kebijakan</template>
                    </SectionHeading>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-neutral-200"
                >
                    <router-link
                        v-for="tokoh in tokohPolitik"
                        :key="tokoh.id"
                        :to="tokoh.to || '/sosok/' + tokoh.slug"
                        class="group p-3 sm:p-5 flex flex-col justify-between hover:bg-neutral-50/80 transition-colors block cursor-pointer"
                    >
                        <div>
                            <div
                                class="flex items-start gap-3 sm:block mb-2 sm:mb-3"
                            >
                                <div
                                    class="w-16 h-16 sm:w-full sm:h-auto sm:aspect-square rounded-md overflow-hidden bg-neutral-200 shrink-0 border border-neutral-200 shadow-2xs group-hover:shadow-md transition-shadow"
                                >
                                    <img
                                        v-if="tokoh.photoUrl"
                                        :src="tokoh.photoUrl"
                                        :alt="tokoh.photoAlt || tokoh.name"
                                        class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex items-center justify-center bg-neutral-100 text-neutral-400"
                                    >
                                        <IconUser
                                            class="w-8 h-8 sm:w-12 sm:h-12"
                                        />
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1 sm:mt-2">
                                    <span
                                        class="text-[10px] font-space-grotesk font-bold uppercase tracking-wider text-isf-red block"
                                    >
                                        Tokoh Politik
                                    </span>
                                    <h3
                                        class="font-space-grotesk font-bold text-sm sm:text-base text-neutral-900 mt-0.5 line-clamp-1 group-hover:text-isf-red transition-colors"
                                    >
                                        {{ tokoh.name }}
                                    </h3>
                                    <p
                                        class="text-xs text-neutral-500 font-medium line-clamp-1 mt-0.5"
                                    >
                                        {{ tokoh.position }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="tokoh.teaser"
                                class="text-xs font-serif text-neutral-600 line-clamp-2 sm:line-clamp-3 leading-relaxed"
                            >
                                {{ tokoh.teaser }}
                            </p>

                            <div
                                v-if="tokoh.stats?.length"
                                class="mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-neutral-200 grid grid-cols-2 gap-2 text-left"
                            >
                                <div
                                    v-for="(st, idx) in tokoh.stats.slice(0, 2)"
                                    :key="idx"
                                >
                                    <div
                                        class="text-[10px] text-neutral-400 truncate"
                                    >
                                        {{ st.label }}
                                    </div>
                                    <div
                                        class="text-xs font-bold text-neutral-900 font-mono"
                                    >
                                        {{ st.value }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-neutral-200"
                        >
                            <div
                                class="inline-flex items-center gap-1.5 text-xs font-space-grotesk font-bold text-isf-red group-hover:text-isf-red-dark transition-colors"
                            >
                                <span>Lihat Profil Sosok</span>
                                <IconArrowRight
                                    class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"
                                />
                            </div>
                        </div>
                    </router-link>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.isf-no-scrollbar::-webkit-scrollbar {
    display: none;
}
.isf-no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
