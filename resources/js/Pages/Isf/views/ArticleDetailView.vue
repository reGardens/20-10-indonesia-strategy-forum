<script setup>
/**
 * Detail Artikel publik — /artikel/{kategori}/{slug} (konten: GET /api/isf/artikel/{slug}, hanya status terbit).
 * URL lain (/artikel/{slug}, kategori salah) diganti ke URL kanonis data.path tanpa menambah riwayat.
 *
 * Identitas visual mengikuti Beranda: section berbingkai (isf-full-layer-section) dengan varian
 * accent (pita merah) / cream / navy, SectionHeading size="lg", padding sectionPad, ikon Tabler.
 *
 * Naskah (body) adalah HTML dari RichTextEditor CMS dan dirender dengan v-html, sama seperti
 * MediaDetailView. Konten hanya bisa ditulis oleh admin CMS (tidak ada input publik).
 */
import { computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    IconArrowLeft,
    IconCalendarEvent,
    IconClock,
    IconUser,
} from "@tabler/icons-vue";
import {
    ArtikelSkeleton,
    FactBlock,
    ImagePlaceholder,
    IsfLink,
    IsfShareBar,
    SectionHeading,
} from "@/Components/Isf";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getArtikel } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();
const router = useRouter();

// = sectionPad di BerandaView / TokohProfileView.
const sectionPad = "px-3 lg:px-4 xl:px-[30px]";
const linkClass =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";

const { data, status, load } = useIsfContent(() =>
    getArtikel(route.params.slug),
);

const article = computed(() => data.value?.data ?? null);
const related = computed(() => data.value?.related ?? []);
const recent = computed(() => data.value?.recentArticles ?? []);
const factBlocks = computed(() => article.value?.factBlocks ?? []);
const hasBody = computed(() =>
    Boolean(
        String(article.value?.body ?? "")
            .replace(/<[^>]*>/g, "")
            .trim(),
    ),
);

// Kategori artikel {key, title, to: /artikel/{kategori}} untuk breadcrumb.
const kategori = computed(() => article.value?.kategori ?? null);

// Samakan URL dengan URL kanonis artikel (mis. /artikel/{slug} → /artikel/hukum/{slug}).
watch(
    () => article.value?.path,
    (path) => {
        if (path && path !== route.path) {
            router.replace({ path, query: route.query, hash: route.hash });
        }
    },
);

watch(
    () => article.value?.title,
    (title) => {
        if (title && typeof document !== "undefined") {
            document.title = `${title} · Indonesia Strategy Forum`;
        }
    },
);

// Muat ulang saat pindah antar artikel (instance komponen yang sama).
watch(
    () => route.params.slug,
    () => {
        load();
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="isf-mobile-gutter w-full px-4 py-8 sm:px-6 sm:py-10 md:px-8 xl:px-12"
    >
        <ArtikelSkeleton v-if="status === 'loading'" variant="artikel" />

        <div v-else-if="status === 'error'" class="mx-auto max-w-3xl py-16">
            <ErrorMessage
                message="Artikel gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="load"
            />
        </div>

        <div
            v-else-if="status === 'not-found' || !article"
            class="py-16 text-center"
        >
            <h1 class="isf-headline font-headline text-2xl">
                Artikel tidak ditemukan
            </h1>
            <p class="mt-2 text-neutral-600">
                Artikel yang Anda cari tidak tersedia atau belum diterbitkan.
            </p>
            <router-link
                to="/beranda"
                class="mt-4 inline-block rounded-sm font-semibold text-isf-red-dark underline underline-offset-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
            >
                Kembali ke Beranda
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
                    <li v-if="kategori">
                        <router-link
                            :to="kategori.to"
                            :class="[
                                linkClass,
                                'text-isf-red hover:text-isf-red-dark',
                            ]"
                        >
                            {{ kategori.title }}
                        </router-link>
                    </li>
                    <li v-else class="text-isf-red">
                        {{ article.category }}
                    </li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-start">
                <div class="isf-full-layer-sections min-w-0 lg:col-span-8">
                    <!-- Artikel: section berbingkai dengan pita merah (= pilar Politik di Beranda) -->
                    <article
                        aria-labelledby="judul-artikel"
                        class="isf-full-layer-section isf-full-layer-section--accent"
                    >
                        <SectionHeading
                            :title="kategori?.title || article.category"
                            size="lg"
                            :class="sectionPad"
                        >
                            <template v-if="article.readingMinutes" #action>
                                <span
                                    class="isf-byline inline-flex items-center gap-1"
                                >
                                    <IconClock
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    {{ article.readingMinutes }} menit baca
                                </span>
                            </template>
                        </SectionHeading>

                        <header :class="['py-5 lg:py-6', sectionPad]">
                            <span class="isf-kicker">{{ article.category }}</span>
                            <h1
                                id="judul-artikel"
                                class="isf-headline mt-1.5 font-headline text-[28px] leading-[1.12] sm:text-[36px] xl:text-[42px]"
                            >
                                {{ article.title }}
                            </h1>
                            <p
                                v-if="article.summary"
                                class="mt-4 font-serif text-[18px] leading-[1.5] text-neutral-600 sm:text-[20px]"
                            >
                                {{ article.summary }}
                            </p>

                            <div
                                class="mt-5 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-y border-neutral-300 py-3"
                            >
                                <p
                                    class="isf-byline flex flex-wrap items-center gap-x-3 gap-y-1"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <IconUser
                                            class="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                        <span
                                            ><strong>{{ article.author.name }}</strong
                                            ><template v-if="article.author.role"
                                                >, {{ article.author.role }}</template
                                            ></span
                                        >
                                    </span>
                                    <time
                                        v-if="article.publishedAt"
                                        :datetime="article.publishedAt"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <IconCalendarEvent
                                            class="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                        {{ formatDate(article.publishedAt) }}
                                    </time>
                                </p>

                                <IsfShareBar
                                    :title="article.title"
                                    label="Bagikan artikel"
                                />
                            </div>
                        </header>

                        <figure v-if="article.imageUrl" class="m-0">
                            <ImagePlaceholder
                                :label="article.imageAlt"
                                :src="article.imageUrl"
                                aspect="news"
                                priority
                            />
                        </figure>

                        <div :class="['py-6 lg:py-8', sectionPad]">
                            <div
                                v-if="hasBody"
                                class="isf-article-body max-w-[46rem]"
                                data-testid="artikel-body"
                                v-html="article.body"
                            ></div>
                            <p
                                v-else
                                class="font-serif text-[17px] leading-[1.6] text-neutral-600"
                            >
                                Naskah lengkap artikel ini belum tersedia.
                            </p>
                        </div>
                    </article>

                    <!-- Fakta Kunci: krem (= baris Sorotan / Indeks Tokoh) -->
                    <section
                        v-if="factBlocks.length"
                        aria-labelledby="fakta-kunci"
                        class="isf-full-layer-section isf-full-layer-section--cream"
                    >
                        <SectionHeading
                            id="fakta-kunci"
                            title="Fakta Kunci"
                            size="lg"
                            :class="sectionPad"
                        />
                        <div
                            :class="[
                                'grid grid-cols-1 gap-4 py-5 sm:grid-cols-2 lg:py-6',
                                sectionPad,
                            ]"
                        >
                            <FactBlock
                                v-for="(fact, idx) in factBlocks"
                                :key="idx"
                                :claim="fact.claim"
                                :source-url="fact.sourceUrl"
                                :source-title="fact.sourceTitle"
                                :source-date="fact.sourceDate"
                                class="border border-neutral-300 p-4"
                            />
                        </div>
                    </section>

                    <!-- Artikel Terkait: navy (= pilar Hukum di Beranda) -->
                    <section
                        v-if="related.length"
                        aria-labelledby="artikel-terkait"
                        class="isf-full-layer-section isf-full-layer-section--navy"
                    >
                        <SectionHeading
                            id="artikel-terkait"
                            :title="`Artikel Terkait`"
                            size="lg"
                            :class="sectionPad"
                        />
                        <div class="grid grid-cols-1 sm:grid-cols-3">
                            <IsfLink
                                v-for="item in related"
                                :key="item.slug"
                                :to="item.to"
                                :class="[
                                    linkClass,
                                    'group block min-w-0 border-b border-neutral-300 px-3 py-4 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0 lg:px-4 lg:py-5 xl:px-[30px]',
                                ]"
                            >
                                <ImagePlaceholder
                                    :label="item.imageAlt"
                                    :src="item.imageUrl || ''"
                                    aspect="photo"
                                    bare
                                />
                                <span class="isf-kicker mt-3">{{
                                    item.category
                                }}</span>
                                <h3
                                    class="isf-headline mt-1 text-[17px] leading-[1.2] group-hover:text-isf-red xl:text-[19px]"
                                >
                                    {{ item.title }}
                                </h3>
                                <time
                                    v-if="item.publishedAt"
                                    :datetime="item.publishedAt"
                                    class="isf-byline mt-2 block"
                                    >{{ formatDate(item.publishedAt) }}</time
                                >
                            </IsfLink>
                        </div>
                    </section>
                </div>

                <aside class="isf-full-layer-sections min-w-0 lg:col-span-4">
                    <!-- Terbaru -->
                    <section
                        aria-labelledby="artikel-terbaru"
                        class="isf-full-layer-section isf-full-layer-section--accent"
                    >
                        <SectionHeading
                            id="artikel-terbaru"
                            title="Terbaru"
                            size="lg"
                            :class="sectionPad"
                        />
                        <ul
                            v-if="recent.length"
                            :class="[
                                'divide-y divide-neutral-200 pb-2',
                                sectionPad,
                            ]"
                        >
                            <li v-for="item in recent" :key="item.slug">
                                <IsfLink
                                    :to="item.to"
                                    :class="[
                                        linkClass,
                                        'group grid grid-cols-[minmax(0,1fr)_5.5rem] items-start gap-3 py-3',
                                    ]"
                                >
                                    <span class="min-w-0">
                                        <span class="isf-kicker">{{
                                            item.category
                                        }}</span>
                                        <span
                                            class="isf-headline mt-1 block text-[16px] leading-snug group-hover:text-isf-red"
                                            >{{ item.title }}</span
                                        >
                                        <time
                                            v-if="item.publishedAt"
                                            :datetime="item.publishedAt"
                                            class="isf-byline mt-1 block"
                                            >{{
                                                formatDate(item.publishedAt)
                                            }}</time
                                        >
                                    </span>
                                    <ImagePlaceholder
                                        :label="item.imageAlt"
                                        :src="item.imageUrl || ''"
                                        aspect="square"
                                        bare
                                    />
                                </IsfLink>
                            </li>
                        </ul>
                        <p
                            v-else
                            :class="[
                                'py-5 text-sm text-neutral-600',
                                sectionPad,
                            ]"
                        >
                            Belum ada artikel lain.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>
