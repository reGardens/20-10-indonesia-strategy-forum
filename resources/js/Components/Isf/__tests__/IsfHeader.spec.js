import { afterEach, beforeEach, describe, expect, it } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import IsfHeader from "../IsfHeader.vue";
import { useIsfHeaderLayout } from "@/Composables/useIsfHeaderLayout";
import { useIsfHeaderFont } from "@/Composables/useIsfHeaderFont";
import appRouter from "@/router/index.js";

let app;
let root;

async function renderHeader(props = {}) {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: appRouter.options.routes,
    });
    await router.push("/beranda");
    await router.isReady();

    root = document.createElement("div");
    document.body.appendChild(root);
    app = createApp({
        render: () => h(IsfHeader, props),
    });
    app.use(router);
    app.mount(root);
    await nextTick();
    return { root, router };
}

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = "";
});

afterEach(() => {
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
});

describe("IsfHeader", () => {
    it("merender format Row 3 secara permanen dengan wordmark inline dan background dark navy", async () => {
        const { root } = await renderHeader();
        const wordmark = root.querySelector(".isf-wordmark");
        expect(wordmark).not.toBeNull();
        expect(wordmark.textContent.replace(/\s+/g, " ").trim()).toBe(
            "ISF Indonesia Strategy Forum",
        );
        expect(root.querySelector(".isf-masthead--row3")).not.toBeNull();
        // Memastikan varian Row 2 (font Anton 8rem) sudah di-takeout
        expect(root.querySelector(".font-anton")).toBeNull();
        expect(root.querySelector("header").classList.contains("w-full")).toBe(
            true,
        );
        expect(root.querySelector("header").classList.contains("bg-isf-navy")).toBe(
            true,
        );
    });

    it("mengunci layout secara permanen ke row-3 pada useIsfHeaderLayout dan membersihkan isf_header_layout dari localStorage", async () => {
        localStorage.setItem("isf_header_layout", "row-3");
        await renderHeader();
        const { layout, setLayout } = useIsfHeaderLayout();

        expect(layout.value).toBe("row-3");
        expect(localStorage.getItem("isf_header_layout")).toBeNull();

        // Memanggil setLayout varian lama tidak mengubah format (tetap terkunci row-3) dan tidak menulis ke localStorage
        setLayout("single-row");
        await nextTick();
        expect(layout.value).toBe("row-3");
        expect(localStorage.getItem("isf_header_layout")).toBeNull();

        setLayout("two-rows");
        await nextTick();
        expect(layout.value).toBe("row-3");
        expect(localStorage.getItem("isf_header_layout")).toBeNull();
    });

    it("menggunakan teks wording bahasa Indonesia yang baku (Masuk, bukan Login)", async () => {
        const { root } = await renderHeader();
        const loginLinks = root.querySelectorAll('a[href="/login"]');
        expect(loginLinks.length).toBeGreaterThan(0);
        loginLinks.forEach((link) => {
            expect(link.textContent.trim()).toBe("Masuk");
        });
    });

    it("tidak memiliki tombol switcher/emulator di dalam header (editorial bersih)", async () => {
        const { root } = await renderHeader();
        expect(root.querySelector('[data-testid="header-font-selector"]')).toBeNull();
        expect(root.querySelector('[data-testid="header-layout-switcher"]')).toBeNull();
    });

    it("header terstandarisasi secara permanen dengan font Montserrat", async () => {
        const { root } = await renderHeader();
        const headerEl = root.querySelector("header");

        // Standard font: montserrat
        expect(headerEl.classList.contains("isf-font-montserrat")).toBe(true);
        expect(document.documentElement.getAttribute("data-isf-font")).toBe("montserrat");
    });

    it("memiliki kontrol aksesibilitas pencarian dan menu hamburger", async () => {
        const { root } = await renderHeader();
        const searchBtn = root.querySelector('button[aria-label="Buka pencarian"]');
        const menuBtn = root.querySelector('button[aria-controls="isf-menu-panel"]');

        expect(searchBtn).not.toBeNull();
        expect(menuBtn).not.toBeNull();
        expect(searchBtn.getAttribute("aria-expanded")).toBe("false");
        expect(menuBtn.getAttribute("aria-expanded")).toBe("false");

        menuBtn.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        await nextTick();
        expect(menuBtn.getAttribute("aria-expanded")).toBe("true");

        searchBtn.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        await nextTick();
        expect(searchBtn.getAttribute("aria-expanded")).toBe("true");
        expect(menuBtn.getAttribute("aria-expanded")).toBe("false");
    });
});
