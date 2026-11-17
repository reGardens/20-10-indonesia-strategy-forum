import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import PolicyBriefCard from "@/Components/Isf/PolicyBriefCard.vue";
import MonitoringKebijakan from "@/Components/Isf/MonitoringKebijakan.vue";
import KebijakanPublikView from "@/Pages/Isf/views/KebijakanPublikView.vue";
import appRouter from "@/router/index.js";
import * as isfPublicService from "@/api/services/isfPublicService";

let app;
let root;

const mockKebijakanData = {
    rubrik: {
        key: "kebijakan",
        title: "Kebijakan Publik",
        label: "Kebijakan Publik",
        description: "Analisis sektoral & kajian strategis kebijakan pemerintah.",
        subkategori: [
            { name: "Ekonomi & Fiskal", slug: "ekonomi", description: "Kebijakan fiskal dan APBN" },
            { name: "Kesehatan", slug: "kesehatan", description: "Sistem kesehatan nasional" },
            { name: "Pendidikan", slug: "pendidikan", description: "Pendidikan & riset" },
            { name: "Pertahanan", slug: "pertahanan", description: "Modernisasi alutsista" },
            { name: "Lingkungan", slug: "lingkungan", description: "Transisi energi terbarukan" },
        ],
        activeSubkategori: null,
    },
    headline: {
        slug: "uji-headline-kebijakan",
        title: "Uji Headline Sorotan Kebijakan Publik",
        category: "Kebijakan Publik",
        subcategory: "ekonomi",
        summary: "Ringkasan headline analisis kebijakan fiskal dan APBN nasional.",
        publishedAt: "2026-11-16",
        author: "Tim Riset ISF",
        imageUrl: "https://example.test/headline.jpg",
        to: "/artikel/kebijakan/uji-headline-kebijakan",
    },
    articles: [
        {
            slug: "uji-artikel-kebijakan-1",
            title: "Uji Artikel Transformasi Kesehatan Nasional",
            category: "Kebijakan Publik",
            subcategory: "kesehatan",
            summary: "Ringkasan evaluasi sistem BPJS dan layanan faskes.",
            publishedAt: "2026-11-15",
            author: "Pakar Kebijakan Publik",
            imageUrl: "https://example.test/1.jpg",
            to: "/artikel/kebijakan/uji-artikel-kebijakan-1",
        },
    ],
    meta: {
        currentPage: 1,
        lastPage: 1,
        total: 2,
        perPage: 9,
    },
    policyBriefs: [
        {
            id: 1,
            title: "Strategi Transformasi Bauran Energi Terbarukan 2030",
            slug: "strategi-bauran-energi-2030",
            sector: "lingkungan",
            author: "Pusat Studi Energi & Iklim",
            institution: "Indonesia Strategy Forum",
            executive_summary: "Akselerasi dekarbonisasi membutuhkan kepastian tarif feed-in tariff dan deregulasi perizinan PLTS atap.",
            key_recommendations: [
                "Revisi skema tarif royalti PLTS atap",
                "Integrasi insentif fiskal hijau",
            ],
            pdf_path: "/storage/policy-briefs/sample-ebt.pdf",
            pdf_size: "2.4 MB PDF",
            published_at: "2026-11-16",
            published_at_formatted: "16 November 2026",
        },
    ],
    dataGrafik: [
        {
            id: 1,
            title: "Realisasi Alokasi Belanja Subsidi Energi APBN",
            description: "Perbandingan pagu vs realisasi subsidi listrik & BBM (Triliun Rupiah)",
            unit: " T",
            sector: "ekonomi",
            data: [
                { label: "BBM & Solar", value: 161.4 },
                { label: "Listrik PLN", value: 87.2 },
            ],
            source_title: "Kemenkeu RI (Laporan APBN Kita)",
            source_url: "https://kemenkeu.go.id",
            author_name: "Divisi Riset Fiskal ISF",
            author_role: "Ekonom Senior",
        },
    ],
    monitoringKebijakan: [
        {
            id: 1,
            program_name: "Pembangunan 3 Juta Unit Rumah Subsidi Layak Huni",
            slug: "pembangunan-3-juta-rumah-subsidi",
            sector: "ekonomi",
            promise: "Penyediaan 3 juta unit rumah layak huni per tahun untuk MBR dan generasi muda.",
            realization: "Sebanyak 1.150.000 unit terealisasi hingga Q3 2026 melalui skema FLPP dan BP2BT.",
            progress_percent: 38,
            status_label: "dalam_proses",
            source_title: "Kementerian PKP & Bank BTN",
            source_url: "https://btn.co.id",
            last_updated_date: "2026-11-16",
            last_updated_formatted: "16 November 2026",
        },
    ],
};

beforeEach(() => {
    document.body.innerHTML = "";
    vi.spyOn(isfPublicService, "getKebijakanPublik").mockResolvedValue(mockKebijakanData);
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

describe("PolicyBriefCard.vue", () => {
    it("renders policy brief title, sector, author, and between: source and cta download", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        app = createApp({
            render() {
                return h(PolicyBriefCard, {
                    brief: mockKebijakanData.policyBriefs[0],
                    isDark: false,
                });
            },
        });

        app.mount(root);
        await nextTick();

        // 1. Should display title and author
        expect(root.textContent).toContain("Strategi Transformasi Bauran Energi Terbarukan 2030");
        expect(root.textContent).toContain("Pusat Studi Energi & Iklim");
        expect(root.textContent).toContain("Indonesia Strategy Forum");

        // 2. Gambar 3 (kicker sektor & ukuran berkas) telah di-takeout sesuai instruksi
        expect(root.textContent).not.toContain("2.4 MB PDF");

        // 3. Should display publication date
        expect(root.textContent).toContain("16 November 2026");

        // 4. Download link
        const downloadLink = root.querySelector("a[href*='sample-ebt.pdf']");
        expect(downloadLink).toBeTruthy();
        expect(downloadLink.textContent).toContain("Unduh PDF");
    });
});

describe("MonitoringKebijakan.vue", () => {
    it("renders tracker cards, Janji vs Realisasi comparison, and toggle table", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        app = createApp({
            render() {
                return h(MonitoringKebijakan, {
                    trackers: mockKebijakanData.monitoringKebijakan,
                    isDark: true,
                });
            },
        });

        app.mount(root);
        await nextTick();

        // 1. Title and program name
        expect(root.textContent).toContain("Monitoring Kebijakan Publik");
        expect(root.textContent).toContain("Pembangunan 3 Juta Unit Rumah Subsidi Layak Huni");

        // 2. Progress percentage
        expect(root.textContent).toContain("38%");

        // 3. Janji vs Realisasi
        expect(root.textContent).toContain("Target / Janji Kebijakan:");
        expect(root.textContent).toContain("Penyediaan 3 juta unit rumah");
        expect(root.textContent).toContain("Capaian & Realisasi Lapangan:");
        expect(root.textContent).toContain("1.150.000 unit terealisasi");

        // 4. Source citation
        expect(root.textContent).toContain("Kementerian PKP & Bank BTN");

        // 5. Accessible table toggle
        const toggleBtn = Array.from(root.querySelectorAll("button")).find(
            (b) => b.textContent.includes("Tabel Alternatif")
        );
        expect(toggleBtn).toBeTruthy();
        toggleBtn.click();
        await nextTick();
        expect(root.textContent).toContain("Tutup Tabel");
    });
});

describe("KebijakanPublikView.vue", () => {
    it("renders landing page /kebijakan-publik with headline, subcategories, widgets, and article list", async () => {
        root = document.createElement("div");
        document.body.appendChild(root);

        const router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });

        await router.push("/kebijakan-publik");
        await router.isReady();

        app = createApp({
            render() {
                return h(KebijakanPublikView);
            },
        });
        app.use(router);

        app.mount(root);

        // Wait for async load()
        for (let i = 0; i < 5; i++) {
            await nextTick();
            await new Promise((r) => setTimeout(r, 20));
        }

        expect(isfPublicService.getKebijakanPublik).toHaveBeenCalled();
        expect(root.textContent).toContain("KEBIJAKAN PUBLIK");
        expect(root.textContent).toContain("Uji Headline Sorotan Kebijakan Publik");
        expect(root.textContent).toContain("Policy Brief & Rekomendasi Kebijakan");
        expect(root.textContent).toContain("Data & Statistik Kebijakan Strategis");
        expect(root.textContent).toContain("Database Peraturan & Rekomendasi Kebijakan");
        expect(root.textContent).toContain("Uji Artikel Transformasi Kesehatan Nasional");
    });
});

