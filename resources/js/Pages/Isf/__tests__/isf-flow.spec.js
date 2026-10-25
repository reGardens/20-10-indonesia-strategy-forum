/**
 * ISF — alur E2E frontend (Vitest + jsdom).
 *
 * Mount App.vue asli dengan route records asli dari resources/js/router/index.js
 * (memory history agar tidak bergantung pada URL jsdom), lalu jalankan alur
 * pengguna: Beranda → Anatomi Isu → Profil Tokoh → slug tidak dikenal → Dasbor Anggota.
 * Data: public API service di-mock dengan file seed yang sama dengan IsfContentSeeder
 * (database/seeders/data/isf/*.json), jadi hanya ada satu sumber data contoh.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import App from "@/App.vue";
import appRouter from "@/router/index.js";
import anggota from "../../../../../database/seeders/data/isf/anggota.json";
import beranda from "../../../../../database/seeders/data/isf/beranda.json";
import isu from "../../../../../database/seeders/data/isf/isu.json";
import tokoh from "../../../../../database/seeders/data/isf/tokoh.json";

// Public API mock: same response shape as GET /api/isf/* (unknown slug → 404).
vi.mock("@/api/services/isfPublicService", async () => {
    const seed = (name) => import(`../../../../../database/seeders/data/isf/${name}.json`).then((m) => m.default);
    const notFound = () => Promise.reject({ response: { status: 404 } });
    const getBeranda = () => seed("beranda");
    const getAnggota = () => seed("anggota");
    const getTokoh = async (slug) => {
        const data = await seed("tokoh");
        return slug === data.slug ? data : notFound();
    };
    const getIsu = async (slug) => {
        const data = await seed("isu");
        return slug === data.slug ? data : notFound();
    };
    const isNotFound = (error) => error?.response?.status === 404;
    return {
        getBeranda,
        getAnggota,
        getTokoh,
        getIsu,
        isNotFound,
        default: { getBeranda, getAnggota, getTokoh, getIsu, isNotFound },
    };
});

const ISU_PATH = `/isu/${isu.slug}`;
const TOKOH_PATH = `/tokoh/${tokoh.slug}`;
const COUNT_MERAH = anggota.alerts.filter((a) => a.priority === "merah").length;
const COUNT_KUNING = anggota.alerts.filter((a) => a.priority === "kuning").length;

// jsdom does not implement these; radix-vue Select calls them.
if (!Element.prototype.hasPointerCapture) Element.prototype.hasPointerCapture = () => false;
if (!Element.prototype.releasePointerCapture) Element.prototype.releasePointerCapture = () => {};
if (!Element.prototype.setPointerCapture) Element.prototype.setPointerCapture = () => {};
if (!Element.prototype.scrollIntoView) Element.prototype.scrollIntoView = () => {};

let app;
let router;
let root;

async function flush() {
    for (let i = 0; i < 5; i += 1) {
        await new Promise((resolve) => setTimeout(resolve, 0));
        await nextTick();
    }
}

async function waitFor(check, message, timeout = 3000) {
    const start = Date.now();
    // eslint-disable-next-line no-constant-condition
    while (true) {
        await flush();
        if (check()) return;
        if (Date.now() - start > timeout) throw new Error(`Timeout: ${message}`);
    }
}

async function mountAt(path) {
    router = createRouter({ history: createMemoryHistory(), routes: appRouter.options.routes });
    await router.push(path);
    await router.isReady();
    root = document.createElement("div");
    document.body.appendChild(root);
    app = createApp(App);
    app.use(router);
    app.mount(root);
    await flush();
}

function click(el) {
    el.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 }));
}

function text() {
    return root.textContent.replace(/\s+/g, " ");
}

function alertRows() {
    const caption = Array.from(root.querySelectorAll("caption")).find((c) =>
        c.textContent.includes("Umpan peringatan dini"),
    );
    return Array.from(caption.closest("table").querySelectorAll("tbody tr")).filter(
        (tr) => !tr.textContent.includes("Tidak ada alert"),
    );
}

function countIn(rows, label) {
    return rows.filter((tr) => tr.textContent.includes(label)).length;
}

beforeEach(() => {
    document.body.innerHTML = "";
});

afterEach(() => {
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
});

describe("ISF alur E2E (frontend)", () => {
    it("Beranda tampil → klik isu → Anatomi Isu 01–07 → klik aktor → Profil Tokoh (Ikuti Tokoh toggle)", async () => {
        // (1) Beranda
        await mountAt("/beranda");
        expect(router.currentRoute.value.name).toBe("isf-beranda");
        await waitFor(() => root.querySelector("#hero-title"), "beranda dimuat dari API");
        expect(text()).not.toContain("Terjadi Kesalahan");

        const searchToggle = root.querySelector('button[aria-label="Buka pencarian"]');
        const searchForm = root.querySelector('form[role="search"]');
        const searchInput = root.querySelector("#isf-search-input");
        expect(searchToggle.getAttribute("aria-expanded")).toBe("false");
        expect(searchForm).not.toBeNull();
        expect(searchInput).not.toBeNull();
        expect(root.querySelector('label[for="isf-search-input"]')).not.toBeNull();
        click(searchToggle);
        await flush();
        expect(searchToggle.getAttribute("aria-expanded")).toBe("true");
        expect(document.activeElement).toBe(searchInput);
        const submit = new Event("submit", { bubbles: true, cancelable: true });
        expect(searchForm.dispatchEvent(submit)).toBe(false);
        await flush();
        expect(text()).toContain("Pencarian akan segera tersedia.");

        const menuToggle = root.querySelector('button[aria-controls="isf-menu-panel"]');
        expect(menuToggle.getAttribute("aria-expanded")).toBe("false");
        click(menuToggle);
        await flush();
        expect(menuToggle.getAttribute("aria-expanded")).toBe("true");
        expect(root.querySelector('#isf-menu-panel a[href="/beranda"]')).not.toBeNull();
        click(menuToggle);
        await flush();
        expect(menuToggle.getAttribute("aria-expanded")).toBe("false");

        const heroTitle = root.querySelector("#hero-title");
        expect(heroTitle).not.toBeNull();
        expect(heroTitle.textContent).toContain(beranda.hero.title);

        expect(root.querySelector('[data-testid="sorotan-line"]').textContent).toContain(
            beranda.topBar.announcement,
        );
        const carousel = root.querySelector('[aria-roledescription="carousel"]');
        expect(carousel).not.toBeNull();
        const slides = Array.from(carousel.querySelectorAll('[aria-roledescription="slide"]'));
        expect(slides).toHaveLength(3);
        expect(slides.map((s) => s.getAttribute("aria-label"))).toEqual(["1 dari 3", "2 dari 3", "3 dari 3"]);
        const visible = () => slides.filter((s) => s.style.display !== "none");
        expect(visible()).toEqual([slides[0]]);
        expect(slides[1].getAttribute("aria-hidden")).toBe("true");
        click(carousel.querySelector('button[aria-label="Slide berikutnya"]'));
        await flush();
        expect(visible()).toEqual([slides[1]]);
        click(carousel.querySelector('button[aria-label="Slide sebelumnya"]'));
        await flush();
        expect(visible()).toEqual([slides[0]]);
        const slideLink = slides[0].querySelector("a");
        expect(slideLink.getAttribute("href")).toBe("#");
        expect(slideLink.getAttribute("aria-disabled")).toBe("true");
        const playToggle = carousel.querySelector('button[aria-label="Jeda putar otomatis"]');
        click(playToggle);
        await flush();
        expect(playToggle.getAttribute("aria-label")).toBe("Putar otomatis");

        const sections = [
            ["terkini-title", "LATEST NEWS"],
            ["infografik-terbaru", "Infografik Terbaru"],
            ["pekan-ini", "Pekan Ini"],
        ];
        for (const [id, heading] of sections) {
            expect(root.querySelector(`h2#${id}`)?.textContent.trim()).toBe(heading);
        }
        expect(root.querySelectorAll('[aria-labelledby="terkini-title"] li').length).toBe(
            Math.min(3, beranda.terkini.length),
        );
        expect(root.querySelectorAll('[aria-labelledby="secondary-stories-title"] li').length).toBe(
            Math.max(beranda.terkini.length - 3, 0),
        );
        const secondaryThumbnails = root.querySelectorAll(
            '[aria-labelledby="secondary-stories-title"] [role="img"]',
        );
        expect(secondaryThumbnails).toHaveLength(Math.max(beranda.terkini.length - 3, 0));
        secondaryThumbnails.forEach((thumbnail, index) => {
            expect(thumbnail.getAttribute("aria-label")).toBe(beranda.terkini[index + 3].title);
        });
        const insightChart = root.querySelector('figure[data-testid="insight-chart"]');
        expect(insightChart).not.toBeNull();
        expect(insightChart.classList.contains("isf-chart-compact")).toBe(true);
        expect(insightChart.textContent).toContain(beranda.chart.title);
        const insightTable = insightChart.querySelector("table");
        expect(insightTable).not.toBeNull();
        expect(insightTable.closest(".sr-only")).not.toBeNull();
        await waitFor(
            () =>
                root.querySelectorAll('[aria-labelledby="pekan-ini"] a').length >=
                beranda.pekanIni.items.length,
            "kartu Pekan Ini tampil",
        );
        expect(
            root.querySelectorAll('[aria-labelledby="pekan-ini"] a').length,
        ).toBeGreaterThanOrEqual(beranda.pekanIni.items.length);
        const factBlocks = root.querySelectorAll('[aria-label^="Fakta Kunci"]');
        expect(factBlocks.length).toBe(beranda.factBlocks.length);
        factBlocks.forEach((block, index) => {
            expect(block.tagName).toBe("A");
            expect(block.querySelectorAll("a")).toHaveLength(0);
            expect(block.getAttribute("href")).toBe(beranda.factBlocks[index].sourceUrl);
            expect(block.getAttribute("target")).toBe("_blank");
            expect(block.getAttribute("rel")).toBe("noopener noreferrer");
            expect(block.textContent).toContain(beranda.factBlocks[index].sourceTitle);
        });

        const lihatSemua = Array.from(root.querySelectorAll("a")).find((link) =>
            link.textContent.includes("Lihat semua"),
        );
        expect(lihatSemua.getAttribute("href")).toBe("#");
        expect(lihatSemua.getAttribute("aria-disabled")).toBe("true");

        // (2) klik tautan isu dari beranda (judul hero)
        click(heroTitle.querySelector(`a[href="${ISU_PATH}"]`));
        await waitFor(() => router.currentRoute.value.path === ISU_PATH && root.querySelector("#isu-title"), "halaman isu");
        expect(router.currentRoute.value.name).toBe("isf-isu");
        expect(root.querySelector("#isu-title").textContent).toContain(isu.title);

        const frameworkHeadings = Array.from(root.querySelectorAll('h2[id^="fw-"]'));
        expect(frameworkHeadings.map((h) => h.id)).toEqual(["fw-01", "fw-02", "fw-03", "fw-04", "fw-05", "fw-06", "fw-07"]);
        expect(frameworkHeadings.map((h) => h.querySelector("span").textContent.trim())).toEqual(["01", "02", "03", "04", "05", "06", "07"]);
        expect(frameworkHeadings[4].textContent).toContain("Lensa GRC");
        const grcSection = frameworkHeadings[4].closest("section");
        for (const lens of ["Governance", "Risk", "Compliance"]) {
            expect(grcSection.textContent).toContain(lens);
        }
        expect(root.querySelector("#metodologi-title")?.textContent).toContain("Cara isu ini dipilih");

        const actorSection = frameworkHeadings[3].closest("section");
        const actorLink = actorSection.querySelector(`a[href="${TOKOH_PATH}"]`);
        expect(actorLink).not.toBeNull();
        expect(actorLink.parentElement.tagName).toBe("LI");
        expect(actorLink.querySelectorAll("a")).toHaveLength(0);
        expect(
            Array.from(actorSection.querySelectorAll("li")).find((item) =>
                item.textContent.includes("Asosiasi Pengusaha"),
            ).querySelector("a"),
        ).toBeNull();

        const grcTable = grcSection.querySelector("table");
        expect(grcTable.classList.contains("min-w-[42rem]")).toBe(true);
        expect(
            grcTable.closest(".overflow-x-auto") !== null ||
                grcTable.closest(".overflow-auto") !== null,
        ).toBe(true);

        const relatedSection = Array.from(root.querySelectorAll("section")).find((section) =>
            section.textContent.includes("Artikel Terkait"),
        );
        const relatedLinks = relatedSection.querySelectorAll("li > a");
        expect(relatedLinks).toHaveLength(isu.related.length);
        expect(Array.from(relatedLinks).filter((link) => link.getAttribute("href") === "#")).toHaveLength(2);

        // (3) klik tautan aktor → profil tokoh
        click(actorLink);
        await waitFor(() => router.currentRoute.value.path === TOKOH_PATH && root.querySelector("#skor-sorotan"), "halaman tokoh");
        expect(router.currentRoute.value.name).toBe("isf-tokoh");
        expect(text()).toContain(tokoh.name);
        expect(root.querySelector("#skor-sorotan").textContent).toContain("Skor Sorotan");
        expect(root.querySelector('[role="progressbar"]').getAttribute("aria-valuenow")).toBe(String(tokoh.skorSorotan.value));
        expect(root.querySelector("#jejak-kebijakan").textContent).toContain("Policy Matrix");
        const matrixTable = root.querySelector("#jejak-kebijakan").closest("section").querySelector("table");
        const matrixRows = matrixTable.querySelectorAll("tbody tr");
        expect(matrixTable.classList.contains("min-w-[48rem]")).toBe(true);
        expect(
            matrixTable.closest(".overflow-x-auto") !== null ||
                matrixTable.closest(".overflow-auto") !== null,
        ).toBe(true);
        expect(matrixRows.length).toBe(tokoh.policyMatrix.rows.length);
        const tokohRelatedSection = Array.from(root.querySelectorAll("section")).find((section) =>
            section.textContent.includes("Artikel Terkait"),
        );
        expect(tokohRelatedSection.querySelectorAll("li > a")).toHaveLength(tokoh.relatedArticles.length);

        const followButton = root.querySelector("button[aria-pressed]");
        expect(followButton.getAttribute("aria-pressed")).toBe("false");
        expect(followButton.textContent).toContain("Ikuti Tokoh");
        click(followButton);
        await flush();
        expect(followButton.getAttribute("aria-pressed")).toBe("true");
        expect(followButton.textContent).toContain("Mengikuti");
        click(followButton);
        await flush();
        expect(followButton.getAttribute("aria-pressed")).toBe("false");
    });

    it("/ (root) merender Beranda dengan judul hero", async () => {
        await mountAt("/");
        expect(router.currentRoute.value.name).toBe("isf-home");
        await waitFor(() => root.querySelector("#hero-title"), "beranda di /");
        expect(root.querySelector("#hero-title").textContent).toContain(beranda.hero.title);
        expect(text()).toContain("ISF Indonesia Strategy Forum");
        expect(root.querySelector(".isf-wordmark").textContent.replace(/\s+/g, " ").trim()).toBe(
            "ISF Indonesia Strategy Forum",
        );
    });

    it("(4) slug tidak dikenal → state not found untuk isu dan tokoh", async () => {
        await mountAt("/isu/slug-tidak-ada");
        expect(router.currentRoute.value.name).toBe("isf-isu");
        await waitFor(() => text().includes("Isu tidak ditemukan"), "isu not found (API 404)");
        expect(root.querySelector("#isu-title")).toBeNull();
        expect(root.querySelector('a[href="/beranda"]')).not.toBeNull();

        await router.push("/tokoh/slug-tidak-ada");
        await waitFor(() => text().includes("Tokoh tidak ditemukan"), "tokoh not found");
        expect(root.querySelector("#skor-sorotan")).toBeNull();
    });

    it("(5) /anggota: tabel alert → menu Prioritas Tinggi (merah) → filter Perlu Dipantau (kuning)", async () => {
        await mountAt("/anggota");
        expect(router.currentRoute.value.name).toBe("isf-anggota");
        await waitFor(() => text().includes("Menampilkan"), "alert dimuat dari API");
        expect(text()).not.toContain("Terjadi Kesalahan");

        let rows = alertRows();
        const alertTable = rows[0].closest("table");
        // Responsif: tabel punya lebar minimum + leluasa di-scroll horizontal lewat
        // pembungkus overflow (div .overflow-x-auto dari view atau wrapper UI Table).
        expect(alertTable.classList.contains("min-w-[42rem]")).toBe(true);
        expect(
            alertTable.closest(".overflow-x-auto") !== null ||
                alertTable.closest(".overflow-auto") !== null,
        ).toBe(true);
        expect(rows.length).toBe(anggota.alerts.length);
        expect(countIn(rows, "Prioritas Tinggi")).toBe(COUNT_MERAH);
        expect(countIn(rows, "Perlu Dipantau")).toBe(COUNT_KUNING);
        expect(text()).toContain(`Menampilkan ${anggota.alerts.length} alert`);

        // klik menu sidebar "Prioritas Tinggi"
        const nav = root.querySelector('nav[aria-label="Menu anggota"]');
        const menuMerah = Array.from(nav.querySelectorAll("a")).find((a) => a.textContent.trim() === "Prioritas Tinggi");
        click(menuMerah);
        await waitFor(() => router.currentRoute.value.query.prioritas === "merah", "query prioritas=merah");
        rows = alertRows();
        expect(rows.length).toBe(COUNT_MERAH);
        expect(countIn(rows, "Prioritas Tinggi")).toBe(COUNT_MERAH);
        expect(countIn(rows, "Perlu Dipantau")).toBe(0);
        expect(text()).toContain(`Menampilkan ${COUNT_MERAH} alert`);
        expect(menuMerah.getAttribute("aria-current")).toBe("page");

        // ubah filter lewat Select → "Perlu Dipantau" (kuning)
        const trigger = root.querySelector("#filter-prioritas");
        trigger.focus();
        trigger.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true, cancelable: true }));
        await waitFor(() => document.querySelector('[role="listbox"]'), "listbox Select terbuka");
        const optionKuning = Array.from(document.querySelectorAll('[role="option"]')).find((o) =>
            o.textContent.includes("Perlu Dipantau"),
        );
        expect(optionKuning).toBeDefined();
        optionKuning.focus();
        optionKuning.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true, cancelable: true }));
        await waitFor(() => router.currentRoute.value.query.prioritas === "kuning", "query prioritas=kuning");
        rows = alertRows();
        expect(rows.length).toBe(COUNT_KUNING);
        expect(countIn(rows, "Perlu Dipantau")).toBe(COUNT_KUNING);
        expect(countIn(rows, "Prioritas Tinggi")).toBe(0);
        expect(text()).toContain(`Menampilkan ${COUNT_KUNING} alert`);
        expect(menuMerah.getAttribute("aria-current")).toBeNull();
    });
});
