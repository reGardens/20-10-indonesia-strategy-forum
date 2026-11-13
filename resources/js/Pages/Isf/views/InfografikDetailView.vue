<script setup>
/**
 * Detail Infografik publik — /infografik/:slug (konten: GET /api/isf/infografik/{slug}, hanya status terbit).
 *
 * Identitas visual mengikuti Beranda / ArticleDetailView: section berbingkai (accent pita merah,
 * cream, navy), SectionHeading size="lg", padding sectionPad, ikon Tabler.
 * Gambar infografik adalah konten utama: tampil penuh (tidak dipotong) + tautan ukuran asli.
 */
import { computed, watch } from "vue";
import { useRoute } from "vue-router";
import {
    IconArrowLeft,
    IconCalendarEvent,
    IconChartBar,
    IconMaximize,
} from "@tabler/icons-vue";
import {
    ArtikelSkeleton,
    CategoryLabel,
    ImagePlaceholder,
    IsfLink,
    IsfShareBar,
    SectionHeading,
} from "@/Components/Isf";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getInfografikDetail } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();

// = sectionPad di BerandaView / TokohProfileView / ArticleDetailView.
const sectionPad = "px-3 lg:px-4 xl:px-[30px]";
const linkClass =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

const { data, status, load } = useIsfContent(() =>
    getInfografikDetail(route.params.slug),
);

const item = computed(() => data.value?.data ?? null);
const related = computed(() => data.value?.related ?? []);

watch(
    () => item.value?.title,
    (title) => {
        if (title && typeof document !== "undefined") {
            document.title = `${title} · Infografik · Indonesia Strategy Forum`;
        }
    },
);

// Muat ulang saat pindah antar infografik (instance komponen yang sama).
watch(() => route.params.slug, () => load(), { immediate: true });
</script>

<template>
    <div
        class="isf-mobile-gutter w-full px-4 py-8 sm:px-6 sm:py-10 md:px-8 xl:px-12"
    >
        <ArtikelSkeleton v-if="status === 'loading'" variant="infografik" />

        <div v-else-if="status === 'error'" class="mx-auto max-w-3xl py-16">
            <ErrorMessage
                message="Infografik gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="load"
            />
        </div>

        <div
            v-else-if="status === 'not-found' || !item"
            class="py-16 text-center"
        >
            <h1 class="isf-headline font-headline text-2xl">
                Infografik tidak ditemukan
            </h1>
            <p class="mt-2 text-neutral-600">
                Infografik yang Anda cari tidak tersedia atau belum
                diterbitkan.
            </p>
            <router-link
                to="/infografik"
                class="mt-4 inline-block rounded-sm font-semibold text-isf-red-dark underline underline-offset-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
            >
                Lihat semua infografik
            </router-link>
        </div>

        <div v-else class="space-y-10">
            <!-- Breadcrumb -->
            <nav aria-label="Breadcrumb" class="isf-col-head">
                <ol
                    class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 font-ui text-[12px] font-bold uppercase tracking-[0.12em]"
                >
                    <li>
                        <router-link
                            to="/beranda"
                            :class="[
                                linkClass,
                                'inline-flex items-center gap-1 text-neutral-600 hover:text-isf-red',
                            ]"
                        >
                            <IconArrowLeft class="h-4 w-4" aria-hidden="true" />
                            Beranda
                        </router-link>
                    </li>
                    <li aria-hidden="true" class="text-neutral-400">/</li>
                    <li>
                        <router-link
                            to="/infografik"
                            :class="[
                                linkClass,
                                'text-neutral-600 hover:text-isf-red',
                            ]"
                        >
                            Infografik
                        </router-link>
                    </li>
                    <li aria-hidden="true" class="text-neutral-400">/</li>
                    <li aria-current="page">
                        <CategoryLabel
                            v-if="item.category"
                            :category="item.category"
                            class="!text-[12px] !tracking-[0.12em]"
                        />
                        <span v-else class="text-isf-red">Detail</span>
                    </li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-start">
                <!-- Infografik: section berbingkai dengan pita merah (= pilar Politik di Beranda) -->
                <article
                    aria-labelledby="judul-infografik"
                    class="isf-full-layer-section isf-full-layer-section--accent min-w-0 lg:col-span-8"
                >
                    <SectionHeading title="Infografik" size="lg" :class="sectionPad">
                        <template #action>
                            <IconChartBar
                                class="h-5 w-5 text-isf-red"
                                aria-hidden="true"
                            />
                        </template>
                    </SectionHeading>

                    <header :class="['py-5 lg:py-6', sectionPad]">
                        <CategoryLabel
                            v-if="item.category"
                            :category="item.category"
                        />
                        <h1
                            id="judul-infografik"
                            class="isf-headline mt-1.5 font-headline text-[28px] leading-[1.12] sm:text-[36px] xl:text-[42px]"
                        >
                            {{ item.title }}
                        </h1>
                        <p
                            v-if="item.alt && item.alt !== item.title"
                            class="mt-4 font-serif text-[18px] leading-[1.5] text-neutral-600 sm:text-[20px]"
                        >
                            {{ item.alt }}
                        </p>

                        <div
                            class="mt-5 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-y border-neutral-300 py-3"
                        >
                            <p
                                class="isf-byline flex flex-wrap items-center gap-x-3 gap-y-1"
                            >
                                <span>
                                    <strong>Tim Data &amp; Riset ISF</strong>
                                </span>
                                <time
                                    v-if="item.publishedAt"
                                    :datetime="item.publishedAt"
                                    class="inline-flex items-center gap-1"
                                >
                                    <IconCalendarEvent
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    {{ formatDate(item.publishedAt) }}
                                </time>
                            </p>
                            <IsfShareBar
                                :title="item.title"
                                label="Bagikan infografik"
                            />
                        </div>
                    </header>

                    <!-- Gambar utuh (object-contain) di atas krem, agar teks pada infografik tidak terpotong -->
                    <figure class="m-0 border-t border-neutral-300 bg-[#fbf9f4]">
                        <img
                            v-if="item.imageUrl"
                            :src="item.imageUrl"
                            :alt="item.alt || item.title"
                            class="mx-auto block h-auto max-h-[80vh] w-auto max-w-full object-contain"
                            fetchpriority="high"
                            decoding="async"
                        />
                        <ImagePlaceholder
                            v-else
                            :label="item.alt || item.title"
                            aspect="news"
                        />
                        <figcaption
                            v-if="item.imageUrl"
                            :class="[
                                'flex flex-wrap items-center justify-end gap-3 border-t border-neutral-300 bg-white py-3',
                                sectionPad,
                            ]"
                        >
                            <IsfLink
                                :to="item.imageUrl"
                                :class="[
                                    linkClass,
                                    'inline-flex items-center gap-1.5 font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                                ]"
                            >
                                <IconMaximize class="h-4 w-4" aria-hidden="true" />
                                Lihat ukuran penuh
                            </IsfLink>
                        </figcaption>
                    </figure>
                </article>

                <!-- Infografik Lainnya: krem (= Infografik Terbaru di Beranda) -->
                <aside class="isf-full-layer-sections min-w-0 lg:col-span-4">
                    <section
                        aria-labelledby="infografik-lainnya"
                        class="isf-full-layer-section isf-full-layer-section--cream"
                    >
                        <SectionHeading
                            id="infografik-lainnya"
                            title="Infografik Lainnya"
                            size="lg"
                            :class="sectionPad"
                        >
                            <template #action>
                                <IsfLink
                                    to="/infografik"
                                    :class="[
                                        linkClass,
                                        'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                                    ]"
                                >
                                    Lihat semua
                                </IsfLink>
                            </template>
                        </SectionHeading>
                        <ul
                            v-if="related.length"
                            :class="['divide-y divide-neutral-200 pb-2', sectionPad]"
                        >
                            <li v-for="other in related" :key="other.slug">
                                <IsfLink
                                    :to="other.to"
                                    :class="[
                                        linkClass,
                                        'group grid grid-cols-[minmax(0,1fr)_5.5rem] items-start gap-3 py-3',
                                    ]"
                                >
                                    <span class="min-w-0">
                                        <CategoryLabel
                                            v-if="other.category"
                                            :category="other.category"
                                        />
                                        <span
                                            class="isf-headline mt-1 block text-[16px] leading-snug group-hover:text-isf-red"
                                            >{{ other.title }}</span
                                        >
                                        <time
                                            v-if="other.publishedAt"
                                            :datetime="other.publishedAt"
                                            class="isf-byline mt-1 block"
                                            >{{ formatDate(other.publishedAt) }}</time
                                        >
                                    </span>
                                    <ImagePlaceholder
                                        :label="other.alt || other.title"
                                        :src="other.imageUrl || ''"
                                        aspect="square"
                                        bare
                                    />
                                </IsfLink>
                            </li>
                        </ul>
                        <p
                            v-else
                            :class="['py-5 text-sm text-neutral-600', sectionPad]"
                        >
                            Belum ada infografik lain.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>
