import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import SurveiCard from "@/Components/Isf/SurveiCard.vue";
import SurveiChart from "@/Components/Isf/SurveiChart.vue";
import SurveiView from "@/Pages/Isf/views/SurveiView.vue";
import SurveiDetailView from "@/Pages/Isf/views/SurveiDetailView.vue";
import appRouter from "@/router/index.js";
import * as isfPublicService from "@/api/services/isfPublicService";

let app;
let root;

const mockSurveiItem = {
    id: 1,
    title: "Survei Evaluasi Kinerja Kabinet & Kebijakan Publik",
    slug: "survei-evaluasi-kinerja-kabinet",
    institution: "ISF Riset Strategis",
    is_isf: true,
    topic: "politik",
    region: "nasional",
    survey_period: "20 - 30 Oktober 2026",
    release_date: "2026-11-05",
    sample_size: 1200,
    margin_of_error: "±2.8%",
    confidence_level: "95%",
    sampling_method: "Multistage Random Sampling",
    interview_method: "Tatap Muka",
    headline_stat_value: "73.5%",
    headline_stat_label: "Tingkat Kepuasan Publik",
    headline_stat_comparison: "+4.2% YoY",
    summary: "Mayoritas responden menyatakan puas terhadap kinerja kabinet.",
    expert_analysis: "Stabilitas ekonomi mendorong sentimen positif publik.",
    chart_data: {
        segments: [
            {
                key: "overall",
                label: "Keseluruhan",
                items: [
                    { label: "Puas", value: 73.5, color: "#16a34a" },
                    { label: "Tidak Puas", value: 22.8, color: "#dc2626" },
                    { label: "TT / Rahasia", value: 3.7, color: "#94a3b8" },
                ],
            },
            {
                key: "usia",
                label: "Berdasarkan Usia",
                groups: [
                    {
                        name: "Gen Z",
                        items: [
                            { label: "Puas", value: 68.0, color: "#16a34a" },
                            { label: "Tidak Puas", value: 28.0, color: "#dc2626" },
                        ],
                    },
                ],
            },
        ],
    },
    pdf_path: "/docs/survei/evaluasi-kabinet.pdf",
    pdf_size: "3.2 MB",
    order: 1,
    is_featured: true,
    status: "terbit",
};

const mockSurveiDashboardData = {
    rubrik: {
        key: "survei",
        title: "Survei",
        label: "Survei",
        description: "Dashboard rilis survei opini publik dan metodologi riset.",
        topics: [
            { slug: "semua", name: "Semua Isu" },
            { slug: "politik", name: "Politik" },
            { slug: "ekonomi", name: "Ekonomi" },
        ],
        regions: [
            { slug: "semua", name: "Semua Wilayah" },
            { slug: "nasional", name: "Nasional" },
            { slug: "jawa", name: "Pulau Jawa" },
        ],
        activeTopic: "semua",
        activeRegion: "semua",
    },
    featuredSurvey: mockSurveiItem,
    surveys: [mockSurveiItem],
    meta: {
        currentPage: 1,
        lastPage: 1,
        total: 1,
        perPage: 9,
    },
};

describe("Survei Opini Publik Components & Views", () => {
    beforeEach(() => {
        root = document.createElement("div");
        document.body.appendChild(root);
        if (typeof window !== "undefined") {
            window.scrollTo = vi.fn();
        }
    });

    afterEach(() => {
        if (app) {
            app.unmount();
            app = null;
        }
        if (root && root.parentNode) {
            root.parentNode.removeChild(root);
        }
        vi.restoreAllMocks();
    });

    it("SurveiCard renders survey details, badges, and headline stat", async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });

        app = createApp({
            render() {
                return h(SurveiCard, { survey: mockSurveiItem });
            },
        });
        app.use(router);
        app.mount(root);

        await router.isReady();
        await nextTick();

        expect(root.textContent).toContain("Survei Evaluasi Kinerja Kabinet");
        expect(root.textContent).toContain("ISF Riset Strategis");
        expect(root.textContent).toContain("ISF Riset");
        expect(root.textContent).toContain("73.5%");
        expect(root.textContent).toContain("1.200");
    });

    it("SurveiChart renders segment data and toggles accessible data table", async () => {
        app = createApp({
            render() {
                return h(SurveiChart, { chartData: mockSurveiItem.chart_data });
            },
        });
        app.mount(root);
        await nextTick();

        expect(root.textContent).toContain("Keseluruhan");
        expect(root.textContent).toContain("Berdasarkan Usia");
        expect(root.textContent).toContain("Puas");
        expect(root.textContent).toContain("73.5%");

        // Accessible table toggle button exists
        const toggleBtn = Array.from(root.querySelectorAll("button")).find(
            (b) => b.textContent.includes("Tabel Alternatif")
        );
        expect(toggleBtn).toBeTruthy();

        // Click toggle to show data table
        toggleBtn.click();
        await nextTick();

        const table = root.querySelector("table");
        expect(table).toBeTruthy();
        expect(table.textContent).toContain("Kategori");
        expect(table.textContent).toContain("Persentase (%)");
    });

    it("SurveiView renders hero featured survey and survey grid from API", async () => {
        vi.spyOn(isfPublicService, "getSurvei").mockResolvedValue(mockSurveiDashboardData);

        const router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });

        await router.push("/survei");
        await router.isReady();

        app = createApp({
            render() {
                return h(SurveiView);
            },
        });
        app.use(router);
        app.mount(root);

        // Wait for async load
        await nextTick();
        await new Promise((r) => setTimeout(r, 50));
        await nextTick();

        expect(root.textContent).toContain("Survei");
        expect(root.textContent).toContain("Survei Evaluasi Kinerja Kabinet");
    });

    it("SurveiDetailView renders full methodology, expert analysis, and PDF card", async () => {
        vi.spyOn(isfPublicService, "getSurveiDetail").mockResolvedValue({
            survey: mockSurveiItem,
            relatedSurveys: [],
        });

        const router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });

        await router.push("/survei/survei-evaluasi-kinerja-kabinet");
        await router.isReady();

        app = createApp({
            render() {
                return h(SurveiDetailView);
            },
        });
        app.use(router);
        app.mount(root);

        await nextTick();
        await new Promise((r) => setTimeout(r, 50));
        await nextTick();

        expect(root.textContent).toContain("Survei Evaluasi Kinerja Kabinet");
        expect(root.textContent).toContain("73.5%");
        expect(root.textContent).toContain("Spesifikasi & Metodologi Survei");
        expect(root.textContent).toContain("Multistage Random Sampling");
        expect(root.textContent).toContain("Analisis Pakar & Implikasi Strategis Kebijakan");
        expect(root.textContent).toContain("3.2 MB");
    });
});
