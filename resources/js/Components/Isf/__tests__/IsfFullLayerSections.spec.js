import { afterEach, beforeEach, describe, expect, it } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import IsfFullLayerSections from "../IsfFullLayerSections.vue";
import appRouter from "@/router/index.js";

let app;
let root;

async function renderSections(props = {}) {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: appRouter.options.routes,
    });
    await router.push("/beranda");
    await router.isReady();

    root = document.createElement("div");
    document.body.appendChild(root);
    app = createApp({
        render: () => h(IsfFullLayerSections, props),
    });
    app.use(router);
    app.mount(root);
    await nextTick();
    return { root, router };
}

beforeEach(() => {
    document.body.innerHTML = "";
});

afterEach(() => {
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
});

describe("IsfFullLayerSections", () => {
    it("renders all four EU Reporter editorial pillars correctly with default fallbacks", async () => {
        const { root } = await renderSections({
            sections: {},
            mediaSorotan: [],
        });

        // 1. Pilar Politik
        const secPolitik = root.querySelector("#section-politik");
        expect(secPolitik).not.toBeNull();
        expect(root.textContent).toContain("Politik & Parlemen");

        // 2. Pilar Hukum
        const secHukum = root.querySelector("#section-hukum");
        expect(secHukum).not.toBeNull();
        expect(root.textContent).toContain("Hukum & Regulasi");

        // 3. Pilar Kebijakan
        const secKebijakan = root.querySelector("#section-kebijakan");
        expect(secKebijakan).not.toBeNull();
        expect(root.textContent).toContain("Kebijakan Publik & Fiskal");

        // 4. Pilar Video
        const secVideo = root.querySelector("#section-video");
        expect(secVideo).not.toBeNull();
        expect(root.textContent).toContain("Multimedia & Video Investigatif");
    });

    it("renders provided custom sections data accurately", async () => {
        const customSections = {
            politik: {
                lead: {
                    title: "Rapat Paripurna DPR Bahas Prioritas Regulasi Sektoral",
                    kicker: "LIPUTAN PARLEMEN",
                    summary: "Paripurna menyepakati daftar 12 RUU prioritas pembahasan tahun berjalan.",
                    publishedAt: "2026-11-10",
                    author: { name: "Bambang Soediro", role: "Wartawan Senior ISF" },
                    readingMinutes: 7,
                    imageUrl: "https://example.com/politik.jpg",
                    to: "/beranda",
                },
                items: [
                    {
                        title: "Komisi XI Sepakati Pengawasan Anggaran Terpadu",
                        kicker: "KOMISI XI",
                        publishedAt: "2026-11-09",
                        summary: "Formula baru transfer ke daerah.",
                        imageUrl: "",
                        to: "/beranda",
                    },
                ],
            },
            hukum: {
                items: [
                    {
                        title: "Putusan MK tentang Batas Usia Capres-Cawapres",
                        kicker: "PUTUSAN MK",
                        publishedAt: "2026-11-10",
                        summary: "Kajian pertimbangan hukum majelis hakim.",
                        imageUrl: "",
                        to: "/beranda",
                    },
                ],
            },
            kebijakan: {
                lead: {
                    title: "Strategi Ketahanan Energi Hijau Nasional 2026",
                    kicker: "STRATEGI ENERGI",
                    summary: "Peningkatan bauran energi baru terbarukan ditopang investasi strategis.",
                    publishedAt: "2026-11-10",
                    author: "Tim Energi ISF",
                    imageUrl: "https://example.com/energi.jpg",
                    to: "/beranda",
                },
                impact: {
                    title: "Dampak Kebijakan Tarif Listrik Industri",
                    kicker: "DAMPAK BIAYA",
                    publishedAt: "2026-11-09",
                    keyPoints: ["Efisiensi beban operasional manufaktur"],
                    to: "/beranda",
                },
                brief: {
                    title: "Catatan Dewan Pakar: Transisi Berkeadilan",
                    kicker: "POLICY BRIEF",
                    publishedAt: "2026-11-08",
                    author: "Dr. Agus Santoso",
                    authorRole: "Pakar Transisi Energi",
                    quote: "Transisi harus menjaga keterjangkauan daya beli rakyat.",
                    to: "/beranda",
                },
            },
            video: {
                featured: {
                    id: 99,
                    title: "Eksklusif: Wawancara Khusus Menkeu Terkait Reformasi Pajak",
                    summary: "Kupas tuntas strategi belanja negara berkeadilan.",
                    category: "Wawancara Utama",
                    duration: "25:30",
                    author: "Redaksi ISF",
                    publishedAt: "2026-11-10",
                    imageUrl: "https://example.com/video.jpg",
                    to: "/media/99",
                },
                playlist: [
                    {
                        id: 100,
                        title: "Bedah Regulasi Fintech & Perlindungan Konsumen",
                        category: "Analisis Kilat",
                        duration: "10:15",
                        author: "Dewan Pakar ISF",
                        publishedAt: "2026-11-09",
                        imageUrl: "",
                        to: "/media/100",
                    },
                ],
            },
        };

        const { root } = await renderSections({
            sections: customSections,
            mediaSorotan: [],
        });

        expect(root.textContent).toContain("Rapat Paripurna DPR Bahas Prioritas Regulasi Sektoral");
        expect(root.textContent).toContain("Putusan MK tentang Batas Usia Capres-Cawapres");
        expect(root.textContent).toContain("Strategi Ketahanan Energi Hijau Nasional 2026");
        expect(root.textContent).toContain("Eksklusif: Wawancara Khusus Menkeu Terkait Reformasi Pajak");
        expect(root.textContent).toContain("25:30");
    });
});
