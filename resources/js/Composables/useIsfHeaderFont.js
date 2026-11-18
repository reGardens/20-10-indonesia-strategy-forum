import { ref } from "vue";

export const FONT_OPTIONS = [
    {
        id: "montserrat",
        label: "Montserrat",
        family: "'Montserrat', sans-serif",
    },
    {
        id: "arial",
        label: "Arial",
        family: "Arial, 'Arial Rounded MT Bold', sans-serif",
    },
    {
        id: "roboto",
        label: "Roboto",
        family: "'Roboto', sans-serif",
    },
];

export const VALID_FONTS = FONT_OPTIONS.map((o) => o.id);
const STORAGE_KEY = "isf_header_font";

function getInitialFont() {
    try {
        if (typeof window !== "undefined" && window.localStorage) {
            const saved = window.localStorage.getItem(STORAGE_KEY);
            if (VALID_FONTS.includes(saved)) {
                return saved;
            }
        }
    } catch {
        // ignore
    }
    return "montserrat";
}

// Module-level reactive singleton state for synchronized font emulator
const currentFont = ref(getInitialFont());

// Initialize document data attribute if running in browser
if (typeof document !== "undefined" && document.documentElement) {
    document.documentElement.setAttribute("data-isf-font", currentFont.value);
}

/**
 * Composable for managing and synchronizing the ISF header font emulator.
 *
 * Supported typography:
 * - 'montserrat': Modern geometric bold (ISF default)
 * - 'arial': Arial / Arial Rounded MT Bold
 * - 'roboto': Roboto
 */
export function useIsfHeaderFont() {
    function setFont(newFont) {
        if (VALID_FONTS.includes(newFont)) {
            currentFont.value = newFont;
            try {
                if (typeof window !== "undefined") {
                    if (window.localStorage) {
                        window.localStorage.setItem(STORAGE_KEY, newFont);
                    }
                    if (document.documentElement) {
                        document.documentElement.setAttribute(
                            "data-isf-font",
                            newFont,
                        );
                    }
                }
            } catch {
                // ignore
            }
        }
    }

    return {
        currentFont,
        setFont,
        fontOptions: FONT_OPTIONS,
        validFonts: VALID_FONTS,
    };
}

