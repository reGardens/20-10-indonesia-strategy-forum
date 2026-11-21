<script setup>
import { useCmsPagination } from '@/Composables/useCmsPagination'
import { ref, computed, onMounted, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { iklanService, errorMessage } from "@/api/services/isfCmsService";
import {
    alertSuccess,
    alertError,
    confirmDelete,
    confirmAction,
} from "@/Composables/useAlert";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import {
    Button,
    Input,
    Badge,
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/UI";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

defineOptions({ layout: CmsLayout });

const POPUP_DEFAULT_FIRST_DELAY_SEC = 5;
const POPUP_DEFAULT_INTERVAL_MIN = 30;

const items = ref([]);
const loading = ref(true);
const loadError = ref("");
const search = ref("");
const statusFilter = ref(""); // '' | 'active' | 'paused' | 'expired'
// Tab aktif disimpan di URL (?tab=popup): tetap saat refresh, kembali ke "body" saat dibuka dari menu lain.
const TABS = ["body", "popup"];
function initialTab() {
    if (typeof window === "undefined") return "body";
    const tab = new URLSearchParams(window.location.search).get("tab");
    return TABS.includes(tab) ? tab : "body";
}
const activeTab = ref(initialTab()); // 'body' | 'popup'
const currentPage = useCmsPagination()
const perPage = ref(10);

watch(activeTab, (tab) => {
    currentPage.value = 1;
    const url = new URL(window.location.href);
    if (tab === "body") url.searchParams.delete("tab");
    else url.searchParams.set("tab", tab);
    // Pertahankan history.state milik Inertia.
    window.history.replaceState(window.history.state, "", url.pathname + url.search + url.hash);
});
watch([search, statusFilter], () => {
    currentPage.value = 1;
});
const reordering = ref(false);

const isFiltered = computed(() => Boolean(search.value || statusFilter.value));
const isPopupTab = computed(() => activeTab.value === "popup");

const statusFilters = [
    {
        value: "",
        label: "Semua",
        activeClass: "bg-red-600/20 text-red-400 border border-red-500/40",
    },
    {
        value: "active",
        label: "Aktif",
        activeClass:
            "bg-emerald-600/20 text-emerald-400 border border-emerald-500/40",
    },
    {
        value: "paused",
        label: "Dijeda",
        activeClass:
            "bg-neutral-600/20 text-neutral-400 border border-neutral-500/40",
    },
    {
        value: "expired",
        label: "Kedaluwarsa",
        activeClass: "bg-rose-600/20 text-rose-400 border border-rose-500/40",
    },
];

// Status tampilan dihitung server (waktu server, Asia/Jakarta).
const displayStates = {
    live: {
        label: "Sedang Tayang",
        badgeClass: "bg-emerald-500/10 text-emerald-400 border-emerald-500/30",
    },
    scheduled: {
        label: "Terjadwal",
        badgeClass: "bg-amber-500/10 text-amber-400 border-amber-500/30",
    },
    off_hours: {
        label: "Di Luar Jam Tayang",
        badgeClass: "bg-blue-500/10 text-blue-400 border-blue-500/30",
    },
    ended: {
        label: "Berakhir",
        badgeClass: "bg-neutral-800 text-neutral-400 border-neutral-700",
    },
    paused: {
        label: "Dijeda",
        badgeClass: "bg-neutral-800 text-neutral-400 border-neutral-700",
    },
    expired: {
        label: "Kedaluwarsa",
        badgeClass: "bg-rose-500/10 text-rose-400 border-rose-500/30",
    },
};

function displayState(item) {
    return displayStates[item.display_state] || displayStates.paused;
}

const isActive = (item) => item.status === "active";
const isVideo = (item) => item.type === "video";

const allBodyItems = computed(() =>
    items.value.filter((it) => it.placement === "spanduk"),
);
const allPopupItems = computed(() =>
    items.value.filter((it) => it.placement === "popup"),
);

function applySearchAndStatus(list) {
    let res = [...list];
    if (statusFilter.value) {
        res = res.filter((it) => it.status === statusFilter.value);
    }

    if (search.value) {
        const q = search.value.toLowerCase();
        res = res.filter((it) => {
            const matchTitle = (it.title || "").toLowerCase().includes(q);
            const matchKicker = (it.kicker || "").toLowerCase().includes(q);
            const matchTarget = (it.target_url || "").toLowerCase().includes(q);
            return matchTitle || matchKicker || matchTarget;
        });
    }
    return res.sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0));
}

const visibleItems = computed(() =>
    applySearchAndStatus(
        isPopupTab.value ? allPopupItems.value : allBodyItems.value,
    ),
);

const paginatedVisibleItems = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return visibleItems.value.slice(start, start + perPage.value);
});

const columnCount = computed(() => (isPopupTab.value ? 8 : 7));

// Posisi popup dalam antrean (1, 2, 3, …) dihitung per tab, tidak ikut nomor spanduk.
const popupPosition = computed(() => {
    const map = {};
    [...allPopupItems.value]
        .sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0))
        .forEach((it, idx) => {
            map[it.id] = idx + 1;
        });
    return map;
});

function getPopupScheduleInfo(item) {
    const livePopups = allPopupItems.value
        .filter((it) => it.display_state === "live")
        .sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0));

    if (livePopups.length > 0 && livePopups[0].id === item.id) {
        const sec = item.first_delay_seconds ?? POPUP_DEFAULT_FIRST_DELAY_SEC;
        return {
            label: `Pertama · ${sec} detik setelah halaman dibuka`,
            badgeClass:
                "bg-emerald-500/10 text-emerald-400 border-emerald-500/30",
        };
    }

    const mins = item.interval_minutes ?? POPUP_DEFAULT_INTERVAL_MIN;
    return {
        label: `${mins} menit setelah popup sebelumnya ditutup`,
        badgeClass: "bg-blue-500/10 text-blue-400 border-blue-500/30",
    };
}

// "2026-11-15 08:30" → "15 Nov 2026 08.30"
function formatDateTime(value) {
    if (!value) return "";
    const [date, time] = String(value).split(" ");
    const parts = date.split("-");
    if (parts.length !== 3) return value;
    const d = new Date(
        Number(parts[0]),
        Number(parts[1]) - 1,
        Number(parts[2]),
    );
    const dateText = d.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
    return time ? `${dateText} ${time.replace(":", ".")}` : dateText;
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString("id-ID");
}

function formatCtr(value) {
    return `${Number(value || 0).toLocaleString("id-ID", { maximumFractionDigits: 2 })}%`;
}

async function loadData() {
    loading.value = true;
    loadError.value = "";
    try {
        const res = await iklanService.get();
        const list = Array.isArray(res) ? res : res?.data || res?.items || [];
        items.value = list;
    } catch (err) {
        loadError.value = errorMessage(err, "Gagal memuat data iklan.");
    } finally {
        loading.value = false;
    }
}

async function toggleActive(item) {
    const willBeActive = !isActive(item);

    const confirmed = await confirmAction({
        title: willBeActive ? "Aktifkan Iklan?" : "Jeda Iklan?",
        text: willBeActive
            ? `Yakin ingin mengaktifkan iklan "${item.title}"? Iklan tayang sesuai jadwal & jam tayangnya.`
            : `Yakin ingin menjeda iklan "${item.title}"? Iklan berhenti tayang sampai diaktifkan lagi.`,
        confirmText: willBeActive ? "Ya, Aktifkan" : "Ya, Jeda",
        cancelText: "Batal",
        icon: "question",
    });
    if (!confirmed) return;

    try {
        await iklanService.toggleActive(item.id);
        alertSuccess(
            `Iklan berhasil ${willBeActive ? "diaktifkan" : "dijeda"}.`,
        );
    } catch (err) {
        alertError(errorMessage(err, "Gagal mengubah status iklan."));
    } finally {
        await loadData();
    }
}

async function moveOrder(currentIndex, direction) {
    if (isFiltered.value || reordering.value) return;

    const list = [...visibleItems.value];
    const targetIndex = currentIndex + direction;
    if (targetIndex < 0 || targetIndex >= list.length) return;

    const [moved] = list.splice(currentIndex, 1);
    list.splice(targetIndex, 0, moved);

    list.forEach((it, idx) => {
        it.order = idx + 1;
    });

    const otherItems = items.value.filter(
        (it) => !list.some((l) => l.id === it.id),
    );
    items.value = [...otherItems, ...list];
    reordering.value = true;

    try {
        // Hanya iklan di tab ini yang dinomori ulang (1..n).
        const payload = list.map((it) => ({ id: it.id, order: it.order }));
        await iklanService.reorder(payload);
        alertSuccess("Urutan iklan berhasil diperbarui.");
    } catch (err) {
        alertError(errorMessage(err, "Gagal mengubah urutan iklan."));
    } finally {
        reordering.value = false;
        await loadData();
    }
}

async function handleDelete(item) {
    const confirmed = await confirmDelete(item.title || "Iklan");
    if (!confirmed) return;

    try {
        await iklanService.remove(item.id);
        alertSuccess("Iklan berhasil dihapus.");
        await loadData();
    } catch (err) {
        alertError(errorMessage(err, "Gagal menghapus iklan."));
    }
}

onMounted(loadData);
</script>

<template>
    <Head title="Kelola Iklan & Monetisasi — Indonesia Strategy Forum" />

    <div class="p-6 space-y-6 w-full">
        <!-- Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2"
                >
                    <IconComponent name="Tag" :size="24" class="text-red-500" />
                    Kelola Iklan &amp; Monetisasi
                </h1>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Kelola iklan spanduk Beranda dan modal popup sponsor saat
                    akses website.
                </p>
            </div>

            <!-- Button Tambah Iklan (Navigasi ke Form Create) -->
            <div class="flex items-center gap-2">
                <Link href="/cms/iklan/create">
                    <Button
                        type="button"
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5 cursor-pointer"
                    >
                        <IconComponent name="Plus" :size="16" />
                        <span>Tambah Iklan</span>
                    </Button>
                </Link>
            </div>
        </div>

        <!-- 2 Tab Pemisah Tabel Iklan -->
        <div class="flex items-center gap-2 border-b border-border pb-3">
            <button
                type="button"
                @click="activeTab = 'body'"
                :class="[
                    'px-4 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer inline-flex items-center gap-2',
                    activeTab === 'body'
                        ? 'bg-red-600 text-white shadow-sm'
                        : 'bg-card text-muted-foreground hover:bg-muted/40 hover:text-foreground border border-border',
                ]"
            >
                <IconComponent name="Layout" :size="15" />
                <span>Iklan Body (Spanduk Beranda)</span>
                <span
                    :class="[
                        'px-1.5 py-0.5 text-[10px] rounded-full font-mono font-bold',
                        activeTab === 'body'
                            ? 'bg-white/20 text-white'
                            : 'bg-neutral-800 text-muted-foreground',
                    ]"
                >
                    {{ allBodyItems.length }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'popup'"
                :class="[
                    'px-4 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer inline-flex items-center gap-2',
                    activeTab === 'popup'
                        ? 'bg-red-600 text-white shadow-sm'
                        : 'bg-card text-muted-foreground hover:bg-muted/40 hover:text-foreground border border-border',
                ]"
            >
                <IconComponent name="ExternalLink" :size="15" />
                <span>Iklan Popup (Modal Popup)</span>
                <span
                    :class="[
                        'px-1.5 py-0.5 text-[10px] rounded-full font-mono font-bold',
                        activeTab === 'popup'
                            ? 'bg-white/20 text-white'
                            : 'bg-neutral-800 text-muted-foreground',
                    ]"
                >
                    {{ allPopupItems.length }}
                </span>
            </button>
        </div>

        <!-- Filters & Search -->
        <div
            class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between"
        >
            <div class="relative w-full lg:w-80">
                <IconComponent
                    name="Search"
                    :size="16"
                    class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari judul iklan, label, tautan..."
                    class="pl-9 h-9 text-xs bg-card/60"
                />
            </div>

            <div class="flex items-center gap-1.5 overflow-x-auto py-0.5">
                <span class="text-xs text-muted-foreground whitespace-nowrap"
                    >Status:</span
                >
                <div class="flex gap-1">
                    <button
                        v-for="opt in statusFilters"
                        :key="opt.value"
                        type="button"
                        @click="statusFilter = opt.value"
                        :class="[
                            'px-2.5 py-1 text-xs rounded-md font-medium transition-colors cursor-pointer whitespace-nowrap',
                            statusFilter === opt.value
                                ? opt.activeClass
                                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border',
                        ]"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading / Error states -->
        <LoadingIndicator v-if="loading" label="Memuat data iklan..." />
        <ErrorMessage
            v-else-if="loadError"
            :message="loadError"
            @retry="loadData"
        />

        <!-- Tabel iklan (tab Spanduk / Popup) -->
        <div
            v-else
            class="rounded-xl border border-border bg-card overflow-hidden shadow-xs"
        >
            <Table
                :current-page="currentPage"
                :per-page="perPage"
                :total="visibleItems.length"
                @update:current-page="currentPage = $event"
                @update:per-page="perPage = $event; currentPage = 1"
            >
                <TableHeader>
                    <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
                        <TableHead
                            :class="[
                                'text-center text-xs',
                                isPopupTab ? 'w-24' : 'w-12',
                            ]"
                            >{{ isPopupTab ? "Urutan" : "No" }}</TableHead
                        >
                        <TableHead class="w-28 text-xs">Materi Media</TableHead>
                        <TableHead class="text-xs"
                            >Judul Kampanye &amp; Tautan</TableHead
                        >
                        <TableHead v-if="isPopupTab" class="w-52 text-xs"
                            >Format &amp; Penayangan</TableHead
                        >
                        <TableHead class="w-60 text-xs"
                            >Jadwal &amp; Status Tayang</TableHead
                        >
                        <TableHead class="w-40 text-xs">Statistik</TableHead>
                        <TableHead class="w-32 text-center text-xs"
                            >Status Aktif</TableHead
                        >
                        <TableHead class="w-36 text-right text-xs"
                            >Aksi</TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="visibleItems.length === 0">
                        <TableCell
                            :colspan="columnCount"
                            class="text-center py-12 text-xs text-muted-foreground"
                        >
                            <template v-if="isFiltered">
                                {{
                                    isPopupTab
                                        ? "Tidak ada iklan popup yang cocok dengan filter pencarian."
                                        : "Tidak ada iklan body yang cocok dengan filter pencarian."
                                }}
                            </template>
                            <template v-else>
                                {{
                                    isPopupTab
                                        ? "Belum ada iklan modal popup yang terdaftar."
                                        : "Belum ada iklan body (spanduk beranda) yang terdaftar."
                                }}
                            </template>
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="(item, idx) in paginatedVisibleItems"
                        :key="item.id"
                        class="hover:bg-muted/30 transition border-b border-border/50"
                        data-testid="iklan-row"
                    >
                        <!-- Spanduk: nomor biasa (rotasi acak, urutan tidak dipakai) -->
                        <TableCell
                            v-if="!isPopupTab"
                            class="text-center text-xs font-mono text-muted-foreground"
                            data-testid="iklan-no"
                        >
                            {{ idx + 1 + (currentPage - 1) * perPage }}
                        </TableCell>

                        <!-- Popup: urutan antrean + pindah naik/turun -->
                        <TableCell v-else class="text-center text-xs">
                            <div
                                class="inline-flex items-center justify-center gap-1.5"
                            >
                                <span
                                    class="font-mono font-bold text-foreground text-xs min-w-[1.25rem] text-center"
                                    data-testid="iklan-urutan"
                                >
                                    {{ popupPosition[item.id] ?? ((currentPage - 1) * perPage + idx + 1) }}
                                </span>
                                <div class="inline-flex flex-col gap-0.5">
                                    <button
                                        type="button"
                                        :title="
                                            isFiltered
                                                ? 'Reset filter untuk mengubah urutan'
                                                : 'Pindah Urutan Naik'
                                        "
                                        :disabled="
                                            (currentPage - 1) * perPage + idx === 0 ||
                                            reordering ||
                                            isFiltered
                                        "
                                        @click="moveOrder((currentPage - 1) * perPage + idx, -1)"
                                        class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer"
                                    >
                                        <IconComponent
                                            name="ChevronUp"
                                            :size="13"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        :title="
                                            isFiltered
                                                ? 'Reset filter untuk mengubah urutan'
                                                : 'Pindah Urutan Turun'
                                        "
                                        :disabled="
                                            (currentPage - 1) * perPage + idx === visibleItems.length - 1 ||
                                            reordering ||
                                            isFiltered
                                        "
                                        @click="moveOrder((currentPage - 1) * perPage + idx, 1)"
                                        class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer"
                                    >
                                        <IconComponent
                                            name="ChevronDown"
                                            :size="13"
                                        />
                                    </button>
                                </div>
                            </div>
                        </TableCell>

                        <!-- Materi Media Preview -->
                        <TableCell>
                            <div
                                class="w-24 h-14 rounded overflow-hidden bg-neutral-900 border border-border/60 flex items-center justify-center"
                            >
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    :alt="item.title"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                                <IconComponent
                                    v-else-if="isVideo(item)"
                                    name="Video"
                                    :size="20"
                                    class="text-amber-500"
                                />
                                <IconComponent
                                    v-else
                                    name="Photo"
                                    :size="20"
                                    class="text-muted-foreground/60"
                                />
                            </div>
                        </TableCell>

                        <!-- Judul & Tautan -->
                        <TableCell>
                            <div class="space-y-1">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground font-mono"
                                >
                                    {{ item.kicker || "SPONSOR RESMI" }}
                                </span>
                                <p
                                    class="font-semibold text-sm text-foreground line-clamp-2"
                                >
                                    {{ item.title }}
                                </p>
                                <a
                                    v-if="item.target_url"
                                    :href="item.target_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-mono text-xs text-red-400 hover:text-red-300 hover:underline inline-flex items-center gap-0.5"
                                >
                                    <span>{{ item.target_url }}</span>
                                    <IconComponent
                                        name="ExternalLink"
                                        :size="11"
                                    />
                                </a>
                            </div>
                        </TableCell>

                        <!-- Format & Penayangan (popup) -->
                        <TableCell v-if="isPopupTab">
                            <div class="flex flex-col gap-1 items-start">
                                <Badge
                                    variant="outline"
                                    :class="[
                                        'text-[10px] font-semibold',
                                        isVideo(item)
                                            ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                                            : 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                                    ]"
                                >
                                    <IconComponent
                                        :name="
                                            isVideo(item) ? 'Video' : 'Photo'
                                        "
                                        :size="11"
                                        class="mr-1 inline-block"
                                    />
                                    {{
                                        isVideo(item)
                                            ? `Video (${item.video_duration || 30}s)`
                                            : "Gambar / Banner"
                                    }}
                                </Badge>
                                <Badge
                                    v-if="isVideo(item)"
                                    variant="outline"
                                    :class="[
                                        'text-[10px] font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1',
                                        item.is_skippable !== false
                                            ? 'bg-blue-500/10 text-blue-400 border-blue-500/30'
                                            : 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                                    ]"
                                >
                                    <IconComponent
                                        :name="item.is_skippable !== false ? 'ArrowRight' : 'Lock'"
                                        :size="10"
                                        class="shrink-0"
                                    />
                                    <span>
                                        {{ item.is_skippable !== false ? `Skip: ${item.skip_delay_seconds ?? 10}s` : 'Non-skippable' }}
                                    </span>
                                </Badge>
                                <Badge
                                    v-else-if="item.skip_delay_seconds > 0"
                                    variant="outline"
                                    class="text-[10px] font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1 bg-blue-500/10 text-blue-400 border-blue-500/30"
                                >
                                    <IconComponent
                                        name="Clock"
                                        :size="10"
                                        class="shrink-0"
                                    />
                                    <span>Tutup: {{ item.skip_delay_seconds }}s</span>
                                </Badge>
                                <Badge
                                    variant="outline"
                                    :class="[
                                        'text-[10px] font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1 whitespace-normal text-left',
                                        getPopupScheduleInfo(item).badgeClass,
                                    ]"
                                >
                                    <IconComponent
                                        name="Clock"
                                        :size="10"
                                        class="shrink-0"
                                    />
                                    {{ getPopupScheduleInfo(item).label }}
                                </Badge>
                            </div>
                        </TableCell>

                        <!-- Jadwal & Status Tayang -->
                        <TableCell>
                            <div
                                class="flex flex-col gap-1.5 items-start text-[11px] text-muted-foreground"
                            >
                                <div class="flex items-center gap-1.5">
                                    <IconComponent
                                        name="Calendar"
                                        :size="12"
                                        class="shrink-0"
                                    />
                                    <span
                                        >{{
                                            item.start_at
                                                ? formatDateTime(item.start_at)
                                                : "Mulai"
                                        }}
                                        –
                                        {{
                                            item.end_at
                                                ? formatDateTime(item.end_at)
                                                : "Seterusnya"
                                        }}</span
                                    >
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <IconComponent
                                        name="Clock"
                                        :size="12"
                                        class="shrink-0"
                                    />
                                    <span>
                                        {{
                                            item.active_hours_start &&
                                            item.active_hours_end
                                                ? `Jam ${item.active_hours_start}–${item.active_hours_end}`
                                                : "Sepanjang hari"
                                        }}
                                    </span>
                                </div>
                                <Badge
                                    variant="outline"
                                    :class="[
                                        'text-[10px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1',
                                        displayState(item).badgeClass,
                                    ]"
                                    data-testid="iklan-display-state"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-current"
                                    ></span>
                                    {{ displayState(item).label }}
                                </Badge>
                            </div>
                        </TableCell>

                        <!-- Statistik -->
                        <TableCell>
                            <div
                                class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 text-[11px]"
                                data-testid="iklan-stats"
                            >
                                <span class="text-muted-foreground"
                                    >Tayangan</span
                                >
                                <span
                                    class="font-mono font-semibold text-foreground"
                                    >{{ formatNumber(item.impressions) }}</span
                                >
                                <span class="text-muted-foreground">Klik</span>
                                <span
                                    class="font-mono font-semibold text-foreground"
                                    >{{ formatNumber(item.clicks) }}</span
                                >
                                <span class="text-muted-foreground">CTR</span>
                                <span
                                    class="font-mono font-semibold text-foreground"
                                    >{{ formatCtr(item.ctr) }}</span
                                >
                            </div>
                        </TableCell>

                        <!-- Toggle Aktif / Dijeda -->
                        <TableCell class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="isActive(item)"
                                    :title="
                                        isActive(item)
                                            ? 'Klik untuk menjeda'
                                            : 'Klik untuk mengaktifkan'
                                    "
                                    @click="toggleActive(item)"
                                    :class="[
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500',
                                        isActive(item)
                                            ? 'bg-emerald-500'
                                            : 'bg-neutral-700',
                                    ]"
                                >
                                    <span
                                        aria-hidden="true"
                                        :class="[
                                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            isActive(item)
                                                ? 'translate-x-4'
                                                : 'translate-x-0',
                                        ]"
                                    />
                                </button>
                                <span
                                    :class="[
                                        'text-xs font-semibold select-none min-w-[50px] text-left',
                                        isActive(item)
                                            ? 'text-emerald-400'
                                            : 'text-muted-foreground',
                                    ]"
                                >
                                    {{
                                        isActive(item)
                                            ? "Aktif"
                                            : item.status === "expired"
                                              ? "Kedaluwarsa"
                                              : "Dijeda"
                                    }}
                                </span>
                            </div>
                        </TableCell>

                        <!-- Aksi (Edit & Hapus) -->
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <Link :href="`/cms/iklan/${item.id}/edit`">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center cursor-pointer"
                                    >
                                        <IconComponent
                                            name="Edit"
                                            :size="14"
                                            class="mr-1"
                                        />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center cursor-pointer"
                                    @click="handleDelete(item)"
                                >
                                    <IconComponent
                                        name="Trash"
                                        :size="14"
                                        class="mr-1"
                                    />
                                    Hapus
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
