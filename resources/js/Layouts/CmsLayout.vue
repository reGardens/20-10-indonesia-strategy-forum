<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import Sidebar from "@/Components/Cms/Sidebar.vue";
import { useI18n } from "@/Composables/useI18n";
import { useDarkMode } from "@/Composables/useDarkMode";
import { useIsfPushNotification } from "@/Composables/useIsfPushNotification";
import WhatsAppVerifyDialog from "@/Components/Isf/WhatsAppVerifyDialog.vue";
import http from "@/api/http";

const { t, locale, toggleLocale } = useI18n();
const { isDark, toggleDarkMode } = useDarkMode();
const {
    isSupported: isPushSupported,
    isSubscribed: isPushSubscribed,
    isLoading: isPushLoading,
    initPush,
    subscribe: subscribePush,
    unsubscribe: unsubscribePush,
} = useIsfPushNotification();

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user);
const userRole = computed(() => user.value?.roles?.[0] || "member");

// Issue #42: alert "perbarui Nomor WhatsApp" di dropdown profil bila nomor kosong / belum terverifikasi.
const whatsappStatus = computed(() => {
    if (!user.value?.whatsapp) return "missing";
    // Verifikasi OTP dimatikan (default) → nomor terisi dianggap cukup, tidak ada alert.
    if (!user.value?.whatsapp_verification) return "verified";
    return user.value?.whatsapp_verified ? "verified" : "unverified";
});
const waDialogOpen = ref(false);
function openWhatsAppDialog() {
    profileOpen.value = false;
    waDialogOpen.value = true;
}
// Setelah nomor disimpan/diverifikasi: muat ulang props auth saja (tanpa reload halaman).
function onWhatsAppUpdated() {
    router.reload({ only: ["auth"] });
}

const sidebarOpen = ref(false);
const profileOpen = ref(false);
const profileDropdownRef = ref(null);
const notificationOpen = ref(false);
const notificationDropdownRef = ref(null);
const notificationsList = ref([]);
const unreadCount = ref(0);
const loadingNotifications = ref(false);

const READ_NOTIFS_KEY = "isf_read_notifs_ids";

function getLocalReadIds() {
    try {
        const stored = localStorage.getItem(READ_NOTIFS_KEY);
        return stored ? new Set(JSON.parse(stored)) : new Set();
    } catch {
        return new Set();
    }
}

function saveLocalReadIds(idsSet) {
    try {
        localStorage.setItem(READ_NOTIFS_KEY, JSON.stringify([...idsSet].slice(-200)));
    } catch {}
}

function markAsReadLocally(id) {
    if (!id) return;
    const set = getLocalReadIds();
    set.add(String(id));
    saveLocalReadIds(set);
}

function markAllAsReadLocally(items) {
    const set = getLocalReadIds();
    items.forEach((it) => {
        if (it?.id) set.add(String(it.id));
    });
    saveLocalReadIds(set);
}

async function fetchNotifications() {
    loadingNotifications.value = true;
    try {
        const localRead = getLocalReadIds();
        const clientReadQuery = localRead.size > 0 ? [...localRead].join(",") : "";
        const res = await http.get("/isf/push/notifications", {
            params: {
                t: Date.now(),
                client_read_ids: clientReadQuery,
            },
        });
        if (res.data) {
            const list = res.data.notifications || [];
            list.forEach((item) => {
                if (localRead.has(String(item.id))) {
                    item.unread = false;
                }
            });
            notificationsList.value = list;
            unreadCount.value = list.filter((n) => n.unread).length;
        }
    } catch {
        // silent fail
    } finally {
        loadingNotifications.value = false;
    }
}

function toggleNotificationDropdown() {
    notificationOpen.value = !notificationOpen.value;
    if (notificationOpen.value) {
        profileOpen.value = false;
        fetchNotifications();
    }
}

async function markAllNotificationsAsRead() {
    unreadCount.value = 0;
    markAllAsReadLocally(notificationsList.value);
    notificationsList.value.forEach((item) => {
        item.unread = false;
    });
    try {
        await http.post("/isf/push/notifications/read", {
            all: true,
            ids: notificationsList.value.map((n) => n.id),
        });
    } catch {
        // silent fail
    }
}

async function handleNotificationItemClick(item) {
    if (item.unread) {
        item.unread = false;
        if (unreadCount.value > 0) unreadCount.value--;
        markAsReadLocally(item.id);
        try {
            await http.post("/isf/push/notifications/read", { id: item.id });
        } catch {
            // silent fail
        }
    }
    notificationOpen.value = false;
    if (!item.url) return;

    try {
        const urlObj = new URL(item.url, window.location.origin);
        if (urlObj.pathname === window.location.pathname) {
            return;
        }
        if (urlObj.pathname.startsWith("/cms")) {
            router.visit(urlObj.pathname + urlObj.search + urlObj.hash);
        } else {
            window.location.href = item.url;
        }
    } catch {
        if (item.url === window.location.pathname) return;
        if (item.url.startsWith("/cms")) {
            router.visit(item.url);
        } else {
            window.location.href = item.url;
        }
    }
}

async function togglePushSetting() {
    if (isPushSubscribed.value) {
        await unsubscribePush();
    } else {
        await subscribePush();
    }
}

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebar() {
    sidebarOpen.value = false;
}

function toggleProfile() {
    profileOpen.value = !profileOpen.value;
    if (profileOpen.value) {
        notificationOpen.value = false;
    }
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
    if (
        notificationOpen.value &&
        notificationDropdownRef.value &&
        !notificationDropdownRef.value.contains(event.target)
    ) {
        notificationOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    initPush();
    fetchNotifications();
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});

function logout() {
    profileOpen.value = false;
    if (typeof sessionStorage !== "undefined") {
        sessionStorage.clear();
    }
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
                    <img
                        src="/images/isf/ISF-Logo-1.png"
                        alt="ISF Logo"
                        class="w-9 h-9 rounded-lg object-contain bg-isf-navy p-0.5 shadow-sm border border-neutral-700/60 shrink-0"
                        width="36"
                        height="36"
                    />
                    <span
                        class="text-xs font-bold tracking-tight text-foreground leading-snug"
                        >Indonesia Strategy Forum</span
                    >
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
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-2 border-b border-border bg-card/95 backdrop-blur-md px-4 lg:px-6 overflow-visible"
            >
                <!-- Mobile hamburger -->
                <button
                    class="lg:hidden p-2 rounded-md hover:bg-accent transition-colors"
                    @click="toggleSidebar"
                    aria-label="Buka menu"
                >
                    <IconComponent name="Menu2" class="h-5 w-5" />
                </button>
                <div class="flex items-center gap-2 lg:hidden">
                    <img
                        src="/images/isf/ISF-Logo-1.png"
                        alt="ISF Logo"
                        class="w-7 h-7 rounded object-contain bg-isf-navy p-0.5 shadow-sm border border-neutral-700/60 shrink-0"
                        width="28"
                        height="28"
                    />
                    <span class="text-xs font-bold"
                        >Indonesia Strategy Forum</span
                    >
                </div>

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

                <!-- Notification Center Bell & Dropdown -->
                <div class="relative" ref="notificationDropdownRef">
                    <button
                        @click="toggleNotificationDropdown"
                        class="relative p-2 rounded-lg hover:bg-accent transition-colors text-foreground"
                        title="Notifikasi"
                        aria-label="Buka Notifikasi"
                    >
                        <svg
                            class="h-4 w-4 transition-transform hover:scale-110"
                            :class="
                                unreadCount > 0
                                    ? 'text-[#E10600]'
                                    : 'text-foreground'
                            "
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>

                        <!-- Indicator Angka (bukan titik) -->
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#E10600] text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-background leading-none shadow-xs"
                        >
                            {{ unreadCount > 99 ? "99+" : unreadCount }}
                        </span>
                    </button>

                    <!-- Dropdown Notifikasi -->
                    <div
                        v-if="notificationOpen"
                        class="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl border border-border bg-card shadow-2xl z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                    >
                        <!-- Header -->
                        <div
                            class="p-3.5 border-b border-border flex items-center justify-between bg-muted/40"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-semibold text-sm text-foreground"
                                    >Notifikasi</span
                                >
                                <span
                                    v-if="unreadCount > 0"
                                    class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 dark:bg-red-950/60 text-[#E10600] border border-red-500/20"
                                >
                                    {{ unreadCount }} Baru
                                </span>
                            </div>
                            <button
                                v-if="unreadCount > 0"
                                @click="markAllNotificationsAsRead"
                                class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Tandai dibaca
                            </button>
                        </div>

                        <!-- List Notifikasi -->
                        <div
                            class="max-h-80 overflow-y-auto divide-y divide-border/60"
                        >
                            <div
                                v-if="loadingNotifications"
                                class="p-6 text-center text-xs text-muted-foreground"
                            >
                                Memuat notifikasi...
                            </div>
                            <div
                                v-else-if="notificationsList.length === 0"
                                class="p-8 text-center"
                            >
                                <div
                                    class="w-10 h-10 rounded-full bg-muted flex items-center justify-center mx-auto mb-2 text-muted-foreground"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                        />
                                    </svg>
                                </div>
                                <div
                                    class="text-xs font-medium text-foreground"
                                >
                                    Belum ada notifikasi
                                </div>
                                <div
                                    class="text-[11px] text-muted-foreground mt-0.5"
                                >
                                    Semua pembaruan sistem dan redaksi akan
                                    muncul di sini.
                                </div>
                            </div>
                            <template v-else>
                                <button
                                    v-for="item in notificationsList"
                                    :key="item.id"
                                    @click="handleNotificationItemClick(item)"
                                    class="w-full text-left p-3 hover:bg-muted/50 transition-colors flex items-start gap-3 group"
                                    :class="item.unread ? 'bg-red-500/5' : ''"
                                >
                                    <div
                                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                                        :class="{
                                            'bg-emerald-500/10 text-emerald-600':
                                                item.type === 'approved',
                                            'bg-rose-500/10 text-rose-600':
                                                item.type === 'rejected',
                                            'bg-amber-500/10 text-amber-600':
                                                item.type === 'pending' ||
                                                item.type === 'moderation',
                                            'bg-red-500/10 text-[#E10600]':
                                                item.type === 'alert',
                                            'bg-blue-500/10 text-blue-600':
                                                item.type === 'article',
                                        }"
                                    >
                                        <svg
                                            v-if="item.type === 'approved'"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        <svg
                                            v-else-if="item.type === 'rejected'"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                        <svg
                                            v-else-if="
                                                item.type === 'moderation' ||
                                                item.type === 'pending'
                                            "
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        <svg
                                            v-else-if="item.type === 'alert'"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
                                            />
                                        </svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex items-center justify-between gap-1"
                                        >
                                            <span
                                                class="text-xs font-semibold text-foreground truncate group-hover:text-[#E10600] transition-colors"
                                                >{{ item.title }}</span
                                            >
                                            <span
                                                class="text-[10px] text-muted-foreground whitespace-nowrap"
                                                >{{ item.created_at }}</span
                                            >
                                        </div>
                                        <p
                                            class="text-xs text-muted-foreground line-clamp-2 mt-0.5 leading-snug"
                                        >
                                            {{ item.message }}
                                        </p>
                                    </div>

                                    <span
                                        v-if="item.unread"
                                        class="w-1.5 h-1.5 rounded-full bg-[#E10600] shrink-0 mt-2"
                                    ></span>
                                </button>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div
                            class="p-2.5 border-t border-border bg-muted/30 text-center"
                        >
                            <Link
                                href="/cms/artikel"
                                @click="notificationOpen = false"
                                class="text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Lihat Semua Artikel di Redaksi →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="h-6 w-px bg-border mx-1 hidden sm:block" />

                <!-- Profile Dropdown -->
                <div class="relative" ref="profileDropdownRef">
                    <button
                        @click="toggleProfile"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-accent transition-colors"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-950/60 flex items-center justify-center border border-red-500/20"
                        >
                            <IconComponent
                                name="User"
                                class="h-4 w-4 text-red-600 dark:text-red-400"
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
                                    class="text-xs text-red-600 dark:text-red-400 capitalize mt-0.5"
                                >
                                    {{ userRole }}
                                </p>
                            </div>

                            <!-- Issue #42: alert Nomor WhatsApp -->
                            <div
                                v-if="whatsappStatus !== 'verified'"
                                class="px-4 py-3 border-b border-border bg-amber-50 dark:bg-amber-950/30"
                                data-testid="cms-wa-alert"
                            >
                                <p
                                    class="text-xs font-medium text-amber-800 dark:text-amber-300"
                                >
                                    {{
                                        whatsappStatus === "missing"
                                            ? "Nomor WhatsApp belum diisi."
                                            : "Nomor WhatsApp belum diverifikasi."
                                    }}
                                </p>
                                <button
                                    type="button"
                                    class="mt-2 w-full rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition-colors cursor-pointer"
                                    @click="openWhatsAppDialog"
                                >
                                    {{
                                        whatsappStatus === "missing"
                                            ? "Tambahkan nomor"
                                            : "Verifikasi sekarang"
                                    }}
                                </button>
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

            <WhatsAppVerifyDialog
                v-if="user"
                v-model:open="waDialogOpen"
                :whatsapp="user.whatsapp || ''"
                :verified="Boolean(user.whatsapp_verified)"
                :verification="Boolean(user.whatsapp_verification)"
                @updated="onWhatsAppUpdated"
            />
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
    background: rgba(220, 38, 38, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(220, 38, 38, 0.4);
}
</style>
