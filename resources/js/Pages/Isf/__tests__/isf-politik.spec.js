import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import PetaKursiParlemen from "@/Components/Isf/PetaKursiParlemen.vue";
import PolitikView from "@/Pages/Isf/views/PolitikView.vue";
import appRouter from "@/router/index.js";
import * as isfPublicService from "@/api/services/isfPublicService";

let app;
let root;

const mockPolitikData = {
    rubrik: {
        key: "politik",
        title: "Politik",
        label: "Politik",
        description: "Dinamika politik nasional, parlemen, partai, pemilu, dan lembaga negara.",
        subkategori: [
            { name: "Pemilu & Pilkada", slug: "pemilu", description: "Isu pemilu" },
            { name: "DPR/DPD/MPR", slug: "parlemen", description: "Isu parlemen" },
            { name: "Eksekutif", slug: "pemerintahan", description: "Isu eksekutif" },
            { name: "Dinamika Partai", slug: "partai", description: "Isu partai" },
        ],
        activeSubkategori: null,
    },
    headline: {
        slug: "uji-headline-politik",
        title: "Uji Headline Sorotan Politik",
        category: "Politik",
        subcategory: "pemilu",
        summary: "Ringkasan headline analisis politik nasional.",
        publishedAt: "2026-11-15",
        author: "Tim Riset ISF",
        imageUrl: "https://example.test/headline.jpg",
        to: "/artikel/politik/uji-headline-politik",
    },
    articles: [
        {
            slug: "uji-artikel-1",
            title: "Uji Artikel Politik Pertama",
            category: "Politik",
            subcategory: "parlemen",
            summary: "Ringkasan artikel pertama.",
            publishedAt: "2026-11-14",
            author: "Analis Parlemen",
            imageUrl: "https://example.test/1.jpg",
            to: "/artikel/politik/uji-artikel-1",
        },
        {
            slug: "uji-artikel-2",
            title: "Uji Artikel Politik Kedua",
            category: "Politik",
            subcategory: "partai",
            summary: "Ringkasan artikel kedua.",
            publishedAt: "2026-11-13",
            author: "Analis Partai",
            imageUrl: "https://example.test/2.jpg",
            to: "/artikel/politik/uji-artikel-2",
        },
    ],
    meta: {
        currentPage: 1,
        lastPage: 2,
        total: 10,
        perPage: 9,
    },
    petaKursi: {
        periode: "2024-2029",
        lembaga: "DPR",
        totalKursi: 580,
        items: [
            { id: 1, partai: "PDI Perjuangan", singkatan: "PDIP", jumlah_kursi: 110, persentase: 18.97, warna_partai: "#D62828", urutan: 1 },
            { id: 2, partai: "Partai Golkar", singkatan: "Golkar", jumlah_kursi: 102, persentase: 17.59, warna_partai: "#F9BC02", urutan: 2 },
            { id: 3, partai: "Partai Gerindra", singkatan: "Gerindra", jumlah_kursi: 86, persentase: 14.83, warna_partai: "#D97706", urutan: 3 },
        ],
    },
    tokohPolitik: [
        {
            id: 1,
            slug: "budi-santoso",
            name: "Budi Santoso",
            position: "Ketua Komisi II DPR RI",
            photoUrl: "https://example.test/budi.jpg",
            photoAlt: "Budi Santoso",
            teaser: "Mendorong reformasi regulasi pemilu dan otonomi daerah.",
            stats: [{ label: "RUU Selesai", value: 4 }],
            to: "/tokoh/budi-santoso",
        },
    ],
};

beforeEach(() => {
    document.body.innerHTML = "";
    if (typeof HTMLCanvasElement !== "undefined" && !HTMLCanvasElement.prototype.getContext) {
        HTMLCanvasElement.prototype.getContext = () => ({
            measureText: () => ({ width: 10, height: 10 }),
            fillRect: () => {},
            clearRect: () => {},
            getImageData: () => ({ data: new Array(4) }),
            putImageData: () => {},
            createImageData: () => [],
            setTransform: () => {},
            drawImage: () => {},
            save: () => {},
            fillText: () => {},
            restore: () => {},
            beginPath: () => {},
            moveTo: () => {},
            lineTo: () => {},
            closePath: () => {},
            stroke: () => {},
            translate: () => {},
            scale: () => {},
            rotate: () => {},
            arc: () => {},
            fill: () => {},
        });
    }
    vi.spyOn(isfPublicService, "getPolitik").mockResolvedValue(mockPolitikData);
    vi.spyOn(isfPublicService, "getPetaKursi").mockResolvedValue(mockPolitikData.petaKursi);
});

afterEach(() => {
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
    vi.restoreAllMocks();
});

describe("PetaKursiParlemen.vue", () => {
    it("renders interactive hemicycle widget, stacked bar, and legend", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        app = createApp({
            render: () => h(PetaKursiParlemen, {
                initialData: mockPolitikData.petaKursi,
                periode: "2024-2029",
                lembaga: "DPR",
            }),
        });
        app.mount(root);
        await nextTick();

        // 1. Check title & total seats
        expect(root.textContent).toContain("Komposisi Parlemen");
        expect(root.textContent).toContain("580");

        // 2. Check Apache ECharts container rendered
        const chartEl = root.querySelector('.echarts-parliament-chart');
        expect(chartEl).not.toBeNull();

        // 3. Check party names in legend
        expect(root.textContent).toContain("PDIP");
        expect(root.textContent).toContain("Golkar");
        expect(root.textContent).toContain("Gerindra");

        // 4. Check accessible table toggle
        const toggleBtn = root.querySelector('button[aria-expanded]');
        expect(toggleBtn).not.toBeNull();
        expect(toggleBtn.textContent).toContain("Tampilkan Tabel Alternatif");

        // Click toggle to reveal screen reader accessible table
        toggleBtn.click();
        await nextTick();

        const table = root.querySelector("table");
        expect(table).not.toBeNull();
        expect(table.textContent).toContain("PDI Perjuangan");
        expect(table.textContent).toContain("110");
        expect(table.textContent).toContain("18.97%");
    });
});

describe("PolitikView.vue", () => {
    it("renders landing page /politik with headline, subcategories, widgets, and article list", async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });
        await router.push("/politik");
        await router.isReady();

        root = document.createElement("div");
        document.body.appendChild(root);

        app = createApp({
            render: () => h(PolitikView),
        });
        app.use(router);
        app.mount(root);

        // Wait for fetch
        await nextTick();
        await new Promise((r) => setTimeout(r, 50));
        await nextTick();

        // Rubrik title
        expect(root.textContent).toContain("Politik");
        expect(root.textContent).toContain("Dinamika politik nasional");

        // Subcategory navigation tabs
        expect(root.textContent).toContain("Semua Artikel");
        expect(root.textContent).toContain("Pemilu Pilkada");
        expect(root.textContent).toContain("Legislatif");
        expect(root.textContent).toContain("Eksekutif");
        expect(root.textContent).toContain("Dinamika Partai");
        expect(root.textContent).toContain("Sorotan");
        expect(root.textContent).toContain("Kegiatan Istana");

        // Headline
        expect(root.textContent).toContain("Uji Headline Sorotan Politik");

        // Widgets
        expect(root.textContent).toContain("Peta Kursi Parlemen");
        expect(root.textContent).toContain("Profil Tokoh Politik");
        expect(root.textContent).toContain("Budi Santoso");

        // Article list
        expect(root.textContent).toContain("Uji Artikel Politik Pertama");
        expect(root.textContent).toContain("Uji Artikel Politik Kedua");
    });
});
