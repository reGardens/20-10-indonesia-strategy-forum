import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import IsfSlidePuzzleCaptcha from "../IsfSlidePuzzleCaptcha.vue";
import http from "@/api/http";

vi.mock("@/api/http", () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
}));

let app;
let root;

const mockChallenge = {
    key: "test-uuid-1234",
    bg_image: "data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=",
    piece_image: "data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=",
    puzzle_y: 65,
    width: 320,
    height: 160,
    piece_size: 44,
    piece_width: 58,
    piece_height: 58,
    max_offset: 276,
};

async function renderCaptcha(props = {}, listeners = {}) {
    root = document.createElement("div");
    document.body.appendChild(root);
    let instance = null;

    app = createApp({
        render() {
            return h(IsfSlidePuzzleCaptcha, {
                ...props,
                ...listeners,
                ref: (el) => {
                    instance = el;
                },
            });
        },
    });

    app.mount(root);
    await nextTick();
    await new Promise((r) => setTimeout(r, 20));
    await nextTick();

    return { root, instance };
}

describe("IsfSlidePuzzleCaptcha", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        http.get.mockResolvedValue({ data: mockChallenge });
        http.post.mockResolvedValue({ data: { success: true } });
    });

    afterEach(() => {
        if (app) app.unmount();
        if (root && root.parentNode) root.parentNode.removeChild(root);
    });

    it("fetches challenge on mount and renders puzzle canvas", async () => {
        const { root } = await renderCaptcha();

        expect(http.get).toHaveBeenCalledWith("/isf/captcha/puzzle");

        const bgImg = root.querySelector("img[alt='Gambar Latar Puzzle ISF']");
        const pieceImg = root.querySelector("img[alt='Kepingan Puzzle']");

        expect(bgImg).not.toBeNull();
        expect(pieceImg).not.toBeNull();
        expect(root.textContent).toContain("Verifikasi Puzzle");
        expect(root.textContent).toContain("Geser slider untuk melengkapi puzzle");
    });

    it("renders slider handle and track with proper accessibility attributes", async () => {
        const { root } = await renderCaptcha();

        const slider = root.querySelector("[role='slider']");
        expect(slider).not.toBeNull();
        expect(slider.getAttribute("aria-valuemin")).toBe("0");
        expect(slider.getAttribute("aria-label")).toContain("Geser kepingan puzzle");
    });

    it("clicking reload button refetches puzzle challenge", async () => {
        const { root } = await renderCaptcha();

        expect(http.get).toHaveBeenCalledTimes(1);

        const reloadBtn = root.querySelector("button[aria-label='Muat ulang gambar puzzle baru']");
        expect(reloadBtn).not.toBeNull();

        reloadBtn.click();
        await nextTick();
        await new Promise((r) => setTimeout(r, 20));

        expect(http.get).toHaveBeenCalledTimes(2);
    });

    it("verifies offset and emits verified event when slider is moved and submitted", async () => {
        http.post.mockResolvedValueOnce({
            data: { success: true, message: "Verifikasi puzzle berhasil!" },
        });

        let verifiedPayload = null;
        const { root } = await renderCaptcha({}, {
            onVerified: (payload) => {
                verifiedPayload = payload;
            },
        });

        const slider = root.querySelector("[role='slider']");
        slider.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowRight", shiftKey: true, bubbles: true }));
        await nextTick();
        slider.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowRight", shiftKey: true, bubbles: true }));
        await nextTick();
        slider.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true }));
        await nextTick();
        await new Promise((r) => setTimeout(r, 30));
        await nextTick();

        expect(http.post).toHaveBeenCalledWith("/isf/captcha/puzzle/verify", expect.objectContaining({
            key: "test-uuid-1234",
        }));

        expect(verifiedPayload).not.toBeNull();
        expect(verifiedPayload).toHaveProperty("key", "test-uuid-1234");
        expect(root.textContent).toContain("Verifikasi Berhasil");
    });

    it("displays error feedback when verification fails", async () => {
        http.post.mockResolvedValueOnce({
            data: { success: false, message: "Posisi kepingan puzzle belum tepat." },
        });

        const { root } = await renderCaptcha();

        const slider = root.querySelector("[role='slider']");
        slider.dispatchEvent(new KeyboardEvent("keydown", { key: "ArrowRight", shiftKey: true, bubbles: true }));
        await nextTick();
        slider.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true }));
        await nextTick();
        await new Promise((r) => setTimeout(r, 30));
        await nextTick();

        expect(root.textContent).toContain("Posisi belum tepat");
    });
});
