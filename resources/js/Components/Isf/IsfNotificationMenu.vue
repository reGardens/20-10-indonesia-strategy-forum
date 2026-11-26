<script setup>
/**
 * IsfNotificationMenu — Menu Notifikasi Lonceng Header ISF (Landing Page).
 * Menggunakan Teleport ke <body> dengan posisi fixed di bawah tombol, sama seperti IsfProfileMenu.
 * Mendukung filter role: untuk member, hanya menampilkan pembaruan terkait naskah/artikel yang dibuatnya.
 */
import {
    ref,
    computed,
    onMounted,
    onBeforeUnmount,
    nextTick,
    useId,
} from "vue";
import { useRouter } from "vue-router";
import http from "@/api/http";

const router = useRouter();
const open = ref(false);
const triggerEl = ref(null);
const menuEl = ref(null);
const position = ref({ top: 0, right: 0 });
const menuId = `isf-notif-menu-${useId()}`;

const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

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
        localStorage.setItem(
            READ_NOTIFS_KEY,
            JSON.stringify([...idsSet].slice(-200)),
        );
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
    loading.value = true;
    try {
        const localRead = getLocalReadIds();
        const clientReadQuery =
            localRead.size > 0 ? [...localRead].join(",") : "";
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
            notifications.value = list;
            unreadCount.value = list.filter((n) => n.unread).length;
        }
    } catch {
        // silent fail
    } finally {
        loading.value = false;
    }
}

function updatePosition() {
    const rect = triggerEl.value?.getBoundingClientRect();
    if (!rect) return;
    const viewportWidth =
        document.documentElement.clientWidth || window.innerWidth;
    position.value = {
        top: Math.round(rect.bottom + 8),
        right: Math.max(8, Math.round(viewportWidth - rect.right)),
    };
}

function setOpen(value) {
    if (value) {
        updatePosition();
        fetchNotifications();
    }
    open.value = value;
}

function onDocumentClick(event) {
    if (!open.value) return;
    const target = event.target;
    if (triggerEl.value?.contains(target) || menuEl.value?.contains(target))
        return;
    setOpen(false);
}

function onKeydown(event) {
    if (event.key === "Escape" && open.value) {
        event.stopPropagation();
        setOpen(false);
    }
}

function onViewportChange() {
    if (open.value) setOpen(false);
}

async function markAllAsRead() {
    unreadCount.value = 0;
    markAllAsReadLocally(notifications.value);
    notifications.value.forEach((item) => {
        item.unread = false;
    });
    try {
        await http.post("/isf/push/notifications/read", {
            all: true,
            ids: notifications.value.map((n) => n.id),
        });
    } catch {
        // silent fail
    }
}

async function handleItemClick(item) {
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
    setOpen(false);
    if (!item.url) return;

    try {
        const urlObj = new URL(item.url, window.location.origin);
        if (urlObj.pathname === window.location.pathname) {
            return;
        }
        if (urlObj.pathname.startsWith("/cms")) {
            window.location.href =
                urlObj.pathname + urlObj.search + urlObj.hash;
        } else {
            router
                .push(urlObj.pathname + urlObj.search + urlObj.hash)
                .catch(() => {
                    window.location.href = item.url;
                });
        }
    } catch {
        if (item.url === window.location.pathname) return;
        window.location.href = item.url;
    }
}

onMounted(() => {
    document.addEventListener("click", onDocumentClick);
    window.addEventListener("scroll", onViewportChange, { passive: true });
    window.addEventListener("resize", onViewportChange);
    fetchNotifications();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", onDocumentClick);
    window.removeEventListener("scroll", onViewportChange);
    window.removeEventListener("resize", onViewportChange);
});

const focusRing =
    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red focus-visible:ring-offset-2 focus-visible:ring-offset-isf-navy";
</script>

<template>
    <div class="relative inline-flex items-center">
        <button
            ref="triggerEl"
            type="button"
            aria-haspopup="menu"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="menuId"
            aria-label="Buka Notifikasi"
            title="Notifikasi"
            :class="[
                'relative inline-flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-md border border-white/10 text-white transition-colors hover:border-white/30 hover:bg-white/10 hover:text-isf-red cursor-pointer',
                open
                    ? 'border-white/30 bg-white/10 text-isf-red ring-2 ring-white/20'
                    : '',
                focusRing,
            ]"
            @click="setOpen(!open)"
            @keydown="onKeydown"
        >
            <svg
                class="h-4 w-4 sm:h-5 sm:w-5 shrink-0"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                aria-hidden="true"
                focusable="false"
            >
                <path
                    d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"
                />
                <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
            </svg>

            <!-- Indikator Angka (Bukan titik) -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 min-w-[17px] h-[17px] px-1 rounded-full bg-isf-red text-white text-[10px] font-bold font-space-grotesk flex items-center justify-center ring-2 ring-isf-navy leading-none shadow-xs"
            >
                {{ unreadCount > 99 ? "99+" : unreadCount }}
            </span>
        </button>

        <Teleport to="body">
            <div
                v-show="open"
                :id="menuId"
                ref="menuEl"
                role="menu"
                aria-label="Daftar Notifikasi"
                class="fixed z-[70] w-80 sm:w-96 border border-white/10 border-t-2 border-t-isf-red bg-isf-navy text-left text-white shadow-2xl rounded-xs overflow-hidden"
                :style="{
                    top: `${position.top}px`,
                    right: `${position.right}px`,
                }"
                @keydown="onKeydown"
            >
                <!-- Header -->
                <div
                    class="p-3 border-b border-white/10 flex items-center justify-between bg-black/20"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="font-space-grotesk font-bold text-xs uppercase tracking-wider text-white"
                            >Notifikasi</span
                        >
                        <span
                            v-if="unreadCount > 0"
                            class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-isf-red/20 text-amber-300 border border-isf-red/30"
                        >
                            {{ unreadCount }} Baru
                        </span>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        @click="markAllAsRead"
                        class="text-[11px] font-medium text-neutral-400 hover:text-white transition-colors cursor-pointer"
                    >
                        Tandai dibaca
                    </button>
                </div>

                <!-- Daftar Notifikasi -->
                <div
                    class="max-h-72 overflow-y-auto divide-y divide-white/5 isf-no-scrollbar"
                >
                    <div
                        v-if="loading"
                        class="p-6 text-center text-xs text-neutral-400"
                    >
                        Memuat notifikasi...
                    </div>
                    <div
                        v-else-if="notifications.length === 0"
                        class="p-6 text-center"
                    >
                        <div
                            class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center mx-auto mb-2 text-neutral-400"
                        >
                            <svg
                                class="w-4 h-4"
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
                        <div class="text-xs font-semibold text-white">
                            Belum ada notifikasi
                        </div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">
                            Pembaruan naskah artikel Anda akan tampil di sini.
                        </div>
                    </div>
                    <template v-else>
                        <button
                            v-for="item in notifications"
                            :key="item.id"
                            type="button"
                            role="menuitem"
                            @click="handleItemClick(item)"
                            class="w-full text-left p-3 hover:bg-white/5 transition-colors flex items-start gap-2.5 cursor-pointer group"
                            :class="item.unread ? 'bg-white/[0.03]' : ''"
                        >
                            <!-- Icon -->
                            <div
                                class="w-6 h-6 rounded-xs flex items-center justify-center shrink-0 mt-0.5"
                                :class="{
                                    'bg-emerald-500/20 text-emerald-400':
                                        item.type === 'approved',
                                    'bg-rose-500/20 text-rose-400':
                                        item.type === 'rejected',
                                    'bg-amber-500/20 text-amber-400':
                                        item.type === 'pending' ||
                                        item.type === 'moderation',
                                    'bg-blue-500/20 text-blue-400':
                                        item.type === 'article' ||
                                        item.type === 'alert',
                                }"
                            >
                                <svg
                                    v-if="item.type === 'approved'"
                                    class="w-3.5 h-3.5"
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
                                    class="w-3.5 h-3.5"
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
                                    v-else
                                    class="w-3.5 h-3.5"
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
                            </div>

                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-center justify-between gap-1"
                                >
                                    <span
                                        class="text-xs font-semibold text-white truncate group-hover:text-amber-300 transition-colors"
                                        >{{ item.title }}</span
                                    >
                                    <span
                                        class="text-[10px] text-neutral-400 whitespace-nowrap shrink-0"
                                        >{{ item.created_at }}</span
                                    >
                                </div>
                                <p
                                    class="text-[11px] text-neutral-300 line-clamp-2 mt-0.5 leading-snug"
                                >
                                    {{ item.message }}
                                </p>
                            </div>

                            <span
                                v-if="item.unread"
                                class="w-1.5 h-1.5 rounded-full bg-isf-red shrink-0 mt-1.5"
                            ></span>
                        </button>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>
