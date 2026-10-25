<script setup>
/**
 * Portal Khusus Anggota B2B — Dasbor Umpan Peringatan (Cetak Biru ISF, bagian 08).
 * Content: GET /api/isf/anggota (published alerts, member profile setting, Tautan Langsung).
 * UI preview: client-side filtering, NO auth gating yet.
 * Priority filter is synced to the URL query (?prioritas=semua|merah|kuning).
 */
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
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
import { IsfLink, PriorityBadge } from "@/Components/Isf";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getAnggota } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();
const router = useRouter();

const { data, status, load } = useIsfContent(getAnggota);
onMounted(load);

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
const initials = computed(() =>
    (user.value.name || "")
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(""),
);

const linkClass =
    "rounded-sm font-semibold text-isf-red-dark underline underline-offset-2 hover:text-isf-navy focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";
</script>

<template>
    <div class="space-y-6">
        <header>
            <div class="isf-col-head">
                <p class="isf-label">Feed Peringatan Dini</p>
            </div>
            <h1
                class="isf-headline mt-4 font-headline text-[30px] leading-[1.1] sm:text-[40px]"
            >
                Dasbor Umpan Peringatan
            </h1>
        </header>

        <div v-if="status === 'loading'" class="py-16">
            <LoadingIndicator text="Memuat umpan peringatan…" />
        </div>

        <ErrorMessage
            v-else-if="status !== 'ready'"
            message="Umpan peringatan gagal dimuat. Periksa koneksi Anda lalu coba lagi."
            retryable
            @retry="load"
        />

        <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <section
                aria-labelledby="daftar-alert"
                class="space-y-4 lg:col-span-8"
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

            <aside class="space-y-6 lg:col-span-4">
                <!-- User / organization card -->
                <Card class="isf-editorial-card isf-editorial-compact">
                    <CardHeader
                        class="isf-hairline-bottom flex-row items-center gap-4 space-y-0"
                    >
                        <span
                            class="inline-flex h-12 w-12 shrink-0 items-center justify-center bg-isf-red text-lg font-bold text-white"
                            aria-hidden="true"
                        >
                            {{ initials }}
                        </span>
                        <div>
                            <CardTitle
                                class="isf-headline font-headline text-lg"
                                >{{ user.name }}</CardTitle
                            >
                            <p class="text-sm text-neutral-600">
                                {{ user.role }}
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3 pt-4 text-sm">
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-xs text-neutral-600">
                                    Organisasi
                                </dt>
                                <dd class="font-semibold">
                                    {{ user.organization }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-neutral-600">Paket</dt>
                                <dd class="font-semibold">{{ user.plan }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-neutral-600">
                                    Sektor dipantau
                                </dt>
                                <dd class="mt-1 flex flex-wrap gap-2">
                                    <span
                                        v-for="sektor in user.sektor"
                                        :key="sektor"
                                        class="border border-neutral-300 bg-white px-2.5 py-0.5 text-xs font-semibold text-neutral-800"
                                    >
                                        {{ sektor }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                        <div class="flex flex-wrap items-center gap-2 pt-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled
                                aria-describedby="kelola-info"
                            >
                                Kelola Profil
                            </Button>
                            <span
                                id="kelola-info"
                                class="text-xs text-neutral-600"
                                >segera</span
                            >
                        </div>
                    </CardContent>
                </Card>

                <!-- Tautan Langsung -->
                <Card class="isf-editorial-card isf-editorial-compact">
                    <CardHeader class="isf-hairline-bottom">
                        <CardTitle class="isf-headline font-headline text-lg"
                            >Tautan Langsung</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="pt-4">
                        <ul class="space-y-2 text-sm">
                            <li
                                v-for="link in tautanLangsung"
                                :key="link.label"
                            >
                                <!-- IsfLink: SPA path → router-link, Blade preview → full page load, http(s) → new tab -->
                                <IsfLink
                                    :to="link.href || link.to"
                                    :class="linkClass"
                                >
                                    {{ link.label }}
                                </IsfLink>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </aside>
        </div>
    </div>
</template>
