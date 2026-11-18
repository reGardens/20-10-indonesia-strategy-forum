import { ref } from "vue";

const STORAGE_KEY = "isf_header_layout";
const FIXED_LAYOUT = "row-3";

function syncStorage() {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            window.localStorage.setItem(STORAGE_KEY, FIXED_LAYOUT);
        }
        if (typeof document !== "undefined" && document.documentElement) {
            document.documentElement.setAttribute("data-isf-layout", FIXED_LAYOUT);
        }
    } catch {
        // ignore
    }
}

syncStorage();

// Module-level reactive singleton state permanently locked to 'row-3'
const layout = ref(FIXED_LAYOUT);

/**
 * Composable for managing the ISF header layout.
 *
 * As per Issue #70, the layout is permanently fixed to 'row-3'
 * (Top Bar Informasi/Tanggal, Main Brand Bar/Logo & Search, Bottom Nav Bar/Kategori).
 * Other layout variants (Row 1, Row 2) are removed to avoid flicker,
 * rendering inconsistency, and height jumps.
 */
export function useIsfHeaderLayout() {
    syncStorage();

    function setLayout(_newLayout) {
        layout.value = FIXED_LAYOUT;
        syncStorage();
    }

    return {
        layout,
        setLayout,
    };
}
