<template>
    <nav
        class="flex flex-col h-full bg-emerald-900 text-emerald-100 w-64 min-w-[16rem]"
    >
        <!-- Brand header -->
        <div class="px-6 py-5 border-b border-emerald-700">
            <div v-if="displayName" class="space-y-1">
                <h1 class="text-lg font-semibold text-white truncate">
                    {{ displayName }}
                </h1>
                <p class="text-xs text-emerald-300 font-mono truncate">
                    {{ activeSlug }}
                </p>
            </div>
            <h1 v-else class="text-lg font-semibold text-white tracking-wide">
                Wedding CMS
            </h1>
            <router-link
                to="/cms/dashboard"
                class="mt-3 inline-flex items-center gap-1.5 text-xs text-emerald-300 hover:text-emerald-200 transition-colors"
            >
                <svg
                    class="w-3.5 h-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                {{
                    locale === "id"
                        ? "Kembali ke Dashboard"
                        : "Back to Dashboard"
                }}
            </router-link>
        </div>

        <!-- Navigation -->
        <ul
            class="flex-1 overflow-y-auto py-4 space-y-0.5 px-3 custom-scrollbar"
        >
            <!-- Dashboard -->
            <li>
                <router-link
                    :to="{
                        name: 'cms-dashboard',
                        params: { slug: activeSlug },
                    }"
                    class="nav-item"
                    :class="
                        route.name === 'cms-dashboard'
                            ? 'nav-item--active'
                            : 'nav-item--default'
                    "
                >
                    <span class="nav-icon">📊</span>
                    <span>{{
                        locale === "id" ? "Dashboard" : "Dashboard"
                    }}</span>
                </router-link>
            </li>

            <!-- Undangan Digital DROPDOWN -->
            <li>
                <button
                    type="button"
                    class="nav-item w-full"
                    :class="
                        isUndanganActive
                            ? 'nav-item--active'
                            : 'nav-item--default'
                    "
                    @click="undanganOpen = !undanganOpen"
                >
                    <span class="nav-icon">🎨</span>
                    <span class="flex-1 text-left">{{
                        locale === "id"
                            ? "Undangan Digital"
                            : "Digital Invitation"
                    }}</span>
                    <svg
                        class="w-4 h-4 transition-transform duration-200 flex-shrink-0"
                        :class="undanganOpen ? 'rotate-180' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>

                <!-- Sub-menu -->
                <transition name="dropdown">
                    <ul v-if="undanganOpen" class="sub-menu">
                        <li v-for="sub in subMenuItems" :key="sub.routeName">
                            <!-- Locked sub-item -->
                            <div
                                v-if="isLocked(sub.featureKey)"
                                class="sub-item sub-item--locked"
                                :title="
                                    locale === 'id'
                                        ? 'Fitur dinonaktifkan administrator'
                                        : 'Feature disabled by admin'
                                "
                            >
                                <span class="sub-icon">{{ sub.icon }}</span>
                                <span class="flex-1 truncate">{{
                                    locale === "id" ? sub.labelId : sub.labelEn
                                }}</span>
                                <span class="text-xs opacity-50">🔒</span>
                            </div>

                            <!-- Active sub-item -->
                            <router-link
                                v-else
                                :to="{
                                    name: sub.routeName,
                                    params: { slug: activeSlug },
                                }"
                                class="sub-item"
                                :class="
                                    route.name === sub.routeName
                                        ? 'sub-item--active'
                                        : 'sub-item--default'
                                "
                            >
                                <span class="sub-icon">{{ sub.icon }}</span>
                                <span class="flex-1 truncate">{{
                                    locale === "id" ? sub.labelId : sub.labelEn
                                }}</span>
                            </router-link>
                        </li>
                    </ul>
                </transition>
            </li>

            <!-- Preview Undangan -->
            <li v-if="activeSlug">
                <a
                    :href="`/wedding/${activeSlug}`"
                    target="_blank"
                    class="nav-item nav-item--default"
                >
                    <span class="nav-icon">👁️</span>
                    <span>{{
                        locale === "id"
                            ? "Preview Undangan"
                            : "Preview Invitation"
                    }}</span>
                    <svg
                        class="w-3 h-3 opacity-50 flex-shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                    </svg>
                </a>
            </li>

            <!-- Divider -->
            <li class="my-2 border-t border-emerald-700/50"></li>

            <!-- Settings -->
            <li>
                <router-link
                    :to="{ name: 'cms-settings', params: { slug: activeSlug } }"
                    class="nav-item"
                    :class="
                        route.name === 'cms-settings'
                            ? 'nav-item--active'
                            : 'nav-item--default'
                    "
                >
                    <span class="nav-icon">⚙️</span>
                    <span>{{
                        locale === "id" ? "Pengaturan" : "Settings"
                    }}</span>
                </router-link>
            </li>
        </ul>

        <!-- Footer: Dark mode + Language + Logout -->
        <div
            class="p-4 border-t border-emerald-700/50 space-y-3 bg-emerald-950/20"
        >
            <div class="flex items-center justify-between px-2 text-xs">
                <button
                    type="button"
                    @click="toggleLocale"
                    class="px-2.5 py-1 rounded bg-emerald-800 hover:bg-emerald-700 text-white font-semibold transition-colors uppercase"
                    title="Ubah Bahasa / Switch Language"
                >
                    🌐 {{ locale === "id" ? "EN" : "ID" }}
                </button>
                <button
                    type="button"
                    @click="toggleDarkMode"
                    class="flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-800 hover:bg-emerald-700 text-white transition-colors"
                    title="Toggle Dark Mode"
                >
                    <span>{{ isDark ? "☀️ Light" : "🌙 Dark" }}</span>
                </button>
            </div>
            <button
                type="button"
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-emerald-200 hover:bg-emerald-800/80 hover:text-white transition-colors duration-150"
            >
                <span class="text-lg leading-none" aria-hidden="true">🚪</span>
                <span>{{ locale === "id" ? "Keluar" : "Sign Out" }}</span>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useStore } from "vuex";
import { useI18n } from "vue-i18n";
import { useDarkMode } from "@/Composables/useDarkMode";

const route = useRoute();
const store = useStore();
const { t, locale } = useI18n();
const { isDark, toggleDarkMode } = useDarkMode();

const activeWedding = computed(() => store.getters["wedding/activeWedding"]);
const activeSlug = computed(() => store.getters["wedding/activeSlug"]);
const allowedFeatures = computed(
    () => store.getters["wedding/allowedFeatures"],
);

// Open undangan dropdown when already on an undangan sub-route
const undanganRouteNames = [
    "cms-undangan-tema",
    "cms-undangan-rsvp",
    "cms-undangan-acara",
    "cms-undangan-love-story",
    "cms-undangan-tamu",
    "cms-undangan-amplop",
    "cms-undangan-galeri",
    "cms-undangan-countdown",
    "cms-undangan-qr",
    "cms-undangan-streaming",
    "cms-undangan-ucapan",
    "cms-undangan-wishlist",
];

const undanganOpen = ref(false);
const isUndanganActive = computed(() =>
    undanganRouteNames.includes(route.name),
);

// Auto-open when navigating to an undangan sub-route
watch(
    () => route.name,
    (name) => {
        if (undanganRouteNames.includes(name)) undanganOpen.value = true;
    },
    { immediate: true },
);

const subMenuItems = [
    {
        icon: "🎨",
        labelId: "Tema & Template",
        labelEn: "Theme & Template",
        routeName: "cms-undangan-tema",
        featureKey: "undangan_digital",
    },
    {
        icon: "💌",
        labelId: "RSVP Online",
        labelEn: "RSVP Online",
        routeName: "cms-undangan-rsvp",
        featureKey: "rsvp_online",
    },
    {
        icon: "📅",
        labelId: "Informasi Acara",
        labelEn: "Event Info",
        routeName: "cms-undangan-acara",
        featureKey: "informasi_acara",
    },
    {
        icon: "📖",
        labelId: "Love Story",
        labelEn: "Love Story",
        routeName: "cms-undangan-love-story",
        featureKey: "love_story",
    },
    {
        icon: "👥",
        labelId: "Manajemen Tamu",
        labelEn: "Guest Management",
        routeName: "cms-undangan-tamu",
        featureKey: "manajemen_tamu",
    },
    {
        icon: "💰",
        labelId: "Amplop Digital",
        labelEn: "Digital Envelope",
        routeName: "cms-undangan-amplop",
        featureKey: "amplop_digital",
    },
    {
        icon: "🖼️",
        labelId: "Galeri Foto",
        labelEn: "Photo Gallery",
        routeName: "cms-undangan-galeri",
        featureKey: "galeri",
    },
    {
        icon: "⏰",
        labelId: "Countdown",
        labelEn: "Countdown",
        routeName: "cms-undangan-countdown",
        featureKey: "countdown",
    },
    {
        icon: "🔍",
        labelId: "QR Check-in",
        labelEn: "QR Check-in",
        routeName: "cms-undangan-qr",
        featureKey: "qr_checkin",
    },
    {
        icon: "🎥",
        labelId: "Live Streaming",
        labelEn: "Live Streaming",
        routeName: "cms-undangan-streaming",
        featureKey: "live_streaming",
    },
    {
        icon: "💬",
        labelId: "Ucapan & Doa",
        labelEn: "Wishes & Prayers",
        routeName: "cms-undangan-ucapan",
        featureKey: "ucapan_doa",
    },
    {
        icon: "🎁",
        labelId: "Wishlist Hadiah",
        labelEn: "Gift Wishlist",
        routeName: "cms-undangan-wishlist",
        featureKey: "wishlist",
    },
];

function isLocked(featureKey) {
    if (!featureKey) return false;
    return !allowedFeatures.value.includes(featureKey);
}

// Fetch user profile on mount
onMounted(() => {
    store.dispatch("wedding/fetchUserProfile");
});

// Auto-fetch couple data when slug changes
watch(
    activeSlug,
    (slug) => {
        if (slug) store.dispatch("couple/fetchCouple");
    },
    { immediate: true },
);

const coupleData = computed(() => store.getters["couple/couple"]);
const displayName = computed(() => {
    if (coupleData.value) {
        const groom =
            coupleData.value.groom?.nickname ||
            coupleData.value.groom?.fullName;
        const bride =
            coupleData.value.bride?.nickname ||
            coupleData.value.bride?.fullName;
        if (groom && bride) return `${groom} & ${bride}`;
        if (groom) return groom;
        if (bride) return bride;
    }
    if (activeWedding.value) return activeWedding.value.label;
    return null;
});

function toggleLocale() {
    const newLocale = locale.value === "id" ? "en" : "id";
    locale.value = newLocale;
    if (typeof window !== "undefined") {
        localStorage.setItem("cms-locale", newLocale);
        document.documentElement.lang = newLocale;
    }
}

function handleLogout() {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    fetch("/logout", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken || "",
            Accept: "text/html",
        },
        credentials: "same-origin",
    })
        .then(() => {
            window.location.href = "/login";
        })
        .catch(() => {
            window.location.href = "/login";
        });
}
</script>

<style scoped>
/* Nav item base */
.nav-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition:
        background-color 0.15s,
        color 0.15s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    color: inherit;
    text-decoration: none;
}
.nav-item--default {
    color: #a7f3d0;
}
.nav-item--default:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}
.nav-item--active {
    background: #059669;
    color: white;
}
.nav-icon {
    font-size: 1.125rem;
    line-height: 1;
    flex-shrink: 0;
}

/* Sub-menu */
.sub-menu {
    margin: 0.25rem 0 0.25rem 0.75rem;
    padding-left: 0.5rem;
    border-left: 2px solid rgba(167, 243, 208, 0.25);
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.sub-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 500;
    transition:
        background-color 0.15s,
        color 0.15s;
    cursor: pointer;
    text-decoration: none;
    color: #a7f3d0;
}
.sub-item--default:hover {
    background: rgba(255, 255, 255, 0.08);
    color: white;
}
.sub-item--active {
    background: rgba(16, 185, 129, 0.3);
    color: white;
}
.sub-item--locked {
    color: rgba(167, 243, 208, 0.3);
    cursor: not-allowed;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.625rem;
    font-size: 0.8125rem;
}
.sub-icon {
    font-size: 1rem;
    line-height: 1;
    flex-shrink: 0;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        max-height 0.25s ease,
        opacity 0.2s ease;
    overflow: hidden;
    max-height: 600px;
}
.dropdown-enter-from,
.dropdown-leave-to {
    max-height: 0;
    opacity: 0;
}

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.35);
}
</style>
