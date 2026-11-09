import { ref } from "vue";

const STORAGE_KEY = "isf_header_layout";
const VALID_LAYOUTS = ["single-row", "two-rows", "row-3"];

function getInitialLayout() {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            const saved = window.localStorage.getItem(STORAGE_KEY);
            if (VALID_LAYOUTS.includes(saved)) {
                return saved;
            }
        }
    } catch {
        // ignore
    }
    return "two-rows";
}

// Module-level reactive singleton state for synchronized header layout across components
const layout = ref(getInitialLayout());

/**
 * Composable for managing and synchronizing the ISF header layout option.
 *
 * Options:
 * - 'single-row': ISF at 3rem with "Indonesia Strategy Forum" beside it (width 156px, letter-spacing 2px, divider).
 * - 'two-rows':   ISF Anton 8rem stacked on top of "Indonesia Strategy Forum" (14px capitalize -mt-6px).
 * - 'row-3':      ISF beside "Indonesia Strategy Forum" inline with equal font-size & divider.
 */
export function useIsfHeaderLayout() {
    function setLayout(newLayout) {
        if (VALID_LAYOUTS.includes(newLayout)) {
            layout.value = newLayout;
            try {
                if (typeof window !== "undefined" && window.localStorage) {
                    window.localStorage.setItem(STORAGE_KEY, newLayout);
                }
            } catch {
                // ignore storage error
            }
        }
    }

    return {
        layout,
        setLayout,
    };
}
