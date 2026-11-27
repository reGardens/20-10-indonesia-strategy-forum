<script setup>
/**
 * Portal Khusus Anggota B2B — Dasbor Umpan Peringatan (Cetak Biru ISF, bagian 08).
 * Content: GET /api/isf/anggota (published alerts, member profile setting, Tautan Langsung).
 * Kartu profil: nama = akun yang login (useIsfSession); tamu melihat ajakan Login, bukan nama contoh.
 * UI preview: client-side filtering, NO auth gating yet.
 * Priority filter is synced to the URL query (?prioritas=semua|merah|kuning).
 */
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    IconHistory,
    IconArticle,
    IconStar,
    IconChartBar,
    IconFlag,
    IconArrowRight,
} from "@tabler/icons-vue";
import http from "@/api/http";
import { getMemberActivities } from "@/api/services/isfMemberActivityService";
import Button from "@/Components/UI/Button.vue";
import Card from "@/Components/UI/Card.vue";
import CardContent from "@/Components/UI/CardContent.vue";
import CardHeader from "@/Components/UI/CardHeader.vue";
import CardTitle from "@/Components/UI/CardTitle.vue";
import Input from "@/Components/UI/Input.vue";
import Label from "@/Components/UI/Label.vue";
import Select from "@/Components/UI/Select.vue";
import SelectContent from "@/Components/UI/SelectContent.vue";
import SelectItem from "@/Components/UI/SelectItem.vue";
import SelectTrigger from "@/Components/UI/SelectTrigger.vue";
import SelectValue from "@/Components/UI/SelectValue.vue";
import Table from "@/Components/UI/Table.vue";
import TableHeader from "@/Components/UI/TableHeader.vue";
import TableBody from "@/Components/UI/TableBody.vue";
import TableRow from "@/Components/UI/TableRow.vue";
import TableHead from "@/Components/UI/TableHead.vue";
import TableCell from "@/Components/UI/TableCell.vue";
import { AnggotaSkeleton, IsfLink, PriorityBadge } from "@/Components/Isf";
import Pagination from "@/Components/UI/Pagination.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getAnggota } from "@/api/services/isfPublicService";
import { useIsfSession } from "@/Composables/useIsfSession";

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const loadError = ref(false);
const data = ref(null);

const alerts = computed(() => data.value?.alerts || []);
const tautanLangsung = computed(() => data.value?.tautanLangsung || []);

const PRIORITAS_OPTIONS = [
    { value: "semua", label: "Semua prioritas" },
    { value: "merah", label: "Prioritas Tinggi" },
    { value: "kuning", label: "Perlu Dipantau" },
];
const VALID = PRIORITAS_OPTIONS.map((option) => option.value);

const search = ref("");

// Source of truth is the URL query; invalid/missing values fall back to "semua".
const prioritas = computed({
    get() {
        const value = route.query.prioritas;
        return VALID.includes(value) ? value : "semua";
    },
    set(value) {
        router.replace({ path: "/anggota", query: { prioritas: value } });
    },
});

const filteredAlerts = computed(() => {
    const keyword = search.value.trim().toLowerCase();
    return alerts.value
        .filter(
            (alert) =>
                prioritas.value === "semua" ||
                alert.priority === prioritas.value,
        )
        .filter(
            (alert) =>
                !keyword ||
                `${alert.title} ${alert.summary}`
                    .toLowerCase()
                    .includes(keyword),
        )
        .sort((a, b) => b.date.localeCompare(a.date));
});

const user = computed(
    () =>
        data.value?.user || {
            name: "",
            role: "",
            organization: "",
            plan: "",
            sektor: [],
        },
);
// Identitas kartu profil = akun yang login (bukan nama contoh dari Pengaturan Halaman).
const { session, isLoggedIn, ensureLoaded: loadSession } = useIsfSession();
const loginHref = computed(() => `/login?redirect=${encodeURIComponent(route.fullPath || "/anggota")}`);
const initials = computed(() =>
    (session.value.name || "")
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(""),
);

const activeTab = computed(() =>
    route.query.prioritas === "semua" || route.query.prioritas === "merah"
        ? "alerts"
        : "aktifitas",
);
const userRoles = ref([]);
const isOnlyMember = computed(() => {
    const roles = userRoles.value || [];
    return roles.includes("member") && !roles.includes("super-admin") && !roles.includes("admin");
});

const activities = ref({
    letters: [],
    reviews: [],
    surveys: [],
    reports: [],
});
const activitiesLoading = ref(false);
const historyFilter = ref("semua"); // semua | letters | reviews | surveys | reports
const activityPage = ref(1);
const activityPerPage = 10;

watch(historyFilter, () => {
    activityPage.value = 1;
});

const unifiedActivities = computed(() => {
    const list = [];
    (activities.value.letters || []).forEach((l) => {
        list.push({
            uid: `letter-${l.id}`,
            kind: "letters",
            kindLabel: "Artikel / Naskah",
            actorLabel: "Anda",
            actionLabel: "membuat artikel terbaru",
            targetLabel: l.title,
            subtitle: `Kategori: ${l.category} · ${l.created_at || ""}`,
            statusLabel: l.moderation_status || "pending",
            statusTone:
                l.moderation_status === "disetujui"
                    ? "bg-emerald-100 text-emerald-800"
                    : l.moderation_status === "ditolak"
                      ? "bg-red-100 text-red-800"
                      : "bg-amber-100 text-amber-800",
            url: l.url,
            timestamp: l.timestamp || 0,
        });
    });
    (activities.value.reviews || []).forEach((r) => {
        list.push({
            uid: `review-${r.id}`,
            kind: "reviews",
            kindLabel: "Rating Sosok",
            actorLabel: "Anda",
            actionLabel: `memberi rating ★ ${r.rating}/5 untuk`,
            targetLabel: r.tokoh_name,
            subtitle: `${r.body ? '"' + r.body + '" · ' : ""}${r.created_at || ""}`,
            statusLabel: `★ ${r.rating}/5`,
            statusTone: "bg-amber-100 text-amber-800",
            url: r.tokoh_slug ? `/sosok/${r.tokoh_slug}` : "/sosok",
            timestamp: r.timestamp || 0,
        });
    });
    (activities.value.surveys || []).forEach((s) => {
        list.push({
            uid: `survey-${s.id}`,
            kind: "surveys",
            kindLabel: "Survei",
            actorLabel: "Anda",
            actionLabel: s.status === "completed" ? "telah mengisi survei" : "menyimpan draft survei",
            targetLabel: s.survey_title,
            subtitle: s.updated_at || "",
            statusLabel: s.status === "completed" ? "Selesai" : "Draft",
            statusTone:
                s.status === "completed"
                    ? "bg-emerald-100 text-emerald-800"
                    : "bg-amber-100 text-amber-800",
            url: s.survey_slug ? `/survei/${s.survey_slug}` : "/survei",
            timestamp: s.timestamp || 0,
        });
    });
    (activities.value.reports || []).forEach((rep) => {
        list.push({
            uid: `report-${rep.id}`,
            kind: "reports",
            kindLabel: "Report",
            actorLabel: "Anda",
            actionLabel: "mengirim report",
            targetLabel: rep.subject,
            subtitle: `${rep.category_label || rep.category} · ${rep.created_at || ""}`,
            statusLabel: rep.status,
            statusTone: "bg-neutral-100 text-neutral-800",
            url: null,
            timestamp: rep.timestamp || 0,
        });
    });
    return list.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));
});

const filteredActivitiesList = computed(() => {
    if (historyFilter.value === "semua") return unifiedActivities.value;
    return unifiedActivities.value.filter((item) => item.kind === historyFilter.value);
});

const totalActivityPages = computed(() =>
    Math.max(1, Math.ceil(filteredActivitiesList.value.length / activityPerPage)),
);

const paginatedActivities = computed(() => {
    const start = (activityPage.value - 1) * activityPerPage;
    return filteredActivitiesList.value.slice(start, start + activityPerPage);
});

async function load() {
    loading.value = !data.value;
    loadError.value = false;
    try {
        const [anggotaRes] = await Promise.all([
            getAnggota(),
            loadSession(),
        ]);
        data.value = anggotaRes;

        if (isLoggedIn.value) {
            const [profileRes, actRes] = await Promise.allSettled([
                http.get("/isf/profile"),
                getMemberActivities(),
            ]);
            if (profileRes.status === "fulfilled") {
                userRoles.value = profileRes.value.data?.data?.roles || [];
            }
            if (actRes.status === "fulfilled") {
                activities.value = {
                    letters: actRes.value.letters || [],
                    reviews: actRes.value.reviews || [],
                    surveys: actRes.value.surveys || [],
                    reports: actRes.value.reports || [],
                };
            }
        }
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

const totalActivitiesCount = computed(() => {
    return (
        (activities.value.letters?.length || 0) +
        (activities.value.reviews?.length || 0) +
        (activities.value.surveys?.length || 0) +
        (activities.value.reports?.length || 0)
    );
});

const linkClass =
    "rounded-sm font-semibold text-isf-red-dark underline underline-offset-2 hover:text-isf-navy focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

// Status verifikasi email dari query param ?verified=success|already|invalid (Issue #74)
const showVerificationBanner = ref(true);
const verificationStatus = computed(() => {
    const val = route.query.verified;
    if (val === "success" || val === "1") {
        return {
            type: "success",
            title: "Alamat Email Berhasil Diverifikasi!",
            message: "Selamat datang di Portal Anggota Indonesia Strategy Forum. Akun Anda kini aktif sepenuhnya.",
        };
    }
    if (val === "already") {
        return {
            type: "info",
            title: "Email Sudah Terverifikasi",
            message: "Alamat email Anda telah diverifikasi sebelumnya. Akun Portal Anggota Anda aktif.",
        };
    }
    if (val === "invalid" || val === "not_found") {
        return {
            type: "error",
            title: "Tautan Verifikasi Tidak Valid atau Kedaluwarsa",
            message: "Tautan verifikasi yang Anda buka tidak valid atau masa berlakunya telah habis. Silakan minta tautan baru.",
        };
    }
    return null;
});

function dismissVerificationBanner() {
    showVerificationBanner.value = false;
    const newQuery = { ...route.query };
    delete newQuery.verified;
    router.replace({ path: "/anggota", query: newQuery });
}
</script>

<template>
    <div class="space-y-6">
        <AnggotaSkeleton v-if="loading && !data" />

        <ErrorMessage
            v-else-if="loadError && !data"
            message="Umpan peringatan gagal dimuat. Periksa koneksi Anda lalu coba lagi."
            retryable
            @retry="load"
        />

        <template v-else>
            <!-- Notification Banner: Email Verification Status (Issue #74) -->
            <div
                v-if="verificationStatus && showVerificationBanner"
                class="rounded-xl p-4 sm:p-5 border transition-all duration-200 shadow-xs"
                :class="{
                    'bg-emerald-50 border-emerald-200 text-emerald-950': verificationStatus.type === 'success',
                    'bg-blue-50 border-blue-200 text-blue-950': verificationStatus.type === 'info',
                    'bg-red-50 border-red-200 text-red-950': verificationStatus.type === 'error',
                }"
                role="alert"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                            :class="{
                                'bg-emerald-100 text-emerald-600': verificationStatus.type === 'success',
                                'bg-blue-100 text-blue-600': verificationStatus.type === 'info',
                                'bg-red-100 text-red-600': verificationStatus.type === 'error',
                            }"
                        >
                            <svg v-if="verificationStatus.type === 'success'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else-if="verificationStatus.type === 'info'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold sm:text-base leading-snug">
                                {{ verificationStatus.title }}
                            </h3>
                            <p class="mt-1 text-xs sm:text-sm text-neutral-600 leading-relaxed">
                                {{ verificationStatus.message }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup notifikasi"
                        class="shrink-0 p-1 rounded-md text-neutral-400 hover:text-neutral-600 hover:bg-black/5 transition-colors cursor-pointer"
                        @click="dismissVerificationBanner"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- TAB AKTIFITAS PENGGUNA -->
            <section
                v-if="activeTab === 'aktifitas'"
                aria-labelledby="aktifitas-member"
                class="space-y-5 lg:col-span-12"
            >
                <div class="isf-hairline-bottom flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4">
                    <div>
                        <h2 id="aktifitas-member" class="isf-headline font-headline text-2xl font-bold text-neutral-900 flex items-center gap-2">
                            <IconHistory class="h-6 w-6 text-isf-red" />
                            <span>Aktifitas</span>
                        </h2>
                        <p class="text-sm text-neutral-600 mt-1">
                            Seluruh riwayat aktifitas akun Anda: artikel/naskah yang ditulis, rating tokoh/sosok, survei, dan laporan.
                        </p>
                    </div>
                    <a
                        v-if="isLoggedIn && isOnlyMember"
                        href="/cms/laporan-member"
                        class="inline-flex items-center justify-center gap-1.5 rounded-sm bg-isf-red px-4 py-2 font-ui text-xs font-bold uppercase tracking-wider text-white hover:bg-isf-red-dark transition-colors shrink-0"
                    >
                        <IconFlag class="h-4 w-4" />
                        <span>Report</span>
                    </a>
                </div>

                <!-- Jika Belum Login -->
                <div v-if="!isLoggedIn" class="border border-neutral-200 bg-neutral-50 p-8 text-center space-y-3">
                    <p class="font-headline text-lg font-bold text-neutral-900">Masuk untuk Melihat Aktifitas</p>
                    <p class="text-sm text-neutral-600 max-w-md mx-auto">
                        Silakan login terlebih dahulu untuk melihat seluruh riwayat artikel, rating sosok, partisipasi survei, dan laporan akun Anda.
                    </p>
                    <a
                        :href="loginHref"
                        class="inline-flex h-9 items-center rounded-sm bg-isf-red px-5 font-ui text-xs font-bold uppercase tracking-wider text-white hover:bg-isf-red-dark"
                    >
                        Login
                    </a>
                </div>

                <!-- Konten Aktifitas saat Login -->
                <template v-else>
                    <!-- Filter Kategori Aktifitas -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="historyFilter = 'semua'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-sm border transition-colors cursor-pointer',
                                historyFilter === 'semua'
                                    ? 'bg-neutral-900 text-white border-neutral-900'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50'
                            ]"
                        >
                            Semua ({{ totalActivitiesCount }})
                        </button>
                        <button
                            type="button"
                            @click="historyFilter = 'letters'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-sm border transition-colors cursor-pointer',
                                historyFilter === 'letters'
                                    ? 'bg-isf-red text-white border-isf-red'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50'
                            ]"
                        >
                            Artikel / Naskah ({{ activities.letters.length }})
                        </button>
                        <button
                            type="button"
                            @click="historyFilter = 'reviews'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-sm border transition-colors cursor-pointer',
                                historyFilter === 'reviews'
                                    ? 'bg-isf-red text-white border-isf-red'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50'
                            ]"
                        >
                            Rating Sosok ({{ activities.reviews.length }})
                        </button>
                        <button
                            type="button"
                            @click="historyFilter = 'surveys'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-sm border transition-colors cursor-pointer',
                                historyFilter === 'surveys'
                                    ? 'bg-isf-red text-white border-isf-red'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50'
                            ]"
                        >
                            Survei ({{ activities.surveys.length }})
                        </button>
                        <button
                            type="button"
                            @click="historyFilter = 'reports'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-sm border transition-colors cursor-pointer',
                                historyFilter === 'reports'
                                    ? 'bg-isf-red text-white border-isf-red'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50'
                            ]"
                        >
                            Report ({{ activities.reports.length }})
                        </button>
                    </div>

                    <div v-if="filteredActivitiesList.length === 0" class="border border-neutral-200 bg-neutral-50 p-8 text-center text-sm text-neutral-600">
                        Belum ada aktifitas tercatat pada kategori ini.
                    </div>

                    <div v-else class="space-y-4">
                        <div class="divide-y divide-neutral-200 border-y border-neutral-200 bg-white">
                            <div
                                v-for="item in paginatedActivities"
                                :key="item.uid"
                                class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                            >
                                <div class="min-w-0">
                                    <span
                                        class="inline-block text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-sm mb-1"
                                        :class="{
                                            'bg-blue-50 text-blue-700': item.kind === 'letters',
                                            'bg-amber-50 text-amber-700': item.kind === 'reviews',
                                            'bg-emerald-50 text-emerald-700': item.kind === 'surveys',
                                            'bg-red-50 text-red-700': item.kind === 'reports',
                                        }"
                                    >
                                        {{ item.kindLabel }}
                                    </span>
                                    <div class="text-sm sm:text-base leading-snug">
                                        <span class="font-bold text-neutral-950">{{ item.actorLabel }}</span>
                                        <span class="text-neutral-500 mx-1">{{ item.actionLabel }}</span>
                                        <IsfLink
                                            v-if="item.url"
                                            :to="item.url"
                                            class="font-headline font-bold text-isf-red hover:underline"
                                        >
                                            "{{ item.targetLabel }}"
                                        </IsfLink>
                                        <span v-else class="font-headline font-bold text-neutral-900">
                                            "{{ item.targetLabel }}"
                                        </span>
                                    </div>
                                    <p class="text-xs text-neutral-500/80 mt-0.5 line-clamp-1">
                                        {{ item.subtitle }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-sm shrink-0 self-start sm:self-center"
                                    :class="item.statusTone"
                                >
                                    {{ item.statusLabel }}
                                </span>
                            </div>
                        </div>

                        <!-- Pagination (10 per halaman) -->
                        <div v-if="totalActivityPages > 1" class="pt-2 flex items-center justify-between">
                            <p class="text-xs text-neutral-500">
                                Menampilkan {{ paginatedActivities.length }} dari {{ filteredActivitiesList.length }} aktifitas
                            </p>
                            <Pagination
                                :current-page="activityPage"
                                :total-pages="totalActivityPages"
                                @update:current-page="activityPage = $event"
                            />
                        </div>
                    </div>
                </template>
            </section>

            <!-- TAB ALERTS & PRIORITAS TINGGI (Existing) -->
            <section
                v-else
                aria-labelledby="daftar-alert"
                class="space-y-4 lg:col-span-12"
            >
                <h2 id="daftar-alert" class="sr-only">Daftar alert</h2>

                <!-- Toolbar: search + priority filter -->
                <div
                    class="isf-hairline-top isf-hairline-bottom grid grid-cols-1 gap-4 bg-white py-4 sm:grid-cols-[minmax(0,1fr)_14rem]"
                >
                    <div class="space-y-2">
                        <Label for="cari-alert">Cari alert</Label>
                        <Input
                            id="cari-alert"
                            v-model="search"
                            type="search"
                            placeholder="Cari alert…"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-prioritas">Prioritas</Label>
                        <Select v-model="prioritas">
                            <SelectTrigger
                                id="filter-prioritas"
                                aria-label="Filter prioritas"
                            >
                                <SelectValue placeholder="Semua prioritas" />
                            </SelectTrigger>
                            <!-- SelectContent is teleported to <body>: pass the scoped theme class -->
                            <SelectContent class="isf-theme">
                                <SelectItem
                                    v-for="option in PRIORITAS_OPTIONS"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <p class="text-sm text-neutral-600" aria-live="polite">
                    Menampilkan {{ filteredAlerts.length }} alert
                </p>

                <div
                    class="isf-hairline-top isf-hairline-bottom overflow-x-auto bg-white"
                >
                    <Table class="isf-stack-table min-w-[42rem]">
                        <caption class="sr-only">
                            Umpan peringatan dini untuk anggota
                        </caption>
                        <TableHeader class="bg-neutral-50">
                            <TableRow>
                                <TableHead
                                    scope="col"
                                    class="w-40 text-neutral-700"
                                    >Alert</TableHead
                                >
                                <TableHead scope="col" class="text-neutral-700"
                                    >Konten</TableHead
                                >
                                <TableHead
                                    scope="col"
                                    class="w-36 text-neutral-700"
                                    >Tanggal</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="alert in filteredAlerts"
                                :key="alert.id"
                            >
                                <TableCell class="align-top">
                                    <PriorityBadge :priority="alert.priority" />
                                </TableCell>
                                <TableCell class="align-top">
                                    <p
                                        class="isf-headline text-[16px] leading-snug"
                                    >
                                        <IsfLink
                                            v-if="alert.url"
                                            :to="alert.url"
                                            class="hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                                            >{{ alert.title }}</IsfLink
                                        >
                                        <template v-else>{{
                                            alert.title
                                        }}</template>
                                    </p>
                                    <p class="text-sm text-neutral-600">
                                        {{ alert.summary }}
                                    </p>
                                </TableCell>
                                <TableCell
                                    class="whitespace-nowrap align-top text-neutral-700"
                                    data-label="Tanggal"
                                >
                                    <time :datetime="alert.date">{{
                                        formatDate(alert.date)
                                    }}</time>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredAlerts.length === 0">
                                <TableCell
                                    colspan="3"
                                    class="py-8 text-center text-neutral-600"
                                >
                                    Tidak ada alert yang cocok dengan filter.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </section>

        </div>
        </template>
    </div>
</template>
