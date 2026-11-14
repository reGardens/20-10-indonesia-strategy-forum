<script setup>
/**
 * IsfFullLayerSections.vue
 *
 * Full-Layer Editorial Sections (ala EU Reporter & ISF broadsheet standard):
 * 1. Pilar Politik: 60/40 Split — Hero Lead Story + 3 Ruled Sub-stories
 * 2. Pilar Hukum: 3 kolom (23/54/23) seperti modul ISF Insight / Top News / Media Sorotan:
 *    urut terbaru dari kiri: kiri 2 berita → tengah 1 berita utama → kanan 4 berita
 * 3. Pilar Kebijakan: 3-Column Ruled Grid (In-depth policy, impact bullet matrix, policy brief)
 * 4. Pilar Video: High-contrast ISF Navy (#0B1220) Multimedia Band (Featured Player + Playlist)
 */
import { computed } from "vue";
import SectionHeading from "@/Components/Isf/SectionHeading.vue";
import ImagePlaceholder from "@/Components/Isf/ImagePlaceholder.vue";
import IsfLink from "@/Components/Isf/IsfLink.vue";

const props = defineProps({
    sections: {
        type: Object,
        default: () => ({}),
    },
    mediaSorotan: {
        type: Array,
        default: () => [],
    },
    cellPad: {
        type: String,
        default: "lg:px-4 xl:px-[30px]",
    },
    linkClass: {
        type: String,
        default: "",
    },
});

// ─── Formatters ─────────────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return "";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        }).format(d);
    } catch {
        return dateStr;
    }
}

// ─── Data Selectors with Robust Fallbacks ────────────────────────────────────
const politikData = computed(() => {
    const sec = props.sections?.politik || {};
    return {
        lead: sec.lead || {
            title: "Konsolidasi Parlemen: Peta Koalisi Pembahasan Agenda Legislasi Prioritas 2026",
            kicker: "ANALISIS POLITIK",
            category: "Politik",
            summary:
                "Dinamika lobi antar-fraksi di Senayan mengindikasikan pergeseran prioritas regulasi strategis. Simak telaah peta kekuatan koalisi dan implikasinya terhadap stabilitas kebijakan nasional.",
            publishedAt: new Date().toISOString(),
            author: {
                name: "Tim Riset Politik ISF",
                role: "Analis Parlemen & Tata Kelola",
            },
            readingMinutes: 5,
            imageUrl:
                "https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=1200&auto=format&fit=crop",
            to: "/beranda",
        },
        items: sec.items?.length
            ? sec.items
            : [
                  {
                      title: "Penyusunan RUU Prioritas Komisi II: Uji Publik & Evaluasi Sistem Pemilu",
                      kicker: "KOMISI II DPR",
                      publishedAt: new Date(
                          Date.now() - 86400000,
                      ).toISOString(),
                      summary:
                          "Harmonisasi draf revisi regulasi kepemiluan mulai memasuki tahap dengar pendapat bersama pakar tata negara.",
                      imageUrl:
                          "https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?q=80&w=400&auto=format&fit=crop",
                      to: "/beranda",
                  },
                  {
                      title: "Dinamika Badan Anggaran: Harmonisasi Alokasi Dana Alokasi Khusus (DAK)",
                      kicker: "BADAN ANGGARAN",
                      publishedAt: new Date(
                          Date.now() - 172800000,
                      ).toISOString(),
                      summary:
                          "Pemerintah dan Banggar sepakati formula pengawasan realisasi belanja transfer ke daerah berbasis kinerja.",
                      imageUrl:
                          "https://images.unsplash.com/photo-1450133064473-71024230f91b?q=80&w=400&auto=format&fit=crop",
                      to: "/beranda",
                  },
                  {
                      title: "Studi Komparatif Tata Kelola Parlemen Regional: Catatan untuk Reformasi Birokrasi",
                      kicker: "KAJIAN REGIONAL",
                      publishedAt: new Date(
                          Date.now() - 259200000,
                      ).toISOString(),
                      summary:
                          "Benchmarking efektivitas fungsi pengawasan legislatif di tingkat regional untuk meningkatkan akuntabilitas publik.",
                      imageUrl:
                          "https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop",
                      to: "/beranda",
                  },
              ],
    };
});

// Kartu Politik untuk layout 4 kolom: berita utama + 3 berita lain.
const politikCards = computed(() =>
    [politikData.value.lead, ...(politikData.value.items || [])]
        .filter(Boolean)
        .slice(0, 4),
);

const hukumItems = computed(() => {
    const items = props.sections?.hukum?.items;
    if (items && items.length > 0) return items;
    return [
        {
            title: "Pembaruan KUHP Nasional: Standardisasi Penilaian Risiko Pidana Korporasi",
            kicker: "PIDANA KORPORASI",
            publishedAt: new Date().toISOString(),
            summary:
                "Pedoman operasional kepatuhan bagi organ perseroan guna memitigasi risiko delik korporasi pasca berlakunya KUHP baru.",
            imageUrl:
                "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=800&auto=format&fit=crop",
            to: "/beranda",
        },
        {
            title: "Yurisprudensi Mahkamah Agung tentang Batasan Tanggung Jawab Direksi",
            kicker: "YURISPRUDENSI",
            publishedAt: new Date(Date.now() - 86400000).toISOString(),
            summary:
                "Analisis doktrin Business Judgment Rule dalam putusan perdata mutakhir yang memperkuat perlindungan itikad baik direksi.",
            imageUrl:
                "https://images.unsplash.com/photo-1505664194779-8beaceb93744?q=80&w=800&auto=format&fit=crop",
            to: "/beranda",
        },
        {
            title: "Pelacakan RUU Perampasan Aset: Harmonisasi Mekanisme Non-Conviction",
            kicker: "LEGISLASI TRACKER",
            publishedAt: new Date(Date.now() - 172800000).toISOString(),
            summary:
                "Mekanisme non-conviction based asset forfeiture ditinjau dari prinsip perlindungan hak kepemilikan dan hukum acara peradilan.",
            imageUrl:
                "https://images.unsplash.com/photo-1453728013993-6d66e9c9123a?q=80&w=800&auto=format&fit=crop",
            to: "/beranda",
        },
        {
            title: "Kepatuhan Regulasi ESG & Audit Lingkungan: Kewajiban Pelaporan Emiten 2026",
            kicker: "KEPATUHAN TATA KELOLA",
            publishedAt: new Date(Date.now() - 259200000).toISOString(),
            summary:
                "Regulator pertegas sanksi administratif bagi keterlambatan pengungkapan jejak karbon dan dampak sosial operasional industri.",
            imageUrl:
                "https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop",
            to: "/beranda",
        },
    ];
});

// Hukum 3 kolom, urut terbaru dari kiri: [0..1] = kiri (2 baris), [2] = tengah (utama),
// [3..6] = kanan (4 baris). Backend memastikan artikel Top News tidak berada di slot tengah.
const hukumLeft = computed(() => hukumItems.value.slice(0, 2));
const hukumLead = computed(() => hukumItems.value[2] || null);
const hukumRight = computed(() => hukumItems.value.slice(3, 7));

const kebijakanData = computed(() => {
    const sec = props.sections?.kebijakan || {};
    return {
        lead: sec.lead || {
            title: "Kebijakan Fiskal Hijau: Skema Insentif Pajak & Transisi Energi Bersih 2026",
            kicker: "ANALISIS FISKAL",
            category: "Kebijakan",
            summary:
                "Pemerintah mematangkan peta jalan insentif perpajakan bagi industri pelopor energi baru terbarukan sembari mempersiapkan pemberlakuan tarif pajak karbon secara bertahap.",
            imageUrl:
                "https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?q=80&w=1000&auto=format&fit=crop",
            publishedAt: new Date().toISOString(),
            author: "Dr. Hendra Wijaya, Dewan Pakar Fiskal ISF",
            to: "/beranda",
        },
        impact: sec.impact || {
            title: "Pemetaan Dampak Regulasi Moneter & Suku Bunga Sektoral",
            kicker: "DAMPAK REGULASI",
            publishedAt: new Date(Date.now() - 86400000).toISOString(),
            keyPoints: [
                "Likuiditas Perbankan: Ketahanan rasio kecukupan modal menghadapi volatilitas pasar global.",
                "Sektor Riil & Manufaktur: Efisiensi beban pinjaman modal kerja industri berorientasi ekspor.",
                "Iklim Investasi Domestik: Dampak stabilisasi nilai tukar terhadap arus penanaman modal asing.",
            ],
            to: "/beranda",
        },
        brief: sec.brief || {
            title: "Rekomendasi Kebijakan: Akselerasi Deregulasi Perizinan Berbasis Risiko",
            kicker: "POLICY BRIEF",
            publishedAt: new Date(Date.now() - 172800000).toISOString(),
            author: "Prof. Siti Rahmawati",
            authorRole: "Ketua Tim Kajian Deregulasi ISF",
            quote: "Kepastian regulasi dan simplifikasi birokrasi digital adalah pilar fundamental untuk mendongkrak daya saing investasi Indonesia.",
            to: "/beranda",
        },
    };
});

const videoData = computed(() => {
    const sec = props.sections?.video || {};
    const featured =
        sec.featured ||
        (props.mediaSorotan?.length
            ? {
                  id: props.mediaSorotan[0].id,
                  title: props.mediaSorotan[0].title,
                  summary:
                      props.mediaSorotan[0].summary ||
                      "Kajian mendalam pakar regulator dan praktisi terkait kepatuhan tata kelola.",
                  category: props.mediaSorotan[0].category || "Diskusi Panel",
                  duration: "18:45",
                  author: props.mediaSorotan[0].author || "Dewan Pakar ISF",
                  publishedAt:
                      props.mediaSorotan[0].publishedAt ||
                      new Date().toISOString(),
                  imageUrl:
                      props.mediaSorotan[0].imageUrl ||
                      "https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=1200&auto=format&fit=crop",
                  to: `/media/${props.mediaSorotan[0].id}`,
              }
            : {
                  id: 1,
                  title: "Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan",
                  summary:
                      "Kajian mendalam regulator dan praktisi perbankan terkait kepatuhan tata kelola AI, perlindungan privasi data nasabah, dan kesiapan sistem pembayaran nasional.",
                  category: "Diskusi Strategis",
                  duration: "18:45",
                  author: "Dewan Pakar Ekonomi & Regulasi ISF",
                  publishedAt: new Date().toISOString(),
                  imageUrl:
                      "https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=1200&auto=format&fit=crop",
                  to: "/media/1",
              });

    const playlist = sec.playlist?.length
        ? sec.playlist
        : props.mediaSorotan?.length > 1
          ? props.mediaSorotan.slice(1, 4).map((m, idx) => ({
                id: m.id,
                title: m.title,
                category: m.category || "Video Analisis",
                duration: idx === 0 ? "08:20" : idx === 1 ? "12:15" : "05:40",
                author: m.author || "Redaksi ISF",
                publishedAt: m.publishedAt || new Date().toISOString(),
                imageUrl:
                    m.imageUrl ||
                    "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=600&auto=format&fit=crop",
                to: `/media/${m.id}`,
            }))
          : [
                {
                    id: 2,
                    title: "Sorotan Kilat: Implikasi Regulasi Transisi Energi Terbarukan bagi Industri",
                    category: "Video Analisis",
                    duration: "08:20",
                    author: "Divisi Riset ISF",
                    publishedAt: new Date(Date.now() - 172800000).toISOString(),
                    imageUrl:
                        "https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?q=80&w=600&auto=format&fit=crop",
                    to: "/media/2",
                },
                {
                    id: 3,
                    title: "Kupas Tuntas: RUU Perampasan Aset dan Efek Jeranya bagi Koruptor",
                    category: "Wawancara Eksklusif",
                    duration: "14:10",
                    author: "Dewan Pakar Hukum ISF",
                    publishedAt: new Date(Date.now() - 345600000).toISOString(),
                    imageUrl:
                        "https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=600&auto=format&fit=crop",
                    to: "/media/3",
                },
                {
                    id: 4,
                    title: "Briefing Redaksi: Tinjauan Ketahanan Moneter & Kebijakan Likuiditas",
                    category: "Editorial Briefing",
                    duration: "06:45",
                    author: "Redaksi ISF",
                    publishedAt: new Date(Date.now() - 432000000).toISOString(),
                    imageUrl:
                        "https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?q=80&w=600&auto=format&fit=crop",
                    to: "/media/4",
                },
            ];

    return { featured, playlist };
});
</script>

<template>
    <div class="isf-full-layer-sections">
        <!-- =================================================================== -->
        <!-- PILAR 1: POLITIK & PARLEMEN (EU Reporter 60/40 Split)              -->
        <!-- =================================================================== -->
        <section
            aria-labelledby="section-politik"
            class="isf-full-layer-section isf-full-layer-section--accent"
        >
            <SectionHeading
                id="section-politik"
                title="Politik"
                size="lg"
                :class="cellPad"
            >
                <template #action>
                    <IsfLink
                        to="/artikel/politik"
                        :class="[
                            linkClass,
                            'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                        ]"
                    >
                        Lihat semua
                    </IsfLink>
                </template>
            </SectionHeading>

            <!-- Layout 4 kolom bergaris (pola Infografik lama): berita utama + 3 berita -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                <IsfLink
                    v-for="(item, idx) in politikCards"
                    :key="item.id || item.title || idx"
                    :to="item.to || '#'"
                    :class="[
                        linkClass,
                        'group block min-w-0 border-b border-neutral-300 py-4 last:border-b-0 px-3 lg:border-b-0 lg:border-r lg:px-4 lg:py-5 lg:last:border-r-0 xl:px-[30px]',
                    ]"
                    ><ImagePlaceholder
                        :label="item.title"
                        :src="item.imageUrl || ''"
                        aspect="photo"
                        bare
                        :priority="idx === 0"
                    />
                    <span class="isf-kicker mt-3">{{
                        item.kicker || item.category
                    }}</span>
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
        </section>

        <!-- =================================================================== -->
        <!-- PILAR 2: HUKUM (3 kolom 23/54/23, urut terbaru: kiri 2 → tengah 1 → kanan 4) -->
        <!-- =================================================================== -->
        <section aria-labelledby="section-hukum" class="isf-full-layer-section isf-full-layer-section--navy">
            <SectionHeading
                id="section-hukum"
                title="Hukum"
                size="lg"
                :class="cellPad"
            >
                <template #action>
                    <IsfLink
                        to="/artikel/hukum"
                        :class="[
                            linkClass,
                            'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                        ]"
                    >
                        Lihat semua
                    </IsfLink>
                </template>
            </SectionHeading>

            <!-- Grid = modul ISF Insight / Top News / Media Sorotan (23 / 54 / 23).
                 Mobile: utama → kanan → kiri (urutan sama dengan modul tersebut). -->
            <div
                class="grid grid-cols-1 lg:grid-cols-[minmax(0,23fr)_minmax(0,54fr)_minmax(0,23fr)]"
            >
                <!-- TENGAH: berita utama (penuh) -->
                <article
                    v-if="hukumLead"
                    :class="[
                        'order-1 min-w-0 px-3 py-4 lg:order-2 lg:border-x lg:border-neutral-300 lg:py-5',
                        cellPad,
                    ]"
                >
                    <!-- Teks di dalam gambar (= kartu Infografik Terbaru) -->
                    <IsfLink
                        v-if="hukumLead.imageUrl"
                        :to="hukumLead.to || '#'"
                        :class="[
                            linkClass,
                            'group relative block min-w-0 overflow-hidden bg-isf-navy',
                        ]"
                        ><ImagePlaceholder
                            :label="hukumLead.title"
                            :src="hukumLead.imageUrl"
                            aspect="photo"
                            bare
                            priority
                            class="transition-transform duration-500 group-hover:scale-[1.03]"
                        />
                        <div
                            class="pointer-events-none absolute inset-0 flex flex-col justify-end overflow-hidden bg-gradient-to-t from-black/90 via-black/45 to-transparent p-4 sm:p-5"
                        >
                            <p class="isf-byline !text-neutral-200">
                                <span class="isf-kicker !text-[#f87171]">{{
                                    hukumLead.kicker
                                }}</span>
                                /
                                <time :datetime="hukumLead.publishedAt">{{
                                    formatDate(hukumLead.publishedAt)
                                }}</time>
                            </p>
                            <h3
                                class="isf-headline mt-1.5 text-[22px] leading-[1.15] !text-white sm:text-[28px] xl:text-[32px]"
                            >
                                {{ hukumLead.title }}
                            </h3>
                            <p
                                v-if="hukumLead.summary"
                                class="mt-2 font-serif text-[15px] leading-[1.45] text-neutral-200 line-clamp-3 xl:text-[16px]"
                            >
                                {{ hukumLead.summary }}
                            </p>
                        </div></IsfLink
                    >
                    <template v-else>
                    <span class="isf-kicker text-isf-red">{{
                        hukumLead.kicker
                    }}</span>
                    <h3
                        class="isf-headline mt-1 text-[24px] leading-[1.15] sm:text-[28px] xl:text-[32px]"
                    >
                        <IsfLink
                            :to="hukumLead.to || '#'"
                            :class="[linkClass, 'hover:text-isf-red']"
                        >
                            {{ hukumLead.title }}
                        </IsfLink>
                    </h3>
                    <p
                        v-if="hukumLead.summary"
                        class="mt-3 font-serif text-[16px] leading-[1.5] text-neutral-600 xl:text-[17px]"
                    >
                        {{ hukumLead.summary }}
                    </p>
                    <time
                        :datetime="hukumLead.publishedAt"
                        class="isf-byline mt-3 block border-t border-neutral-200 pt-2"
                    >
                        {{ formatDate(hukumLead.publishedAt) }}
                    </time>
                    </template>
                </article>

                <!-- KANAN: 4 baris -->
                <div
                    v-if="hukumRight.length"
                    class="order-2 min-w-0 border-t border-neutral-300 lg:order-3 lg:border-t-0"
                >
                    <article
                        v-for="(item, idx) in hukumRight"
                        :key="item.id || idx"
                        :class="[
                            'border-b border-neutral-300 px-3 py-4 last:border-b-0 lg:py-5',
                            cellPad,
                        ]"
                    >
                        <div
                            :class="
                                item.imageUrl
                                    ? 'grid grid-cols-[minmax(0,1fr)_4.5rem] items-start gap-3'
                                    : ''
                            "
                        >
                            <div class="min-w-0">
                                <span class="isf-kicker text-isf-red">{{
                                    item.kicker
                                }}</span>
                                <h3
                                    class="isf-headline mt-1 text-[16px] leading-[1.25] xl:text-[17px]"
                                >
                                    <IsfLink
                                        :to="item.to || '#'"
                                        :class="[linkClass, 'hover:text-isf-red']"
                                    >
                                        {{ item.title }}
                                    </IsfLink>
                                </h3>
                                <time
                                    :datetime="item.publishedAt"
                                    class="isf-byline mt-1.5 block"
                                >
                                    {{ formatDate(item.publishedAt) }}
                                </time>
                            </div>
                            <div
                                v-if="item.imageUrl"
                                class="overflow-hidden bg-neutral-100"
                            >
                                <ImagePlaceholder
                                    :label="item.title"
                                    :src="item.imageUrl"
                                    aspect="square"
                                    bare
                                />
                            </div>
                        </div>
                    </article>
                </div>

                <!-- KIRI: 2 baris (gambar 4:3 agar teks di dalam gambar tidak terpotong) -->
                <div
                    v-if="hukumLeft.length"
                    class="order-3 min-w-0 border-t border-neutral-300 lg:order-1 lg:border-t-0"
                >
                    <article
                        v-for="(item, idx) in hukumLeft"
                        :key="item.id || idx"
                        :class="[
                            'border-b border-neutral-300 px-3 py-4 last:border-b-0 lg:py-5',
                            cellPad,
                        ]"
                    >
                        <!-- Teks di dalam gambar (= kartu Infografik Terbaru) -->
                        <IsfLink
                            v-if="item.imageUrl"
                            :to="item.to || '#'"
                            :class="[
                                linkClass,
                                'group relative block min-w-0 overflow-hidden bg-isf-navy',
                            ]"
                            ><ImagePlaceholder
                                :label="item.title"
                                :src="item.imageUrl"
                                aspect="card"
                                bare
                                class="transition-transform duration-500 group-hover:scale-[1.03]"
                            />
                            <div
                                class="pointer-events-none absolute inset-0 flex flex-col justify-end overflow-hidden bg-gradient-to-t from-black/90 via-black/55 to-transparent p-3 sm:p-4 lg:p-3 xl:p-4"
                            >
                                <p class="isf-byline !text-neutral-200">
                                    <span class="isf-kicker !text-[#f87171]">{{
                                        item.kicker
                                    }}</span>
                                    /
                                    <time :datetime="item.publishedAt">{{
                                        formatDate(item.publishedAt)
                                    }}</time>
                                </p>
                                <h3
                                    class="isf-headline mt-1 text-[16px] leading-[1.2] !text-white line-clamp-3 xl:text-[18px]"
                                >
                                    {{ item.title }}
                                </h3>
                                <p
                                    v-if="item.summary"
                                    class="mt-1.5 font-serif text-[13px] leading-[1.4] text-neutral-200 line-clamp-2"
                                >
                                    {{ item.summary }}
                                </p>
                            </div></IsfLink
                        >
                        <template v-else>
                        <span class="isf-kicker text-isf-red">{{
                            item.kicker
                        }}</span>
                        <h3
                            class="isf-headline mt-1 text-[17px] leading-[1.2] xl:text-[19px]"
                        >
                            <IsfLink
                                :to="item.to || '#'"
                                :class="[linkClass, 'hover:text-isf-red']"
                            >
                                {{ item.title }}
                            </IsfLink>
                        </h3>
                        <p
                            v-if="item.summary"
                            class="mt-2 font-serif text-[14px] leading-[1.45] text-neutral-600 line-clamp-3"
                        >
                            {{ item.summary }}
                        </p>
                        <time
                            :datetime="item.publishedAt"
                            class="isf-byline mt-3 block border-t border-neutral-200 pt-2"
                        >
                            {{ formatDate(item.publishedAt) }}
                        </time>
                        </template>
                    </article>
                </div>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- PILAR 3: KEBIJAKAN PUBLIK (EU Reporter 3-Column Ruled Feature)      -->
        <!-- =================================================================== -->
        <section
            aria-labelledby="section-kebijakan"
            class="isf-full-layer-section isf-full-layer-section--accent"
        >
            <SectionHeading
                id="section-kebijakan"
                title="Kebijakan Publik"
                size="lg"
                :class="cellPad"
            >
                <template #action>
                    <IsfLink
                        to="/artikel/kebijakan"
                        :class="[
                            linkClass,
                            'font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-isf-red hover:text-isf-red-dark',
                        ]"
                    >
                        Lihat semua
                    </IsfLink>
                </template>
            </SectionHeading>

            <div
                class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-neutral-300"
            >
                <!-- Kolom 1: Sorotan Utama Kebijakan -->
                <article
                    class="py-5 lg:py-6 flex flex-col justify-between"
                    :class="cellPad"
                >
                    <div>
                        <div class="overflow-hidden bg-neutral-100 mb-3">
                            <ImagePlaceholder
                                :label="kebijakanData.lead.title"
                                :src="kebijakanData.lead.imageUrl"
                                aspect="photo"
                                priority
                            />
                        </div>
                        <span class="isf-kicker text-isf-red">{{
                            kebijakanData.lead.kicker
                        }}</span>
                        <h3
                            class="isf-headline mt-1.5 text-[20px] sm:text-[22px] leading-[1.2]"
                        >
                            <IsfLink
                                :to="kebijakanData.lead.to || '#'"
                                :class="[linkClass, 'hover:text-isf-red']"
                            >
                                {{ kebijakanData.lead.title }}
                            </IsfLink>
                        </h3>
                        <p
                            v-if="kebijakanData.lead.summary"
                            class="mt-2.5 font-serif text-[15px] leading-[1.5] text-neutral-600"
                        >
                            {{ kebijakanData.lead.summary }}
                        </p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-neutral-200 flex items-center justify-between text-xs text-neutral-500 font-ui"
                    >
                        <span class="font-semibold text-neutral-800">{{
                            kebijakanData.lead.author
                        }}</span>
                        <time :datetime="kebijakanData.lead.publishedAt">{{
                            formatDate(kebijakanData.lead.publishedAt)
                        }}</time>
                    </div>
                </article>

                <!-- Kolom 2: Dampak Regulasi & Pasar Matrix -->
                <article
                    class="py-5 lg:py-6 flex flex-col justify-between isf-surface-cream"
                    :class="cellPad"
                >
                    <div>
                        <div
                            v-if="kebijakanData.impact.imageUrl"
                            class="overflow-hidden bg-neutral-100 mb-3"
                        >
                            <ImagePlaceholder
                                :label="kebijakanData.impact.title"
                                :src="kebijakanData.impact.imageUrl"
                                aspect="photo"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="h-2 w-2 rounded-full bg-isf-red"
                            ></span>
                            <span class="isf-kicker text-neutral-900">{{
                                kebijakanData.impact.kicker
                            }}</span>
                        </div>
                        <h3
                            class="isf-headline mt-1.5 text-[19px] sm:text-[21px] leading-[1.2]"
                        >
                            <IsfLink
                                :to="kebijakanData.impact.to || '#'"
                                :class="[linkClass, 'hover:text-isf-red']"
                            >
                                {{ kebijakanData.impact.title }}
                            </IsfLink>
                        </h3>
                        <ul
                            v-if="kebijakanData.impact.keyPoints?.length"
                            class="mt-3.5 space-y-2.5 font-serif text-[14px] text-neutral-700 leading-relaxed"
                        >
                            <li
                                v-for="(point, pIdx) in kebijakanData.impact
                                    .keyPoints"
                                :key="pIdx"
                                class="flex items-start gap-2"
                            >
                                <span
                                    class="text-isf-red font-bold select-none leading-none mt-1"
                                    >›</span
                                >
                                <span>{{ point }}</span>
                            </li>
                        </ul>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-neutral-200 flex items-center justify-between text-xs text-neutral-500 font-ui"
                    >
                        <span>Pembaruan Analisis Fiskal</span>
                        <time :datetime="kebijakanData.impact.publishedAt">{{
                            formatDate(kebijakanData.impact.publishedAt)
                        }}</time>
                    </div>
                </article>

                <!-- Kolom 3: Policy Brief Dewan Pakar -->
                <article
                    class="py-5 lg:py-6 flex flex-col justify-between"
                    :class="cellPad"
                >
                    <div>
                        <div
                            v-if="kebijakanData.brief.imageUrl"
                            class="overflow-hidden bg-neutral-100 mb-3"
                        >
                            <ImagePlaceholder
                                :label="kebijakanData.brief.title"
                                :src="kebijakanData.brief.imageUrl"
                                aspect="photo"
                            />
                        </div>
                        <span class="isf-kicker text-neutral-900">{{
                            kebijakanData.brief.kicker
                        }}</span>
                        <h3
                            class="isf-headline mt-1.5 text-[19px] sm:text-[21px] leading-[1.2]"
                        >
                            <IsfLink
                                :to="kebijakanData.brief.to || '#'"
                                :class="[linkClass, 'hover:text-isf-red']"
                            >
                                {{ kebijakanData.brief.title }}
                            </IsfLink>
                        </h3>
                        <blockquote
                            v-if="kebijakanData.brief.quote"
                            class="mt-3.5 border-l-2 border-isf-red pl-3.5 italic text-neutral-700 font-serif text-[14.5px] leading-relaxed"
                        >
                            "{{ kebijakanData.brief.quote }}"
                        </blockquote>
                    </div>
                    <div class="mt-4 pt-3 border-t border-neutral-200">
                        <p class="text-xs font-bold text-neutral-900 font-ui">
                            {{ kebijakanData.brief.author }}
                        </p>
                        <p
                            v-if="kebijakanData.brief.authorRole"
                            class="text-[11px] text-neutral-500 font-ui"
                        >
                            {{ kebijakanData.brief.authorRole }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <!-- =================================================================== -->
        <!-- PILAR 4: MULTIMEDIA & VIDEO INVESTIGATIF (ISF Navy Editorial Band)   -->
        <!-- =================================================================== -->
        <section
            aria-labelledby="section-video"
            class="isf-full-layer-section isf-full-layer-section--video text-white"
        >
            <div
                :class="[
                    'isf-col-head--section border-b border-white/10 flex items-center justify-between gap-3',
                    cellPad,
                ]"
            >
                <div class="flex min-w-0 items-center">
                    <h2
                        id="section-video"
                        class="isf-label isf-label--section"
                    >
                        Multimedia &amp; Video Investigatif
                    </h2>
                </div>
                <router-link
                    to="/beranda"
                    class="font-ui text-[12px] font-bold uppercase tracking-[0.12em] text-neutral-400 hover:text-white transition-colors"
                >
                    Lihat semua video ›
                </router-link>
            </div>

            <div
                class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-white/10"
            >
                <!-- Sisi Kiri: Featured Video Showcase (7 cols di lg, 8 cols di xl) -->
                <article
                    class="lg:col-span-7 xl:col-span-8 py-5 lg:py-6 flex flex-col justify-between"
                    :class="cellPad"
                >
                    <IsfLink
                        :to="videoData.featured.to || '#'"
                        :class="[linkClass, 'group block']"
                    >
                        <div
                            class="relative overflow-hidden aspect-video bg-neutral-900 shadow-sm"
                        >
                            <img
                                :src="videoData.featured.imageUrl"
                                :alt="videoData.featured.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent flex items-center justify-center"
                            >
                                <div
                                    class="w-14 h-14 rounded-full bg-isf-red text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform"
                                >
                                    <svg
                                        class="w-6 h-6 fill-current translate-x-0.5"
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div
                                class="absolute bottom-2.5 right-2.5 bg-black/80 text-white text-[11px] font-bold px-2 py-0.5 rounded font-ui"
                            >
                                {{ videoData.featured.duration || "18:45" }}
                            </div>
                            <div
                                class="absolute top-2.5 left-2.5 bg-isf-red text-white text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded font-ui"
                            >
                                {{
                                    videoData.featured.category || "Video Utama"
                                }}
                            </div>
                        </div>

                        <h3
                            class="font-headline mt-3.5 text-[20px] sm:text-[24px] leading-tight text-white group-hover:text-red-400 transition-colors"
                        >
                            {{ videoData.featured.title }}
                        </h3>

                        <p
                            class="mt-2 font-serif text-[14px] text-neutral-300 leading-relaxed line-clamp-2"
                        >
                            {{ videoData.featured.summary }}
                        </p>

                        <div
                            class="mt-3.5 flex items-center justify-between text-xs text-neutral-400 font-ui"
                        >
                            <span>{{
                                videoData.featured.author || "Dewan Pakar ISF"
                            }}</span>
                            <time :datetime="videoData.featured.publishedAt">{{
                                formatDate(videoData.featured.publishedAt)
                            }}</time>
                        </div>
                    </IsfLink>
                </article>

                <!-- Sisi Kanan: Video Playlist Queue (5 cols di lg, 4 cols di xl) -->
                <div
                    class="lg:col-span-5 xl:col-span-4 flex flex-col divide-y divide-white/10 py-2 lg:py-3"
                    :class="cellPad"
                >
                    <p
                        class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 font-ui py-2"
                    >
                        Daftar Putar Terkait
                    </p>
                    <article
                        v-for="(item, idx) in videoData.playlist"
                        :key="item.id || idx"
                        class="py-3 first:pt-2 last:pb-2"
                    >
                        <IsfLink
                            :to="item.to || '#'"
                            :class="[
                                linkClass,
                                'group flex gap-3.5 items-start',
                            ]"
                        >
                            <div
                                class="relative w-28 shrink-0 aspect-video overflow-hidden bg-neutral-900"
                            >
                                <img
                                    :src="item.imageUrl"
                                    :alt="item.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                    loading="lazy"
                                />
                                <div
                                    class="absolute bottom-1 right-1 bg-black/85 text-[10px] text-white px-1.5 py-0.2 rounded font-ui font-semibold"
                                >
                                    {{ item.duration }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-red-400 font-ui block"
                                >
                                    {{ item.category }}
                                </span>
                                <h4
                                    class="text-xs sm:text-[13px] font-semibold leading-snug text-white group-hover:text-red-300 transition-colors line-clamp-2 mt-0.5"
                                >
                                    {{ item.title }}
                                </h4>
                                <time
                                    :datetime="item.publishedAt"
                                    class="text-[10px] text-neutral-400 font-ui mt-1 block"
                                >
                                    {{ formatDate(item.publishedAt) }}
                                </time>
                            </div>
                        </IsfLink>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>
