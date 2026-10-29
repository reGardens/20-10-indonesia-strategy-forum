<script setup>
/**
 * Profil & Tokoh Pekan Ini (Cetak Biru ISF, bagian 04).
 * Content: GET /api/isf/tokoh/{slug} (published CMS content only; 404 → not found state).
 *
 * "Ikuti Tokoh": NON-FUNCTIONAL local toggle (UI preview only, aria-pressed);
 * nothing is saved and no backend is called.
 * "Subscribe" (calendar): rendered DISABLED with helper text (no .ics export yet).
 */
import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { IconCheck } from "@tabler/icons-vue";
import Button from "@/Components/UI/Button.vue";
import Card from "@/Components/UI/Card.vue";
import CardContent from "@/Components/UI/CardContent.vue";
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
    SectionHeading,
} from "@/Components/Isf";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { formatDate } from "@/Utils/formatters";
import { getTokoh } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const route = useRoute();

const { data, status, load } = useIsfContent(() => getTokoh(route.params.slug));

const isFollowing = ref(false);
function toggleFollow() {
    isFollowing.value = !isFollowing.value;
}

// Refetch when navigating between tokoh slugs (same component instance).
watch(
    () => route.params.slug,
    () => {
        isFollowing.value = false;
        load();
    },
    { immediate: true },
);

const tokoh = computed(() => data.value);
const skor = computed(() => data.value?.skorSorotan);
</script>

<template>
    <div class="isf-mobile-gutter mx-auto max-w-6xl px-4 py-8 sm:py-10">
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

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <div class="space-y-10 lg:col-span-8">
                    <!-- Identity card -->
                    <Card class="isf-editorial-card isf-editorial-compact">
                        <CardContent
                            class="grid grid-cols-1 gap-6 pt-6 sm:grid-cols-[10rem_minmax(0,1fr)]"
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

                                <dl
                                    class="grid grid-cols-2 gap-4 md:grid-cols-4"
                                >
                                    <div
                                        v-for="stat in tokoh.stats"
                                        :key="stat.label"
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
                        </CardContent>
                    </Card>

                    <!-- Policy Matrix / Jejak Kebijakan -->
                    <section
                        aria-labelledby="jejak-kebijakan"
                        class="isf-hairline-top space-y-4 pt-5"
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
                                            v-for="column in tokoh.policyMatrix
                                                .columns"
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
                                        v-for="row in tokoh.policyMatrix.rows"
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
                                                <span class="sr-only">Ya</span>
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
                </div>

                <aside
                    class="isf-hairline-top space-y-7 pt-6 lg:col-span-4 lg:border-l lg:pl-7"
                >
                    <!-- Related articles -->
                    <section
                        aria-labelledby="artikel-terkait"
                        class="isf-hairline-top space-y-4 pt-5"
                    >
                        <SectionHeading
                            id="artikel-terkait"
                            title="Artikel Terkait"
                        />
                        <ul class="divide-y divide-neutral-200">
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
                        class="isf-hairline-top space-y-4 pt-5"
                    >
                        <SectionHeading
                            id="kalender-agenda"
                            title="Kalender Agenda"
                        />
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
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>
