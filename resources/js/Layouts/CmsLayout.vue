<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import Sidebar from "@/Components/Cms/Sidebar.vue";
import { useI18n } from "@/Composables/useI18n";
import { useDarkMode } from "@/Composables/useDarkMode";

const { t, locale, toggleLocale } = useI18n();
const { isDark, toggleDarkMode } = useDarkMode();

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user);
const userRole = computed(() => user.value?.roles?.[0] || "user");

const sidebarOpen = ref(false);
const profileOpen = ref(false);
const profileDropdownRef = ref(null);
const csrfToken = computed(
    () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "",
);

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebar() {
    sidebarOpen.value = false;
}

function toggleProfile() {
    profileOpen.value = !profileOpen.value;
}

function closeProfile() {
    profileOpen.value = false;
}

// Close dropdown when clicking outside
function handleClickOutside(event) {
    if (
        profileOpen.value &&
        profileDropdownRef.value &&
        !profileDropdownRef.value.contains(event.target)
    ) {
        profileOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});

function logout() {
    profileOpen.value = false;
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    fetch("/logout", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken || "",
            "X-Requested-With": "XMLHttpRequest",
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

<template>
    <div class="h-screen flex flex-col bg-background overflow-hidden">
        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="closeSidebar"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-[260px] border-r border-border bg-card transition-transform duration-200 lg:translate-x-0 flex flex-col',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <div
                class="flex h-16 items-center border-b border-border px-4 shrink-0"
            >
                <Link
                    href="/cms/dashboard"
                    class="flex items-center gap-2.5 font-bold"
                >
                    <div
                        class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-600 shadow-sm shadow-emerald-600/30"
                    >
                        <span class="text-white text-lg font-bold">C</span>
                    </div>
                    <span class="text-lg tracking-tight">CMS</span>
                </Link>
                <button
                    class="ml-auto lg:hidden"
                    @click="closeSidebar"
                    aria-label="Tutup menu"
                >
                    <IconComponent name="X" class="h-5 w-5" />
                </button>
            </div>
            <Sidebar class="flex-1 overflow-y-auto custom-scrollbar" />
        </aside>

        <!-- Main content wrapper -->
        <div class="lg:pl-[260px] flex flex-col flex-1 min-h-0">
            <!-- Top navbar -->
            <header
                class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-border bg-card/80 backdrop-blur px-4 lg:px-6 overflow-visible"
            >
                <!-- Mobile hamburger -->
                <button
                    class="lg:hidden p-2 rounded-md hover:bg-accent transition-colors"
                    @click="toggleSidebar"
                    aria-label="Buka menu"
                >
                    <IconComponent name="Menu2" class="h-5 w-5" />
                </button>
                <span class="text-lg font-bold lg:hidden">CMS</span>

                <div class="flex-1" />

                <!-- Language toggle -->
                <button
                    @click="toggleLocale"
                    class="p-2 rounded-lg hover:bg-accent transition-colors flex items-center gap-1.5 text-sm font-medium"
                    :title="t('settings.language')"
                >
                    <IconComponent name="Language" class="h-4 w-4" />
                    <span class="uppercase hidden sm:inline">{{ locale }}</span>
                </button>

                <!-- Dark mode toggle -->
                <button
                    @click="toggleDarkMode"
                    class="p-2 rounded-lg hover:bg-accent transition-colors"
                    :title="
                        isDark
                            ? t('settings.light_mode')
                            : t('settings.dark_mode')
                    "
                >
                    <IconComponent v-if="isDark" name="Sun" class="h-4 w-4" />
                    <IconComponent v-else name="Moon" class="h-4 w-4" />
                </button>

                <!-- Divider -->
                <div class="h-6 w-px bg-border mx-1 hidden sm:block" />

                <!-- Profile Dropdown -->
                <div class="relative" ref="profileDropdownRef">
                    <button
                        @click="toggleProfile"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-accent transition-colors"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900 flex items-center justify-center"
                        >
                            <IconComponent
                                name="User"
                                class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                        <div class="hidden sm:flex sm:flex-col sm:items-start">
                            <span class="text-sm font-medium leading-tight">{{
                                user?.name
                            }}</span>
                            <span
                                class="text-xs text-muted-foreground capitalize leading-tight"
                                >{{ userRole }}</span
                            >
                        </div>
                        <IconComponent
                            name="ChevronDown"
                            class="h-3.5 w-3.5 text-muted-foreground hidden sm:block"
                        />
                    </button>

                    <!-- Dropdown menu -->
                    <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="profileOpen"
                            class="absolute right-0 top-full mt-2 w-56 rounded-xl border border-border bg-card shadow-lg z-50 py-1"
                        >
                            <!-- User info -->
                            <div class="px-4 py-3 border-b border-border">
                                <p class="text-sm font-medium">
                                    {{ user?.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ user?.email }}
                                </p>
                                <p
                                    class="text-xs text-emerald-600 dark:text-emerald-400 capitalize mt-0.5"
                                >
                                    {{ userRole }}
                                </p>
                            </div>

                            <!-- Logout -->
                            <form method="POST" action="/logout">
                                <input
                                    type="hidden"
                                    name="_token"
                                    :value="csrfToken"
                                />
                                <button
                                    type="submit"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-destructive hover:bg-destructive/10 transition-colors cursor-pointer"
                                >
                                    <IconComponent
                                        name="Logout"
                                        class="h-4 w-4"
                                    />
                                    {{ t("common.logout") }}
                                </button>
                            </form>
                        </div>
                    </Transition>
                </div>
            </header>

            <main class="flex-1 min-h-0 overflow-auto overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(16, 185, 129, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(16, 185, 129, 0.4);
}
</style>
