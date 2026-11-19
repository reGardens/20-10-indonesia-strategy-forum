import { ref } from "vue";

const FIXED_LAYOUT = "row-3";

function cleanupLegacyStorage() {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            window.localStorage.removeItem("isf_header_layout");
        }
        if (typeof document !== "undefined" && document.documentElement) {
            document.documentElement.setAttribute("data-isf-layout", FIXED_LAYOUT);
        }
    } catch {
        // ignore
    }
}

cleanupLegacyStorage();

// Module-level reactive singleton state permanently locked to 'row-3'
const layout = ref(FIXED_LAYOUT);

/**
 * Composable for managing the ISF header layout.
 *
 * Header layout is permanently fixed to 'row-3'
 * (Top Bar Informasi/Tanggal, Main Brand Bar/Logo & Search, Bottom Nav Bar/Kategori).
 * Tidak lagi menggunakan localStorage karena layout sudah permanen row-3.
 */
export function useIsfHeaderLayout() {
    cleanupLegacyStorage();

    function setLayout(_newLayout) {
        layout.value = FIXED_LAYOUT;
    }

    return {
        layout,
        setLayout,
    };
}
