import { ref } from "vue";

export const FONT_OPTIONS = [
    {
        id: "montserrat",
        label: "Montserrat",
        family: "'Montserrat', sans-serif",
    },
];

export const VALID_FONTS = ["montserrat"];

// Module-level reactive singleton state for synchronized font (permanently locked to Montserrat)
const currentFont = ref("montserrat");

// Initialize document data attribute if running in browser
if (typeof document !== "undefined" && document.documentElement) {
    document.documentElement.setAttribute("data-isf-font", "montserrat");
}

/**
 * Composable for managing header font (standardized permanently to Montserrat).
 */
export function useIsfHeaderFont() {
    function setFont(newFont) {
        currentFont.value = "montserrat";
        if (typeof document !== "undefined" && document.documentElement) {
            document.documentElement.setAttribute("data-isf-font", "montserrat");
        }
    }

    return {
        currentFont,
        setFont,
        fontOptions: FONT_OPTIONS,
        validFonts: VALID_FONTS,
    };
}
