<script setup>
/**
 * Anatomi Isu Mingguan (Cetak Biru ISF, bagian 05).
 * One issue card with 7 standardized, numbered frameworks (01–07).
 * Content: GET /api/isf/isu/{slug} (published CMS content only; 404 → not found state).
 * Not built on purpose (not assigned): share/save/download/cite buttons and ad/promo block.
 */
import { computed, watch } from "vue";
import { useRoute } from "vue-router";
import Accordion from "@/Components/UI/Accordion.vue";
import Badge from "@/Components/UI/Badge.vue";
import Card from "@/Components/UI/Card.vue";
import CardContent from "@/Components/UI/CardContent.vue";
import CardHeader from "@/Components/UI/CardHeader.vue";
import Table from "@/Components/UI/Table.vue";
import TableHeader from "@/Components/UI/TableHeader.vue";
import TableBody from "@/Components/UI/TableBody.vue";
import TableRow from "@/Components/UI/TableRow.vue";
import TableHead from "@/Components/UI/TableHead.vue";
import TableCell from "@/Components/UI/TableCell.vue";
import {
    CategoryLabel,
    FactBlock,
    ImagePlaceholder,
    IsfLink,
    SectionHeading,
    SimpleBarChart,
} from "@/Components/Isf";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getIsu } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();

const { data, status, load } = useIsfContent(() => getIsu(route.params.slug));
// Refetch when navigating between isu slugs (same component instance).
watch(() => route.params.slug, load, { immediate: true });

const isu = computed(() => data.value);
const fw = computed(() => data.value?.frameworks);

const linkClass =
    "rounded-sm font-semibold text-isf-red-dark underline underline-offset-2 hover:text-isf-navy focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";
// Headline-style story links (related / populer lists).
const headlineLinkClass =
    "isf-headline text-[16px] leading-snug text-neutral-900 hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";
</script>

<template>
    <div class="isf-mobile-gutter mx-auto max-w-6xl px-4 py-8 sm:py-10">
        <div v-if="status === 'loading'" class="py-16">
            <LoadingIndicator text="Memuat anatomi isu…" />
        </div>

        <div v-else-if="status === 'error'" class="mx-auto max-w-3xl py-16">
            <ErrorMessage
                message="Anatomi isu gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="load"
            />
        </div>

        <div
            v-else-if="status === 'not-found' || !isu"
            class="py-16 text-center"
        >
            <h1 class="isf-headline font-headline text-2xl">
                Isu tidak ditemukan
            </h1>
            <p class="mt-2 text-neutral-600">
                Anatomi isu yang Anda cari tidak tersedia.
            </p>
            <router-link
                to="/beranda"
                :class="['mt-4 inline-block', linkClass]"
            >
                Kembali ke Beranda
            </router-link>
        </div>

        <div v-else class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <div class="space-y-10 lg:col-span-8">
                <article aria-labelledby="isu-title">
                    <Card class="border-0 bg-transparent shadow-none">
                        <CardHeader
                            class="isf-hairline-bottom space-y-3 px-0 pb-6 pt-0"
                        >
                            <div class="isf-col-head">
                                <p class="isf-label">Anatomi Isu Mingguan</p>
                            </div>
                            <div
                                class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-2"
                            >
                                <CategoryLabel :category="isu.category" />
                                <span class="isf-byline">
                                    Pekan
                                    <time :datetime="isu.publishedAt">{{
                                        formatDate(isu.publishedAt)
                                    }}</time>
                                </span>
                            </div>
                            <h1
                                id="isu-title"
                                class="isf-headline font-headline text-[30px] leading-[1.1] sm:text-[40px]"
                            >
                                {{ isu.title }}
                            </h1>
                            <p
                                class="font-serif text-[17px] leading-[1.55] text-neutral-600 sm:text-[19px]"
                            >
                                {{ isu.summary }}
                            </p>
                            <p class="isf-byline">
                                Oleh
                                <strong>{{ isu.author }}</strong>
                            </p>
                        </CardHeader>

                        <CardContent class="px-0 pb-0 pt-0">
                            <!-- Ringkasan Isu -->
                            <section
                                aria-labelledby="fw-01"
                                class="isf-hairline-top space-y-3 py-7"
                            >
                                <h2
                                    id="fw-01"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Ringkasan Isu
                                </h2>
                                <p
                                    class="font-body leading-relaxed text-neutral-800"
                                >
                                    {{ fw.ringkasan }}
                                </p>
                            </section>

                            <!-- Visual Kunci -->
                            <section
                                aria-labelledby="fw-02"
                                class="isf-hairline-top space-y-4 py-7"
                            >
                                <h2
                                    id="fw-02"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Visual Kunci
                                </h2>
                                <template v-if="fw.visualKunci">
                                    <ImagePlaceholder
                                        v-if="
                                            fw.visualKunci.imageAlt ||
                                            fw.visualKunci.imageUrl
                                        "
                                        :label="
                                            fw.visualKunci.imageAlt ||
                                            fw.visualKunci.title ||
                                            isu.title
                                        "
                                        :src="fw.visualKunci.imageUrl || ''"
                                    />
                                    <SimpleBarChart
                                        v-if="
                                            fw.visualKunci.title &&
                                            fw.visualKunci.data.length
                                        "
                                        :title="fw.visualKunci.title"
                                        :description="
                                            fw.visualKunci.description
                                        "
                                        :data="fw.visualKunci.data"
                                        :unit="fw.visualKunci.unit"
                                        :source-title="
                                            fw.visualKunci.sourceTitle
                                        "
                                        :source-url="fw.visualKunci.sourceUrl"
                                    />
                                </template>
                                <p v-else class="text-sm text-neutral-600">
                                    Visual kunci belum tersedia.
                                </p>
                            </section>

                            <!-- Fakta Kunci & Linimasa -->
                            <section
                                aria-labelledby="fw-03"
                                class="isf-hairline-top space-y-4 py-7"
                            >
                                <h2
                                    id="fw-03"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Fakta Kunci &amp; Linimasa
                                </h2>
                                <FactBlock
                                    v-for="fact in fw.faktaKunci"
                                    :key="fact.claim"
                                    :claim="fact.claim"
                                    :source-url="fact.sourceUrl"
                                    :source-title="fact.sourceTitle"
                                    :source-date="fact.sourceDate"
                                />
                                <h3 class="pt-2 font-semibold">Linimasa</h3>
                                <ol
                                    class="relative space-y-4 border-l-2 border-neutral-200 pl-5"
                                >
                                    <li
                                        v-for="item in fw.linimasa"
                                        :key="item.date"
                                        class="relative"
                                    >
                                        <span
                                            class="absolute -left-[1.6rem] top-1.5 h-3 w-3 rounded-full bg-isf-red"
                                            aria-hidden="true"
                                        ></span>
                                        <time
                                            :datetime="item.date"
                                            class="block text-sm font-semibold text-isf-red-dark"
                                        >
                                            {{ formatDate(item.date) }}
                                        </time>
                                        <p class="text-neutral-800">
                                            {{ item.label }}
                                        </p>
                                    </li>
                                </ol>
                            </section>

                            <!-- Aktor & Posisi -->
                            <section
                                aria-labelledby="fw-04"
                                class="isf-hairline-top space-y-4 py-7"
                            >
                                <h2
                                    id="fw-04"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Aktor &amp; Posisi
                                </h2>
                                <ul
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
                                >
                                    <li
                                        v-for="actor in fw.aktor"
                                        :key="actor.name"
                                        :class="
                                            actor.to
                                                ? ''
                                                : 'isf-editorial-card space-y-2 p-4'
                                        "
                                    >
                                        <IsfLink
                                            v-if="actor.to"
                                            :to="actor.to"
                                            class="isf-editorial-card block h-full space-y-2 p-4 hover:border-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2"
                                        >
                                            <p
                                                class="font-semibold text-neutral-900"
                                            >
                                                {{ actor.name }}
                                            </p>
                                            <p class="text-sm text-neutral-600">
                                                {{ actor.role }}
                                            </p>
                                            <Badge
                                                variant="outline"
                                                class="border-neutral-300"
                                                >{{ actor.stance }}</Badge
                                            >
                                        </IsfLink>
                                        <template v-else>
                                            <p
                                                class="font-semibold text-neutral-900"
                                            >
                                                {{ actor.name }}
                                            </p>
                                            <p class="text-sm text-neutral-600">
                                                {{ actor.role }}
                                            </p>
                                            <Badge
                                                variant="outline"
                                                class="border-neutral-300"
                                                >{{ actor.stance }}</Badge
                                            >
                                        </template>
                                    </li>
                                </ul>
                            </section>

                            <!-- Lensa GRC -->
                            <section
                                aria-labelledby="fw-05"
                                class="isf-hairline-top space-y-5 py-7"
                            >
                                <h2
                                    id="fw-05"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Lensa GRC
                                </h2>
                                <div class="space-y-2">
                                    <h3 class="font-semibold">Governance</h3>
                                    <p class="text-neutral-800">
                                        {{ fw.grc.governance }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3 class="font-semibold">Risk</h3>
                                        <Badge
                                            >Tingkat risiko:
                                            {{ fw.grc.risk.level }}</Badge
                                        >
                                    </div>
                                    <p class="text-neutral-800">
                                        {{ fw.grc.risk.description }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <h3 class="font-semibold">Compliance</h3>
                                    <div class="overflow-x-auto">
                                        <Table
                                            class="isf-stack-table min-w-[42rem] border border-neutral-200"
                                        >
                                            <caption class="sr-only">
                                                Daftar kewajiban kepatuhan dan
                                                tenggatnya
                                            </caption>
                                            <TableHeader class="bg-neutral-50">
                                                <TableRow>
                                                    <TableHead
                                                        scope="col"
                                                        class="text-neutral-700"
                                                        >Kewajiban</TableHead
                                                    >
                                                    <TableHead
                                                        scope="col"
                                                        class="text-neutral-700"
                                                        >Tenggat</TableHead
                                                    >
                                                    <TableHead
                                                        scope="col"
                                                        class="text-neutral-700"
                                                        >Regulasi</TableHead
                                                    >
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow
                                                    v-for="item in fw.grc
                                                        .compliance"
                                                    :key="item.kewajiban"
                                                >
                                                    <TableCell
                                                        class="py-3"
                                                        data-label="Kewajiban"
                                                        >{{
                                                            item.kewajiban
                                                        }}</TableCell
                                                    >
                                                    <TableCell
                                                        class="whitespace-nowrap py-3"
                                                        data-label="Tenggat"
                                                    >
                                                        <time
                                                            :datetime="
                                                                item.tenggat
                                                            "
                                                            >{{
                                                                formatDate(
                                                                    item.tenggat,
                                                                )
                                                            }}</time
                                                        >
                                                    </TableCell>
                                                    <TableCell
                                                        class="py-3"
                                                        data-label="Regulasi"
                                                        >{{
                                                            item.regulasi
                                                        }}</TableCell
                                                    >
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>
                            </section>

                            <!-- Dampak & Reaksi -->
                            <section
                                aria-labelledby="fw-06"
                                class="isf-hairline-top space-y-4 py-7"
                            >
                                <h2
                                    id="fw-06"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Dampak &amp; Reaksi
                                </h2>
                                <h3 class="font-semibold">Dampak sektoral</h3>
                                <dl class="space-y-3">
                                    <div
                                        v-for="item in fw.dampak.sektoral"
                                        :key="item.sektor"
                                    >
                                        <dt
                                            class="font-semibold text-neutral-900"
                                        >
                                            {{ item.sektor }}
                                        </dt>
                                        <dd class="text-neutral-700">
                                            {{ item.dampak }}
                                        </dd>
                                    </div>
                                </dl>
                                <h3 class="pt-2 font-semibold">Reaksi</h3>
                                <ul class="space-y-3">
                                    <li
                                        v-for="item in fw.dampak.reaksi"
                                        :key="item.pihak"
                                    >
                                        <span
                                            class="font-semibold text-neutral-900"
                                            >{{ item.pihak }}:</span
                                        >
                                        <span class="text-neutral-700">
                                            {{ item.reaksi }}</span
                                        >
                                    </li>
                                </ul>
                            </section>

                            <!-- Apa Selanjutnya -->
                            <section
                                aria-labelledby="fw-07"
                                class="isf-hairline-top space-y-4 py-7"
                            >
                                <h2
                                    id="fw-07"
                                    class="isf-headline font-headline text-[24px] leading-[1.15]"
                                >
                                    Apa Selanjutnya
                                </h2>
                                <ol class="space-y-3">
                                    <li
                                        v-for="item in fw.selanjutnya.tahapan"
                                        :key="item.date"
                                        class="flex gap-3"
                                    >
                                        <time
                                            :datetime="item.date"
                                            class="w-36 shrink-0 text-sm font-semibold text-isf-red-dark"
                                        >
                                            {{ formatDate(item.date) }}
                                        </time>
                                        <span class="text-neutral-800">{{
                                            item.label
                                        }}</span>
                                    </li>
                                </ol>
                                <div
                                    class="flex flex-wrap items-center gap-2 text-sm"
                                >
                                    <span class="font-semibold">Pantau:</span>
                                    <template
                                        v-for="item in fw.selanjutnya.pantau"
                                        :key="item.label"
                                    >
                                        <IsfLink
                                            v-if="item.to"
                                            :to="item.to"
                                            :class="linkClass"
                                        >
                                            {{ item.label }}
                                        </IsfLink>
                                        <span
                                            v-else
                                            class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-semibold text-neutral-800"
                                        >
                                            {{ item.label }}
                                        </span>
                                    </template>
                                </div>
                            </section>
                        </CardContent>
                    </Card>
                </article>

                <!-- FAQ (existing Accordion component) -->
                <section
                    aria-labelledby="faq-title"
                    class="isf-hairline-top space-y-4 pt-6"
                >
                    <SectionHeading
                        id="faq-title"
                        title="Pertanyaan yang Sering Diajukan"
                    />
                    <Accordion :items="isu.faq" />
                </section>

                <!-- Related articles -->
                <section
                    aria-labelledby="related-title"
                    class="isf-hairline-top space-y-4 pt-6"
                >
                    <SectionHeading
                        id="related-title"
                        title="Artikel Terkait"
                    />
                    <ul class="divide-y divide-neutral-200">
                        <li
                            v-for="item in isu.related"
                            :key="item.title"
                            class="py-3"
                        >
                            <IsfLink
                                :to="item.to || '#'"
                                :class="[headlineLinkClass, 'block']"
                            >
                                {{ item.title }}
                            </IsfLink>
                            <time
                                :datetime="item.publishedAt"
                                class="isf-byline mt-1 block"
                            >
                                {{ formatDate(item.publishedAt) }}
                            </time>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Sidebar -->
            <aside
                class="isf-hairline-top space-y-7 pt-6 lg:col-span-4 lg:border-l lg:pl-7"
            >
                <section aria-labelledby="populer-title" class="space-y-4">
                    <SectionHeading id="populer-title" title="Isu Terpopuler" />
                    <ol class="space-y-3">
                        <li
                            v-for="(item, index) in isu.populer"
                            :key="item.title"
                            class="flex gap-3"
                        >
                            <span
                                class="isf-headline shrink-0 font-headline text-2xl tabular-nums !text-isf-red"
                                aria-hidden="true"
                            >
                                {{ index + 1 }}
                            </span>
                            <IsfLink
                                v-if="item.to"
                                :to="item.to"
                                :class="[headlineLinkClass, 'min-w-0']"
                            >
                                {{ item.title }}
                            </IsfLink>
                            <span
                                v-else
                                class="isf-headline min-w-0 text-[16px] leading-snug"
                                >{{ item.title }}</span
                            >
                        </li>
                    </ol>
                </section>

                <section
                    aria-labelledby="metodologi-title"
                    class="isf-editorial-card isf-editorial-compact isf-editorial-rule space-y-3 bg-white p-5 text-neutral-900"
                >
                    <h2
                        id="metodologi-title"
                        class="isf-headline font-headline text-xl"
                    >
                        Cara isu ini dipilih
                    </h2>
                    <p class="text-sm text-neutral-700">
                        {{ isu.metodologi.text }}
                    </p>
                    <ul
                        class="list-disc space-y-1 pl-5 text-sm text-neutral-800"
                    >
                        <li
                            v-for="signal in isu.metodologi.signals"
                            :key="signal"
                        >
                            {{ signal }}
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>
