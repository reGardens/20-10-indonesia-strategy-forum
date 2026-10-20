<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { useI18n } from "@/Composables/useI18n";
import { confirmDelete } from "@/Composables/useAlert";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

defineOptions({ layout: CmsLayout });
const { t } = useI18n();
const page = usePage();

const props = defineProps({
    readyTemplates: { type: Array, required: true },
    ongoingTemplates: { type: Array, required: true },
});

const userRole = computed(() => page.props.auth?.user?.roles?.[0] || "user");
const isSuperAdmin = computed(() => userRole.value === "super-admin");

const readyExpanded = ref(true);
const ongoingExpanded = ref(true);
const searchQuery = ref("");

const filteredReadyTemplates = computed(() => {
    if (!searchQuery.value) return props.readyTemplates;
    const q = searchQuery.value.toLowerCase();
    return props.readyTemplates.filter(t => 
        t.name.toLowerCase().includes(q) || 
        (t.description && t.description.toLowerCase().includes(q))
    );
});

const filteredOngoingTemplates = computed(() => {
    if (!searchQuery.value) return props.ongoingTemplates;
    const q = searchQuery.value.toLowerCase();
    return props.ongoingTemplates.filter(t => 
        t.name.toLowerCase().includes(q) || 
        (t.description && t.description.toLowerCase().includes(q))
    );
});

async function handleDelete(template) {
    const confirmed = await confirmDelete(template.name);
    if (!confirmed) return;
    router.delete(`/cms/templates/${template.id}`);
}
</script>

<template>
    <Head title="Pilih & Kelola Template Undangan" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Katalog Template</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Sesuaikan detail dan gaya visual untuk template undangan pernikahan Anda.
                </p>
            </div>
            
            <!-- Search and Create button -->
            <div class="flex items-center gap-3">
                <!-- Search input -->
                <div class="relative w-64">
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari template..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-border bg-card text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-600 shadow-sm"
                    />
                    <IconComponent
                        name="Search"
                        class="absolute left-3 top-2.5 h-3.5 w-3.5 text-muted-foreground pointer-events-none"
                    />
                </div>
                
                <Link
                    v-if="isSuperAdmin"
                    href="/cms/templates/create?type=code"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow hover:bg-blue-700 transition"
                >
                    <IconComponent name="Plus" class="h-4 w-4" />
                    Buat Template Code
                </Link>
            </div>
        </div>

        <!-- Ready Templates Accordion Section -->
        <div class="border border-border bg-card rounded-2xl overflow-hidden shadow-sm">
            <button
                @click="readyExpanded = !readyExpanded"
                class="w-full flex items-center justify-between p-5 font-bold text-base text-foreground hover:bg-muted/30 transition-colors text-left"
            >
                <div class="flex items-center gap-2">
                    <span class="text-lg">🚀</span>
                    <span>Ready ({{ filteredReadyTemplates.length }})</span>
                </div>
                <IconComponent
                    name="ChevronDown"
                    class="h-4 w-4 text-muted-foreground transition-transform duration-200"
                    :class="readyExpanded ? '' : '-rotate-90'"
                />
            </button>
            <div
                v-show="readyExpanded"
                class="p-6 border-t border-border bg-card/50"
            >
                <div v-if="filteredReadyTemplates.length === 0" class="text-center py-10 text-muted-foreground text-sm">
                    Belum ada template yang ready.
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div
                        v-for="tmpl in filteredReadyTemplates"
                        :key="tmpl.id"
                        class="group relative rounded-2xl border border-border bg-card overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-[340px]"
                    >
                        <!-- Thumbnail area -->
                        <div class="w-full h-48 bg-muted relative overflow-hidden flex items-center justify-center">
                            <img
                                :src="tmpl.thumbnail_url || '/templates/default-custom.png'"
                                :alt="tmpl.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                                @error="(e) => { e.target.src = '/templates/default-custom.png' }"
                            />

                            <!-- Badge -->
                            <span class="absolute top-3 right-3 text-[10px] px-2 py-0.5 rounded-full font-semibold shadow-sm z-20 bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Ready
                            </span>

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center gap-3 z-10">
                                <a
                                    :href="`/wedding/demo-${tmpl.id}`"
                                    target="_blank"
                                    class="px-4 py-2 bg-white text-black hover:bg-gray-100 rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5 shadow"
                                >
                                    <IconComponent name="Eye" class="h-4.5 w-4.5" />
                                    Lihat Demo
                                </a>
                                <Link
                                    v-if="isSuperAdmin"
                                    :href="`/cms/templates/create?template=${tmpl.id}`"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition-colors shadow"
                                >
                                    Pilih Template
                                </Link>
                            </div>
                        </div>

                        <!-- Info Area -->
                        <div class="p-4 flex-1 flex flex-col justify-between bg-card">
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-foreground line-clamp-1">{{ tmpl.name }}</h3>
                                <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                    {{ tmpl.description || 'Desain undangan pernikahan premium.' }}
                                </p>
                            </div>

                            <!-- Template Action Buttons (Super Admin Only) -->
                            <div v-if="isSuperAdmin" class="flex items-center justify-between pt-2 border-t border-border/50 mt-2">
                                <Link
                                    :href="`/cms/templates/${tmpl.db_id || tmpl.id}/edit`"
                                    class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline flex items-center gap-1"
                                >
                                    <IconComponent name="Edit" class="h-3.5 w-3.5" />
                                    Edit Design
                                </Link>
                                <button
                                    type="button"
                                    @click="handleDelete(tmpl)"
                                    class="text-xs text-destructive hover:underline flex items-center gap-1"
                                >
                                    <IconComponent name="Trash" class="h-3.5 w-3.5" />
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ongoing Templates Accordion Section (Shown for all roles, positioned at the bottom) -->
        <div class="border border-border bg-card rounded-2xl overflow-hidden shadow-sm">
            <button
                @click="ongoingExpanded = !ongoingExpanded"
                class="w-full flex items-center justify-between p-5 font-bold text-base text-foreground hover:bg-muted/30 transition-colors text-left"
            >
                <div class="flex items-center gap-2">
                    <span class="text-lg">🚧</span>
                    <span>Ongoing ({{ filteredOngoingTemplates.length }})</span>
                </div>
                <IconComponent
                    name="ChevronDown"
                    class="h-4 w-4 text-muted-foreground transition-transform duration-200"
                    :class="ongoingExpanded ? '' : '-rotate-90'"
                />
            </button>
            <div
                v-show="ongoingExpanded"
                class="p-6 border-t border-border bg-card/50"
            >
                <div v-if="filteredOngoingTemplates.length === 0" class="text-center py-10 text-muted-foreground text-sm">
                    Belum ada template ongoing.
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div
                        v-for="tmpl in filteredOngoingTemplates"
                        :key="tmpl.id"
                        class="group relative rounded-2xl border border-border bg-card overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-[340px]"
                    >
                        <!-- Thumbnail area -->
                        <div class="w-full h-48 bg-muted relative overflow-hidden flex items-center justify-center">
                            <img
                                :src="tmpl.thumbnail_url || '/templates/default-custom.png'"
                                :alt="tmpl.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                                @error="(e) => { e.target.src = '/templates/default-custom.png' }"
                            />

                            <!-- Badge -->
                            <span class="absolute top-3 right-3 text-[10px] px-2 py-0.5 rounded-full font-semibold shadow-sm z-20 bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                Ongoing
                            </span>

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center gap-3 z-10">
                                <a
                                    :href="`/wedding/demo-${tmpl.id}`"
                                    target="_blank"
                                    class="px-4 py-2 bg-white text-black hover:bg-gray-100 rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5 shadow"
                                >
                                    <IconComponent name="Eye" class="h-4.5 w-4.5" />
                                    Lihat Demo
                                </a>
                                <Link
                                    v-if="isSuperAdmin"
                                    :href="`/cms/templates/${tmpl.db_id || tmpl.id}/release`"
                                    method="put"
                                    as="button"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition-colors shadow flex items-center gap-1.5"
                                >
                                    <IconComponent name="Check" class="h-4 w-4" />
                                    Rilis ke Ready
                                </Link>
                            </div>
                        </div>

                        <!-- Info Area -->
                        <div class="p-4 flex-1 flex flex-col justify-between bg-card">
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-foreground line-clamp-1">{{ tmpl.name }}</h3>
                                <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                    {{ tmpl.description || 'Template dalam tahap pengembangan.' }}
                                </p>
                            </div>

                            <!-- Action Buttons (Super Admin Only) -->
                            <div v-if="isSuperAdmin" class="flex items-center justify-between pt-2 border-t border-border/50 mt-2">
                                <Link
                                    :href="`/cms/templates/${tmpl.db_id || tmpl.id}/developer`"
                                    class="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline flex items-center gap-1"
                                >
                                    <IconComponent name="Code" class="h-3.5 w-3.5" />
                                    Code Status
                                </Link>
                                <button
                                    type="button"
                                    @click="handleDelete(tmpl)"
                                    class="text-xs text-destructive hover:underline flex items-center gap-1"
                                >
                                    <IconComponent name="Trash" class="h-3.5 w-3.5" />
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
