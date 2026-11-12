<script setup>
/**
 * SearchView.vue
 *
 * Halaman Mesin Pencarian Terpadu (Global Search) Lintas Konten ISF:
 * - Mengagregasi: Artikel, Profil Tokoh, Infografik, Briefing, dan Video.
 * - Filter cepat per tipe konten dengan counter badges.
 * - Rekomendasi Kata Kunci Populer (AI Suggestions) 5 pilar strategis:
 *   (Politik, Hukum, Kebijakan & Ekonomi, Geopolitik, Tokoh).
 * - Tampilan kartu hasil dengan cuplikan (snippet), kicker, byline, dan gambar.
 */
import { ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { searchContent } from "@/api/services/isfPublicService";
import { formatDate } from "@/Utils/formatters";
import { ImagePlaceholder, IsfLink } from "@/Components/Isf";

const route = useRoute();
const router = useRouter();

const inputQuery = ref((route.query.q || "").toString());
const activeType = ref((route.query.type || "semua").toString().toLowerCase());
const loading = ref(false);
const searchPerformed = ref(false);

const results = ref([]);
const counts = ref({
    semua: 0,
    artikel: 0,
    tokoh: 0,
    infografik: 0,
    briefing: 0,
    video: 0,
});

const defaultSuggestions = {
    Politik: [
        "RUU Perampasan Aset",
        "Koalisi Partai",
        "Reshuffle Kabinet",
        "Pilkada Serentak",
        "Pemilu",
        "Ambang Batas Parlemen",
    ],
    Hukum: [
        "Mahkamah Konstitusi",
        "Mahkamah Agung",
        "Praperadilan",
        "UU ITE",
        "Penegakan Tipikor",
        "Reformasi Kepolisian",
    ],
    "Kebijakan & Ekonomi": [
        "Hilirisasi Nikel",
        "Transisi Energi Hijau",
        "Defisit APBN",
        "Pajak PPN 12%",
        "Subsidi Tepat Sasaran",
        "IKN Nusantara",
    ],
    Geopolitik: [
        "Diplomasi ASEAN",
        "Aksesi OECD",
        "Keanggotaan BRICS",
        "Laut Natuna Utara",
        "Ketahanan Pangan",
    ],
    Tokoh: [
        "Prabowo Subianto",
        "Gibran Rakabuming",
        "Sri Mulyani",
        "Erick Thohir",
        "Puan Maharani",
        "Mahfud MD",
    ],
};

const suggestions = ref(defaultSuggestions);
const popularKeywords = ref([
    "RUU Perampasan Aset",
    "Hilirisasi Nikel",
    "Mahkamah Konstitusi",
    "Transisi Energi Hijau",
    "Prabowo Subianto",
    "IKN Nusantara",
    "Koalisi Partai",
    "Pajak PPN 12%",
]);

const filterTabs = computed(() => [
    { key: "semua", label: "Semua Konten", count: counts.value.semua || 0 },
    { key: "artikel", label: "Artikel", count: counts.value.artikel || 0 },
    { key: "tokoh", label: "Profil Tokoh", count: counts.value.tokoh || 0 },
    {
        key: "infografik",
        label: "Infografik",
        count: counts.value.infografik || 0,
    },
    { key: "briefing", label: "Briefing", count: counts.value.briefing || 0 },
    { key: "video", label: "Video Analisis", count: counts.value.video || 0 },
]);

// Hanya respons dari permintaan terakhir yang dipakai (ganti kata kunci/filter cepat tidak menampilkan hasil lama).
let latestRequest = 0;
const loadError = ref(false);

async function fetchResults(q, type) {
    const requestId = ++latestRequest;
    loading.value = true;
    loadError.value = false;
    try {
        const res = await searchContent({
            q: q || "",
            type: type && type !== "semua" ? type : undefined,
        });
        if (requestId !== latestRequest) return;

        results.value = res?.data || [];
        if (res?.counts) {
            counts.value = res.counts;
        }
        if (res?.suggestions) {
            suggestions.value = res.suggestions;
        }
        if (res?.popularKeywords?.length) {
            popularKeywords.value = res.popularKeywords;
        }
        searchPerformed.value = true;
    } catch {
        if (requestId !== latestRequest) return;
        results.value = [];
        loadError.value = true;
    } finally {
        if (requestId === latestRequest) loading.value = false;
    }
}

function handleSearchSubmit() {
    const q = inputQuery.value.trim();
    router.push({
        path: "/pencarian",
        query: {
            ...(q ? { q } : {}),
            ...(activeType.value !== "semua" ? { type: activeType.value } : {}),
        },
    });
}

function setFilterType(type) {
    activeType.value = type;
    const q = inputQuery.value.trim();
    router.push({
        path: "/pencarian",
        query: {
            ...(q ? { q } : {}),
            ...(type !== "semua" ? { type } : {}),
        },
    });
}

function applyKeyword(keyword) {
    inputQuery.value = keyword;
    router.push({
        path: "/pencarian",
        query: {
            q: keyword,
            ...(activeType.value !== "semua" ? { type: activeType.value } : {}),
        },
    });
}

function clearQuery() {
    inputQuery.value = "";
    router.push({
        path: "/pencarian",
        query: activeType.value !== "semua" ? { type: activeType.value } : {},
    });
}

function getTypeBadgeClass(type) {
    switch (type) {
        case "artikel":
            return "bg-red-50 text-red-700 border-red-200";
        case "tokoh":
            return "bg-blue-50 text-blue-700 border-blue-200";
        case "infografik":
            return "bg-emerald-50 text-emerald-700 border-emerald-200";
        case "briefing":
            return "bg-amber-50 text-amber-800 border-amber-200";
        case "video":
            return "bg-slate-900 text-white border-slate-700";
        default:
            return "bg-neutral-100 text-neutral-800 border-neutral-200";
    }
}

watch(
    () => [route.query.q, route.query.type],
    ([newQ, newType]) => {
        const qStr = (newQ || "").toString();
        const typeStr = (newType || "semua").toString().toLowerCase();
        inputQuery.value = qStr;
        activeType.value = typeStr;
        fetchResults(qStr, typeStr);
    },
    // immediate: memuat hasil awal saat halaman dibuka (tidak perlu onMounted terpisah → tidak ada request ganda).
    { immediate: true },
);
</script>

<template>
    <div class="isf-search-page w-full bg-neutral-50/40 min-h-screen">
        <!-- Top Breadcrumb & Heading Bar -->
        <div class="border-b border-neutral-300 bg-white">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 py-6 sm:py-8">
                <!-- Breadcrumb -->
                <nav
                    aria-label="Breadcrumb"
                    class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-neutral-500 font-ui"
                >
                    <router-link
                        to="/beranda"
                        class="hover:text-isf-red transition-colors"
                        >Beranda</router-link
                    >
                    <span class="text-neutral-400 select-none">/</span>
                    <span class="text-neutral-900 font-bold"
                        >Pencarian Terpadu</span
                    >
                </nav>

                <div class="max-w-4xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-xs bg-isf-red/10 border border-isf-red/20 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-isf-red font-ui mb-2"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-isf-red animate-pulse"
                        ></span>
                        Mesin Indeks Pencarian ISF
                    </div>
                    <h1
                        class="font-headline text-2xl sm:text-3xl lg:text-4xl leading-tight text-neutral-950 font-bold"
                    >
                        Pencarian Lintas Konten Strategis
                    </h1>
                    <p
                        class="mt-2 font-serif text-sm sm:text-base text-neutral-600 leading-relaxed max-w-3xl"
                    >
                        Akses menyeluruh ke seluruh arsip artikel analisis,
                        profil tokoh, infografik data, briefing regulasi, dan
                        video investigatif Indonesia Strategy Forum.
                    </p>
                </div>

                <!-- Main Search Input Bar -->
                <div class="mt-6 max-w-4xl">
                    <form
                        role="search"
                        class="flex flex-col sm:flex-row gap-2.5"
                        @submit.prevent="handleSearchSubmit"
                    >
                        <div class="relative flex-1">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-neutral-400"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        cx="11"
                                        cy="11"
                                        r="7"
                                        stroke-width="2"
                                    />
                                    <path
                                        d="m16.5 16.5 4.5 4.5"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </div>
                            <input
                                id="search-page-input"
                                v-model="inputQuery"
                                type="search"
                                placeholder="Cari isu, undang-undang, nama tokoh, kebijakan, atau topik..."
                                class="w-full rounded-md border border-neutral-300 bg-white py-3 pl-11 pr-10 text-sm font-space-grotesk text-neutral-900 placeholder:text-neutral-400 shadow-xs focus:border-isf-red focus:outline-none focus:ring-2 focus:ring-isf-red/20 transition-all"
                            />
                            <button
                                v-if="inputQuery"
                                type="button"
                                @click="clearQuery"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-700"
                                aria-label="Hapus kata kunci"
                            >
                                <svg
                                    class="h-4 w-4"
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
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-isf-red px-6 py-3 font-space-grotesk text-xs sm:text-sm font-bold uppercase tracking-[0.1em] text-white shadow-xs transition-colors hover:bg-isf-red-dark focus:outline-none focus:ring-2 focus:ring-isf-red focus:ring-offset-2 shrink-0 cursor-pointer"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="7"
                                    stroke-width="2"
                                />
                                <path
                                    d="m16.5 16.5 4.5 4.5"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                />
                            </svg>
                            Cari Konten
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Tabs & Content Section -->
        <main class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 py-8 space-y-8">
            <!-- Filter Tabs (Kategori / Tipe Konten) -->
            <div class="border-b border-neutral-300 pb-3">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div
                        class="flex items-center gap-2 overflow-x-auto isf-no-scrollbar py-1"
                    >
                        <button
                            v-for="tab in filterTabs"
                            :key="tab.key"
                            type="button"
                            @click="setFilterType(tab.key)"
                            :class="[
                                'inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-bold font-ui uppercase tracking-wider transition-all cursor-pointer border',
                                activeType === tab.key
                                    ? 'bg-isf-navy text-white border-isf-navy shadow-xs'
                                    : 'bg-white text-neutral-600 border-neutral-300 hover:border-neutral-400 hover:bg-neutral-100',
                            ]"
                        >
                            <span>{{ tab.label }}</span>
                            <span
                                :class="[
                                    'inline-flex h-4 min-w-[16px] items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none',
                                    activeType === tab.key
                                        ? 'bg-isf-red text-white'
                                        : 'bg-neutral-200 text-neutral-700',
                                ]"
                            >
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>

                    <div
                        v-if="searchPerformed && inputQuery"
                        class="text-xs text-neutral-500 font-ui"
                    >
                        Menampilkan
                        <strong class="text-neutral-900 font-bold">{{
                            results.length
                        }}</strong>
                        hasil
                        <span v-if="inputQuery">
                            untuk "<strong class="text-neutral-900 font-bold">{{
                                inputQuery
                            }}</strong
                            >"</span
                        >
                    </div>
                </div>
            </div>

            <!-- Rekomendasi Kata Kunci Populer (AI Suggestions) -->
            <section
                aria-labelledby="ai-suggestions-heading"
                class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4"
                >
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-isf-red"></span>
                        <h2
                            id="ai-suggestions-heading"
                            class="font-ui text-xs font-bold uppercase tracking-[0.14em] text-neutral-900"
                        >
                            Rekomendasi Kata Kunci Populer (AI Suggestions)
                        </h2>
                    </div>
                    <span
                        class="text-[11px] text-neutral-500 font-ui hidden sm:inline"
                    >
                        Klik kata kunci untuk pencarian instan
                    </span>
                </div>

                <!-- Grid 5 Kolom Rekomendasi berdasarkan Pilar Strategis ISF -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4"
                >
                    <div
                        v-for="(tags, category) in suggestions"
                        :key="category"
                        class="rounded-md border border-neutral-200/80 bg-neutral-50/50 p-3 flex flex-col justify-between"
                    >
                        <div>
                            <div
                                class="font-ui text-[11px] font-bold uppercase tracking-wider text-isf-red pb-2 border-b border-neutral-200 mb-2.5"
                            >
                                {{ category }}
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="tag in tags"
                                    :key="tag"
                                    type="button"
                                    @click="applyKeyword(tag)"
                                    :class="[
                                        'rounded-md px-2 py-1 text-[11px] font-ui transition-all text-left cursor-pointer border',
                                        inputQuery.toLowerCase() ===
                                        tag.toLowerCase()
                                            ? 'bg-isf-red text-white border-isf-red font-bold shadow-xs'
                                            : 'bg-white text-neutral-700 border-neutral-200 hover:border-isf-red/50 hover:bg-neutral-100 hover:text-neutral-900',
                                    ]"
                                    :title="`Cari topik '${tag}'`"
                                >
                                    {{ tag }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Loading Skeleton -->
            <div v-if="loading" class="space-y-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="rounded-lg border border-neutral-200 bg-white p-5 shadow-xs animate-pulse flex flex-col sm:flex-row gap-5"
                >
                    <div
                        class="w-full sm:w-48 h-28 bg-neutral-200 rounded shrink-0"
                    ></div>
                    <div class="flex-1 space-y-3">
                        <div class="h-4 w-28 bg-neutral-200 rounded"></div>
                        <div class="h-6 w-3/4 bg-neutral-200 rounded"></div>
                        <div class="h-4 w-full bg-neutral-100 rounded"></div>
                        <div class="h-4 w-1/2 bg-neutral-100 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Error State: API gagal (bukan "tidak ditemukan") -->
            <div
                v-else-if="loadError"
                role="alert"
                class="rounded-lg border border-dashed border-neutral-300 bg-white p-10 text-center shadow-xs"
            >
                <h3
                    class="font-headline text-lg sm:text-xl font-bold text-neutral-900"
                >
                    Pencarian gagal dimuat
                </h3>
                <p
                    class="mt-1.5 font-serif text-sm text-neutral-600 max-w-md mx-auto"
                >
                    Terjadi kendala saat menghubungi server. Silakan coba lagi.
                </p>
                <button
                    type="button"
                    @click="fetchResults(inputQuery.trim(), activeType)"
                    class="mt-5 rounded-md bg-isf-red px-5 py-2 font-space-grotesk text-xs font-bold uppercase tracking-[0.1em] text-white hover:bg-isf-red-dark cursor-pointer"
                >
                    Coba lagi
                </button>
            </div>

            <!-- Empty Results State -->
            <div
                v-else-if="searchPerformed && results.length === 0"
                class="rounded-lg border border-dashed border-neutral-300 bg-white p-10 text-center shadow-xs"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 mb-4"
                >
                    <svg
                        class="h-7 w-7"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle cx="11" cy="11" r="7" stroke-width="2" />
                        <path
                            d="m16.5 16.5 4.5 4.5"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>
                <h3
                    class="font-headline text-lg sm:text-xl font-bold text-neutral-900"
                >
                    Tidak Ditemukan Konten
                </h3>
                <p
                    class="mt-1.5 font-serif text-sm text-neutral-600 max-w-md mx-auto"
                >
                    Tidak ada arsip ISF yang sesuai dengan kata kunci "<strong
                        class="font-bold text-neutral-900"
                        >{{ inputQuery }}</strong
                    >" pada kategori ini.
                </p>
                <div
                    class="mt-6 flex flex-wrap items-center justify-center gap-2"
                >
                    <span class="text-xs text-neutral-500 font-ui font-medium"
                        >Coba topik populer:</span
                    >
                    <button
                        v-for="kw in popularKeywords.slice(0, 5)"
                        :key="kw"
                        type="button"
                        @click="applyKeyword(kw)"
                        class="rounded-full bg-neutral-100 hover:bg-isf-red hover:text-white px-3 py-1 text-xs font-ui text-neutral-700 transition-colors border border-neutral-200 cursor-pointer"
                    >
                        {{ kw }}
                    </button>
                </div>
            </div>

            <!-- Results List -->
            <div v-else class="space-y-4">
                <article
                    v-for="item in results"
                    :key="item.id + item.type"
                    class="group rounded-lg border border-neutral-200 bg-white p-5 shadow-xs hover:border-neutral-400 hover:shadow-sm transition-all"
                >
                    <div class="flex flex-col sm:flex-row gap-5 items-start">
                        <!-- Thumbnail Image (if exists) -->
                        <div
                            v-if="item.imageUrl"
                            class="w-full sm:w-44 md:w-52 aspect-video sm:aspect-4/3 shrink-0 overflow-hidden rounded bg-neutral-100 border border-neutral-200"
                        >
                            <img
                                :src="item.imageUrl"
                                :alt="item.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                        </div>

                        <!-- Card Content -->
                        <div
                            class="min-w-0 flex-1 flex flex-col justify-between"
                        >
                            <div>
                                <!-- Top Badges -->
                                <div
                                    class="flex flex-wrap items-center gap-2 mb-2 font-ui"
                                >
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-xs px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border',
                                            getTypeBadgeClass(item.type),
                                        ]"
                                    >
                                        {{ item.typeLabel }}
                                    </span>
                                    <span
                                        v-if="item.kicker"
                                        class="text-[11px] font-bold uppercase tracking-wider text-isf-red"
                                    >
                                        {{ item.kicker }}
                                    </span>
                                    <time
                                        v-if="item.publishedAt"
                                        :datetime="item.publishedAt"
                                        class="text-xs text-neutral-400"
                                    >
                                        • {{ formatDate(item.publishedAt) }}
                                    </time>
                                </div>

                                <!-- Title Linked -->
                                <h3
                                    class="font-headline text-lg sm:text-xl font-bold leading-snug text-neutral-900 group-hover:text-isf-red transition-colors"
                                >
                                    <IsfLink :to="item.to || '/beranda'">
                                        {{ item.title }}
                                    </IsfLink>
                                </h3>

                                <!-- Snippet / Excerpt -->
                                <p
                                    class="mt-2 font-serif text-sm text-neutral-600 leading-relaxed line-clamp-3"
                                >
                                    {{ item.snippet || item.summary }}
                                </p>
                            </div>

                            <!-- Byline & Action Footer -->
                            <div
                                class="mt-4 pt-3 border-t border-neutral-100 flex flex-wrap items-center justify-between text-xs text-neutral-500 font-ui gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="item.author"
                                        class="font-semibold text-neutral-800"
                                    >
                                        {{ item.author }}
                                    </span>
                                    <span
                                        v-if="item.readingMinutes"
                                        class="text-neutral-400"
                                    >
                                        • {{ item.readingMinutes }} mnt baca
                                    </span>
                                    <span
                                        v-if="item.duration"
                                        class="inline-flex items-center gap-1 text-neutral-700 font-medium"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-isf-red"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                        {{ item.duration }}
                                    </span>
                                </div>

                                <IsfLink
                                    :to="item.to || '/beranda'"
                                    class="inline-flex items-center gap-1 font-bold text-isf-red hover:text-isf-red-dark transition-colors"
                                >
                                    <span>Buka Konten</span>
                                    <span class="text-sm">›</span>
                                </IsfLink>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </main>
    </div>
</template>
