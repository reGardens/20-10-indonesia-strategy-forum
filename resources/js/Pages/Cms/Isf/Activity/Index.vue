<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import http from "@/api/http";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import {
    Input,
    Badge,
    Button,
    DatePicker,
    FilterSelect,
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/UI";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

defineOptions({ layout: CmsLayout });

const items = ref([]);
const loading = ref(true);
const errorMsg = ref("");
const scope = ref("self");
const typeFilter = ref("semua");
const categoryFilter = ref("");
const dateFilter = ref("");
const search = ref("");
const page = ref(1);
const perPage = ref(10);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 });

const typeOptions = [
    { value: "semua", label: "Semua Jenis" },
    { value: "artikel", label: "Artikel" },
    { value: "rating", label: "Rating" },
    { value: "survei", label: "Survei" },
    { value: "report", label: "Report" },
];

const categoryOptions = [
    { value: "", label: "Semua Kategori" },
    { value: "politik", label: "Politik" },
    { value: "ekonomi", label: "Ekonomi" },
    { value: "sosial", label: "Sosial" },
    { value: "hukum", label: "Hukum" },
    { value: "kebijakan", label: "Kebijakan" },
    { value: "opini", label: "Opini" },
    { value: "sosok", label: "Sosok" },
    { value: "survei", label: "Survei" },
    { value: "kendala_teknis", label: "Kendala Teknis" },
    { value: "lainnya", label: "Lainnya" },
];

async function loadData() {
    loading.value = true;
    errorMsg.value = "";
    try {
        const res = await http.get("/cms/aktifitas", {
            params: {
                type: typeFilter.value,
                category: categoryFilter.value || undefined,
                date: dateFilter.value || undefined,
                search: search.value || undefined,
                page: page.value,
                per_page: perPage.value,
            },
        });
        items.value = res.data?.data || [];
        meta.value = res.data?.meta || {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: perPage.value,
        };
        scope.value = res.data?.scope || "self";
    } catch (err) {
        errorMsg.value =
            err?.response?.data?.message || "Gagal memuat data aktifitas.";
    } finally {
        loading.value = false;
    }
}

onMounted(loadData);

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        page.value = 1;
        loadData();
    }, 350);
});

function onTypeChange(val) {
    typeFilter.value = val;
    page.value = 1;
    loadData();
}

function onFilterChange() {
    page.value = 1;
    loadData();
}

const hasActiveFilters = computed(() => {
    return (
        typeFilter.value !== "semua" ||
        Boolean(categoryFilter.value) ||
        Boolean(dateFilter.value) ||
        Boolean(search.value)
    );
});

function resetFilters() {
    typeFilter.value = "semua";
    categoryFilter.value = "";
    dateFilter.value = "";
    search.value = "";
    page.value = 1;
    loadData();
}

function onPageChange(newPage) {
    page.value = newPage;
    loadData();
}

function onPerPageChange(newPerPage) {
    perPage.value = newPerPage;
    page.value = 1;
    loadData();
}
</script>

<template>
    <Head title="Aktifitas — Indonesia Strategy Forum" />

    <div class="p-6 space-y-6 w-full">
        <!-- Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground">
                    Aktifitas
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Catatan aktifitas penulisan artikel, rating sosok,
                    partisipasi survei, dan report pengguna.
                </p>
            </div>
        </div>

        <!-- Filter and Search -->
        <div
            class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between"
        >
            <div class="relative w-full lg:w-72">
                <Input
                    v-model="search"
                    placeholder="Cari judul atau nama..."
                    class="pl-9 h-9 text-xs"
                />
                <IconComponent
                    name="Search"
                    :size="16"
                    class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
                />
            </div>

            <div
                class="flex flex-wrap items-center gap-3 justify-start lg:justify-end"
            >
                <div class="flex items-center gap-1.5">
                    <label
                        for="filter-date-aktifitas"
                        class="text-xs text-muted-foreground whitespace-nowrap"
                        >Tanggal:</label
                    >
                    <div class="w-36">
                        <DatePicker
                            id="filter-date-aktifitas"
                            v-model="dateFilter"
                            @update:model-value="onFilterChange"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <label
                        for="filter-kat-aktifitas"
                        class="text-xs text-muted-foreground whitespace-nowrap"
                        >Kategori:</label
                    >
                    <FilterSelect
                        id="filter-kat-aktifitas"
                        v-model="categoryFilter"
                        placeholder="Semua Kategori"
                        :options="categoryOptions"
                        @change="onFilterChange"
                    />
                </div>

                <div class="flex items-center gap-1.5">
                    <label
                        for="filter-jenis-aktifitas"
                        class="text-xs text-muted-foreground whitespace-nowrap"
                        >Jenis:</label
                    >
                    <FilterSelect
                        id="filter-jenis-aktifitas"
                        v-model="typeFilter"
                        placeholder="Semua Jenis"
                        :options="typeOptions"
                        @change="onFilterChange"
                    />
                </div>

                <Button
                    v-if="hasActiveFilters"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-9 text-xs"
                    @click="resetFilters"
                >
                    Reset
                </Button>
            </div>
        </div>

        <!-- Loading & Error -->
        <LoadingIndicator
            v-if="loading && items.length === 0"
            text="Memuat aktifitas..."
        />
        <ErrorMessage
            v-else-if="errorMsg"
            :message="errorMsg"
            @retry="loadData"
        />

        <!-- Table -->
        <div
            v-else
            class="rounded-xl border border-border bg-card overflow-hidden shadow-xs"
        >
            <Table
                :current-page="meta.current_page"
                :per-page="meta.per_page"
                :total="meta.total"
                :total-pages="meta.last_page"
                @update:current-page="onPageChange"
                @update:per-page="onPerPageChange"
            >
                <TableHeader>
                    <TableRow
                        class="bg-muted/40 hover:bg-muted/40 border-b border-border"
                    >
                        <TableHead class="w-12 text-center text-xs"
                            >#</TableHead
                        >
                        <TableHead>Aktifitas &amp; Keterangan</TableHead>
                        <TableHead class="text-right w-40">Waktu</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="items.length === 0">
                        <TableCell
                            colspan="3"
                            class="text-center py-10 text-muted-foreground text-sm"
                        >
                            Tidak ada data aktifitas ditemukan.
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-for="(item, idx) in items"
                        :key="item.uid"
                        class="hover:bg-muted/30 transition border-b border-border/50"
                    >
                        <TableCell
                            class="text-center font-mono text-xs text-muted-foreground"
                        >
                            {{
                                (meta.current_page - 1) * meta.per_page +
                                idx +
                                1
                            }}
                        </TableCell>
                        <TableCell>
                            <div
                                class="flex flex-wrap items-center gap-1.5 text-sm leading-snug"
                            >
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border mr-1"
                                    :class="{
                                        'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20':
                                            item.type === 'artikel',
                                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20':
                                            item.type === 'rating',
                                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20':
                                            item.type === 'survei',
                                        'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20':
                                            item.type === 'report',
                                    }"
                                >
                                    {{ item.type_label }}
                                </span>
                                <span class="font-bold text-foreground">{{
                                    item.actor_label || item.user_name
                                }}</span>
                                <span
                                    v-if="
                                        scope !== 'self' &&
                                        item.actor_label !== 'Anda'
                                    "
                                    class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-muted text-muted-foreground/80"
                                >
                                    {{ item.user_role }}
                                </span>
                                <span class="text-muted-foreground/85">{{
                                    item.action_label
                                }}</span>
                                <span
                                    class="font-semibold text-red-600 dark:text-red-400"
                                    >"{{
                                        item.target_label || item.title
                                    }}"</span
                                >
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span
                                    class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-muted text-muted-foreground"
                                >
                                    {{ item.status }}
                                </span>
                                <p
                                    class="text-xs text-muted-foreground/75 line-clamp-1"
                                >
                                    {{ item.detail }}
                                </p>
                            </div>
                        </TableCell>
                        <TableCell
                            class="text-right text-xs text-muted-foreground whitespace-nowrap"
                        >
                            {{ item.created_at }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
