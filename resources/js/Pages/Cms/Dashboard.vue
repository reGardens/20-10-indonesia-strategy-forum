<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
    Badge,
    Button,
} from "@/Components/UI";

defineOptions({ layout: CmsLayout });

const { t, locale } = useI18n();

const props = defineProps({
    isSuperAdmin: { type: Boolean, default: false },
    totalUsers: { type: Number, default: 0 },
    totalRoles: { type: Number, default: 0 },
    totalWeddings: { type: Number, default: 0 },
    activeUsers: { type: Number, default: 0 },
    totalCustomTemplates: { type: Number, default: 0 },
    recentWeddings: { type: Array, default: () => [] },
    recentUsers: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const cards = computed(() => [
    {
        label: "Total Pengguna",
        value: props.totalUsers,
        icon: "Users",
        desc: "User terdaftar di sistem",
    },
    {
        label: "User Aktif Undangan",
        value: props.activeUsers,
        icon: "CircleCheck",
        desc: "User yang memiliki undangan",
    },
    {
        label: "Total Undangan (Weddings)",
        value: props.totalWeddings,
        icon: "Heart",
        desc: "Pernikahan yang didaftarkan",
    },
    {
        label: "Template Kustom",
        value: props.totalCustomTemplates,
        icon: "Template",
        desc: "Template kustom buatan admin",
    },
]);

// Modal State
const selectedWedding = ref(null);
const isModalOpen = ref(false);

function showDetail(wedding) {
    selectedWedding.value = wedding;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    selectedWedding.value = null;
}

function formatDate(dateString) {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function formatEventDate(dateString) {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

const availableFeatures = [
    { key: "undangan_digital", label: "Undangan Digital" },
    { key: "rsvp_online", label: "RSVP Online" },
    { key: "informasi_acara", label: "Informasi Acara" },
    { key: "love_story", label: "Love Story / Timeline" },
    { key: "manajemen_tamu", label: "Manajemen Tamu" },
    { key: "amplop_digital", label: "Amplop Digital" },
    { key: "galeri", label: "Galeri Foto & Video" },
    { key: "countdown", label: "Countdown Wedding" },
    { key: "qr_checkin", label: "QR Check-In Tamu" },
    { key: "live_streaming", label: "Live Streaming" },
    { key: "ucapan_doa", label: "Ucapan & Doa" },
    { key: "wishlist", label: "Wishlist / Registry" },
];

function isFeatureEnabled(wedding, featureKey) {
    const userFeatures = wedding?.user?.allowed_features;
    if (!userFeatures) return true; // Default true if not defined
    return userFeatures.includes(featureKey);
}
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <div class="p-6 space-y-8">
        <!-- Welcoming -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                {{ t("common.welcome") }}, {{ currentUser?.name }}!
            </h1>
            <p class="text-muted-foreground mt-1">
                <span v-if="isSuperAdmin"
                    >Panel Super Admin WeddingApp. Kelola performa, pengguna,
                    dan template secara terpusat.</span
                >
                <span v-else
                    >Selamat datang di WeddingApp. Kelola undangan pernikahan
                    Anda di sini.</span
                >
            </p>
        </div>

        <!-- ===== SUPER ADMIN VIEW ===== -->
        <template v-if="isSuperAdmin">
            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="card in cards"
                    :key="card.label"
                    class="rounded-xl border border-border bg-card p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                >
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-sm font-medium text-muted-foreground"
                                >{{ card.label }}</span
                            >
                            <IconComponent
                                :name="card.icon"
                                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                        <p class="text-3xl font-bold tracking-tight">
                            {{ card.value }}
                        </p>
                    </div>
                    <p class="text-xs text-muted-foreground mt-2">
                        {{ card.desc }}
                    </p>
                </div>
            </div>

            <!-- Weddings Section (Admin sees all) -->
            <div class="space-y-4">
                <h2 class="text-xl font-bold tracking-tight">
                    Daftar Undangan Aktif
                </h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="wedding in recentWeddings"
                        :key="wedding.id"
                        class="rounded-xl border border-border bg-card p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3
                                        class="font-bold text-base text-foreground line-clamp-1"
                                    >
                                        {{ wedding.label }}
                                    </h3>
                                    <p
                                        class="text-xs font-mono text-muted-foreground"
                                    >
                                        /wedding/{{ wedding.slug }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400"
                                >
                                    <IconComponent
                                        name="Heart"
                                        class="h-5 w-5"
                                    />
                                </span>
                            </div>

                            <div
                                class="border-t border-border/60 pt-3 space-y-2 text-xs"
                            >
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Pemilik:</span
                                    >
                                    <span
                                        class="font-medium text-right line-clamp-1 w-2/3"
                                    >
                                        {{
                                            wedding.user
                                                ? wedding.user.name
                                                : "-"
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Email Pemilik:</span
                                    >
                                    <span
                                        class="font-medium text-right line-clamp-1 w-2/3"
                                    >
                                        {{
                                            wedding.user
                                                ? wedding.user.email
                                                : "-"
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between pt-1">
                                    <span class="text-muted-foreground"
                                        >Status Undangan:</span
                                    >
                                    <Badge
                                        class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                        >Aktif</Badge
                                    >
                                </div>
                            </div>

                            <!-- Mini stats -->
                            <div
                                class="grid grid-cols-2 gap-2 text-center bg-muted/40 rounded-lg p-2 mt-2"
                            >
                                <div>
                                    <p
                                        class="text-[10px] text-muted-foreground uppercase font-semibold"
                                    >
                                        Tamu
                                    </p>
                                    <p
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{ wedding.guests_count }}
                                    </p>
                                </div>
                                <div class="border-l border-border">
                                    <p
                                        class="text-[10px] text-muted-foreground uppercase font-semibold"
                                    >
                                        RSVP
                                    </p>
                                    <p
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{ wedding.rsvps_count }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 mt-4 pt-3 border-t border-border/60"
                        >
                            <a
                                :href="`/wedding/${wedding.slug}`"
                                target="_blank"
                                class="flex-1 text-center py-1.5 px-3 rounded-lg border border-border hover:bg-muted text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                            >
                                <IconComponent name="Eye" class="h-3.5 w-3.5" />
                                Lihat Live
                            </a>
                            <Button
                                variant="default"
                                size="sm"
                                class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center justify-center gap-1.5"
                                @click="showDetail(wedding)"
                            >
                                <IconComponent
                                    name="InfoCircle"
                                    class="h-3.5 w-3.5"
                                />
                                Detail
                            </Button>
                        </div>
                    </div>
                </div>
                <div
                    v-if="recentWeddings.length === 0"
                    class="text-center py-12 border border-dashed rounded-xl text-muted-foreground bg-card"
                >
                    Belum ada data pernikahan/undangan.
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Users Table -->
                <div
                    class="rounded-xl border border-border bg-card overflow-hidden"
                >
                    <div
                        class="p-5 border-b border-border flex items-center justify-between"
                    >
                        <h2 class="font-semibold text-lg">Pengguna Terbaru</h2>
                        <span class="text-xs text-muted-foreground"
                            >5 Registrasi Terakhir</span
                        >
                    </div>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Peran</TableHead>
                                    <TableHead>Terdaftar</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="u in recentUsers" :key="u.id">
                                    <TableCell class="font-medium text-sm">{{
                                        u.name
                                    }}</TableCell>
                                    <TableCell class="text-sm">{{
                                        u.email
                                    }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            v-for="role in u.roles"
                                            :key="role"
                                            class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs px-1.5 py-0.5 rounded"
                                        >
                                            {{ role }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell
                                        class="text-xs text-muted-foreground"
                                        >{{
                                            formatDate(u.created_at)
                                        }}</TableCell
                                    >
                                </TableRow>
                                <TableRow v-if="recentUsers.length === 0">
                                    <TableCell
                                        colspan="4"
                                        class="text-center py-6 text-sm text-muted-foreground"
                                        >Belum ada data pengguna.</TableCell
                                    >
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== REGULAR USER VIEW ===== -->
        <template v-else>
            <!-- User's Wedding Stats -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div
                    class="rounded-xl border border-border bg-card p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-muted-foreground"
                            >Undangan Saya</span
                        >
                        <IconComponent
                            name="Heart"
                            class="h-5 w-5 text-emerald-600"
                        />
                    </div>
                    <p class="text-3xl font-bold tracking-tight">
                        {{ totalWeddings }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-2">
                        Total undangan yang Anda buat
                    </p>
                </div>
                <div
                    class="rounded-xl border border-border bg-card p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-muted-foreground"
                            >Total Tamu</span
                        >
                        <IconComponent
                            name="Users"
                            class="h-5 w-5 text-emerald-600"
                        />
                    </div>
                    <p class="text-3xl font-bold tracking-tight">
                        {{
                            recentWeddings.reduce(
                                (sum, w) => sum + w.guests_count,
                                0,
                            )
                        }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-2">
                        Tamu di semua undangan Anda
                    </p>
                </div>
                <div
                    class="rounded-xl border border-border bg-card p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-muted-foreground"
                            >Total RSVP</span
                        >
                        <IconComponent
                            name="CircleCheck"
                            class="h-5 w-5 text-emerald-600"
                        />
                    </div>
                    <p class="text-3xl font-bold tracking-tight">
                        {{
                            recentWeddings.reduce(
                                (sum, w) => sum + w.rsvps_count,
                                0,
                            )
                        }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-2">
                        Konfirmasi kehadiran tamu
                    </p>
                </div>
            </div>

            <!-- User's Weddings -->
            <div class="space-y-4">
                <h2 class="text-xl font-bold tracking-tight">
                    Undangan Pernikahan Saya
                </h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="wedding in recentWeddings"
                        :key="wedding.id"
                        class="rounded-xl border border-border bg-card p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3
                                        class="font-bold text-base text-foreground line-clamp-1"
                                    >
                                        {{ wedding.label }}
                                    </h3>
                                    <p
                                        class="text-xs font-mono text-muted-foreground"
                                    >
                                        /wedding/{{ wedding.slug }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400"
                                >
                                    <IconComponent
                                        name="Heart"
                                        class="h-5 w-5"
                                    />
                                </span>
                            </div>

                            <!-- Mini stats -->
                            <div
                                class="grid grid-cols-2 gap-2 text-center bg-muted/40 rounded-lg p-2 mt-2"
                            >
                                <div>
                                    <p
                                        class="text-[10px] text-muted-foreground uppercase font-semibold"
                                    >
                                        Tamu
                                    </p>
                                    <p
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{ wedding.guests_count }}
                                    </p>
                                </div>
                                <div class="border-l border-border">
                                    <p
                                        class="text-[10px] text-muted-foreground uppercase font-semibold"
                                    >
                                        RSVP
                                    </p>
                                    <p
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{ wedding.rsvps_count }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 mt-4 pt-3 border-t border-border/60"
                        >
                            <a
                                :href="`/wedding/${wedding.slug}`"
                                target="_blank"
                                class="flex-1 text-center py-1.5 px-3 rounded-lg border border-border hover:bg-muted text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                            >
                                <IconComponent name="Eye" class="h-3.5 w-3.5" />
                                Lihat Live
                            </a>
                            <a
                                :href="`/cms/${wedding.slug}/dashboard`"
                                class="flex-1 text-center py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                            >
                                <IconComponent
                                    name="Edit"
                                    class="h-3.5 w-3.5"
                                />
                                Kelola
                            </a>
                        </div>
                    </div>
                </div>
                <div
                    v-if="recentWeddings.length === 0"
                    class="text-center py-16 border border-dashed rounded-xl text-muted-foreground bg-card"
                >
                    <span class="text-4xl block mb-3">💒</span>
                    <p class="font-medium">Belum ada undangan</p>
                    <p class="text-sm mt-1">
                        Hubungi admin untuk membuat undangan pernikahan Anda.
                    </p>
                </div>
            </div>
        </template>
    </div>

    <!-- Detail Modal -->
    <div
        v-if="isModalOpen && selectedWedding"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        @click.self="closeModal"
    >
        <div
            class="bg-card border border-border rounded-xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh] animate-in fade-in zoom-in-95 duration-150"
        >
            <!-- Modal Header -->
            <div
                class="p-5 border-b border-border flex items-center justify-between bg-emerald-900/10 dark:bg-emerald-950/20"
            >
                <div>
                    <h3 class="text-lg font-bold text-foreground">
                        Detail Undangan: {{ selectedWedding.label }}
                    </h3>
                    <p class="text-xs text-muted-foreground font-mono">
                        ID: {{ selectedWedding.slug }}
                    </p>
                </div>
                <button
                    @click="closeModal"
                    class="p-1 rounded-lg hover:bg-muted text-muted-foreground transition-colors"
                >
                    <IconComponent name="X" class="h-5 w-5" />
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto space-y-6">
                <!-- Bride & Groom Info -->
                <div class="space-y-2">
                    <h4
                        class="text-sm font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5"
                    >
                        <IconComponent
                            name="Heart"
                            class="h-4 w-4 text-emerald-600"
                        />
                        Pasangan Pengantin
                    </h4>
                    <div
                        class="grid grid-cols-2 gap-4 bg-muted/30 rounded-xl p-4 border border-border/50"
                    >
                        <div class="space-y-1">
                            <span
                                class="text-[10px] text-muted-foreground uppercase font-bold"
                                >Mempelai Pria</span
                            >
                            <p class="font-bold text-foreground text-sm">
                                {{
                                    selectedWedding.couple?.groom_full_name ||
                                    "Belum diisi"
                                }}
                            </p>
                            <p
                                class="text-xs text-muted-foreground italic"
                                v-if="selectedWedding.couple?.groom_nickname"
                            >
                                ({{ selectedWedding.couple.groom_nickname }})
                            </p>
                        </div>
                        <div class="space-y-1 border-l border-border pl-4">
                            <span
                                class="text-[10px] text-muted-foreground uppercase font-bold"
                                >Mempelai Wanita</span
                            >
                            <p class="font-bold text-foreground text-sm">
                                {{
                                    selectedWedding.couple?.bride_full_name ||
                                    "Belum diisi"
                                }}
                            </p>
                            <p
                                class="text-xs text-muted-foreground italic"
                                v-if="selectedWedding.couple?.bride_nickname"
                            >
                                ({{ selectedWedding.couple.bride_nickname }})
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Event Info -->
                <div class="space-y-2">
                    <h4
                        class="text-sm font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5"
                    >
                        <IconComponent
                            name="CalendarEvent"
                            class="h-4 w-4 text-emerald-600"
                        />
                        Agenda & Acara
                    </h4>
                    <div
                        v-if="
                            selectedWedding.events &&
                            selectedWedding.events.length > 0
                        "
                        class="space-y-3"
                    >
                        <div
                            v-for="ev in selectedWedding.events"
                            :key="ev.id"
                            class="border border-border/60 bg-muted/20 rounded-xl p-3.5 space-y-1 text-xs"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span
                                    class="font-bold text-foreground text-sm"
                                    >{{ ev.name }}</span
                                >
                                <Badge
                                    class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                                    >Jadwal</Badge
                                >
                            </div>
                            <p class="text-muted-foreground">
                                📅 {{ formatEventDate(ev.date) }}
                            </p>
                            <p class="text-muted-foreground">
                                ⏰ {{ ev.start_time || "00:00" }} WIB s/d
                                selesai
                            </p>
                            <p class="text-muted-foreground">
                                📍
                                {{
                                    ev.location_name ||
                                    "Lokasi belum dispesifikasikan"
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-xs text-center py-4 bg-muted/20 rounded-xl border border-border/50 text-muted-foreground"
                    >
                        Belum ada agenda acara yang dibuat.
                    </div>
                </div>

                <!-- ACL Feature Enabled Checklist -->
                <div class="space-y-3">
                    <h4
                        class="text-sm font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5"
                    >
                        <IconComponent
                            name="ShieldLock"
                            class="h-4 w-4 text-emerald-600"
                        />
                        Akses Fitur User (ACL)
                    </h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="feat in availableFeatures"
                            :key="feat.key"
                            class="flex items-center gap-2 p-2.5 rounded-lg border border-border/50 bg-card text-xs"
                        >
                            <span
                                :class="[
                                    'w-4 h-4 rounded-full flex items-center justify-center text-[10px] text-white',
                                    isFeatureEnabled(selectedWedding, feat.key)
                                        ? 'bg-emerald-600'
                                        : 'bg-red-500',
                                ]"
                            >
                                <IconComponent
                                    :name="
                                        isFeatureEnabled(
                                            selectedWedding,
                                            feat.key,
                                        )
                                            ? 'Check'
                                            : 'X'
                                    "
                                    class="h-3 w-3 stroke-[3]"
                                />
                            </span>
                            <span
                                :class="
                                    isFeatureEnabled(selectedWedding, feat.key)
                                        ? 'font-medium text-foreground'
                                        : 'text-muted-foreground line-through'
                                "
                            >
                                {{ feat.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="p-4 border-t border-border flex justify-end bg-muted/20"
            >
                <Button variant="outline" @click="closeModal"
                    >Tutup Detail</Button
                >
            </div>
        </div>
    </div>
</template>
