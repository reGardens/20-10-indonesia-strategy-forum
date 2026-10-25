/**
 * IsfLink — CMS link targets: SPA path → router-link, other in-site path → plain <a>,
 * absolute http(s) URL → <a target="_blank" rel="noopener noreferrer">.
 */
import { afterEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import IsfLink from "../IsfLink.vue";
import appRouter from "@/router/index.js";

let app;

async function render(to) {
    const router = createRouter({ history: createMemoryHistory(), routes: appRouter.options.routes });
    await router.push("/anggota");
    await router.isReady();
    const root = document.createElement("div");
    document.body.appendChild(root);
    app = createApp({ render: () => h(IsfLink, { to, class: "link" }, () => "Judul") });
    app.use(router);
    app.mount(root);
    await nextTick();
    return { link: root.querySelector("a"), router };
}

afterEach(() => {
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
});

describe("IsfLink", () => {
    it("SPA path → router-link (client-side navigation)", async () => {
        const { link, router } = await render("/isu/kuhp-baru-pelaku-usaha");
        expect(link.getAttribute("href")).toBe("/isu/kuhp-baru-pelaku-usaha");
        expect(link.getAttribute("target")).toBeNull();
        expect(link.classList.contains("link")).toBe(true);
        const push = vi.spyOn(router, "push");
        const event = new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 });
        link.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
        expect(push).toHaveBeenCalledWith("/isu/kuhp-baru-pelaku-usaha");
    });

    it("absolute URL → external <a> in a new tab (not /https://…)", async () => {
        const { link } = await render("https://example.com/a");
        expect(link.getAttribute("href")).toBe("https://example.com/a");
        expect(link.getAttribute("target")).toBe("_blank");
        expect(link.getAttribute("rel")).toBe("noopener noreferrer");
        expect(link.textContent).toContain("(membuka tab baru)");
        expect(link.classList.contains("link")).toBe(true);
    });

    it("protocol-relative URL (//host) → external <a> in a new tab", async () => {
        const { link } = await render("//example.com/a");
        expect(link.getAttribute("href")).toBe("//example.com/a");
        expect(link.getAttribute("target")).toBe("_blank");
        expect(link.getAttribute("rel")).toBe("noopener noreferrer");
    });

    it("in-site path outside the SPA → plain <a> (full page load)", async () => {
        const warn = vi.spyOn(console, "warn").mockImplementation(() => {});
        const { link, router } = await render("/anggota/briefing/preview");
        warn.mockRestore();
        expect(link.getAttribute("href")).toBe("/anggota/briefing/preview");
        expect(link.getAttribute("target")).toBeNull();
        const push = vi.spyOn(router, "push");
        // Plain <a>: the router does not intercept the click (jsdom would navigate; prevent it here).
        link.addEventListener("click", (e) => e.preventDefault());
        link.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 }));
        expect(push).not.toHaveBeenCalled();
    });

    it("not-yet-available target → placeholder href='#' that does not navigate", async () => {
        const warn = vi.spyOn(console, "warn").mockImplementation(() => {});
        const { link } = await render("/artikel/belum-ada");
        warn.mockRestore();
        expect(link.getAttribute("href")).toBe("#");
        expect(link.getAttribute("aria-disabled")).toBe("true");
        const event = new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 });
        link.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
    });


    it("unsupported target → prevented placeholder without external attributes", async () => {
        const { link } = await render("javascript:alert(1)");
        expect(link.getAttribute("href")).toBe("#");
        expect(link.getAttribute("target")).toBeNull();
        expect(link.getAttribute("rel")).toBeNull();
        const event = new MouseEvent("click", { bubbles: true, cancelable: true, button: 0 });
        link.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
    });
});
