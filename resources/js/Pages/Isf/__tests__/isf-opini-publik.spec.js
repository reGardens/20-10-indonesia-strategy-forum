import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createApp, h, nextTick } from "vue";
import { createMemoryHistory, createRouter } from "vue-router";
import ReaderPollCard from "@/Components/Isf/ReaderPollCard.vue";
import KutipanPublikCard from "@/Components/Isf/KutipanPublikCard.vue";
import SuratPembacaForm from "@/Components/Isf/SuratPembacaForm.vue";
import OpiniPublikView from "@/Pages/Isf/views/OpiniPublikView.vue";
import appRouter from "@/router/index.js";
import * as isfPublicService from "@/api/services/isfPublicService";

let app;
let root;

const mockOpiniData = {
    rubrik: {
        key: "opini",
        title: "Opini Publik",
        label: "Opini Publik",
        description: "Wadah dialektika pemikiran, analisis kritis akademisi, serta sikap resmi dewan redaksi.",
        subkategori: [
            { name: "Op-Ed & Pakar", slug: "op-ed", description: "Tulisan pakar tamu independen" },
            { name: "Editorial ISF", slug: "editorial", description: "Sikap resmi dewan redaksi" },
            { name: "Surat Pembaca", slug: "surat-pembaca", description: "Aspirasi masyarakat luas" },
        ],
        activeSubkategori: null,
    },
    headline: {
        slug: "editorial-menjaga-demokrasi",
        title: "Editorial ISF: Menjaga Kedaulatan Hukum",
        is_editorial: true,
        subcategory: "editorial",
        subcategory_name: "Editorial ISF",
        excerpt: "Sikap redaksi atas pentingnya menjaga independensi peradilan.",
        published_at: "2026-11-16",
        author_name: "Dewan Redaksi ISF",
        author_affiliation: "Sikap Kebijakan Redaksi",
        to: "/artikel/opini/editorial-menjaga-demokrasi",
    },
    articles: [
        {
            slug: "op-ed-reformasi-fiskal",
            title: "Urgensi Pembenahan Tata Kelola Pajak",
            subcategory: "op-ed",
            subcategory_name: "Op-Ed & Pakar",
            excerpt: "Analisis kritis akademisi atas reformasi regulasi perpajakan.",
            published_at: "2026-11-15",
            author_name: "Dr. Budi Santoso",
            author_affiliation: "Guru Besar FEB UI",
            to: "/artikel/opini/op-ed-reformasi-fiskal",
        },
    ],
    meta: {
        currentPage: 1,
        lastPage: 1,
        total: 2,
        perPage: 9,
    },
    readerPoll: {
        id: 1,
        question: "Apakah Anda setuju dengan efisiensi anggaran kementerian?",
        description: "Jajak pendapat publik interaktif non-ilmiah",
        total_votes: 100,
        options: [
            { id: "opt_1", label: "Sangat Setuju", votes: 65, percentage: 65 },
            { id: "opt_2", label: "Ragu-ragu", votes: 20, percentage: 20 },
            { id: "opt_3", label: "Tidak Setuju", votes: 15, percentage: 15 },
        ],
        status: "aktif",
        has_voted: false,
    },
    kutipanPublik: [
        {
            id: 1,
            kutipan: "Transparansi adalah fondasi utama bagi kepercayaan rakyat terhadap institusi publik.",
            tokoh_nama: "Prof. Dr. Mahfud MD",
            tokoh_jabatan: "Guru Besar Hukum Tata Negara",
            tokoh_tipe: "tokoh",
            tokoh_id: 1,
            tokoh: { slug: "mahfud-md" },
            isu_terkait: "Reformasi Hukum",
            sumber_nama: "Kompas TV",
            sumber_url: "https://example.test",
            tanggal_kutipan: "2026-11-15",
        },
        {
            id: 2,
            kutipan: "Kami sebagai pelaku UMKM hanya butuh kepastian aturan dan kemudahan akses kredit.",
            tokoh_nama: "Ahmad Fauzi",
            tokoh_jabatan: "Pelaku UMKM Kuliner",
            tokoh_tipe: "netizen",
            isu_terkait: "Kebijakan UMKM",
            sumber_nama: "Twitter / X",
            tanggal_kutipan: "2026-11-14",
        },
    ],
    suratPembaca: [
        {
            id: 1,
            nama_pengirim: "Hendra Gunawan",
            email_pengirim: "hendra@example.test",
            kota_domisili: "Bandung, Jawa Barat",
            judul_surat: "Catatan Kritis atas RUU Energi Baru Terbarukan",
            isi_surat: "Pemerintah perlu mempercepat deregulasi perizinan PLTS atap bagi sektor industri...",
            moderation_status: "disetujui",
            created_at: "2026-11-15T10:00:00Z",
        },
    ],
};

function mountComponent(Component, props = {}) {
    root = document.createElement("div");
    document.body.appendChild(root);

    const testRouter = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: "/", component: { template: "<div />" } },
            { path: "/opini-publik", component: Component },
            { path: "/opini-publik/:subkategori", component: Component },
            { path: "/artikel/:slug", component: { template: "<div />" } },
            { path: "/tokoh/:slug", component: { template: "<div />" } },
        ],
    });

    app = createApp({
        render() {
            return h(Component, props);
        },
    });
    app.use(testRouter);
    app.mount(root);
    return { root, router: testRouter };
}

describe("Rubrik Opini Publik ISF", () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        window.localStorage.clear();
    });

    afterEach(() => {
        if (app) {
            app.unmount();
            app = null;
        }
        if (root && root.parentNode) {
            root.parentNode.removeChild(root);
            root = null;
        }
    });

    it("ReaderPollCard menampilkan pertanyaan, opsi voting, dan label non-ilmiah", () => {
        const { root: el } = mountComponent(ReaderPollCard, {
            poll: mockOpiniData.readerPoll,
        });

        expect(el.textContent).toContain("Apakah Anda setuju dengan efisiensi anggaran kementerian?");
        expect(el.textContent).toContain("Survei Persepsi");
        expect(el.textContent).toContain("Non-Ilmiah");
        expect(el.textContent).toContain("Sangat Setuju");
        expect(el.textContent).toContain("Ragu-ragu");
        expect(el.textContent).toContain("Tidak Setuju");
    });

    it("KutipanPublikCard menampilkan kutipan, penutur, badge, dan tautan tokoh jika ada", () => {
        const quoteTokoh = mockOpiniData.kutipanPublik[0];
        const { root: elTokoh } = mountComponent(KutipanPublikCard, {
            quote: quoteTokoh,
            theme: "dark",
        });

        expect(elTokoh.textContent).toContain("Transparansi adalah fondasi utama");
        expect(elTokoh.textContent).toContain("Prof. Dr. Mahfud MD");
        expect(elTokoh.textContent).toContain("Tokoh Publik");
        expect(elTokoh.textContent).toContain("#Reformasi Hukum");
        expect(elTokoh.querySelector('a[href="/tokoh/mahfud-md"]')).not.toBeNull();

        // Netizen card
        const quoteNetizen = mockOpiniData.kutipanPublik[1];
        const { root: elNetizen } = mountComponent(KutipanPublikCard, {
            quote: quoteNetizen,
            theme: "dark",
        });

        expect(elNetizen.textContent).toContain("Kami sebagai pelaku UMKM");
        expect(elNetizen.textContent).toContain("Ahmad Fauzi");
        expect(elNetizen.textContent).toContain("Suara Netizen");
    });

    it("SuratPembacaForm memuat field nama, email, kota, judul, isi, dan captcha", () => {
        const { root: el } = mountComponent(SuratPembacaForm);

        expect(el.textContent).toContain("Buat Artikel");
        expect(el.querySelector('input[id="sp_nama"]')).not.toBeNull();
        expect(el.querySelector('input[id="sp_email"]')).not.toBeNull();
        expect(el.querySelector('input[id="sp_kota"]')).not.toBeNull();
        expect(el.querySelector('input[id="sp_judul"]')).not.toBeNull();
        expect(el.querySelector('textarea[id="sp_isi"]')).not.toBeNull();
        expect(el.querySelector('input[id="sp_captcha"]')).not.toBeNull();
    });

    it("Router mendaftarkan rute /opini-publik dan subkategorinya dengan benar", () => {
        const routes = appRouter.getRoutes();
        const mainRoute = routes.find((r) => r.path === "/opini-publik");
        expect(mainRoute).toBeDefined();

        const subkategoriRoute = routes.find((r) => r.name === "isf-opini-publik-subkategori");
        expect(subkategoriRoute).toBeDefined();

        const opiniRedirect = routes.find((r) => r.path === "/artikel/opini");
        expect(opiniRedirect).toBeDefined();
    });
});

