<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import StatusBadge from "@/Pages/Cms/components/shared/StatusBadge.vue";

defineOptions({ layout: CmsLayout });

const props = defineProps({
    isSuperAdmin: { type: Boolean, default: false },
    totalUsers: { type: Number, default: 0 },
    totalRoles: { type: Number, default: 0 },
    contentStats: { type: Array, default: () => [] },
    recentContent: { type: Array, default: () => [] },
    publicLinks: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

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
</script>

<template>
    <Head title="Dasbor — Indonesia Strategy Forum" />

    <div class="p-6 space-y-8 max-w-7xl mx-auto">
        <!-- Welcoming Header (from plan.md & screenshot) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-border">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                    Selamat Datang, {{ currentUser?.name || 'Super Admin' }}!
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Panel Admin Indonesia Strategy Forum. Kelola konten publik, portal anggota, dan pengguna secara terpusat.
                </p>
            </div>

            <!-- Quick Action Links -->
            <div class="flex items-center gap-2">
                <a
                    href="/"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm"
                >
                    <IconComponent name="World" :size="15" />
                    <span>Beranda ISF</span>
                    <IconComponent name="ExternalLink" :size="12" class="opacity-70" />
                </a>
                <a
                    href="/anggota"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border border-border bg-card text-foreground hover:bg-accent transition"
                >
                    <IconComponent name="Shield" :size="15" />
                    <span>Portal Anggota</span>
                    <IconComponent name="ExternalLink" :size="12" class="opacity-70" />
                </a>
            </div>
        </div>

        <!-- Primary Stat Cards Grid (styled like the user's screenshot) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Super Admin Card -->
            <div
                v-if="isSuperAdmin"
                class="p-5 rounded-xl border border-border bg-card flex flex-col justify-between shadow-xs transition hover:border-emerald-500/40"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Pengguna</span>
                    <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-500">
                        <IconComponent name="Users" :size="18" />
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-foreground">{{ totalUsers }}</div>
                    <div class="text-xs text-muted-foreground mt-1">User terdaftar di sistem</div>
                </div>
                <div class="mt-4 pt-3 border-t border-border flex items-center justify-between">
                    <Link href="/cms/users" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Kelola Pengguna &rarr;
                    </Link>
                    <span class="text-[11px] text-muted-foreground font-medium">{{ totalRoles }} Peran</span>
                </div>
            </div>

            <!-- Content Stat Cards -->
            <div
                v-for="stat in contentStats"
                :key="stat.key"
                class="p-5 rounded-xl border border-border bg-card flex flex-col justify-between shadow-xs transition hover:border-emerald-500/40"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ stat.label }}</span>
                    <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-500">
                        <IconComponent :name="stat.icon || 'Article'" :size="18" />
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-foreground">{{ stat.total }}</div>
                    <div class="flex items-center gap-2 mt-1 text-xs">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ stat.terbit }} Terbit</span>
                        <span class="text-muted-foreground/60">•</span>
                        <span class="text-muted-foreground">{{ stat.draf }} Draf</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-border flex items-center justify-between">
                    <Link :href="stat.href" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Buka Daftar &rarr;
                    </Link>
                    <Link :href="`${stat.href}/create`" class="text-[11px] text-muted-foreground hover:text-foreground">
                        + Tambah
                    </Link>
                </div>
            </div>
        </div>

        <!-- Recent Content & Public Links -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Konten Terbaru Table -->
            <div class="lg:col-span-2 rounded-xl border border-border bg-card overflow-hidden shadow-xs">
                <div class="p-4 border-b border-border flex items-center justify-between bg-muted/20">
                    <div>
                        <h2 class="text-sm font-bold text-foreground">Konten Terbaru Diperbarui</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">8 data editorial dan peringatan intelijen terkini.</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                        Live Data
                    </span>
                </div>

                <div v-if="recentContent.length === 0" class="p-12 text-center text-muted-foreground text-sm">
                    Belum ada konten.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40 text-muted-foreground text-xs uppercase font-medium border-b border-border">
                            <tr>
                                <th class="px-4 py-3">Judul Konten</th>
                                <th class="px-4 py-3">Modul</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Diperbarui</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="item in recentContent"
                                :key="`${item.type}-${item.title}`"
                                class="hover:bg-muted/30 transition"
                            >
                                <td class="px-4 py-3 font-semibold text-foreground text-xs max-w-xs truncate">
                                    {{ item.title }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="px-2 py-0.5 rounded bg-muted text-muted-foreground font-medium text-[11px]">
                                        {{ item.label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <StatusBadge :status="item.status" />
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ formatDate(item.updated_at) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="item.href"
                                        class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                                    >
                                        Edit
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tautan Halaman Publik -->
            <div class="rounded-xl border border-border bg-card p-5 space-y-4 shadow-xs">
                <div class="pb-3 border-b border-border">
                    <h3 class="text-sm font-bold text-foreground">Tautan Situs Publik</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Verifikasi langsung tampilan editorial dan portal.</p>
                </div>

                <div class="space-y-2">
                    <a
                        v-for="link in publicLinks"
                        :key="link.href"
                        :href="link.href"
                        target="_blank"
                        class="flex items-center justify-between p-3 rounded-lg border border-border hover:border-emerald-500/50 hover:bg-muted/30 transition text-xs font-medium group"
                    >
                        <span class="text-foreground group-hover:text-emerald-500 transition">{{ link.label }}</span>
                        <IconComponent name="ChevronRight" :size="14" class="text-muted-foreground group-hover:translate-x-0.5 transition" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
