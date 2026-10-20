/**
 * useFormatBrand — Composable for formatting brand names with proper superscript ®/™
 *
 * Usage:
 *   import { useFormatBrand } from '@/Composables/useFormatBrand';
 *   const { formatBrand } = useFormatBrand();
 *
 *   // In template:
 *   <span v-html="formatBrand(product.name)"></span>
 *   <span v-html="formatBrand('topi-care® Smoothing Cream')"></span>
 *
 * What it does:
 *   - Converts ® and ™ symbols into <sup> tags
 *   - "topi-care®" → "topi-care<sup>®</sup>"
 *   - "topi-care Expert®" → "topi-care Expert<sup>®</sup>"
 *   - Works with any text containing ® or ™
 */

/**
 * Format brand text — replaces ® and ™ with superscript HTML
 * @param {string} text - Raw text from database
 * @returns {string} HTML string with <sup> tags
 */
export function formatBrand(text) {
    if (!text) return '';
    return text
        .replace(/®/g, '<sup>®</sup>')
        .replace(/™/g, '<sup>™</sup>');
}

/**
 * Vue composable wrapper
 */
export function useFormatBrand() {
    return { formatBrand };
}
