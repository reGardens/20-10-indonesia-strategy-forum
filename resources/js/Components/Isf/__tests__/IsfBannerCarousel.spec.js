import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import IsfBannerCarousel from "../IsfBannerCarousel.vue";
import appRouter from "@/router/index.js";

let app;
let root;

const mockSlides = [
    {
        id: "banner-1",
        kicker: "Sorotan 1",
        title: "Judul Slide Pertama",
        deck: "Deskripsi slide pertama",
        to: "/isu/slide-1",
        imageLabel: "Gambar Slide 1",
        imageUrl: "https://example.test/slide1.jpg",
    },
    {
        id: "banner-2",
        kicker: "Sorotan 2",
        title: "Judul Slide Kedua",
        deck: "Deskripsi slide kedua",
        to: "/isu/slide-2",
        imageLabel: "Gambar Slide 2",
        imageUrl: "https://example.test/slide2.jpg",
    },
    {
        id: "banner-3",
        kicker: "Sorotan 3",
        title: "Judul Slide Ketiga",
        deck: "Deskripsi slide ketiga",
        to: "/isu/slide-3",
        imageLabel: "Gambar Slide 3",
        imageUrl: "https://example.test/slide3.jpg",
    },
];

async function renderCarousel(props = {}) {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: appRouter.options.routes,
    });
    await router.push("/beranda");
    await router.isReady();

    root = document.createElement("div");
    document.body.appendChild(root);
    app = createApp({
        render: () =>
            h(IsfBannerCarousel, {
                slides: mockSlides,
                ...props,
            }),
    });
    app.use(router);
    app.mount(root);
    await nextTick();
    return { root, router };
}

beforeEach(() => {
    vi.useFakeTimers();
    document.body.innerHTML = "";
});

afterEach(() => {
    vi.clearAllTimers();
    vi.useRealTimers();
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
});

describe("IsfBannerCarousel", () => {
    it("merender tombol panah tebal Prev dan Next tanpa background saat slides > 1", async () => {
        const { root } = await renderCarousel();

        const prevBtn = root.querySelector('[data-testid="banner-prev-button"]');
        const nextBtn = root.querySelector('[data-testid="banner-next-button"]');

        expect(prevBtn).not.toBeNull();
        expect(nextBtn).not.toBeNull();
        expect(prevBtn.getAttribute("aria-label")).toBe("Slide sebelumnya");
        expect(nextBtn.getAttribute("aria-label")).toBe("Slide berikutnya");

        // Memastikan background transparan / dihilangkan
        expect(prevBtn.classList.contains("bg-transparent")).toBe(true);
        expect(nextBtn.classList.contains("bg-transparent")).toBe(true);
        expect(prevBtn.style.background).toContain("transparent");
        expect(nextBtn.style.background).toContain("transparent");

        // Memastikan icon panah memiliki stroke tebal
        const prevSvg = prevBtn.querySelector("svg");
        const nextSvg = nextBtn.querySelector("svg");
        expect(prevSvg.getAttribute("stroke-width")).toBe("2.5");
        expect(nextSvg.getAttribute("stroke-width")).toBe("2.5");
    });

    it("memastikan indikator titik (dots) di bawah sudah di-takeout", async () => {
        const { root } = await renderCarousel();
        const dotsNav = root.querySelector('[data-testid="banner-dots-nav"]');
        expect(dotsNav).toBeNull();
    });

    it("tidak merender tombol panah saat slide hanya 1", async () => {
        const { root } = await renderCarousel({
            slides: [mockSlides[0]],
        });

        const prevBtn = root.querySelector('[data-testid="banner-prev-button"]');
        const nextBtn = root.querySelector('[data-testid="banner-next-button"]');

        expect(prevBtn).toBeNull();
        expect(nextBtn).toBeNull();
    });

    it("mengklik tombol panah Next memindahkan slide ke berikutnya", async () => {
        const { root } = await renderCarousel();

        const nextBtn = root.querySelector('[data-testid="banner-next-button"]');
        const slides = root.querySelectorAll('[role="group"][aria-roledescription="slide"]');

        expect(slides[0].getAttribute("aria-hidden")).toBeNull();
        expect(slides[1].getAttribute("aria-hidden")).toBe("true");

        // Klik Next
        nextBtn.click();
        await nextTick();

        expect(slides[0].getAttribute("aria-hidden")).toBe("true");
        expect(slides[1].getAttribute("aria-hidden")).toBeNull();

        // Klik Next lagi
        nextBtn.click();
        await nextTick();

        expect(slides[2].getAttribute("aria-hidden")).toBeNull();

        // Klik Next di ujung slide membungkus kembali ke slide pertama (wrap-around)
        nextBtn.click();
        await nextTick();

        expect(slides[0].getAttribute("aria-hidden")).toBeNull();
    });

    it("mengklik tombol panah Prev memindahkan slide ke sebelumnya (wrap-around)", async () => {
        const { root } = await renderCarousel();

        const prevBtn = root.querySelector('[data-testid="banner-prev-button"]');
        const slides = root.querySelectorAll('[role="group"][aria-roledescription="slide"]');

        expect(slides[0].getAttribute("aria-hidden")).toBeNull();

        // Klik Prev dari indeks 0 membungkus ke slide terakhir (indeks 2)
        prevBtn.click();
        await nextTick();

        expect(slides[2].getAttribute("aria-hidden")).toBeNull();
        expect(slides[0].getAttribute("aria-hidden")).toBe("true");

        // Klik Prev dari indeks 2 kembali ke indeks 1
        prevBtn.click();
        await nextTick();

        expect(slides[1].getAttribute("aria-hidden")).toBeNull();
    });

    it("takeout drag gesture: container tidak memiliki class drag atau event pencegah scroll", async () => {
        const { root } = await renderCarousel();

        const trackContainer = root.querySelector(".overflow-hidden.relative.w-full");
        expect(trackContainer).not.toBeNull();

        // Tidak ada cursor grab / grabbing
        expect(trackContainer.classList.contains("cursor-grab")).toBe(false);
        expect(trackContainer.classList.contains("active:cursor-grabbing")).toBe(false);
    });

    it("menghormati opsi autoplay dan interval rotasi", async () => {
        // Autoplay aktif dengan interval 3000ms
        const { root: rootAuto } = await renderCarousel({
            autoplay: true,
            interval: 3000,
        });

        const slidesAuto = rootAuto.querySelectorAll('[role="group"][aria-roledescription="slide"]');
        expect(slidesAuto[0].getAttribute("aria-hidden")).toBeNull();

        vi.advanceTimersByTime(3000);
        await nextTick();

        expect(slidesAuto[1].getAttribute("aria-hidden")).toBeNull();

        // Autoplay nonaktif
        app?.unmount();
        const { root: rootManual } = await renderCarousel({
            autoplay: false,
            interval: 3000,
        });

        const slidesManual = rootManual.querySelectorAll('[role="group"][aria-roledescription="slide"]');
        expect(slidesManual[0].getAttribute("aria-hidden")).toBeNull();

        vi.advanceTimersByTime(6000);
        await nextTick();

        // Tetap di slide pertama karena autoplay false
        expect(slidesManual[0].getAttribute("aria-hidden")).toBeNull();
    });

    it("navigasi keyboard panah kiri dan kanan berfungsi", async () => {
        const { root } = await renderCarousel();

        const section = root.querySelector(".isf-banner-carousel");
        const slides = root.querySelectorAll('[role="group"][aria-roledescription="slide"]');

        // ArrowRight
        section.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowRight", bubbles: true }));
        await nextTick();
        expect(slides[1].getAttribute("aria-hidden")).toBeNull();

        // ArrowLeft
        section.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowLeft", bubbles: true }));
        await nextTick();
        expect(slides[0].getAttribute("aria-hidden")).toBeNull();
    });
});
