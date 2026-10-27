/**
 * Dataset default / fallback untuk modul ISF Insight Slider (Politico Poll Style).
 * Digunakan jika backend hanya mengembalikan 1 data atau saat mengisi konten jamak.
 */
export const BERANDA_INSIGHT_DUMMY = [
    {
        id: "insight-kuhp",
        kicker: "ISF INSIGHT",
        title: "Topik KUHP yang paling banyak ditanyakan pelaku usaha",
        description:
            "Mayoritas korporasi menyoroti kepastian hukum terkait pertanggungjawaban pidana korporasi, kepatuhan direksi, serta penyesuaian SOP internal pasca berlakunya KUHP nasional.",
        readMoreUrl: "/isu/kuhp-baru-pelaku-usaha",
        author: {
            name: "Budi Santoso",
            role: "Analis Regulasi & Kebijakan",
            avatar: "https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=200&auto=format&fit=crop",
        },
        unit: "%",
        data: [
            { label: "Pidana korporasi", value: 48 },
            { label: "Tanggung jawab direksi", value: 36 },
            { label: "Kepatuhan & audit", value: 29 },
            { label: "Pidana lingkungan", value: 17 },
            { label: "Lainnya", value: 9 },
        ],
        sourceTitle: "Data riset redaksi ISF",
        sourceUrl: "https://peraturan.bpk.go.id/",
        signature: "Pusat Data Kebijakan ISF",
    },
    {
        id: "insight-pajak-karbon",
        kicker: "ISF INSIGHT",
        title: "Kesiapan sektor industri hadapi implementasi bursa karbon",
        description:
            "Survei terhadap 120 perusahaan lintas sektor menunjukkan hampir separuh pelaku usaha masih mematangkan metodologi penghitungan emisi GRK dan sertifikasi pelaporan.",
        readMoreUrl: "#",
        author: {
            name: "Dewi Lestari",
            role: "Riset Transisi Energi & ESG",
            avatar: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=200&auto=format&fit=crop",
        },
        unit: "%",
        data: [
            { label: "Sangat siap", value: 18 },
            { label: "Tahap penyesuaian", value: 44 },
            { label: "Belum siap", value: 32 },
            { label: "Belum terinformasi", value: 6 },
        ],
        sourceTitle: "Survei Sentimen Anggota ISF",
        sourceUrl: "",
        signature: "Divisi Riset Ekonomi ISF",
    },
    {
        id: "insight-prolegnas",
        kicker: "ISF INSIGHT",
        title: "Prioritas RUU ekonomi menurut pandangan dunia usaha",
        description:
            "Kepastian regulasi ketenagakerjaan dan transparansi aset menduduki urutan teratas aspirasi dunia usaha terhadap agenda pembahasan legislasi parlemen tahun ini.",
        readMoreUrl: "#",
        author: {
            name: "Ahmad Fauzi",
            role: "Koresponden Parlemen & Legislasi",
            avatar: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=200&auto=format&fit=crop",
        },
        unit: "%",
        data: [
            { label: "RUU Perampasan Aset", value: 58 },
            { label: "RUU Ketenagakerjaan", value: 46 },
            { label: "RUU BUMN", value: 34 },
            { label: "RUU Energi Baru", value: 24 },
        ],
        sourceTitle: "Poling Cepat Jaringan ISF",
        sourceUrl: "",
        signature: "Tim Analis Kebijakan ISF",
    },
];
