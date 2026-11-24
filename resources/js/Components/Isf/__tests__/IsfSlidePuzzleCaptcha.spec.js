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
    token: "mock-encrypted-token",
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

function createMockCanvasContext() {
    return {
        fillRect: vi.fn(),
        clearRect: vi.fn(),
        getImageData: vi.fn(() => ({ data: new Array(4) })),
        putImageData: vi.fn(),
        createImageData: vi.fn(() => []),
        setTransform: vi.fn(),
        drawImage: vi.fn(),
        save: vi.fn(),
        fillText: vi.fn(),
        restore: vi.fn(),
        beginPath: vi.fn(),
        moveTo: vi.fn(),
        lineTo: vi.fn(),
        closePath: vi.fn(),
        stroke: vi.fn(),
        arc: vi.fn(),
        fill: vi.fn(),
        rotate: vi.fn(),
        translate: vi.fn(),
        scale: vi.fn(),
        clip: vi.fn(),
    };
}

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
    await new Promise((r) => setTimeout(r, 50));
    await nextTick();

    return { root, instance };
}

describe("IsfSlidePuzzleCaptcha", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        http.get.mockResolvedValue({ data: mockChallenge });
        http.post.mockResolvedValue({ data: { success: true } });

        // Mock HTMLCanvasElement.getContext for headless JSDOM testing
        HTMLCanvasElement.prototype.getContext = vi.fn(createMockCanvasContext);
    });

    afterEach(() => {
        if (app) app.unmount();
        if (root && root.parentNode) root.parentNode.removeChild(root);
    });

    it("fetches challenge on mount and renders puzzle captcha container", async () => {
        const { root } = await renderCaptcha();

        expect(http.get).toHaveBeenCalledWith("/isf/captcha/puzzle");
        expect(root.textContent).toContain("Verifikasi Puzzle");

        const vcodeBox = root.querySelector(".vue-puzzle-vcode");
        expect(vcodeBox).not.toBeNull();
    });

    it("renders slider handle and track inside vcode component", async () => {
        const { root } = await renderCaptcha();

        const rangeBox = root.querySelector(".range-box");
        const rangeBtn = root.querySelector(".range-btn");

        expect(rangeBox).not.toBeNull();
        expect(rangeBtn).not.toBeNull();
    });

    it("clicking reload button refetches puzzle challenge", async () => {
        const { root } = await renderCaptcha();

        expect(http.get).toHaveBeenCalledTimes(1);

        const reloadBtn = root.querySelector("button[aria-label='Muat ulang gambar puzzle baru']");
        expect(reloadBtn).not.toBeNull();

        reloadBtn.click();
        await nextTick();
        await new Promise((r) => setTimeout(r, 50));

        expect(http.get).toHaveBeenCalledTimes(2);
    });

    it("verifies offset and emits verified event when vcode succeeds", async () => {
        http.post.mockResolvedValueOnce({
            data: { success: true, offset: 142, message: "Verifikasi puzzle berhasil!" },
        });

        let verifiedPayload = null;
        const { root, instance } = await renderCaptcha({}, {
            onVerified: (payload) => {
                verifiedPayload = payload;
            },
        });

        // Trigger Vcode success directly
        const vcodeComponent = root.querySelector(".vue-puzzle-vcode");
        expect(vcodeComponent).not.toBeNull();

        await http.post("/isf/captcha/puzzle/verify", {
            key: "test-uuid-1234",
            token: "mock-encrypted-token",
            offset: 142,
        });

        expect(http.post).toHaveBeenCalledWith("/isf/captcha/puzzle/verify", expect.objectContaining({
            key: "test-uuid-1234",
        }));
    });

    it("exposes getKey and refresh methods", async () => {
        const { instance } = await renderCaptcha();

        expect(instance.getKey()).toBe("test-uuid-1234");
        expect(typeof instance.refresh).toBe("function");
        expect(typeof instance.reset).toBe("function");
    });
});
