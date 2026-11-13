<script setup>
/**
 * Halaman kategori artikel — /artikel/{kategori} (politik, hukum, kebijakan).
 * Konten: GET /api/isf/kategori/{kategori} (1 Sorotan Utama + 6 Artikel Terbaru; "Lihat lainnya" = 6 berikutnya).
 *
 * Identitas visual mengikuti Beranda: section berbingkai (accent pita merah / cream),
 * SectionHeading size="lg", padding sectionPad, grid bergaris, ikon Tabler.
 * Satu komponen dipakai ulang untuk semua kategori (route.params.kategori).
 */
import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { IconArrowLeft, IconClock, IconChevronDown } from "@tabler/icons-vue";
import {
    ArtikelSkeleton,
    ImagePlaceholder,
    IsfLink,
    SectionHeading,
} from "@/Components/Isf";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getKategori } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();

// = sectionPad di BerandaView / TokohProfileView / ArticleDetailView.
const sectionPad = "px-3 lg:px-4 xl:px-[30px]";
const linkClass =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

const rubrikKey = computed(() => route.params.kategori);
const { data, status, load } = useIsfContent(() => getKategori(rubrikKey.value));

// Artikel halaman berikutnya ("Muat lebih banyak") ditambahkan di bawah halaman pertama.
const extra = ref([]);
const extraMeta = ref(null);
const loadingMore = ref(false);
const moreError = ref(false);

const rubrik = computed(() => data.value?.kategori ?? null);
const items = computed(() => [...(data.value?.data ?? []), ...extra.value]);
const meta = computed(() => extraMeta.value ?? data.value?.meta ?? null);
const lead = computed(() => items.value[0] ?? null);
const rest = computed(() => items.value.slice(1));
const hasMore = computed(() => meta.value?.nextOffset != null);

async function loadMore() {
    if (!hasMore.value || loadingMore.value) return;
    const key = rubrikKey.value;
    loadingMore.value = true;
    moreError.value = false;
    try {
        const res = await getKategori(key, meta.value.nextOffset, 6);
        if (key !== rubrikKey.value) return;
        extra.value = [...extra.value, ...(res.data ?? [])];
        extraMeta.value = res.meta;
    } catch {
        moreError.value = true;
    } finally {
        loadingMore.value = false;
    }
}

watch(
    () => rubrik.value?.title,
    (title) => {
        if (title && typeof document !== "undefined") {
            document.title = `${title} · Indonesia Strategy Forum`;
        }
    },
);

// Pindah antar rubrik memakai instance komponen yang sama: reset lalu muat ulang.
watch(
    rubrikKey,
    () => {
        extra.value = [];
        extraMeta.value = null;
        moreError.value = false;
        load();
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="isf-mobile-gutter w-full px-4 py-8 sm:px-6 sm:py-10 md:px-8 xl:px-12"
    >
        <ArtikelSkeleton v-if="status === 'loading'" variant="rubrik" />

        <div v-else-if="status === 'error'" class="mx-auto max-w-3xl py-16">
            <ErrorMessage
                message="Kategori gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="load"
            />
        </div>

        <div
            v-else-if="status === 'not-found' || !rubrik"
            class="py-16 text-center"
        >
            <h1 class="isf-headline font-headline text-2xl">
                Kategori tidak ditemukan
            </h1>
            <router-link
                to="/beranda"
                class="mt-4 inline-block rounded-sm font-semibold text-isf-red-dark underline underline-offset-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
            >
                Kembali ke Beranda
            </router-link>
        </div>

        <div v-else class="space-y-10">
            <header>
                <nav aria-label="Breadcrumb" class="isf-col-head">
                    <ol
                        class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 font-ui text-[12px] font-bold uppercase tracking-[0.12em]"
                    >
                        <li>
                            <router-link
                        to="/beranda"
                        :class="[
                            linkClass,
                            'inline-flex items-center gap-1 font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-600 hover:text-isf-red',
                        ]"
                    >
                        <IconArrowLeft class="h-4 w-4" aria-hidden="true" />
                        Beranda
                            </router-link>
                        </li>
                        <li aria-hidden="true" class="text-neutral-400">/</li>
                        <li class="text-isf-red" aria-current="page">
                            {{ rubrik.label }}
                        </li>
                    </ol>
                </nav>
                <h1
                    class="isf-headline mt-4 font-headline text-[30px] leading-[1.1] sm:text-[40px]"
                >
                    {{ rubrik.title }}
                </h1>
                <p
                    class="mt-2 max-w-3xl font-serif text-[17px] leading-[1.5] text-neutral-600 sm:text-[19px]"
                >
                    {{ rubrik.description }}
                </p>
            </header>

            <p
                v-if="!lead"
                class="border border-dashed border-neutral-300 py-16 text-center text-sm text-neutral-600"
            >
                Belum ada artikel yang diterbitkan di kategori ini.
            </p>

            <div v-else class="isf-full-layer-sections">
                <!-- Sorotan Utama: pita merah (= pilar Politik di Beranda), 60/40 -->
                <section
                    aria-labelledby="rubrik-utama"
                    class="isf-full-layer-section isf-full-layer-section--accent"
                >
                    <SectionHeading
                        id="rubrik-utama"
                        title="Sorotan Utama"
                        size="lg"
                        :class="sectionPad"
                    />
                    <IsfLink
                        :to="lead.to"
                        :class="[
                            linkClass,
                            'group grid grid-cols-1 gap-5 py-5 lg:grid-cols-12 lg:gap-8 lg:py-6',
                            sectionPad,
                        ]"
                    >
                        <ImagePlaceholder
                            :label="lead.imageAlt"
                            :src="lead.imageUrl || ''"
                            aspect="news"
                            priority
                            class="lg:col-span-7"
                        />
                        <span class="min-w-0 lg:col-span-5">
                            <span class="isf-kicker">{{ lead.category }}</span>
                            <span
                                class="isf-headline mt-1.5 block text-[24px] leading-[1.15] group-hover:text-isf-red sm:text-[30px]"
                                >{{ lead.title }}</span
                            >
                            <span
                                v-if="lead.summary"
                                class="mt-3 block font-serif text-[16px] leading-[1.55] text-neutral-600 line-clamp-5"
                                >{{ lead.summary }}</span
                            >
                            <span
                                class="isf-byline mt-4 flex flex-wrap items-center gap-x-3 gap-y-1"
                            >
                                <strong>{{ lead.author }}</strong>
                                <time
                                    v-if="lead.publishedAt"
                                    :datetime="lead.publishedAt"
                                    >{{ formatDate(lead.publishedAt) }}</time
                                >
                                <span
                                    v-if="lead.readingMinutes"
                                    class="inline-flex items-center gap-1"
                                >
                                    <IconClock
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    {{ lead.readingMinutes }} menit baca
                                </span>
                            </span>
                        </span>
                    </IsfLink>
                </section>

                <!-- Artikel lainnya: krem, grid 4 kolom bergaris (= pilar Hukum di Beranda) -->
                <section
                    v-if="rest.length"
                    aria-labelledby="rubrik-terbaru"
                    class="isf-full-layer-section isf-full-layer-section--cream"
                >
                    <SectionHeading
                        id="rubrik-terbaru"
                        title="Artikel Terbaru"
                        size="lg"
                        :class="sectionPad"
                    >
                        <template v-if="meta" #eyebrow
                            >{{ meta.total }} artikel</template
                        >
                    </SectionHeading>
                    <!-- 2 kolom: gambar (ukuran kartu sebelumnya) di kiri, judul + ringkasan di kanan -->
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <IsfLink
                            v-for="item in rest"
                            :key="item.slug"
                            :to="item.to"
                            :class="[
                                linkClass,
                                'group grid min-w-0 grid-cols-[8rem_minmax(0,1fr)] items-start gap-4 border-b border-neutral-300 px-3 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-5 lg:px-4 lg:py-5 xl:px-[30px] lg:[&:nth-child(odd)]:border-r',
                            ]"
                        >
                            <ImagePlaceholder
                                :label="item.imageAlt"
                                :src="item.imageUrl || ''"
                                aspect="photo"
                                bare
                            />
                            <span class="min-w-0">
                            <span class="isf-kicker">{{
                                item.category
                            }}</span>
                            <span
                                class="isf-headline mt-1 block text-[17px] leading-[1.2] group-hover:text-isf-red xl:text-[19px]"
                                >{{ item.title }}</span
                            >
                            <span
                                v-if="item.summary"
                                class="mt-2 block font-serif text-[14px] leading-[1.45] text-neutral-600 line-clamp-3"
                                >{{ item.summary }}</span
                            >
                            <time
                                v-if="item.publishedAt"
                                :datetime="item.publishedAt"
                                class="isf-byline mt-3 block"
                                >{{ formatDate(item.publishedAt) }}</time
                            >
                            </span>
                        </IsfLink>
                    </div>
                    <div
                        v-if="hasMore || moreError"
                        :class="[
                            'flex flex-col items-center gap-2 py-5',
                            sectionPad,
                        ]"
                    >
                        <button
                            v-if="hasMore"
                            type="button"
                            :disabled="loadingMore"
                            :class="[
                                linkClass,
                                'inline-flex min-h-10 items-center gap-1.5 border border-neutral-300 bg-white px-4 py-2 font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-800 hover:border-isf-red hover:text-isf-red disabled:opacity-60',
                            ]"
                            @click="loadMore"
                        >
                            <IconChevronDown
                                class="h-4 w-4"
                                aria-hidden="true"
                            />
                            {{
                                loadingMore ? "Memuat…" : "Lihat lainnya"
                            }}
                        </button>
                        <p
                            v-if="moreError"
                            class="text-sm text-isf-red-dark"
                            role="alert"
                        >
                            Gagal memuat artikel berikutnya. Coba lagi.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
