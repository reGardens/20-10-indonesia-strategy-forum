import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";

// GET /api/isf/me & POST /logout dimock; status sesi dikendalikan per test.
const session = vi.hoisted(() => ({ value: { authenticated: false, name: null, initials: null } }));
const getSessionSpy = vi.hoisted(() => vi.fn());
const logoutSpy = vi.hoisted(() => vi.fn(() => Promise.resolve()));
vi.mock("@/api/services/isfSessionService", () => ({
    getSession: (...args) => {
        getSessionSpy(...args);
        return Promise.resolve(session.value);
    },
    logout: logoutSpy,
    default: {},
}));
vi.mock("@/api/services/isfPublicService", () => ({
    getAnggota: () => Promise.resolve({ user: { name: "Anindya Putri" } }),
    searchContent: () => Promise.resolve({ data: [] }),
    default: {},
}));

let app;

// useIsfSession menyimpan state di level modul → muat ulang modul per test agar terisolasi.
async function renderAt(path, { flushSession = true } = {}) {
    vi.resetModules();
    const { default: IsfHeader } = await import("../IsfHeader.vue");
    const { default: appRouter } = await import("@/router/index.js");
    const router = createRouter({ history: createMemoryHistory(), routes: appRouter.options.routes });
    await router.push(path);
    await router.isReady();
    const root = document.createElement("div");
    document.body.appendChild(root);
    app = createApp({ render: () => h(IsfHeader) });
    app.use(router);
    app.mount(root);
    await nextTick();
    if (flushSession) {
        await new Promise((r) => setTimeout(r, 0));
        await nextTick();
    }
    return root;
}

const loginLinks = (root) => [...root.querySelectorAll('a[href="/login"]')];
const click = (el) => el.dispatchEvent(new MouseEvent("click", { bubbles: true }));

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = "";
    delete window.__ISF_SESSION__;
    getSessionSpy.mockClear();
    logoutSpy.mockClear();
});

afterEach(() => {
    app?.unmount();
    app = undefined;
    document.body.innerHTML = "";
    delete window.__ISF_SESSION__;
});

// Import modul header + router per test cukup berat → timeout longgar agar test tidak saling bocor.
describe("IsfHeader — status login", { timeout: 90000 }, () => {
    it("tamu: menampilkan Login, tanpa menu profil", async () => {
        session.value = { authenticated: false, name: null, initials: null };
        const root = await renderAt("/tokoh/budi-santoso");

        expect(loginLinks(root).length).toBeGreaterThan(0);
        expect(root.querySelector('[data-testid="profile-menu-trigger"]')).toBeNull();
    });

    it("login (data awal dari server): ikon avatar langsung tampil di render pertama, tanpa request /me", async () => {
        window.__ISF_SESSION__ = { authenticated: true, name: "Rina Kartika" };
        const root = await renderAt("/tokoh/budi-santoso", { flushSession: false });

        // render pertama, sebelum promise apa pun selesai → tidak ada "Login" sekejap
        expect(loginLinks(root)).toHaveLength(0);
        const trigger = root.querySelector('[data-testid="profile-menu-trigger"]');
        expect(trigger).not.toBeNull();
        expect(trigger.textContent.trim()).toBe("RK");
        expect(getSessionSpy).not.toHaveBeenCalled();
    });

    it("ikon avatar → menu berisi nama + Keluar; Esc menutup menu", async () => {
        window.__ISF_SESSION__ = { authenticated: true, name: "Rina Kartika" };
        const root = await renderAt("/beranda");
        const trigger = root.querySelector('[data-testid="profile-menu-trigger"]');
        const menu = document.querySelector('[data-testid="profile-menu"]');

        // nama tidak tampil di header, hanya di dalam menu
        expect(menu.style.display).toBe("none");
        expect(trigger.getAttribute("aria-expanded")).toBe("false");

        click(trigger);
        await nextTick();
        expect(trigger.getAttribute("aria-expanded")).toBe("true");
        expect(menu.style.display).not.toBe("none");
        expect(document.querySelector('[data-testid="profile-menu-name"]').textContent).toContain("Rina Kartika");
        expect(document.querySelector('[data-testid="header-logout"]').textContent).toContain("Keluar");

        menu.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
        await nextTick();
        expect(trigger.getAttribute("aria-expanded")).toBe("false");
    });

    it("klik Keluar di menu memanggil POST /logout lalu memuat ulang halaman", async () => {
        window.__ISF_SESSION__ = { authenticated: true, name: "Rina Kartika" };
        const reload = vi.fn();
        const original = window.location;
        Object.defineProperty(window, "location", { configurable: true, value: { ...original, reload } });
        try {
            const root = await renderAt("/beranda");
            click(root.querySelector('[data-testid="profile-menu-trigger"]'));
            await nextTick();
            click(document.querySelector('[data-testid="header-logout"]'));
            await new Promise((r) => setTimeout(r, 0));
            expect(logoutSpy).toHaveBeenCalledTimes(1);
            expect(reload).toHaveBeenCalledTimes(1);
        } finally {
            Object.defineProperty(window, "location", { configurable: true, value: original });
        }
    });

    it("tanpa data awal: status login diambil dari /api/isf/me", async () => {
        session.value = { authenticated: true, name: "Rina Kartika", initials: "RK" };
        const root = await renderAt("/beranda");

        expect(getSessionSpy).toHaveBeenCalledTimes(1);
        expect(root.querySelector('[data-testid="profile-menu-trigger"]')).not.toBeNull();
    });

    it("tamu di /anggota: tanpa nama contoh, tampil tombol Login (tanpa menu profil)", async () => {
        session.value = { authenticated: false, name: null, initials: null };
        const root = await renderAt("/anggota");
        expect(root.textContent).not.toContain("Anindya Putri");
        expect(root.querySelector('a[href="/login"]')).not.toBeNull();
        expect(root.querySelector('[data-testid="profile-menu-trigger"]')).toBeNull();
    });
});
