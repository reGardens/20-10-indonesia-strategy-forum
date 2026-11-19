import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import BerandaView from "../views/BerandaView.vue";
import appRouter from "@/router/index.js";
import * as isfPublicService from "@/api/services/isfPublicService";

let app;
let root;
let router;

const mockBerandaData = {
    topBars: [{ badge: "Sorotan", announcement: "Berita Kilat", info: "Edisi", status: "terbit" }],
    banners: [],
    hotNews: [],
    hero: {
        id: "hero-1",
        title: "Headline Berita Utama Hero",
        summary: "Ringkasan hero",
        category: "Politik",
        publishedAt: "2026-11-19",
        author: { name: "Tim Redaksi ISF", role: "Analis Utama" },
        readingMinutes: 5,
        to: "/artikel/politik/headline-utama",
    },
    factBlocks: [],
    chart: null,
    charts: [],
    mediaSorotan: [
        {
            id: 101,
            title: "Sorotan Regulasi Terbaru 2026",
            category: "YouTube",
            badge: "Hukum & Regulasi",
            publishedAt: "2026-11-19",
            published_at: "2026-11-19",
            duration: "12:30",
            author: "Dewan Pakar Hukum",
            is_popular: true,
            order: 1,
        },
        {
            id: 102,
            title: "Sorotan Video Pendek Kebijakan Fiskal",
            category: "TikTok",
            badge: "Kebijakan Publik",
            publishedAt: "2026-11-18",
            published_at: "2026-11-18",
            duration: "01:00",
            author: "Divisi Riset",
            is_popular: true,
            order: 2,
        },
        {
            id: 103,
            title: "Sorotan Ketiga Default Media Path",
            category: "YouTube",
            publishedAt: "2026-11-17",
            published_at: "2026-11-17",
            duration: "05:20",
            author: "Redaksi ISF",
            is_popular: false,
            order: 3,
        },
    ],
    sections: {},
};

beforeEach(() => {
    document.body.innerHTML = "";
    vi.spyOn(isfPublicService, "getBeranda").mockResolvedValue(mockBerandaData);
    vi.spyOn(isfPublicService, "getAds").mockResolvedValue([]);
});

afterEach(() => {
    vi.restoreAllMocks();
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
});

async function mountBeranda() {
    router = createRouter({
        history: createMemoryHistory(),
        routes: appRouter.options.routes,
    });
    await router.push("/beranda");
    await router.isReady();

    root = document.createElement("div");
    document.body.appendChild(root);

    app = createApp(BerandaView);
    app.use(router);
    app.mount(root);

    await nextTick();
    // Allow useIsfContent async load to resolve
    await new Promise((r) => setTimeout(r, 100));
    await nextTick();
}

describe("BerandaView - Section Sorotan & Link Management", () => {
    it("menampilkan kartu sorotan dengan badge topik, judul tajam, dan timestamp update", async () => {
        await mountBeranda();

        expect(root.textContent).toContain("Sorotan");
        expect(root.textContent).toContain("Popular");
        expect(root.textContent).toContain("Latest");

        // Memuat judul berita sorotan dari data API
        expect(root.textContent).toContain("Sorotan Regulasi Terbaru 2026");
        expect(root.textContent).toContain("Sorotan Video Pendek Kebijakan Fiskal");

        // Memuat badge topik / kategori
        expect(root.textContent).toContain("Hukum & Regulasi");
        expect(root.textContent).toContain("Kebijakan Publik");

        // Memuat penanda waktu terformat bahasa Indonesia
        expect(root.textContent).toContain("19 November 2026");
    });

    it("mengarahkan klik kartu sorotan ke link detail /media/:id", async () => {
        await mountBeranda();

        // Kartu 1 memiliki link detail ke /media/101
        const mediaLink1 = root.querySelector('a[href="/media/101"]');
        expect(mediaLink1).not.toBeNull();

        // Kartu 2 memiliki link detail ke /media/102
        const mediaLink2 = root.querySelector('a[href="/media/102"]');
        expect(mediaLink2).not.toBeNull();
    });

    it("berpindah ke tab Latest dan menampilkan feed berita update terbaru", async () => {
        await mountBeranda();

        // Cari tombol tab Latest dan klik
        const buttons = Array.from(root.querySelectorAll("button"));
        const latestBtn = buttons.find((b) => b.textContent.trim() === "Latest");
        expect(latestBtn).toBeDefined();

        latestBtn.click();
        await nextTick();

        // Memuat item ketiga dalam tab Latest
        expect(root.textContent).toContain("Sorotan Ketiga Default Media Path");

        // Item tanpa target URL kustom menggunakan fallback /media/:id
        const fallbackLink = root.querySelector('a[href="/media/103"]');
        expect(fallbackLink).not.toBeNull();
    });
});

