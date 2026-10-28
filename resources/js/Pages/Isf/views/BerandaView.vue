<script setup>
/** Public front page driven by GET /api/isf/beranda (Politico-style 3-column module). */
import { computed, onMounted } from "vue";
import {
    BerandaSkeleton,
    CategoryLabel,
    FactBlock,
    ImagePlaceholder,
    IsfBannerCarousel,
    IsfInsightSlider,
    IsfLink,
    SectionHeading,
    SimpleBarChart,
} from "@/Components/Isf";
import LoadingIndicator from "@/Pages/Cms/components/shared/LoadingIndicator.vue";
import ErrorMessage from "@/Pages/Cms/components/shared/ErrorMessage.vue";
import { BERANDA_BANNER_DUMMY } from "@/Pages/Isf/data/berandaBannerDummy";
import { BERANDA_INSIGHT_DUMMY } from "@/Pages/Isf/data/berandaInsightDummy";
import { formatDate } from "@/Utils/formatters";
import { getBeranda } from "@/api/services/isfPublicService";
import { useIsfContent } from "@/Composables/useIsfContent";

const { data, status, load } = useIsfContent(getBeranda);
onMounted(load);
const topBar = computed(() => data.value?.topBar);
const topBars = computed(() => {
    if (Array.isArray(data.value?.topBars) && data.value.topBars.length > 0) {
        return data.value.topBars;
    }
    return topBar.value?.announcement ? [topBar.value] : [];
});
const showTopBar = computed(() => topBars.value.length > 0);
const hero = computed(() => data.value?.hero);
const factBlocks = computed(() => data.value?.factBlocks || []);
const chart = computed(() => data.value?.chart);
const terkini = computed(() => data.value?.terkini || []);
const latestNews = computed(() => terkini.value.slice(0, 3));
const mediaSorotan = computed(() => {
    if (data.value?.mediaSorotan?.length) {
        return data.value.mediaSorotan.slice(0, 3);
    }
    return [
        {
            id: 1,
            title: 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan',
            imageUrl: '',
            mediaUrl: 'https://www.youtube.com',
            category: 'YouTube',
            publishedAt: '2026-10-20',
        },
        {
            id: 2,
            title: 'Sorotan Visual: Implikasi UU Energi Terbarukan bagi Sektor Industri',
            imageUrl: '',
            mediaUrl: 'https://www.instagram.com',
            category: 'Instagram',
            publishedAt: '2026-10-18',
        },
        {
            id: 3,
            title: 'Kupas Tuntas: RUU Perampasan Aset dan Efek Jeranya bagi Koruptor',
            imageUrl: '',
            mediaUrl: 'https://www.tiktok.com',
            category: 'TikTok',
            publishedAt: '2026-10-16',
        },
    ];
});

function isVerticalMedia(category) {
    const cat = (category || '').toLowerCase();
    return cat.includes('instagram') || cat.includes('tiktok') || cat.includes('reel') || cat.includes('short');
}
const infografik = computed(() => data.value?.infografik || []);
const pekanIni = computed(() => data.value?.pekanIni || { items: [] });
const bannerSlides = computed(() =>
    data.value?.banners?.length ? data.value.banners : BERANDA_BANNER_DUMMY,
);
const insightList = computed(() => {
    if (Array.isArray(data.value?.charts) && data.value.charts.length > 0) {
        return data.value.charts.map((c) => ({
            ...c,
            readMoreUrl: c.readMoreUrl || '/isu/kuhp-baru-pelaku-usaha',
        }));
    }
    if (data.value?.chart && data.value.chart.data?.length) {
        return [{
            ...data.value.chart,
            readMoreUrl: data.value.chart.readMoreUrl || '/isu/kuhp-baru-pelaku-usaha',
        }];
    }
    return BERANDA_INSIGHT_DUMMY;
});
const linkClass =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2";
// Inner padding of every module cell (Politico keeps text ~30px inside the rules).
const cellPad = "lg:px-4 xl:px-[30px]";
</script>

<template>
    <div>
        <BerandaSkeleton v-if="status === 'loading'" />
        <div
            v-else-if="status !== 'ready'"
            class="mx-auto max-w-3xl px-4 py-16"
        >
            <ErrorMessage
                message="Konten beranda gagal dimuat. Periksa koneksi Anda lalu coba lagi."
                retryable
                @retry="load"
            />
        </div>
        <template v-else>
            <div
                class="isf-mobile-gutter mx-auto w-full max-w-[1400px] px-4 pb-10 pt-2 lg:px-6 lg:pt-3"
            >
                <!-- Sorotan (CMS topBar): Running text marquee -->
                <div
                    v-if="showTopBar"
                    data-testid="sorotan-line"
                    class="relative flex items-center border-b border-neutral-300 py-2.5 overflow-hidden group select-none"
                >
                    <div class="z-10 flex items-center bg-[#fbf9f4] pr-3 shrink-0">
                        <span class="isf-label uppercase tracking-widest text-isf-red font-bold">
                            {{ topBars[0]?.badge || "Sorotan" }}
                        </span>
                        <span class="ml-3 h-3 w-px bg-neutral-300"></span>
                    </div>

                    <!-- Marquee Track -->
                    <div class="overflow-hidden flex-1 relative">
                        <div class="isf-marquee-track flex whitespace-nowrap gap-10 font-ui text-[13.5px] font-medium text-neutral-900">
                            <!-- Repeat items to form seamless scroll -->
                            <div
                                v-for="(item, idx) in [...topBars, ...topBars]"
                                :key="idx"
                                class="flex items-center gap-2.5 shrink-0"
                            >
                                <span
                                    v-if="item.badge && item.badge !== topBars[0]?.badge"
                                    class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold bg-neutral-200 text-neutral-800"
                                >
                                    {{ item.badge }}
                                </span>
                                <span>{{ item.announcement }}</span>
                                <span v-if="item.info" class="text-neutral-500 text-xs font-normal">({{ item.info }})</span>
                                <span class="text-neutral-400 mx-2 select-none">•</span>
                            </div>
                        </div>
                    </div>
                </div>

                <IsfBannerCarousel
                    :slides="bannerSlides"
                    label="Banner sorotan"
                    class="mt-4"
                />

                <!-- 3-column ruled module (23 / 54 / 23) -->
                <div class="mt-6 lg:border lg:border-neutral-300">
                    <div
                        class="grid grid-cols-1 lg:grid-cols-[minmax(0,23fr)_minmax(0,54fr)_minmax(0,23fr)]"
                    >
                        <!-- CENTER: Top News hero -->
                        <article
                            v-if="hero"
                            aria-labelledby="hero-title"
                            class="order-1 min-w-0 lg:order-2 lg:border-x lg:border-neutral-300"
                        >
                            <div :class="['isf-col-head', cellPad]">
                                <p class="isf-label">Top News</p>
                            </div>
                            <div :class="['pb-6 pt-5', cellPad]">
                                <div
                                    class="flex flex-wrap items-center gap-x-3 gap-y-1"
                                >
                                    <CategoryLabel
                                        :category="hero.category"
                                    /><time
                                        :datetime="hero.publishedAt"
                                        class="isf-byline"
                                        >{{
                                            formatDate(hero.publishedAt)
                                        }}</time
                                    >
                                </div>
                                <h1
                                    id="hero-title"
                                    class="isf-headline mt-2 text-[30px] leading-[1.1] sm:text-[36px] xl:text-[40px]"
                                >
                                    <IsfLink
                                        v-if="hero.to"
                                        :to="hero.to"
                                        :class="[
                                            linkClass,
                                            'hover:text-isf-red',
                                        ]"
                                        >{{ hero.title }}</IsfLink
                                    ><template v-else>{{
                                        hero.title
                                    }}</template>
                                </h1>
                                <p
                                    class="mt-3 font-serif text-[17px] leading-[1.5] text-neutral-600 xl:text-[19px]"
                                >
                                    {{ hero.summary }}
                                </p>
                                <p
                                    v-if="
                                        hero.author.name || hero.readingMinutes
                                    "
                                    class="isf-byline mt-3"
                                >
                                    <template v-if="hero.author.name"
                                        >Oleh
                                        <strong>{{ hero.author.name }}</strong
                                        ><template v-if="hero.author.role"
                                            >, {{ hero.author.role }}</template
                                        ></template
                                    ><template v-if="hero.readingMinutes">
                                        · {{ hero.readingMinutes }} menit
                                        baca</template
                                    >
                                </p>
                                <ImagePlaceholder
                                    :label="hero.infographicAlt"
                                    :src="hero.imageUrl || ''"
                                    aspect="photo"
                                    class="mt-4"
                                />
                                <div
                                    v-if="factBlocks.length"
                                    class="mt-6 grid grid-cols-1 border-t border-neutral-300 sm:grid-cols-2"
                                >
                                    <FactBlock
                                        v-for="fact in factBlocks"
                                        :key="fact.claim"
                                        :claim="fact.claim"
                                        :source-url="fact.sourceUrl"
                                        :source-title="fact.sourceTitle"
                                        :source-date="fact.sourceDate"
                                        class="border-b border-neutral-300 py-4 last:border-b-0 sm:border-b-0 sm:[&:nth-child(even)]:pl-4 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(odd)]:border-neutral-300 sm:[&:nth-child(odd)]:pr-4"
                                    />
                                </div>
                            </div>
                        </article>

                        <!-- RIGHT: image-on-top stories, no visible heading -->
                        <div
                            class="order-2 min-w-0 border-t border-neutral-300 lg:order-3 lg:border-t-0"
                        >
                            <div
                                :class="[
                                    'isf-col-head max-lg:!hidden',
                                    cellPad,
                                ]"
                                aria-hidden="true"
                            ></div>
                            <section
                                aria-labelledby="secondary-stories-title"
                                :class="cellPad"
                            >
                                <h2
                                    id="secondary-stories-title"
                                    class="sr-only"
                                >
                                    Media Sorotan
                                </h2>
                                <ol class="divide-y divide-neutral-300">
                                    <li
                                        v-for="item in mediaSorotan"
                                        :key="item.id || item.title"
                                        class="py-4 lg:py-5"
                                    >
                                        <!-- Thumbnail with Platform category badge (YouTube 16:9 / Instagram & TikTok vertical 9:16) -->
                                        <a
                                            :href="item.mediaUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            :class="[
                                                'group relative block overflow-hidden rounded-xs bg-neutral-100 border border-neutral-300 shadow-2xs',
                                                isVerticalMedia(item.category)
                                                    ? 'aspect-[9/16] max-w-[240px] mx-auto'
                                                    : 'aspect-video w-full'
                                            ]"
                                        >
                                            <ImagePlaceholder
                                                :label="item.title"
                                                :src="item.imageUrl || ''"
                                                :aspect="isVerticalMedia(item.category) ? 'reel' : 'video'"
                                                bare
                                                class="h-full w-full transition-transform duration-300 group-hover:scale-105"
                                            />
                                            <span class="absolute bottom-2 left-2 rounded bg-black/75 px-1.5 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider backdrop-blur-xs">
                                                {{ item.category || 'Video' }}
                                            </span>
                                        </a>

                                        <div class="mt-3">
                                            <h3 class="isf-headline text-[16px] leading-[1.25] xl:text-[18px]">
                                                <a
                                                    :href="item.mediaUrl"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="hover:text-isf-red transition-colors"
                                                >
                                                    {{ item.title }}
                                                </a>
                                            </h3>
                                            <time
                                                v-if="item.publishedAt"
                                                :datetime="item.publishedAt"
                                                class="isf-byline mt-1.5 block text-neutral-500"
                                            >
                                                {{ formatDate(item.publishedAt) }}
                                            </time>
                                        </div>
                                    </li>
                                </ol>
                                <p
                                    v-if="mediaSorotan.length === 0"
                                    class="py-4 text-sm text-neutral-600"
                                >
                                    Belum ada media sorotan.
                                </p>
                            </section>
                        </div>

                        <!-- LEFT: ISF Insight widget + Latest News -->
                        <aside
                            aria-labelledby="insight-title"
                            class="order-3 min-w-0 border-t border-neutral-300 lg:order-1 lg:border-t-0"
                        >
                            <IsfInsightSlider
                                :insights="insightList"
                                :cell-pad="cellPad"
                            />
                            <section
                                aria-labelledby="terkini-title"
                                class="border-t border-neutral-300"
                            >
                                <SectionHeading
                                    id="terkini-title"
                                    title="LATEST NEWS"
                                    :class="cellPad"
                                />
                                <ol
                                    :class="[
                                        'divide-y divide-neutral-300',
                                        cellPad,
                                    ]"
                                >
                                    <li
                                        v-for="item in latestNews"
                                        :key="item.title"
                                        class="flex gap-3 py-3"
                                    >
                                        <ImagePlaceholder
                                            :label="item.title"
                                            aspect="photo"
                                            bare
                                            class="w-14 shrink-0 xl:w-16"
                                        />
                                        <div class="min-w-0">
                                            <CategoryLabel
                                                :category="item.category"
                                            />
                                            <h3
                                                class="isf-headline mt-0.5 text-[14px] leading-[1.25] xl:text-[15px]"
                                            >
                                                <IsfLink
                                                    :to="item.to || '#'"
                                                    :class="[
                                                        linkClass,
                                                        'hover:text-isf-red',
                                                    ]"
                                                    >{{ item.title }}</IsfLink
                                                >
                                            </h3>
                                            <time
                                                :datetime="item.publishedAt"
                                                class="isf-byline mt-1 block"
                                                >{{
                                                    formatDate(item.publishedAt)
                                                }}</time
                                            >
                                        </div>
                                    </li>
                                </ol>
                                <p
                                    v-if="latestNews.length === 0"
                                    :class="[
                                        'py-4 text-sm text-neutral-600',
                                        cellPad,
                                    ]"
                                >
                                    Belum ada artikel terbaru.
                                </p>
                            </section>
                        </aside>
                    </div>

                    <!-- Below the fold: Infografik Terbaru (4-up ruled row) -->
                    <section
                        aria-labelledby="infografik-terbaru"
                        class="border-t border-neutral-300"
                    >
                        <SectionHeading
                            id="infografik-terbaru"
                            title="Infografik Terbaru"
                            :class="cellPad"
                            ><template #action
                                ><IsfLink
                                    to="/infografik"
                                    :class="[
                                        linkClass,
                                        'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                                    ]"
                                    >Lihat semua</IsfLink
                                ></template
                            ></SectionHeading
                        >
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4"
                        >
                            <IsfLink
                                v-for="item in infografik"
                                :key="item.title"
                                :to="item.to || '#'"
                                :class="[
                                    linkClass,
                                    'group block min-w-0 border-b border-neutral-300 py-4 last:border-b-0 sm:px-3 lg:border-b-0 lg:border-r lg:px-4 lg:py-5 lg:last:border-r-0 xl:px-[30px]',
                                ]"
                                ><ImagePlaceholder
                                    :label="item.alt"
                                    :src="item.imageUrl || ''"
                                    aspect="photo"
                                    bare
                                />
                                <CategoryLabel
                                    :category="item.category"
                                    class="mt-3"
                                />
                                <h3
                                    class="isf-headline mt-1 text-[17px] leading-[1.2] group-hover:text-isf-red xl:text-[19px]"
                                >
                                    {{ item.title }}
                                </h3>
                                <time
                                    :datetime="item.publishedAt"
                                    class="isf-byline mt-2 block"
                                    >{{ formatDate(item.publishedAt) }}</time
                                ></IsfLink
                            >
                        </div>
                        <p
                            v-if="infografik.length === 0"
                            :class="['py-4 text-sm text-neutral-600', cellPad]"
                        >
                            Belum ada infografik.
                        </p>
                    </section>

                    <!-- Below the fold: Pekan Ini (2-up ruled row) -->
                    <section
                        v-if="pekanIni.items.length"
                        aria-labelledby="pekan-ini"
                        class="border-t border-neutral-300"
                    >
                        <SectionHeading
                            id="pekan-ini"
                            title="Pekan Ini"
                            :class="cellPad"
                            ><template
                                v-if="pekanIni.dateStart && pekanIni.dateEnd"
                                #eyebrow
                                ><time :datetime="pekanIni.dateStart">{{
                                    formatDate(pekanIni.dateStart)
                                }}</time>
                                –
                                <time :datetime="pekanIni.dateEnd">{{
                                    formatDate(pekanIni.dateEnd)
                                }}</time></template
                            ></SectionHeading
                        >
                        <div class="grid grid-cols-1 md:grid-cols-2">
                            <article
                                v-for="item in pekanIni.items"
                                :key="item.to"
                                class="border-b border-neutral-300 py-5 last:border-b-0 md:border-b-0 md:px-4 md:[&:first-child]:border-r xl:px-[30px]"
                            >
                                <p class="isf-kicker">{{ item.typeLabel }}</p>
                                <h3
                                    class="isf-headline mt-1.5 text-[22px] leading-[1.15] xl:text-[26px]"
                                >
                                    <IsfLink
                                        :to="item.to"
                                        :class="[
                                            linkClass,
                                            'hover:text-isf-red',
                                        ]"
                                        >{{ item.title }}</IsfLink
                                    >
                                </h3>
                                <p
                                    class="mt-2 font-serif text-[15px] leading-[1.5] text-neutral-600"
                                >
                                    {{ item.summary }}
                                </p>
                            </article>
                        </div>
                    </section>
                </div>
            </div>
        </template>
    </div>
</template>
