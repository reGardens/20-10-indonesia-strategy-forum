<script setup>
/**
 * Profil & Tokoh Pekan Ini (Cetak Biru ISF, bagian 04).
 * Content: GET /api/isf/tokoh/{slug} (published CMS content only; 404 → not found state).
 *
 * "Ikuti Tokoh": NON-FUNCTIONAL local toggle (UI preview only, aria-pressed);
 * nothing is saved and no backend is called.
 * "Subscribe" (calendar): rendered DISABLED with helper text (no .ics export yet).
 * "Indeks Tokoh" (#41): rating & ulasan via isfTokohReviewService (tanpa cache); tab Karya berisi
 * Jejak Kebijakan; kirim ulasan wajib login (/login?redirect=...).
 */
import { computed, nextTick, ref, watch } from "vue";
import { useRoute } from "vue-router";
import {
    IconCheck,
    IconFileText,
    IconLock,
    IconPencil,
    IconThumbDown,
    IconThumbUp,
} from "@tabler/icons-vue";
import Button from "@/Components/UI/Button.vue";
import Label from "@/Components/UI/Label.vue";
import Tabs from "@/Components/UI/Tabs.vue";
import Table from "@/Components/UI/Table.vue";
import TableHeader from "@/Components/UI/TableHeader.vue";
import TableBody from "@/Components/UI/TableBody.vue";
import TableRow from "@/Components/UI/TableRow.vue";
import TableHead from "@/Components/UI/TableHead.vue";
import TableCell from "@/Components/UI/TableCell.vue";
import {
    ArtikelSkeleton,
    ImagePlaceholder,
    IsfLink,
    MonthCalendar,
    RatingSummary,
    ReviewCard,
    SectionHeading,
    StarRating,
} from "@/Components/Isf";
import {
    getTokohUlasan,
    submitTokohUlasan,
} from "@/api/services/isfTokohReviewService";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getTokoh } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();

// = sectionPad di BerandaView: section berbingkai perlu padding kecil di mobile, desktop = cellPad Beranda.
const sectionPad = "px-3 lg:px-4 xl:px-[30px]";

const { data, status, load } = useIsfContent(() => getTokoh(route.params.slug));

const isFollowing = ref(false);
function toggleFollow() {
    isFollowing.value = !isFollowing.value;
}

const tokoh = computed(() => data.value);
const skor = computed(() => data.value?.skorSorotan);

/* ── Indeks Tokoh: Rating & Ulasan (GET/POST /api/isf/tokoh/{slug}/ulasan) ──────────────
 * Tab "Karya" = Jejak Kebijakan (dipindah dari bawah halaman). "Tingkat Kesukaan" = bintang 4–5,
 * "Tidak Suka" = bintang 1–2 (bintang 3 hanya masuk skor & sebaran). Ulasan wajib login. */
const activeTab = ref("karya");
const ulasan = ref(null);
const ulasanStatus = ref("loading"); // loading | ready | error
const reviews = ref([]);
const reviewPage = ref({ currentPage: 1, lastPage: 1, total: 0 });
const loadingMore = ref(false);
let ulasanRequest = 0;

const summary = computed(
    () =>
        ulasan.value?.summary ?? {
            average: 0,
            total: 0,
            distribution: {},
            suka: 0,
            tidakSuka: 0,
        },
);
const viewer = computed(
    () => ulasan.value?.viewer ?? { authenticated: false, myReview: null },
);
const reviewTab = computed(() =>
    activeTab.value === "tidak-suka" ? "tidak-suka" : "suka",
);
const karyaCount = computed(() => tokoh.value?.policyMatrix?.rows?.length ?? 0);

const indeksStats = computed(() => [
    { key: "karya", label: "Karya", value: karyaCount.value },
    // "–" selama data ulasan belum termuat / gagal, agar tidak terbaca sebagai 0 ulasan.
    {
        key: "suka",
        label: "Tingkat Kesukaan",
        value: ulasan.value ? summary.value.suka : "–",
    },
    {
        key: "tidak-suka",
        label: "Tidak Suka",
        value: ulasan.value ? summary.value.tidakSuka : "–",
    },
]);

const indeksTabs = computed(() => [
    { key: "karya", label: `Karya (${karyaCount.value})` },
    { key: "suka", label: `Tingkat Kesukaan (${summary.value.suka})` },
    { key: "tidak-suka", label: `Tidak Suka (${summary.value.tidakSuka})` },
]);

function applyUlasan(payload, { append = false } = {}) {
    ulasan.value = payload;
    reviews.value = append
        ? [...reviews.value, ...(payload.reviews?.data ?? [])]
        : (payload.reviews?.data ?? []);
    reviewPage.value = {
        currentPage: payload.reviews?.currentPage ?? 1,
        lastPage: payload.reviews?.lastPage ?? 1,
        total: payload.reviews?.total ?? 0,
    };
}

async function loadUlasan({ page = 1, append = false } = {}) {
    const slug = route.params.slug;
    const requestId = ++ulasanRequest;
    if (append) loadingMore.value = true;
    else ulasanStatus.value = "loading";
    try {
        const payload = await getTokohUlasan(slug, {
            tab: reviewTab.value,
            page,
        });
        if (requestId !== ulasanRequest) return;
        applyUlasan(payload, { append });
        ulasanStatus.value = "ready";
    } catch {
        if (requestId !== ulasanRequest) return;
        if (!append) ulasanStatus.value = "error";
    } finally {
        if (requestId === ulasanRequest) loadingMore.value = false;
    }
}

// Daftar ulasan dimuat ulang saat pindah antara "Tingkat Kesukaan" dan "Tidak Suka".
watch(reviewTab, () => loadUlasan());

/* Form ulasan */
const form = ref({ rating: 0, body: "", isAnonymous: false });
const formErrors = ref({});
const formMessage = ref("");
const submitting = ref(false);

function resetForm(myReview) {
    form.value = {
        rating: myReview?.rating ?? 0,
        body: myReview?.body ?? "",
        isAnonymous: myReview?.isAnonymous ?? false,
    };
    formErrors.value = {};
}

// Isi form dengan ulasan milik user (kirim ulang = perbarui).
// Dipantau lewat updatedAt (bukan objeknya) agar "muat lebih banyak" / ganti tab tidak menghapus isian yang sedang diketik.
watch(
    () => viewer.value.myReview?.updatedAt ?? null,
    () => resetForm(viewer.value.myReview),
);

/* Setelah ulasan terkirim, form disembunyikan dan diganti kartu "Ulasan Anda" + tombol Edit.
 * Kartu ini selalu tampil (juga untuk bintang 3 yang tidak masuk tab mana pun). */
const editing = ref(false);
const showForm = computed(
    () =>
        viewer.value.authenticated && (!viewer.value.myReview || editing.value),
);
const myReviewCard = computed(() => {
    const mine = viewer.value.myReview;
    if (!mine) return null;
    const name = mine.isAnonymous
        ? "Anonim"
        : viewer.value.name || "Pengguna ISF";
    return {
        id: "mine",
        rating: mine.rating,
        body: mine.body,
        isAnonymous: mine.isAnonymous,
        isMine: true,
        author: {
            name,
            initials: mine.isAnonymous
                ? null
                : name
                      .split(/\s+/)
                      .filter(Boolean)
                      .slice(0, 2)
                      .map((p) => p[0].toUpperCase())
                      .join(""),
        },
        updatedAt: mine.updatedAt,
    };
});

function startEdit() {
    resetForm(viewer.value.myReview);
    formMessage.value = "";
    editing.value = true;
    // Fokus ke kolom ulasan setelah form muncul (aksesibilitas keyboard).
    nextTick(() => document.getElementById("ulasan-body")?.focus());
}

function cancelEdit() {
    editing.value = false;
    resetForm(viewer.value.myReview);
    nextTick(() => document.getElementById("ulasan-edit-btn")?.focus());
}

const loginHref = computed(
    () => `/login?redirect=${encodeURIComponent(route.fullPath)}`,
);

async function submitUlasan() {
    formErrors.value = {};
    formMessage.value = "";
    if (!form.value.rating) {
        formErrors.value = { rating: "Pilih rating bintang 1–5." };
        return;
    }
    if (form.value.body.trim().length < 3) {
        formErrors.value = { body: "Ulasan minimal 3 karakter." };
        return;
    }
    submitting.value = true;
    const requestId = ++ulasanRequest;
    try {
        const payload = await submitTokohUlasan(
            route.params.slug,
            {
                rating: form.value.rating,
                body: form.value.body.trim(),
                is_anonymous: form.value.isAnonymous,
            },
            { tab: reviewTab.value },
        );
        // Skor agregat, sebaran, jumlah per tab, dan daftar langsung diperbarui dari respons.
        if (requestId === ulasanRequest) applyUlasan(payload);
        ulasanStatus.value = "ready";
        editing.value = false;
        formMessage.value =
            "Terima kasih, ulasan Anda tersimpan dan skor sudah diperbarui.";
    } catch (error) {
        const status = error?.response?.status;
        if (status === 401 || status === 403) {
            ulasan.value = {
                ...ulasan.value,
                viewer: { authenticated: false, myReview: null },
            };
            formMessage.value =
                "Sesi login berakhir. Silakan login kembali untuk mengirim ulasan.";
        } else if (status === 422) {
            const errs = error.response.data?.errors ?? {};
            formErrors.value = Object.fromEntries(
                Object.entries(errs).map(([k, v]) => [
                    k === "is_anonymous" ? "isAnonymous" : k,
                    v[0],
                ]),
            );
        } else if (status === 429) {
            formMessage.value =
                "Terlalu banyak percobaan. Coba lagi dalam satu menit.";
        } else {
            formMessage.value =
                "Ulasan gagal dikirim. Periksa koneksi Anda lalu coba lagi.";
        }
    } finally {
        submitting.value = false;
    }
}

// Refetch when navigating between tokoh slugs (same component instance).
watch(
    () => route.params.slug,
    () => {
        isFollowing.value = false;
        load();
        activeTab.value = "karya";
        ulasan.value = null;
        reviews.value = [];
        formMessage.value = "";
        editing.value = false;
        resetForm(null);
        loadUlasan();
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="isf-mobile-gutter w-full px-4 py-8 sm:px-6 sm:py-10 md:px-8 xl:px-12"
    >
        <ArtikelSkeleton v-if="status === 'loading'" variant="tokoh" />

        <div v-else-if="status === 'error'" class="mx-auto max-w-3xl py-16">
            <ErrorMessage
                message="Profil tokoh gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="load"
            />
        </div>

        <div
            v-else-if="status === 'not-found' || !tokoh"
            class="py-16 text-center"
        >
            <h1 class="isf-headline font-headline text-2xl">
                Tokoh tidak ditemukan
            </h1>
            <p class="mt-2 text-neutral-600">
                Profil yang Anda cari tidak tersedia.
            </p>
            <router-link
                to="/beranda"
                class="mt-4 inline-block font-semibold text-isf-red-dark underline underline-offset-2 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
            >
                Kembali ke Beranda
            </router-link>
        </div>

        <div v-else class="space-y-10">
            <header>
                <div class="isf-col-head">
                    <p class="isf-label">Tokoh Pekan Ini</p>
                </div>
                <h1
                    class="isf-headline mt-4 font-headline text-[30px] leading-[1.1] sm:text-[40px]"
                >
                    Profil &amp; Tokoh Pekan Ini
                </h1>
            </header>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-start">
                <div class="isf-full-layer-sections lg:col-span-8">
                    <!-- Identity: section berbingkai dengan pita merah (= pilar Politik di Beranda) -->
                    <section
                        aria-labelledby="profil-tokoh"
                        class="isf-full-layer-section isf-full-layer-section--accent"
                    >
                        <SectionHeading
                            id="profil-tokoh"
                            title="Profil Tokoh"
                            size="lg"
                            :class="sectionPad"
                        />
                        <div
                            :class="[
                                'grid grid-cols-1 gap-6 py-5 sm:grid-cols-[10rem_minmax(0,1fr)] lg:py-6',
                                sectionPad,
                            ]"
                        >
                            <ImagePlaceholder
                                :label="tokoh.photoAlt"
                                :src="tokoh.photoUrl || ''"
                                aspect="portrait"
                            />
                            <div class="space-y-5">
                                <div>
                                    <h2
                                        class="isf-headline font-headline text-[28px] leading-[1.1]"
                                    >
                                        {{ tokoh.name }}
                                    </h2>
                                    <p
                                        class="mt-1 font-serif text-[17px] leading-[1.5] text-neutral-600"
                                    >
                                        {{ tokoh.position }}
                                    </p>
                                </div>

                                <!-- Statistik Indeks Tokoh (menggantikan RUU Diusulkan/Dibahas, Artikel Menyebut, Putusan Terkait).
                                     Field "stats" di CMS tidak diubah, hanya tidak ditampilkan lagi di sini. -->
                                <dl
                                    class="grid grid-cols-3 gap-4"
                                    data-testid="indeks-stats"
                                >
                                    <div
                                        v-for="stat in indeksStats"
                                        :key="stat.key"
                                        class="isf-hairline-top isf-hairline-bottom flex flex-col-reverse p-3"
                                    >
                                        <dt class="text-xs text-neutral-600">
                                            {{ stat.label }}
                                        </dt>
                                        <dd
                                            class="isf-headline font-headline text-2xl"
                                        >
                                            {{ stat.value }}
                                        </dd>
                                    </div>
                                </dl>

                                <!-- Skor Sorotan -->
                                <section
                                    aria-labelledby="skor-sorotan"
                                    class="isf-hairline-top isf-hairline-bottom space-y-2 py-4"
                                >
                                    <div
                                        class="flex items-baseline justify-between gap-3"
                                    >
                                        <h3 id="skor-sorotan" class="isf-label">
                                            Skor Sorotan
                                        </h3>
                                        <p
                                            class="font-headline text-3xl font-bold text-isf-red"
                                        >
                                            {{ skor.value }}%
                                        </p>
                                    </div>
                                    <div
                                        role="progressbar"
                                        aria-label="Skor Sorotan"
                                        :aria-valuenow="skor.value"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        class="h-2 w-full overflow-hidden bg-neutral-200"
                                    >
                                        <div
                                            class="h-full bg-isf-red"
                                            :style="{ width: `${skor.value}%` }"
                                        ></div>
                                    </div>
                                    <p class="text-sm text-neutral-700">
                                        {{ skor.explanation }}
                                    </p>
                                    <p class="text-xs text-neutral-600">
                                        Periode: {{ skor.period }}
                                    </p>
                                </section>

                                <!-- Ikuti Tokoh: non-functional local toggle (UI only) -->
                                <div class="flex flex-wrap items-center gap-3">
                                    <Button
                                        class="bg-isf-red text-white hover:bg-isf-red-dark"
                                        :aria-pressed="
                                            isFollowing ? 'true' : 'false'
                                        "
                                        aria-describedby="ikuti-info"
                                        @click="toggleFollow"
                                    >
                                        <IconCheck
                                            v-if="isFollowing"
                                            class="mr-2 h-4 w-4"
                                            aria-hidden="true"
                                        />
                                        {{
                                            isFollowing
                                                ? "Mengikuti"
                                                : "Ikuti Tokoh"
                                        }}
                                    </Button>
                                    <span
                                        id="ikuti-info"
                                        class="text-xs text-neutral-600"
                                    >
                                        Pratinjau UI — belum tersimpan.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Indeks Tokoh: Rating & Ulasan (gaya Play Store) -->
                    <section
                        aria-labelledby="indeks-tokoh"
                        class="isf-full-layer-section isf-full-layer-section--cream"
                    >
                        <SectionHeading
                            id="indeks-tokoh"
                            title="Indeks Tokoh: Rating & Ulasan"
                            size="lg"
                            :class="sectionPad"
                        />
                        <div :class="['space-y-6 py-5 lg:py-6', sectionPad]">

                        <div
                            v-if="ulasanStatus === 'error' && !ulasan"
                            class="space-y-2"
                        >
                            <ErrorMessage
                                message="Rating & ulasan gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                                retryable
                                @retry="loadUlasan()"
                            />
                        </div>
                        <RatingSummary
                            v-else
                            :class="{
                                'opacity-60':
                                    ulasanStatus === 'loading' && !ulasan,
                            }"
                            :average="summary.average"
                            :total="summary.total"
                            :distribution="summary.distribution"
                            :aria-busy="
                                ulasanStatus === 'loading' ? 'true' : 'false'
                            "
                        />

                        <Tabs
                            v-model="activeTab"
                            :tabs="indeksTabs"
                            variant="pills"
                        >
                            <template #icon-karya
                                ><IconFileText
                                    class="h-4 w-4"
                                    aria-hidden="true"
                            /></template>
                            <template #icon-suka
                                ><IconThumbUp
                                    class="h-4 w-4"
                                    aria-hidden="true"
                            /></template>
                            <template #icon-tidak-suka
                                ><IconThumbDown
                                    class="h-4 w-4"
                                    aria-hidden="true"
                            /></template>
                        </Tabs>

                        <!-- Tab Karya: Policy Matrix / Jejak Kebijakan (dipindah ke sini, isi tidak diubah) -->
                        <section
                            v-if="activeTab === 'karya'"
                            aria-labelledby="jejak-kebijakan"
                            class="space-y-4"
                        >
                            <SectionHeading
                                id="jejak-kebijakan"
                                title="Jejak Kebijakan (Policy Matrix)"
                            />
                            <div class="overflow-x-auto">
                                <Table
                                    class="isf-stack-table min-w-[48rem] border border-neutral-200"
                                >
                                    <caption class="sr-only">
                                        Keterlibatan
                                        {{
                                            tokoh.name
                                        }}
                                        per kebijakan/RUU dan tahap
                                    </caption>
                                    <TableHeader class="bg-neutral-50">
                                        <TableRow>
                                            <TableHead
                                                scope="col"
                                                class="text-neutral-700"
                                                >Kebijakan/RUU</TableHead
                                            >
                                            <TableHead
                                                scope="col"
                                                class="text-neutral-700"
                                                >Tahap</TableHead
                                            >
                                            <TableHead
                                                v-for="column in tokoh
                                                    .policyMatrix.columns"
                                                :key="column"
                                                scope="col"
                                                class="break-normal text-center text-neutral-700"
                                            >
                                                <!-- allow a line break after each slash ("Menolak/<wbr>Mempertanyakan") -->
                                                <template
                                                    v-for="(
                                                        part, p
                                                    ) in column.split('/')"
                                                    :key="p"
                                                    ><template v-if="p"
                                                        >/<wbr /></template
                                                    >{{ part }}</template
                                                >
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="row in tokoh.policyMatrix
                                                .rows"
                                            :key="row.kebijakan"
                                        >
                                            <TableHead
                                                scope="row"
                                                class="h-auto py-3 font-semibold text-neutral-900"
                                            >
                                                {{ row.kebijakan }}
                                            </TableHead>
                                            <TableCell
                                                class="py-3 text-neutral-700"
                                                data-label="Tahap"
                                                >{{ row.tahap }}</TableCell
                                            >
                                            <TableCell
                                                v-for="(
                                                    involved, index
                                                ) in row.involvement"
                                                :key="index"
                                                class="isf-stack-inline py-3 text-center"
                                                :data-label="
                                                    tokoh.policyMatrix.columns[
                                                        index
                                                    ]
                                                "
                                            >
                                                <template v-if="involved">
                                                    <IconCheck
                                                        class="mx-auto h-5 w-5 text-isf-red"
                                                        aria-hidden="true"
                                                    />
                                                    <span class="sr-only"
                                                        >Ya</span
                                                    >
                                                </template>
                                                <template v-else>
                                                    <span
                                                        class="text-neutral-400"
                                                        aria-hidden="true"
                                                        >–</span
                                                    >
                                                    <span class="sr-only"
                                                        >Tidak</span
                                                    >
                                                </template>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                            <p class="text-xs text-neutral-600">
                                Label kolom mengikuti mock; perlu konfirmasi.
                            </p>
                        </section>

                        <!-- Tab Tingkat Kesukaan (bintang 4–5) / Tidak Suka (bintang 1–2) -->
                        <section
                            v-else
                            :aria-label="
                                activeTab === 'suka'
                                    ? 'Ulasan Tingkat Kesukaan'
                                    : 'Ulasan Tidak Suka'
                            "
                            :aria-busy="
                                ulasanStatus === 'loading' ? 'true' : 'false'
                            "
                            data-testid="ulasan-list"
                        >
                            <p
                                v-if="ulasanStatus === 'loading'"
                                class="py-6 text-sm text-neutral-500"
                            >
                                Memuat ulasan…
                            </p>
                            <p
                                v-else-if="ulasanStatus === 'error'"
                                class="py-6 text-sm text-neutral-600"
                            >
                                Ulasan gagal dimuat.
                                <button
                                    type="button"
                                    class="font-semibold text-isf-red-dark underline underline-offset-2"
                                    @click="loadUlasan()"
                                >
                                    Coba lagi
                                </button>
                            </p>
                            <p
                                v-else-if="!reviews.length"
                                class="py-6 text-sm text-neutral-600"
                            >
                                {{
                                    activeTab === "suka"
                                        ? "Belum ada ulasan positif (bintang 4–5)."
                                        : "Belum ada kritik atau catatan evaluasi (bintang 1–2)."
                                }}
                            </p>
                            <template v-else>
                                <div class="divide-y divide-neutral-200">
                                    <ReviewCard
                                        v-for="review in reviews"
                                        :key="review.id"
                                        :review="review"
                                    >
                                        <template v-if="review.isMine" #action>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                aria-label="Edit ulasan Anda"
                                                data-testid="ulasan-edit-list"
                                                @click="startEdit"
                                            >
                                                <IconPencil
                                                    class="mr-1.5 h-4 w-4"
                                                    aria-hidden="true"
                                                />
                                                Edit
                                            </Button>
                                        </template>
                                    </ReviewCard>
                                </div>
                                <Button
                                    v-if="
                                        reviewPage.currentPage <
                                        reviewPage.lastPage
                                    "
                                    variant="outline"
                                    class="mt-2"
                                    :disabled="loadingMore"
                                    @click="
                                        loadUlasan({
                                            page: reviewPage.currentPage + 1,
                                            append: true,
                                        })
                                    "
                                >
                                    {{
                                        loadingMore
                                            ? "Memuat…"
                                            : "Muat ulasan lainnya"
                                    }}
                                </Button>
                            </template>
                        </section>

                        <!-- Form Beri Ulasan: wajib login akun ISF -->
                        <section
                            aria-labelledby="beri-ulasan"
                            class="space-y-4 border border-neutral-300 bg-white p-5"
                        >
                            <h3 id="beri-ulasan" class="isf-label">
                                {{
                                    editing
                                        ? "Edit Ulasan Anda"
                                        : viewer.myReview
                                          ? "Ulasan Anda"
                                          : "Beri Rating & Ulasan"
                                }}
                            </h3>

                            <!-- Sudah mengirim ulasan: form disembunyikan, tampil kartu ulasan + tombol Edit -->
                            <div
                                v-if="
                                    viewer.authenticated &&
                                    myReviewCard &&
                                    !editing
                                "
                                data-testid="ulasan-mine"
                            >
                                <ReviewCard :review="myReviewCard">
                                    <template #action>
                                        <Button
                                            id="ulasan-edit-btn"
                                            variant="outline"
                                            size="sm"
                                            aria-label="Edit ulasan Anda"
                                            data-testid="ulasan-edit"
                                            @click="startEdit"
                                        >
                                            <IconPencil
                                                class="mr-1.5 h-4 w-4"
                                                aria-hidden="true"
                                            />
                                            Edit
                                        </Button>
                                    </template>
                                </ReviewCard>
                                <p
                                    v-if="formMessage"
                                    class="text-sm text-emerald-700"
                                    role="status"
                                >
                                    {{ formMessage }}
                                </p>
                            </div>

                            <div
                                v-if="!viewer.authenticated"
                                class="flex flex-wrap items-center gap-3"
                                data-testid="ulasan-login"
                            >
                                <IconLock
                                    class="h-5 w-5 text-neutral-500"
                                    aria-hidden="true"
                                />
                                <p class="text-sm text-neutral-700">
                                    Login dengan akun ISF untuk memberi rating
                                    dan ulasan.
                                </p>
                                <!-- /login adalah halaman Inertia (bukan rute SPA) → <a href> biasa -->
                                <a
                                    :href="loginHref"
                                    class="inline-flex items-center rounded-md bg-isf-red px-4 py-2 font-space-grotesk text-xs font-bold uppercase tracking-[0.1em] text-white hover:bg-isf-red-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                                    >Login untuk memberi ulasan</a
                                >
                                <p
                                    v-if="formMessage"
                                    class="w-full text-sm text-neutral-700"
                                    role="status"
                                >
                                    {{ formMessage }}
                                </p>
                            </div>

                            <form
                                v-else-if="showForm"
                                class="space-y-4"
                                novalidate
                                data-testid="ulasan-form"
                                @submit.prevent="submitUlasan"
                            >
                                <div class="space-y-1">
                                    <p
                                        id="ulasan-rating-label"
                                        class="text-sm font-semibold text-neutral-900"
                                    >
                                        Rating
                                    </p>
                                    <StarRating
                                        v-model="form.rating"
                                        interactive
                                        size="lg"
                                        label="Rating bintang untuk tokoh"
                                        :describedby="
                                            formErrors.rating
                                                ? 'ulasan-rating-error'
                                                : undefined
                                        "
                                    />
                                    <p
                                        v-if="formErrors.rating"
                                        id="ulasan-rating-error"
                                        class="text-sm text-isf-red-dark"
                                    >
                                        {{ formErrors.rating }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        for="ulasan-body"
                                        class="text-sm font-semibold text-neutral-900"
                                        >Ulasan</Label
                                    >
                                    <textarea
                                        id="ulasan-body"
                                        v-model="form.body"
                                        rows="4"
                                        maxlength="2000"
                                        placeholder="Tulis pendapat Anda tentang kinerja tokoh ini…"
                                        :aria-invalid="
                                            formErrors.body ? 'true' : 'false'
                                        "
                                        :aria-describedby="
                                            formErrors.body
                                                ? 'ulasan-body-error'
                                                : 'ulasan-body-count'
                                        "
                                        class="w-full rounded-md border border-neutral-300 bg-white p-3 font-serif text-[15px] text-neutral-900 placeholder:text-neutral-400 focus:border-isf-red focus:outline-none focus:ring-2 focus:ring-isf-red/20"
                                    ></textarea>
                                    <p
                                        v-if="formErrors.body"
                                        id="ulasan-body-error"
                                        class="text-sm text-isf-red-dark"
                                    >
                                        {{ formErrors.body }}
                                    </p>
                                    <p
                                        v-else
                                        id="ulasan-body-count"
                                        class="text-xs text-neutral-500"
                                    >
                                        {{ form.body.length }}/2000
                                    </p>
                                </div>

                                <label
                                    class="flex items-start gap-2 text-sm text-neutral-800"
                                >
                                    <input
                                        v-model="form.isAnonymous"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 accent-isf-red"
                                        data-testid="ulasan-anonim"
                                    />
                                    <span>
                                        Kirim sebagai Anonim
                                        <span
                                            class="block text-xs text-neutral-500"
                                        >
                                            Nama Anda tidak ditampilkan pada
                                            kartu ulasan publik.
                                        </span>
                                    </span>
                                </label>

                                <div class="flex flex-wrap items-center gap-3">
                                    <Button
                                        type="submit"
                                        class="bg-isf-red text-white hover:bg-isf-red-dark"
                                        :disabled="submitting"
                                    >
                                        {{
                                            submitting
                                                ? "Mengirim…"
                                                : viewer.myReview
                                                  ? "Simpan Perubahan"
                                                  : "Kirim Ulasan"
                                        }}
                                    </Button>
                                    <Button
                                        v-if="editing"
                                        type="button"
                                        variant="outline"
                                        :disabled="submitting"
                                        data-testid="ulasan-batal"
                                        @click="cancelEdit"
                                    >
                                        Batal
                                    </Button>
                                    <p
                                        v-if="formMessage"
                                        class="text-sm text-neutral-700"
                                        role="status"
                                    >
                                        {{ formMessage }}
                                    </p>
                                </div>
                            </form>
                        </section>
                        </div>
                    </section>
                </div>

                <aside
                    class="isf-full-layer-sections lg:col-span-4"
                >
                    <!-- Related articles -->
                    <section
                        aria-labelledby="artikel-terkait"
                        class="isf-full-layer-section isf-full-layer-section--accent"
                    >
                        <SectionHeading
                            id="artikel-terkait"
                            title="Artikel Terkait"
                            size="lg"
                            :class="sectionPad"
                        />
                        <ul :class="['divide-y divide-neutral-200 pb-2', sectionPad]">
                            <li
                                v-for="article in tokoh.relatedArticles"
                                :key="article.title"
                                class="py-3"
                            >
                                <IsfLink
                                    :to="article.to"
                                    class="isf-headline block text-[16px] leading-snug text-neutral-900 hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                                >
                                    {{ article.title }}
                                </IsfLink>
                                <time
                                    :datetime="article.publishedAt"
                                    class="isf-byline mt-1 block"
                                >
                                    {{
                                        article.relativeLabel ||
                                        formatDate(article.publishedAt)
                                    }}
                                </time>
                            </li>
                        </ul>
                    </section>

                    <!-- Calendar -->
                    <section
                        aria-labelledby="kalender-agenda"
                        class="isf-full-layer-section isf-full-layer-section--cream"
                    >
                        <SectionHeading
                            id="kalender-agenda"
                            title="Kalender Agenda"
                            size="lg"
                            :class="sectionPad"
                        />
                        <div :class="['py-5', sectionPad]">
                        <MonthCalendar
                            :year="tokoh.calendar.year"
                            :month="tokoh.calendar.month"
                            :events="tokoh.calendar.events"
                        >
                            <template #footer>
                                <!-- Subscribe: disabled, UI only (no .ics export yet) -->
                                <div class="flex flex-wrap items-center gap-3">
                                    <Button
                                        disabled
                                        aria-describedby="subscribe-info"
                                        >Subscribe</Button
                                    >
                                    <span
                                        id="subscribe-info"
                                        class="text-xs text-neutral-600"
                                    >
                                        Ekspor kalender (.ics) segera hadir
                                    </span>
                                </div>
                            </template>
                        </MonthCalendar>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>
