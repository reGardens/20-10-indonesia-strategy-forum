<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import { useI18n } from "@/Composables/useI18n";

const { t, locale } = useI18n();
const page = usePage();

const auth = computed(() => page.props.auth);
const userRoles = computed(() => auth.value?.user?.roles || []);

function hasRole(role) {
    return userRoles.value.includes(role);
}

function isActive(path) {
    if (!path) return false;
    // Abaikan query/hash (mis. /cms/iklan?tab=popup) agar menu tetap aktif.
    const url = page.url.split(/[?#]/)[0];
    return url === path || url.startsWith(path + "/");
}

const userFeatures = computed(() => auth.value?.user?.features || []);
function hasFeature(feature) {
    if (hasRole("super-admin")) return true;
    return userFeatures.value.includes(feature);
}

const menuGroups = computed(() => [
    {
        key: "navigasi-utama",
        label: locale.value === "id" ? "Navigasi Utama" : "Main Navigation",
        collapsible: false,
        items: [
            {
                label: t("nav.dashboard") || "Dasbor",
                href: "/cms/dashboard",
                icon: "LayoutDashboard",
                visible: true,
            },
        ],
    },
    {
        key: "beranda-editorial",
        label: locale.value === "id" ? "Beranda & Konten Umum" : "Home & General Content",
        icon: "Article",
        collapsible: true,
        items: [
            {
                label: "Banner Sorotan",
                href: "/cms/banner",
                icon: "Slideshow",
                visible: hasFeature("banner"),
            },
            {
                label: "Running Text",
                href: "/cms/running-text",
                icon: "Speakerphone",
                visible: true,
            },
            {
                label: "Media Sorotan",
                href: "/cms/media-sorotan",
                icon: "Video",
                visible: true,
            },
            {
                label: "Hot News",
                href: "/cms/hot-news",
                icon: "Sparkles",
                visible: true,
            },
            {
                label: "Kelola Iklan",
                href: "/cms/iklan",
                icon: "Tag",
                visible: true,
            },
            {
                label: "Kategori",
                href: "/cms/kategori",
                icon: "Category",
                visible: true,
            },
            {
                label: "Artikel",
                href: "/cms/artikel",
                icon: "Article",
                visible: hasFeature("artikel"),
            },
            {
                label: "Infografik",
                href: "/cms/infografik",
                icon: "ChartInfographic",
                visible: hasFeature("infografik"),
            },
            {
                label: "ISF Insight (Grafik)",
                href: "/cms/data-grafik",
                icon: "ChartBar",
                visible: hasFeature("data_grafik"),
            },
        ],
    },
    {
        key: "rubrik-politik",
        label: locale.value === "id" ? "Politik" : "Politics",
        icon: "BuildingBank",
        collapsible: true,
        items: [
            {
                label: "Kursi Parlemen",
                href: "/cms/kursi-parlemen",
                icon: "BuildingBank",
                visible: hasFeature("kursi_parlemen"),
            },
        ],
    },
    {
        key: "rubrik-hukum",
        label: locale.value === "id" ? "Hukum" : "Law",
        icon: "Book",
        collapsible: true,
        items: [
            {
                label: "Glosarium Hukum",
                href: "/cms/glosarium-hukum",
                icon: "Book",
                visible: hasFeature("glosarium_hukum"),
            },
            {
                label: "Timeline Kasus",
                href: "/cms/timeline-kasus",
                icon: "Clock",
                visible: hasFeature("timeline_kasus"),
            },
        ],
    },
    {
        key: "rubrik-kebijakan-publik",
        label: locale.value === "id" ? "Kebijakan Publik" : "Public Policy",
        icon: "FileText",
        collapsible: true,
        items: [
            {
                label: "Policy Brief",
                href: "/cms/policy-brief",
                icon: "FileText",
                visible: hasFeature("policy_brief"),
            },
            {
                label: "Monitoring Kebijakan",
                href: "/cms/monitoring-kebijakan",
                icon: "Checklist",
                visible: hasFeature("monitoring_kebijakan"),
            },
        ],
    },
    {
        key: "rubrik-sosok",
        label: locale.value === "id" ? "Sosok" : "Figures",
        icon: "UserStar",
        collapsible: true,
        items: [
            {
                label: "Direktori Tokoh",
                href: "/cms/tokoh",
                icon: "UserStar",
                visible: hasFeature("tokoh"),
            },
        ],
    },
    {
        key: "rubrik-opini-publik",
        label: locale.value === "id" ? "Opini Publik" : "Public Opinion",
        icon: "Quote",
        collapsible: true,
        items: [
            {
                label: "Surat Pembaca",
                href: "/cms/surat-pembaca",
                icon: "Mail",
                visible: hasFeature("surat_pembaca"),
            },
            {
                label: "Reader Poll",
                href: "/cms/reader-poll",
                icon: "ChartBar",
                visible: hasFeature("reader_poll"),
            },
            {
                label: "Kutipan Publik",
                href: "/cms/kutipan-publik",
                icon: "Quote",
                visible: hasFeature("kutipan_publik"),
            },
        ],
    },
    {
        key: "rubrik-survei",
        label: locale.value === "id" ? "Survei" : "Surveys",
        icon: "ChartBar",
        collapsible: true,
        items: [
            {
                label: "Survei Opini Publik",
                href: "/cms/survei",
                icon: "ChartBar",
                visible: hasFeature("survei"),
            },
        ],
    },
    {
        key: "portal-anggota",
        label: locale.value === "id" ? "Portal Anggota" : "Member Portal",
        icon: "AlertTriangle",
        collapsible: true,
        items: [
            {
                label: "Alert Peringatan Dini",
                href: "/cms/alert",
                icon: "AlertTriangle",
                visible: hasFeature("alert"),
            },
            {
                label: "Email Briefing",
                href: "/cms/email-briefing",
                icon: "Mail",
                visible: hasFeature("email_briefing"),
            },
        ],
    },
    {
        key: "akses-pengguna",
        label: locale.value === "id" ? "Akses & Pengguna" : "Access & Users",
        icon: "Shield",
        collapsible: true,
        items: [
            {
                label: locale.value === "id" ? "Kelola Peran" : "Manage Roles",
                href: "/cms/roles",
                icon: "Shield",
                visible: hasRole("super-admin"),
            },
            {
                label: locale.value === "id" ? "Kelola Pengguna" : "Manage Users",
                href: "/cms/users",
                icon: "Users",
                visible: hasRole("super-admin"),
            },
            {
                label: locale.value === "id" ? "ACL Fitur User" : "User Feature ACL",
                href: "/cms/acl",
                icon: "Lock",
                visible: hasRole("super-admin"),
            },
        ],
    },
    {
        key: "situs-publik",
        label: locale.value === "id" ? "Situs Publik" : "Public Sites",
        collapsible: false,
        items: [
            {
                label: "Beranda ISF",
                href: "/",
                icon: "World",
                visible: true,
                external: true,
            },
            {
                label: "Dasbor Anggota",
                href: "/anggota",
                icon: "Shield",
                visible: true,
                external: true,
            },
        ],
    },
]);

const visibleGroups = computed(() =>
    menuGroups.value
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => item.visible !== false),
        }))
        .filter((group) => group.items.length > 0),
);

// State accordion: Simpan key group yang sedang terbuka
const openGroups = ref({});

function isGroupActive(group) {
    return group.items.some((item) => isActive(item.href));
}

function isGroupOpen(key) {
    return Boolean(openGroups.value[key]);
}

function toggleGroup(key) {
    openGroups.value[key] = !openGroups.value[key];
}

// Buka otomatis grup yang memiliki menu yang sedang aktif
function syncActiveGroup() {
    visibleGroups.value.forEach((group) => {
        if (group.collapsible && isGroupActive(group)) {
            openGroups.value[group.key] = true;
        }
    });
}

onMounted(() => {
    syncActiveGroup();
});

watch(
    () => page.url,
    () => {
        syncActiveGroup();
    },
);
</script>

<template>
    <nav class="flex flex-col gap-1 p-3 overflow-y-auto">
        <template v-for="group in visibleGroups" :key="group.key || group.label">
            <!-- Non-collapsible Group (e.g. Navigasi Utama, Situs Publik) -->
            <template v-if="!group.collapsible">
                <p
                    class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground/70"
                >
                    {{ group.label }}
                </p>

                <template v-for="item in group.items" :key="item.href || item.key">
                    <!-- External link -->
                    <a
                        v-if="item.external"
                        :href="item.href"
                        target="_blank"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all text-muted-foreground hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                    >
                        <IconComponent
                            :name="item.icon"
                            class="h-4 w-4 flex-shrink-0"
                        />
                        <span class="truncate flex-1">{{ item.label }}</span>
                        <IconComponent
                            name="ExternalLink"
                            class="h-3 w-3 opacity-40"
                        />
                    </a>

                    <!-- Internal Inertia Link -->
                    <Link
                        v-else
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all w-full text-left',
                            isActive(item.href)
                                ? 'bg-red-600 text-white shadow-sm shadow-red-600/30 dark:bg-red-600 font-semibold'
                                : 'text-muted-foreground hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40 dark:hover:text-red-400',
                        ]"
                    >
                        <IconComponent
                            :name="item.icon"
                            class="h-4 w-4 flex-shrink-0"
                        />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </template>
            </template>

            <!-- Collapsible Accordion Group (e.g. Beranda, Rubrik Politik, Hukum, Kebijakan, Sosok, Opini, Survei) -->
            <div v-else class="flex flex-col mt-1">
                <button
                    type="button"
                    @click="toggleGroup(group.key)"
                    :class="[
                        'flex items-center justify-between w-full px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-left',
                        isGroupActive(group)
                            ? 'text-red-600 dark:text-red-400 bg-red-500/10 font-bold'
                            : 'text-muted-foreground/80 hover:text-foreground hover:bg-muted/40 font-medium',
                    ]"
                >
                    <div class="flex items-center gap-2.5 truncate">
                        <IconComponent
                            v-if="group.icon"
                            :name="group.icon"
                            :class="[
                                'h-4 w-4 flex-shrink-0',
                                isGroupActive(group)
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-muted-foreground/60',
                            ]"
                        />
                        <span class="truncate text-[12px]">{{ group.label }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span
                            v-if="isGroupActive(group) && !isGroupOpen(group.key)"
                            class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"
                            title="Menu aktif di dalam grup ini"
                        ></span>
                        <IconComponent
                            name="ChevronDown"
                            :class="[
                                'h-3.5 w-3.5 transition-transform duration-200 text-muted-foreground/70',
                                isGroupOpen(group.key) ? 'rotate-180' : '',
                            ]"
                        />
                    </div>
                </button>

                <!-- Accordion Content -->
                <div
                    v-show="isGroupOpen(group.key)"
                    class="flex flex-col gap-0.5 pl-3 mt-1 border-l-2 border-border/40 ml-4.5 space-y-0.5"
                >
                    <Link
                        v-for="item in group.items"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-all w-full text-left',
                            isActive(item.href)
                                ? 'bg-red-600 text-white font-semibold shadow-xs'
                                : 'text-muted-foreground hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40 dark:hover:text-red-400',
                        ]"
                    >
                        <IconComponent
                            :name="item.icon"
                            class="h-3.5 w-3.5 flex-shrink-0"
                        />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </div>
            </div>
        </template>
    </nav>
</template>
