import http from "@/api/http";

/**
 * ISF CMS JSON API (/api/cms/*). Session auth + per-module ACL on the server.
 * Errors are axios errors: 422 → error.response.data.errors (field → messages).
 */

/**
 * CRUD client for one CMS module.
 * @param {string} path URL segment under /api/cms (e.g. "artikel")
 */
export function createCmsResource(path) {
    const base = `/cms/${path}`;
    return {
        /** Paginated list: { data, current_page, last_page, total, ... } */
        list: (params = {}) => http.get(base, { params }).then((r) => r.data),
        get: (id) => http.get(`${base}/${id}`).then((r) => r.data.data),
        create: (data) => http.post(base, data).then((r) => r.data.data),
        update: (id, data) =>
            http.put(`${base}/${id}`, data).then((r) => r.data.data),
        remove: (id) => http.delete(`${base}/${id}`),
    };
}

export const cmsResources = {
    banner: {
        ...createCmsResource("banner"),
        reorder: (items) =>
            http.post("/cms/banner/reorder", { items }).then((r) => r.data),
    },
    artikel: {
        ...createCmsResource("artikel"),
        setHero: (id) =>
            http.post(`/cms/artikel/${id}/hero`).then((r) => r.data),
        toggleRunningText: (id, payload = {}) =>
            http.post(`/cms/artikel/${id}/toggle-running-text`, payload).then((r) => r.data),
        toggleTopRank: (id, payload = {}) =>
            http.post(`/cms/artikel/${id}/toggle-top-rank`, payload).then((r) => r.data),
    },
    infografik: createCmsResource("infografik"),
    tokoh: {
        ...createCmsResource("tokoh"),
        setFeatured: (id) =>
            http.post(`/cms/tokoh/${id}/set-featured`).then((r) => r.data),
        togglePolitik: (id) =>
            http.post(`/cms/tokoh/${id}/toggle-politik`).then((r) => r.data),
    },
    kategoriLembaga: createCmsResource("kategori-lembaga"),
    dataGrafik: {
        ...createCmsResource("data-grafik"),
        reorder: (items) =>
            http
                .post("/cms/data-grafik/reorder", { items })
                .then((r) => r.data),
    },
    alert: createCmsResource("alert"),
    emailBriefing: createCmsResource("email-briefing"),
    runningText: createCmsResource("running-text"),
    mediaSorotan: createCmsResource("media-sorotan"),
    hotNews: {
        ...createCmsResource("hot-news"),
        saveAll: (items) =>
            http.put("/cms/hot-news", { items }).then((r) => r.data.data),
        getArticleOptions: () =>
            http.get("/cms/hot-news/article-options").then((r) => r.data.data),
        syncArticles: () =>
            http.post("/cms/hot-news/sync-articles").then((r) => r.data.data),
    },
    topRank: {
        get: () => http.get("/cms/top-rank").then((r) => r.data.data),
        update: (items) => http.put("/cms/top-rank", { items }).then((r) => r.data),
        getArticleOptions: () => http.get("/cms/top-rank/article-options").then((r) => r.data.data),
    },
    kategori: createCmsResource("kategori"),
    kursiParlemen: {
        ...createCmsResource("kursi-parlemen"),
        reorder: (items) =>
            http
                .post("/cms/kursi-parlemen/reorder", { items })
                .then((r) => r.data),
    },
    glosariumHukum: createCmsResource("glosarium-hukum"),
    istilahHukum: createCmsResource("istilah-hukum"),
    timelineKasus: createCmsResource("timeline-kasus"),
    policyBrief: createCmsResource("policy-brief"),
    kategoriSektor: createCmsResource("kategori-sektor"),
    databasePeraturan: createCmsResource("database-peraturan"),
    monitoringKebijakan: createCmsResource("monitoring-kebijakan"),
    readerPoll: createCmsResource("reader-poll"),
    kutipanPublik: createCmsResource("kutipan-publik"),
    suratPembaca: {
        ...createCmsResource("surat-pembaca"),
        approve: (id, notes = "") =>
            http.post(`/cms/surat-pembaca/${id}/approve`, { notes }).then((r) => r.data),
        reject: (id, notes = "") =>
            http.post(`/cms/surat-pembaca/${id}/reject`, { notes }).then((r) => r.data),
    },
    survei: createCmsResource("survei"),
    laporanMember: createCmsResource("laporan-member"),
    kemitraan: {
        ...createCmsResource("kemitraan"),
        markContacted: (id, notes = "") =>
            http
                .post(`/cms/kemitraan/${id}/contacted`, { notes })
                .then((r) => r.data),
        updateStatus: (id, status, internal_notes = "") =>
            http
                .post(`/cms/kemitraan/${id}/status`, { status, internal_notes })
                .then((r) => r.data),
        getSections: () =>
            http.get("/cms/kemitraan-sections").then((r) => r.data),
        updateSections: (payload) =>
            http.put("/cms/kemitraan-sections", payload).then((r) => r.data),
        exportCsvUrl: (params = {}) => {
            const qs = new URLSearchParams(params).toString();
            return `/api/cms/kemitraan/export${qs ? "?" + qs : ""}`;
        },
    },
    iklan: {
        ...createCmsResource("iklan"),
        reorder: (items) =>
            http.post("/cms/iklan/reorder", { items }).then((r) => r.data),
        toggleActive: (id) =>
            http.post(`/cms/iklan/${id}/toggle`).then((r) => r.data.data),
    },
};

/** Pengaturan Halaman (singleton): { beranda, anggota } */
export const settings = {
    get: () => http.get("/cms/pengaturan-halaman").then((r) => r.data.data),
    put: (data) =>
        http.put("/cms/pengaturan-halaman", data).then((r) => r.data.data),
    update: (data) =>
        http.put("/cms/pengaturan-halaman", data).then((r) => r.data.data),
};

/** Running Text (Teks Sorotan) standalone service */
export const runningTextService = {
    ...cmsResources.runningText,
    saveAll: (items) =>
        http.put("/cms/running-text", { items }).then((r) => r.data.data),
};

/** Media Sorotan standalone service */
export const hotNewsService = {
    ...cmsResources.hotNews,
};

export const mediaSorotanService = {
    ...cmsResources.mediaSorotan,
    saveAll: (items) =>
        http.put("/cms/media-sorotan", { items }).then((r) => r.data.data),
};

/** Manajemen Iklan standalone service (Banner Slot & Popup Modal) */
export const iklanService = {
    get: (params) => http.get("/cms/iklan", { params }).then((r) => r.data),
    list: (params) =>
        http.get("/cms/iklan", { params }).then((r) => r.data.data || r.data),
    show: (id) => http.get(`/cms/iklan/${id}`).then((r) => r.data.data),
    create: (data) => http.post("/cms/iklan", data).then((r) => r.data.data),
    updateItem: (id, data) =>
        http.put(`/cms/iklan/${id}`, data).then((r) => r.data.data),
    remove: (id) => http.delete(`/cms/iklan/${id}`).then((r) => r.data),
    reorder: (items) =>
        http.post("/cms/iklan/reorder", { items }).then((r) => r.data),
    toggleActive: (id) =>
        http.post(`/cms/iklan/${id}/toggle`).then((r) => r.data.data),
};

/** Footer Setting standalone service (Issue #99) */
export const footerService = {
    get: () => http.get("/cms/footer").then((r) => r.data.data),
    update: (data) => http.put("/cms/footer", data).then((r) => r.data),
};

/** Category options for dropdown pickers across CMS modules: [{ id, value, label, color }] */
export function categoryOptions() {
    return http.get("/cms/options/categories").then((r) => r.data.data);
}

/** First readable message from an API error. */
export function errorMessage(
    error,
    fallback = "Terjadi kesalahan. Silakan coba lagi.",
) {
    const status = error?.response?.status;
    if (status === 403) return "Anda tidak memiliki akses ke modul ini.";
    if (status === 404) return "Data tidak ditemukan.";
    if (status === 419)
        return "Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.";
    return error?.response?.data?.message || fallback;
}
