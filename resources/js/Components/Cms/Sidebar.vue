<script setup>
import { ref, computed } from "vue";
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
    return page.url === path || page.url.startsWith(path + "/");
}

const userFeatures = computed(() => auth.value?.user?.features || []);
function hasFeature(feature) {
    if (hasRole("super-admin")) return true;
    return userFeatures.value.includes(feature);
}

const menuGroups = computed(() => [
    {
        label: locale.value === "id" ? "Navigasi Utama" : "Main Navigation",
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
        label: locale.value === "id" ? "Konten Publik" : "Public Content",
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
                label: "Tokoh",
                href: "/cms/tokoh",
                icon: "UserStar",
                visible: hasFeature("tokoh"),
            },
            {
                label: "Isu Mingguan",
                href: "/cms/isu",
                icon: "Bulb",
                visible: hasFeature("isu"),
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
        label: locale.value === "id" ? "Portal Anggota" : "Member Portal",
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
        label: locale.value === "id" ? "Akses & Pengguna" : "Access & Users",
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
        label: locale.value === "id" ? "Situs Publik" : "Public Sites",
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
</script>

<template>
    <nav class="flex flex-col gap-1 p-3 overflow-y-auto">
        <template v-for="group in visibleGroups" :key="group.label">
            <!-- Group Label -->
            <p
                class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground/70"
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
                            ? 'bg-red-600 text-white shadow-sm shadow-red-600/30 dark:bg-red-600'
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
    </nav>
</template>
