import { ref } from "vue";
import http from "@/api/http";

/**
 * Option lists for ISF CMS forms and filters.
 * Initialized with default categories (Politik, Hukum, Kebijakan Publik = menu navbar;
 * RUU & Ekonomi sudah digabung dan dihapus)
 * and can be dynamically refreshed from the database.
 */

export const CATEGORIES = [
    { value: "politik", label: "Politik" },
    { value: "kebijakan", label: "Kebijakan Publik" },
    { value: "hukum", label: "Hukum" },
    { value: "opini", label: "Opini Publik" },
];

export const allCategories = ref([...CATEGORIES]);
export const activeCategories = ref([...CATEGORIES]);

/**
 * Fetch latest categories from database (/api/cms/options/categories)
 */
export async function loadCategories() {
    try {
        const res = await http.get("/cms/options/categories");
        const list = res?.data?.data || [];
        if (Array.isArray(list) && list.length > 0) {
            allCategories.value = list;
            // Only top-level / parent categories for the category select options (matches menu rubrik)
            const parents = list.filter((c) => !c.parent_id);
            activeCategories.value =
                parents.length > 0 ? parents : [...CATEGORIES];
            return activeCategories.value;
        }
    } catch (err) {
        // fallback to initial static categories if API fails
    }
    return activeCategories.value;
}

/**
 * Returns available subcategories for a given category slug based on loaded database options or static fallbacks.
 */
export function getSubcategoriesFor(categoryValue) {
    const all = allCategories.value || [];
    const parent = all.find((c) => c.value === categoryValue);
    if (parent?.id) {
        const subs = all.filter((c) => c.parent_id === parent.id);
        if (subs.length > 0) {
            return subs.map((s) => ({
                value: s.value || s.slug,
                label: s.label || s.name,
            }));
        }
    }

    // Fallbacks matching database structure
    if (categoryValue === "kebijakan") {
        return [
            { value: "ekonomi", label: "Ekonomi & Fiskal" },
            { value: "kesehatan", label: "Kesehatan Masyarakat" },
            { value: "pendidikan", label: "Pendidikan & Riset" },
            { value: "pertahanan", label: "Pertahanan & Keamanan" },
            { value: "lingkungan", label: "Lingkungan & Energi" },
        ];
    }
    if (categoryValue === "politik") {
        return [
            { value: "pemilu", label: "Pemilu & Pilkada" },
            { value: "parlemen", label: "DPR/DPD/MPR" },
            { value: "pemerintahan", label: "Eksekutif" },
            { value: "partai", label: "Dinamika Partai" },
        ];
    }
    if (categoryValue === "hukum") {
        return [
            { value: "regulasi", label: "Regulasi & Perundang-undangan" },
            { value: "putusan", label: "Putusan Peradilan" },
            { value: "kasus", label: "Kasus Hukum" },
        ];
    }
    if (categoryValue === "opini") {
        return [
            { value: "op-ed", label: "Op-Ed & Analisis Pakar" },
            { value: "editorial", label: "Editorial ISF" },
            { value: "surat-pembaca", label: "Artikel by Member" },
        ];
    }
    return [];
}

export const PRIORITIES = [
    { value: "merah", label: "Prioritas Tinggi (Merah)" },
    { value: "kuning", label: "Perlu Dipantau (Kuning)" },
];

export const STATUSES = [
    { value: "draf", label: "Draf" },
    { value: "terbit", label: "Terbit" },
];

export function categoryLabel(value) {
    const found =
        activeCategories.value.find((c) => c.value === value) ||
        CATEGORIES.find((c) => c.value === value);
    return found?.label || found?.name || value || "-";
}

export function priorityLabel(value) {
    return PRIORITIES.find((p) => p.value === value)?.label || value || "-";
}

export function statusLabel(value) {
    return STATUSES.find((s) => s.value === value)?.label || value || "-";
}
