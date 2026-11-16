import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import GlosariumHukum from "@/Components/Isf/GlosariumHukum.vue";
import TimelineKasus from "@/Components/Isf/TimelineKasus.vue";
import HukumView from "@/Pages/Isf/views/HukumView.vue";
import appRouter from "@/router/index.js";
import * as isfPublicService from "@/api/services/isfPublicService";

let app;
let root;

const mockHukumData = {
    rubrik: {
        key: "hukum",
        title: "Hukum",
        label: "Hukum",
        description: "Analisis yuridis, putusan pengadilan, kepatuhan regulasi, dan kasus hukum nasional.",
        subkategori: [
            { name: "Regulasi", slug: "regulasi", description: "UU dan Perpres" },
            { name: "Putusan", slug: "putusan", description: "Putusan MK & MA" },
            { name: "Kasus Hukum", slug: "kasus", description: "Kasus besar & Tipikor" },
        ],
        activeSubkategori: null,
    },
    headline: {
        slug: "uji-headline-hukum",
        title: "Uji Headline Sorotan Hukum",
        category: "Hukum",
        subcategory: "regulasi",
        summary: "Ringkasan headline analisis hukum nasional.",
        publishedAt: "2026-11-15",
        author: "Tim Riset ISF",
        imageUrl: "https://example.test/headline.jpg",
        to: "/artikel/hukum/uji-headline-hukum",
    },
    articles: [
        {
            slug: "uji-artikel-hukum-1",
            title: "Uji Artikel Putusan MK",
            category: "Hukum",
            subcategory: "putusan",
            summary: "Ringkasan putusan MK.",
            publishedAt: "2026-11-14",
            author: "Pakar Konstitusi",
            imageUrl: "https://example.test/1.jpg",
            to: "/artikel/hukum/uji-artikel-hukum-1",
        },
    ],
    meta: {
        currentPage: 1,
        lastPage: 1,
        total: 2,
        perPage: 9,
    },
    opiniPakar: [
        {
            id: 1,
            slug: "opini-pakar-uji",
            title: "Tinjauan Kritis Formulasi Kodifikasi UU Pemilu",
            summary: "Analisis yuridis pentingnya penataan ulang desain elektoral.",
            expert_name: "Prof. Dr. Bagir Manan, S.H.",
            expert_title: "Guru Besar Hukum Tata Negara",
            expert_avatar: "https://example.test/pakar.jpg",
            readingMinutes: 6,
            to: "/artikel/hukum/opini-pakar-uji",
        },
    ],
    timelineKasus: [
        {
            id: 1,
            judul_kasus: "Uji Materiil Ambang Batas Parlemen di MK",
            slug: "uji-materiil-threshold-mk",
            kategori_kasus: "Sengketa Konstitusi",
            deskripsi: "Perkara pengujian norma threshold pemilu.",
            status_kasus: "Putusan Inkracht",
            events: [
                {
                    id: 101,
                    tanggal: "2024-02-29",
                    tanggal_formatted: "29 Februari 2024",
                    tahap: "Pembacaan Putusan",
                    judul: "MK Mengabulkan Sebagian Permohonan Ambang Batas 4%",
                    deskripsi: "Norma 4% inkonstitusional bersyarat untuk 2029.",
                    sumber_tautan: "https://mkri.id/putusan",
                },
            ],
        },
    ],
    glosarium: [
        {
            id: 1,
            istilah: "Kasasi",
            slug: "kasasi",
            abjad: "K",
            definisi: "Upaya hukum biasa ke Mahkamah Agung.",
            dasar_hukum: "Pasal 244 KUHAP",
            istilah_terkait: ["Banding", "Inkracht"],
        },
        {
            id: 2,
            istilah: "Actus Reus",
            slug: "actus-reus",
            abjad: "A",
            definisi: "Unsur perbuatan lahiriah fisik tindak pidana.",
            dasar_hukum: "Pasal 1 ayat (1) KUHP",
            istilah_terkait: ["Mens Rea"],
        },
    ],
};

beforeEach(() => {
    document.body.innerHTML = "";
    vi.spyOn(isfPublicService, "getHukum").mockResolvedValue(mockHukumData);
});

afterEach(() => {
    if (app) {
        app.unmount();
        app = null;
    }
    if (root && root.parentNode) {
        root.parentNode.removeChild(root);
        root = null;
    }
    vi.restoreAllMocks();
});

describe("GlosariumHukum.vue", () => {
    it("renders glossary terms, filters by letter, and performs search", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        app = createApp({
            render() {
                return h(GlosariumHukum, {
                    terms: mockHukumData.glosarium,
                    isDark: false,
                });
            },
        });

        app.mount(root);
        await nextTick();

        // 1. Should display glossary header
        expect(root.textContent).toContain("Glosarium Hukum Nasional");

        // 2. Should display terms
        expect(root.textContent).toContain("Kasasi");
        expect(root.textContent).toContain("Actus Reus");

        // 3. Test filter letter
        const btnK = Array.from(root.querySelectorAll("button")).find(
            (b) => b.textContent.trim() === "K"
        );
        expect(btnK).toBeTruthy();
        btnK.click();
        await nextTick();

        // Should keep Kasasi and hide Actus Reus
        expect(root.textContent).toContain("Kasasi");
        expect(root.textContent).not.toContain("Actus Reus");
    });
});

describe("TimelineKasus.vue", () => {
    it("renders case timeline with stages, date, and source link", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        app = createApp({
            render() {
                return h(TimelineKasus, {
                    timelines: mockHukumData.timelineKasus,
                    isDark: true,
                });
            },
        });

        app.mount(root);
        await nextTick();

        expect(root.textContent).toContain("Timeline Kasus Hukum Besar");
        expect(root.textContent).toContain("Uji Materiil Ambang Batas Parlemen di MK");
        expect(root.textContent).toContain("MK Mengabulkan Sebagian Permohonan Ambang Batas 4%");
        expect(root.textContent).toContain("Pembacaan Putusan");
        expect(root.textContent).toContain("29 Februari 2024");
    });
});

describe("HukumView.vue", () => {
    it("renders landing page /hukum with headline, subcategories, widgets, and article list", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        const router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });

        await router.push("/hukum");
        await router.isReady();

        app = createApp({
            render() {
                return h(HukumView);
            },
        });
        app.use(router);

        app.mount(root);

        // Wait for async load()
        for (let i = 0; i < 5; i++) {
            await nextTick();
            await new Promise((r) => setTimeout(r, 20));
        }

        expect(isfPublicService.getHukum).toHaveBeenCalled();
        expect(root.textContent).toContain("HUKUM");
        expect(root.textContent).toContain("Uji Headline Sorotan Hukum");
        expect(root.textContent).toContain("Opini & Analisis Pakar Hukum");
        expect(root.textContent).toContain("Prof. Dr. Bagir Manan, S.H.");
        expect(root.textContent).toContain("Timeline Kasus Hukum Besar");
        expect(root.textContent).toContain("Glosarium Hukum Nasional");
        expect(root.textContent).toContain("Uji Artikel Putusan MK");
    });
});
