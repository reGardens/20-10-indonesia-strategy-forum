import { afterEach, beforeEach, describe, expect, it } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import IsfHeader from "../IsfHeader.vue";
import { useIsfHeaderLayout } from "@/Composables/useIsfHeaderLayout";
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
    it("merender Row 2 secara default dengan Anton 8rem dan background dark navy", async () => {
        const { root } = await renderHeader();
        const wordmark = root.querySelector(".isf-wordmark");
        expect(wordmark).not.toBeNull();
        expect(wordmark.textContent.replace(/\s+/g, " ").trim()).toBe(
            "ISF Indonesia Strategy Forum",
        );
        expect(root.querySelector("header").classList.contains("w-full")).toBe(
            true,
        );
        expect(root.querySelector("header").classList.contains("bg-isf-navy")).toBe(
            true,
        );
    });

    it("tidak memiliki switcher di dalam header, dan sinkron secara reaktif dengan useIsfHeaderLayout", async () => {
        const { root } = await renderHeader();
        // Switcher dipindahkan ke bawah sorotan di Beranda, tidak ada tombol switcher di header
        const btnRow1 = root.querySelector('button[title*="Row 1"]');
        expect(btnRow1).toBeNull();

        const { setLayout } = useIsfHeaderLayout();

        // Switch to Row 1
        setLayout("single-row");
        await nextTick();
        expect(localStorage.getItem("isf_header_layout")).toBe("single-row");
        expect(
            root.querySelector(".isf-wordmark").textContent.replace(/\s+/g, " ").trim(),
        ).toBe("ISF Indonesia Strategy Forum");

        // Switch to Row 2
        setLayout("two-rows");
        await nextTick();
        expect(localStorage.getItem("isf_header_layout")).toBe("two-rows");
        expect(
            root.querySelector(".isf-wordmark").textContent.replace(/\s+/g, " ").trim(),
        ).toBe("ISF Indonesia Strategy Forum");
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
