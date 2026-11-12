/**
 * Tests for ISF Global Search (Pencarian Terpadu Lintas Konten) — Issue #40.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import App from "@/App.vue";
import appRouter from "@/router/index.js";

const mockSearchData = {
    query: "kuhp",
    type: "semua",
    total: 3,
    counts: {
        semua: 3,
        artikel: 1,
        tokoh: 1,
        infografik: 1,
        briefing: 0,
        video: 0,
    },
    data: [
        {
            id: "1",
            type: "artikel",
            typeLabel: "Artikel",
            title: "Apa yang berubah bagi pelaku usaha setelah KUHP baru berlaku",
            kicker: "HUKUM",
            category: "hukum",
            summary: "KUHP nasional (UU No. 1 Tahun 2023) kini berlaku penuh.",
            snippet: "...berubah bagi pelaku usaha setelah KUHP baru berlaku...",
            publishedAt: "2026-10-15",
            author: "Redaksi ISF",
            imageUrl: "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=800&auto=format&fit=crop",
            to: "/beranda",
            readingMinutes: 6,
        },
        {
            id: "2",
            type: "tokoh",
            typeLabel: "Profil Tokoh",
            title: "Budi Santoso",
            kicker: "TOKOH PUBLIK",
            category: "Tokoh",
            summary: "Profil, Skor Sorotan, dan jejak keterlibatan dalam pembahasan RUU pekan ini.",
            snippet: "...jejak keterlibatan dalam pembahasan RUU pekan ini...",
            publishedAt: null,
            author: "Redaksi ISF",
            imageUrl: "https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=800&auto=format&fit=crop",
            to: "/tokoh/budi-santoso",
            readingMinutes: null,
        },
        {
            id: "3",
            type: "infografik",
            typeLabel: "Infografik",
            title: "Update linimasa berlakunya KUHP nasional",
            kicker: "INFOGRAFIK",
            category: "hukum",
            summary: "Linimasa dan tahapan penegakan pidana korporasi.",
            snippet: "...linimasa berlakunya KUHP nasional...",
            publishedAt: "2026-10-14",
            author: "Tim Data ISF",
            imageUrl: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=800&auto=format&fit=crop",
            to: "/infografik",
            readingMinutes: null,
        },
    ],
    suggestions: {
        Politik: [
            "RUU Perampasan Aset",
            "Koalisi Partai",
            "Reshuffle Kabinet",
            "Pilkada Serentak",
            "Pemilu",
            "Ambang Batas Parlemen",
        ],
        Hukum: [
            "Mahkamah Konstitusi",
            "Mahkamah Agung",
            "Praperadilan",
            "UU ITE",
            "Penegakan Tipikor",
            "Reformasi Kepolisian",
        ],
        "Kebijakan & Ekonomi": [
            "Hilirisasi Nikel",
            "Transisi Energi Hijau",
            "Defisit APBN",
            "Pajak PPN 12%",
            "Subsidi Tepat Sasaran",
            "IKN Nusantara",
        ],
        Geopolitik: [
            "Diplomasi ASEAN",
            "Aksesi OECD",
            "Keanggotaan BRICS",
            "Laut Natuna Utara",
            "Ketahanan Pangan",
        ],
        Tokoh: [
            "Prabowo Subianto",
            "Gibran Rakabuming",
            "Sri Mulyani",
            "Erick Thohir",
            "Puan Maharani",
            "Mahfud MD",
        ],
    },
    popularKeywords: [
        "RUU Perampasan Aset",
        "Hilirisasi Nikel",
        "Mahkamah Konstitusi",
        "Transisi Energi Hijau",
        "Prabowo Subianto",
        "IKN Nusantara",
    ],
};

vi.mock("@/api/services/isfPublicService", async () => {
    const seed = (name) =>
        import(`../../../../../database/seeders/data/isf/${name}.json`).then(
            (m) => m.default,
        );
    const getBeranda = () => seed("beranda");
    const getAnggota = () => seed("anggota");
    const getTokoh = () => seed("tokoh");
    const getInfografik = () => seed("beranda");
    const getMediaDetail = async () => ({
        data: { id: 1, title: "Video", mediaUrl: "https://www.youtube.com/watch?v=1" },
    });
    const searchContent = vi.fn(async (params = {}) => {
        const q = typeof params === "string" ? params : (params?.q || "");
        const type = typeof params === "object" ? (params?.type || "semua") : "semua";
        let filtered = mockSearchData.data;
        if (type && type !== "semua") {
            filtered = filtered.filter((i) => i.type === type);
        }
        return {
            ...mockSearchData,
            query: q,
            type: type,
            total: filtered.length,
            data: filtered,
        };
    });
    const isNotFound = () => false;

    return {
        getBeranda,
        getAnggota,
        getTokoh,
        getInfografik,
        getMediaDetail,
        searchContent,
        isNotFound,
        default: {
            getBeranda,
            getAnggota,
            getTokoh,
            getInfografik,
            getMediaDetail,
            searchContent,
            isNotFound,
        },
    };
});

describe("ISF Global Search (Pencarian Terpadu)", () => {
    let app;
    let router;
    let root;

    async function setup(initialUrl = "/pencarian") {
        router = createRouter({
            history: createMemoryHistory(),
            routes: appRouter.options.routes,
        });

        root = document.createElement("div");
        root.id = "app";
        document.body.appendChild(root);

        app = createApp(App);
        app.use(router);

        await router.push(initialUrl);
        await router.isReady();
        app.mount(root);
        await nextTick();
        await new Promise((r) => setTimeout(r, 60));
    }

    beforeEach(() => {
        document.body.innerHTML = "";
    });

    afterEach(() => {
        app?.unmount();
        app = undefined;
        document.body.innerHTML = "";
    });

    it("merender halaman /pencarian dengan input pencarian dan AI suggestions 5 pilar", async () => {
        await setup("/pencarian");

        const text = root.textContent;
        expect(text).toContain("Pencarian Lintas Konten Strategis");
        expect(text).toContain("Rekomendasi Kata Kunci Populer (AI Suggestions)");

        // 5 pilar strategis tampil
        expect(text).toContain("Politik");
        expect(text).toContain("Hukum");
        expect(text).toContain("Kebijakan & Ekonomi");
        expect(text).toContain("Geopolitik");
        expect(text).toContain("Tokoh");

        // Rekomendasi kata kunci populer tampil
        expect(text).toContain("RUU Perampasan Aset");
        expect(text).toContain("Hilirisasi Nikel");
        expect(text).toContain("Mahkamah Konstitusi");
        expect(text).toContain("Prabowo Subianto");
    });

    it("menampilkan filter tab tipe konten dengan badge jumlah", async () => {
        await setup("/pencarian?q=kuhp");

        const filterPills = root.querySelectorAll(".isf-search-page button");
        const pillTexts = Array.from(filterPills).map((p) => p.textContent.trim());

        expect(pillTexts.some((t) => t.includes("Semua Konten"))).toBe(true);
        expect(pillTexts.some((t) => t.includes("Artikel"))).toBe(true);
        expect(pillTexts.some((t) => t.includes("Profil Tokoh"))).toBe(true);
        expect(pillTexts.some((t) => t.includes("Infografik"))).toBe(true);
    });

    it("menampilkan cuplikan (snippet), kategori, dan tautan hasil pencarian", async () => {
        await setup("/pencarian?q=kuhp");

        const text = root.textContent;
        expect(text).toContain("Apa yang berubah bagi pelaku usaha setelah KUHP baru berlaku");
        expect(text).toContain("...berubah bagi pelaku usaha setelah KUHP baru berlaku...");
        expect(text).toContain("Artikel");
        expect(text).toContain("Profil Tokoh");

        // Tautan artikel
        const articleLink = root.querySelector('a[href="/beranda"]');
        expect(articleLink).not.toBeNull();
    });

    it("memiliki panel pencarian di IsfHeader dengan tren kata kunci dan navigasi ke /pencarian", async () => {
        await setup("/beranda");

        const searchBtn = root.querySelector('button[aria-label="Buka pencarian"]');
        expect(searchBtn).not.toBeNull();

        searchBtn.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        await nextTick();

        const searchInput = root.querySelector("#isf-search-input");
        expect(searchInput).not.toBeNull();

        // Tren kata kunci di header
        const text = root.textContent;
        expect(text).toContain("Tren Pencarian:");
        expect(text).toContain("RUU Perampasan Aset");
        expect(text).toContain("Hilirisasi Nikel");

        // Klik salah satu tren kata kunci
        const nikelBtn = Array.from(root.querySelectorAll("button")).find((b) =>
            b.textContent.includes("Hilirisasi Nikel"),
        );
        expect(nikelBtn).toBeDefined();

        nikelBtn.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        await nextTick();
        await new Promise((r) => setTimeout(r, 60));

        expect(router.currentRoute.value.path).toBe("/pencarian");
        expect(router.currentRoute.value.query.q).toBe("Hilirisasi Nikel");
    });
});

