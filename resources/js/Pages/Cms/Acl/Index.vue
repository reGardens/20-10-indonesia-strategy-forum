<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";
import {
    Badge,
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
    Button
} from "@/Components/UI";

defineOptions({ layout: CmsLayout });
const { t, locale } = useI18n();

const props = defineProps({
    users: { type: Array, required: true },
});

// Selected user for ACL configuration
const selectedUser = ref(props.users[0] || null);

function selectUser(user) {
    selectedUser.value = user;
}

const features = [
    { key: "banner", label: "Banner Sorotan", desc: "Kelola carousel banner sorotan di bagian atas beranda publik (judul, kicker, gambar, dan urutan)" },
    { key: "artikel", label: "Artikel", desc: "Hero beranda, artikel terkini, isu terpopuler, dan konten editorial analitis" },
    { key: "infografik", label: "Infografik", desc: "Grafik data visual, infografik terbaru, dan aset publikasi" },
    { key: "tokoh", label: "Tokoh", desc: "Profil tokoh pekan ini, skor sorotan, jejak legislasi, dan jejaring relasi" },
    { key: "isu", label: "Isu Mingguan", desc: "Anatomi isu pekan ini, kerangka baku 01-07, visual kunci, dan analisis regulasi" },
    { key: "data_grafik", label: "Data Grafik", desc: "Polling, survei, data visual beranda, dan grafik visual isu" },
    { key: "alert", label: "Alert Peringatan Dini", desc: "Feed peringatan dini regulasi, tingkat urgensi, dan status kepatuhan B2B" },
    { key: "email_briefing", label: "Email Briefing", desc: "Briefing eksklusif anggota, ringkasan eksekutif, dan pratinjau newsletter" },
    { key: "pengaturan_halaman", label: "Pengaturan Halaman", desc: "Baris sorotan (topBar), teks pengumuman, dan rentang tanggal pekan ini" }
];

function toggleFeature(featureKey) {
    if (!selectedUser.value) return;
    
    router.post(
        `/cms/acl/${selectedUser.value.id}/toggle`,
        { feature: featureKey },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Keep the same user selected after refresh
                const updated = props.users.find(u => u.id === selectedUser.value.id);
                if (updated) {
                    selectedUser.value = updated;
                }
            }
        }
    );
}

function hasFeature(user, featureKey) {
    return user.allowed_features && user.allowed_features.includes(featureKey);
}
</script>

<template>
    <Head title="ACL - Hak Akses Fitur User" />

    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">ACL (Access Control List)</h1>
            <p class="text-muted-foreground text-sm mt-1">
                Atur fitur-fitur aktif untuk setiap pengguna standard (User POV). Aktifkan atau nonaktifkan menu sesuai paket atau kebutuhan mereka.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-5">
            <!-- Left Side: Users list -->
            <div class="md:col-span-2 rounded-xl border border-border bg-card overflow-hidden h-fit">
                <div class="p-4 border-b border-border bg-muted/20">
                    <h2 class="font-semibold text-sm">Daftar Pengguna Standard</h2>
                </div>
                <div class="divide-y divide-border max-h-[60vh] overflow-y-auto">
                    <div
                        v-for="u in users"
                        :key="u.id"
                        @click="selectUser(u)"
                        :class="[
                            'p-4 cursor-pointer transition-colors flex items-center justify-between',
                            selectedUser && selectedUser.id === u.id
                                ? 'bg-emerald-500/10 dark:bg-emerald-950/30 border-l-4 border-emerald-600 dark:border-emerald-500'
                                : 'hover:bg-muted/40 border-l-4 border-transparent'
                        ]"
                    >
                        <div>
                            <p class="font-semibold text-sm text-foreground">{{ u.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ u.email }}</p>
                        </div>
                        <Badge class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-[10px]">
                            {{ u.allowed_features ? u.allowed_features.length : 0 }} Fitur
                        </Badge>
                    </div>

                    <div v-if="users.length === 0" class="p-8 text-center text-muted-foreground text-sm">
                        Belum ada pengguna standard terdaftar.
                    </div>
                </div>
            </div>

            <!-- Right Side: Features checklist -->
            <div class="md:col-span-3 rounded-xl border border-border bg-card flex flex-col">
                <div v-if="selectedUser" class="flex-1 flex flex-col">
                    <div class="p-4 border-b border-border bg-muted/20 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-sm">Konfigurasi Fitur: <span class="text-emerald-600 dark:text-emerald-400">{{ selectedUser.name }}</span></h2>
                            <p class="text-xs text-muted-foreground mt-0.5">{{ selectedUser.email }}</p>
                        </div>
                    </div>

                    <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto divide-y divide-border/60">
                        <div
                            v-for="(feat, idx) in features"
                            :key="feat.key"
                            :class="['flex items-start justify-between gap-4 py-3.5', idx === 0 ? '' : 'pt-4']"
                        >
                            <div class="space-y-1 pr-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-sm text-foreground">{{ feat.label }}</span>
                                    <Badge
                                        :class="[
                                            'text-[9px] px-1 py-0.5 rounded',
                                            hasFeature(selectedUser, feat.key)
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300'
                                        ]"
                                    >
                                        {{ hasFeature(selectedUser, feat.key) ? 'Aktif' : 'Non-aktif' }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">{{ feat.desc }}</p>
                            </div>

                            <!-- Toggle button -->
                            <button
                                @click="toggleFeature(feat.key)"
                                :class="[
                                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none shrink-0',
                                    hasFeature(selectedUser, feat.key)
                                        ? 'bg-emerald-600 dark:bg-emerald-500'
                                        : 'bg-gray-300 dark:bg-gray-700',
                                ]"
                                role="switch"
                                :aria-checked="hasFeature(selectedUser, feat.key)"
                            >
                                <span
                                    :class="[
                                        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm',
                                        hasFeature(selectedUser, feat.key)
                                            ? 'translate-x-6'
                                            : 'translate-x-1',
                                    ]"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="flex-1 flex flex-col items-center justify-center p-12 text-muted-foreground text-center">
                    <IconComponent name="ShieldLock" class="h-12 w-12 text-muted-foreground/50 mb-3" />
                    <p class="text-sm">Pilih pengguna standard di sebelah kiri untuk mengonfigurasi hak akses fitur.</p>
                </div>
            </div>
        </div>
    </div>
</template>
