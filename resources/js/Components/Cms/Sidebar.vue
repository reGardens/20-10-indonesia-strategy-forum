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
    // Exact match for template list page, startsWith for others
    if (path === "/cms/templates") {
        return page.url === "/cms/templates" || page.url.startsWith("/cms/templates?");
    }
    return page.url === path || page.url.startsWith(path + "/");
}

// Expandable menu state
const expandedMenus = ref({
    homepage: true,
});

function toggleMenu(key) {
    expandedMenus.value[key] = !expandedMenus.value[key];
}

/**
 * Menu structure with labels (groups) and items.
 * Sub-menus are items with 'children' array.
 */
const menuGroups = computed(() => [
    {
        label: locale.value === "id" ? "Navigasi Utama" : "Main Navigation",
        items: [
            {
                label: t("nav.dashboard"),
                href: "/cms/dashboard",
                icon: "LayoutDashboard",
                visible: true,
            },
            {
                label: locale.value === "id" ? "Template Kustom" : "Custom Templates",
                href: "/cms/templates",
                icon: "Template",
                visible: true,
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
        label: locale.value === "id" ? "Aplikasi Undangan" : "Wedding App",
        items: [
            {
                label: locale.value === "id" ? "Kembali ke Undangan" : "Back to Weddings",
                href: "/cms",
                icon: "Heart",
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
                <!-- Item with children (expandable sub-menu) -->
                <template v-if="item.children">
                    <button
                        @click="toggleMenu(item.key)"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all w-full text-left',
                            item.children.some((c) => isChildActive(c))
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'text-muted-foreground hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400',
                        ]"
                    >
                        <IconComponent
                            :name="item.icon"
                            class="h-4 w-4 flex-shrink-0"
                        />
                        <span class="truncate flex-1">{{ item.label }}</span>
                        <IconComponent
                            v-if="expandedMenus[item.key]"
                            name="ChevronDown"
                            class="h-3.5 w-3.5 opacity-50"
                        />
                        <IconComponent
                            v-else
                            name="ChevronRight"
                            class="h-3.5 w-3.5 opacity-50"
                        />
                    </button>

                    <!-- Sub-menu items -->
                    <div
                        v-if="expandedMenus[item.key]"
                        class="ml-4 pl-3 border-l border-border/50 space-y-0.5 mt-0.5"
                    >
                        <Link
                            v-for="child in item.children"
                            :key="child.href"
                            :href="child.href"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                                isChildActive(child)
                                    ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 dark:bg-emerald-500'
                                    : 'text-muted-foreground hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400',
                            ]"
                        >
                            <IconComponent
                                :name="child.icon"
                                class="h-3.5 w-3.5 flex-shrink-0"
                            />
                            <span class="truncate">{{ child.label }}</span>
                        </Link>
                    </div>
                </template>

                <!-- Regular item (no children) -->
                <a
                    v-else-if="item.external"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all text-muted-foreground hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400',
                    ]"
                >
                    <IconComponent
                        :name="item.icon"
                        class="h-4 w-4 flex-shrink-0"
                    />
                    <span class="truncate">{{ item.label }}</span>
                </a>
                <Link
                    v-else
                    :href="item.href"
                    :method="item.method || 'get'"
                    :as="item.as || 'a'"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all w-full text-left',
                        isActive(item.href)
                            ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 dark:bg-emerald-500'
                            : 'text-muted-foreground hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400',
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
