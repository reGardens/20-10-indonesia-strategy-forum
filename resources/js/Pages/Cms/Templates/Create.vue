<script setup>
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { Button, Input, Label } from "@/Components/UI";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

defineOptions({ layout: CmsLayout });

const form = useForm({
    is_custom: true,
    name: "",
    slug: "",
    description: "",
    thumbnail_url: "",
    config: {
        primary_color: "#1b4332",
        secondary_color: "#40916c",
        accent_color: "#d8f3dc",
        font_family: "Playfair Display",
        animation_style: "fade-up",
        bg_pattern_url: "",
        custom_css: ""
    }
});

function pascalName(slug) {
    if (!slug) return "";
    return slug
        .replace(/[-_]+/g, " ")
        .replace(/[^\w\s]/g, "")
        .replace(/\s+(.)(\w*)/g, (m, $1, $2) => $1.toUpperCase() + $2.toLowerCase())
        .replace(/^\w/, l => l.toUpperCase());
}

const fontOptions = [
    "Playfair Display", "Lora", "Cinzel", "Poppins",
    "Montserrat", "Cormorant Garamond", "Outfit", "Alex Brush", "Great Vibes"
];

const animationOptions = [
    { label: "Fade Up", value: "fade-up" },
    { label: "Fade Down", value: "fade-down" },
    { label: "Fade Left", value: "fade-left" },
    { label: "Fade Right", value: "fade-right" },
    { label: "Zoom In", value: "zoom-in" },
    { label: "None", value: "none" }
];

// ── Sub-menu sections ──
const sections = [
    { key: "basic",      icon: "InfoCircle",  label: "Info Dasar",      desc: "Nama, slug & deskripsi template" },
    { key: "colors",     icon: "Palette",     label: "Skema Warna",     desc: "Warna primer, sekunder & aksen" },
    { key: "typography", icon: "Language",    label: "Tipografi & Gaya", desc: "Font, animasi & background" },
    { key: "advanced",   icon: "Settings",    label: "CSS Kustom",      desc: "Styling tambahan dengan CSS" },
];
const visibleSections = computed(() => {
    if (!form.is_custom) {
        return sections.filter(s => s.key === 'basic');
    }
    return sections;
});
const page = usePage();
const activeSection = ref("basic");

watch(() => page.url, () => {
    const urlParams = new URLSearchParams(window.location.search);
    const sec = urlParams.get("section");
    if (sec && sections.some(s => s.key === sec)) {
        activeSection.value = sec;
    } else {
        activeSection.value = "basic";
    }
}, { immediate: true });

function changeSection(newSectionKey) {
    const urlParams = new URLSearchParams(window.location.search);
    const templateSlug = urlParams.get("template");
    const query = templateSlug ? `?template=${templateSlug}&section=${newSectionKey}` : `?section=${newSectionKey}`;
    router.visit(`/cms/templates/create${query}`, {
        preserveScroll: true,
        preserveState: true,
    });
}

const activeLabel = computed(() => visibleSections.value.find(s => s.key === activeSection.value)?.label ?? "");

function submit() {
    form.post("/cms/templates");
}

function generateSlug() {
    form.slug = form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/(^-|-$)+/g, "");
}

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const templateSlug = urlParams.get("template");
    const typeParam = urlParams.get("type");
    if (typeParam === "code") {
        form.is_custom = false;
    } else {
        form.is_custom = true;
    }

    if (templateSlug) {
        axios.get("/api/templates").then(res => {
            const matched = res.data.find(t => t.id === templateSlug);
            if (matched) {
                form.name = `Salinan ${matched.name}`;
                form.description = matched.description || '';
                
                const config = matched.config || {};
                form.config.primary_color = config.primary_color || config.primaryColor || '#1b4332';
                form.config.secondary_color = config.secondary_color || config.secondaryColor || '#40916c';
                form.config.accent_color = config.accent_color || config.accentColor || '#d8f3dc';
                form.config.font_family = config.font_family || config.fontFamily || 'Playfair Display';
                form.config.animation_style = config.animation_style || config.animationStyle || 'fade-up';
                form.config.bg_pattern_url = config.bg_pattern_url || config.bgPatternUrl || '';
                form.config.custom_css = config.custom_css || config.customCss || '';
                
                form.slug = templateSlug + '-' + Math.floor(1000 + Math.random() * 9000);
            }
        });
    }
});

// Live dynamic styles for preview pane
const dynamicStyles = computed(() => {
    const fontName = form.config.font_family.replace(/\s+/g, "+");
    const importFont = `@import url('https://fonts.googleapis.com/css2?family=${fontName}:ital,wght@0,300;0,400;0,700;1,400&display=swap');`;
    return `
        ${importFont}
        :root {
            --mock-primary: ${form.config.primary_color};
            --mock-secondary: ${form.config.secondary_color};
            --mock-accent: ${form.config.accent_color};
            --mock-font: '${form.config.font_family}', serif;
        }
        .mock-invitation-container {
            font-family: var(--mock-font), sans-serif;
            background-color: var(--mock-accent);
            ${form.config.bg_pattern_url ? `background-image: url('${form.config.bg_pattern_url}');` : ""}
            background-size: cover;
            background-position: center;
        }
        .mock-text-primary { color: var(--mock-primary); }
        .mock-text-secondary { color: var(--mock-secondary); }
        .mock-btn-primary { background-color: var(--mock-primary); color: #ffffff; }
        .mock-btn-outline { border: 2px solid var(--mock-primary); color: var(--mock-primary); }
        ${form.config.custom_css || ""}
    `;
});
</script>

<template>
    <Head title="Buat Template Undangan Baru" />

    <!-- Inject live styles -->
    <component :is="'style'">{{ dynamicStyles }}</component>

    <div class="flex flex-col h-full">
        <!-- Page header -->
        <div class="flex items-center gap-4 px-6 py-4 border-b border-border bg-card/50">
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold tracking-tight truncate">Buat Template Kustom</h1>
                <p class="text-xs text-muted-foreground">Sesuaikan detail dan gaya tampilan untuk undangan kustom baru.</p>
            </div>
            <Button
                type="button"
                @click="submit"
                class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-600 gap-1.5 shrink-0"
                :disabled="form.processing"
            >
                <IconComponent name="DeviceFloppy" class="h-4 w-4" />
                <span v-if="form.processing">Menyimpan...</span>
                <span v-else>Simpan Template</span>
            </Button>
        </div>

        <!-- 2-column body -->
        <div class="flex flex-1 min-h-0 overflow-hidden">

            <!-- ② Center form content -->
            <form @submit.prevent="submit" class="flex-1 overflow-y-auto p-6 bg-background">
                <div class="max-w-2xl mx-auto space-y-6">
                    
                    <!-- Wizard Stepper with Step Indicators -->
                    <div class="flex items-center justify-between w-full mb-8 border border-border/60 bg-muted/10 p-4 rounded-xl">
                        <div v-for="(sec, index) in visibleSections" :key="sec.key" class="flex items-center flex-1 last:flex-none">
                            <button
                                type="button"
                                @click="changeSection(sec.key)"
                                class="flex flex-col items-center focus:outline-none group relative"
                            >
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all duration-300 font-bold text-[10px] z-10"
                                    :class="[
                                        activeSection === sec.key
                                            ? 'bg-emerald-600 border-emerald-600 text-white shadow-md shadow-emerald-600/20'
                                            : visibleSections.findIndex(s => s.key === activeSection) > index
                                            ? 'bg-emerald-100 border-emerald-600 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-card border-border text-muted-foreground hover:border-emerald-500'
                                    ]"
                                >
                                    <IconComponent v-if="visibleSections.findIndex(s => s.key === activeSection) > index" name="Check" class="w-3 h-3 stroke-[3]" />
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <span
                                    class="text-[9px] font-bold mt-1.5 whitespace-nowrap transition-colors"
                                    :class="activeSection === sec.key ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'"
                                >
                                    {{ sec.label }}
                                </span>
                            </button>
                            <div
                                v-if="index < visibleSections.length - 1"
                                class="h-[1.5px] flex-1 mx-2 transition-all duration-500"
                                :class="visibleSections.findIndex(s => s.key === activeSection) > index ? 'bg-emerald-600' : 'bg-border'"
                            />
                        </div>
                    </div>

                    <!-- Section heading -->
                    <div class="flex items-center gap-2 pb-3 border-b border-border">
                        <IconComponent :name="visibleSections.find(s=>s.key===activeSection)?.icon" class="h-5 w-5 text-emerald-600" />
                        <h2 class="font-bold text-base text-foreground">{{ activeLabel }}</h2>
                    </div>

                    <!-- Section: Info Dasar -->
                    <div v-show="activeSection === 'basic'" class="space-y-4">
                        <!-- Tipe Template Selector -->
                        <div class="space-y-1.5 border-b pb-4 mb-4">
                            <Label class="text-xs font-bold text-muted-foreground uppercase">Tipe Template</Label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-1.5">
                                <button
                                    type="button"
                                    @click="form.is_custom = true"
                                    class="flex flex-col items-start gap-1.5 p-4 border rounded-2xl hover:bg-muted/30 transition text-left"
                                    :class="form.is_custom ? 'border-emerald-600 bg-emerald-50/10 dark:bg-emerald-950/20' : 'border-border'"
                                >
                                    <div class="flex items-center gap-2 font-bold text-sm" :class="form.is_custom ? 'text-emerald-600' : 'text-foreground'">
                                        <span class="text-lg">🎨</span>
                                        <span>Kustom (Visual Editor)</span>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground leading-normal">Desain template dinamis, edit warna, font, & CSS via dashboard editor.</p>
                                </button>
                                <button
                                    type="button"
                                    @click="form.is_custom = false"
                                    class="flex flex-col items-start gap-1.5 p-4 border rounded-2xl hover:bg-muted/30 transition text-left"
                                    :class="!form.is_custom ? 'border-blue-600 bg-blue-50/10 dark:bg-blue-950/20' : 'border-border'"
                                >
                                    <div class="flex items-center gap-2 font-bold text-sm" :class="!form.is_custom ? 'text-blue-600' : 'text-foreground'">
                                        <span class="text-lg">💻</span>
                                        <span>Code (Developer Scaffold)</span>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground leading-normal">Scaffold boilerplate Vue murni. File template akan langsung di-scaffold di resources.</p>
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="name">Nama Template</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    @input="generateSlug"
                                    placeholder="Contoh: Rustik Klasik"
                                    required
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="slug">Slug / ID Unik</Label>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    placeholder="rustik-klasik"
                                    required
                                />
                                <p v-if="form.errors.slug" class="text-xs text-destructive">{{ form.errors.slug }}</p>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="description">Deskripsi Template</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="w-full min-h-[100px] rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-600"
                                placeholder="Tulis deskripsi atau instruksi template di sini..."
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="thumbnail">URL Gambar Thumbnail</Label>
                            <Input
                                id="thumbnail"
                                v-model="form.thumbnail_url"
                                placeholder="Contoh: /templates/rustik-klasik/thumbnail.webp"
                            />
                        </div>
                    </div>

                    <!-- Section: Skema Warna -->
                    <div v-show="activeSection === 'colors'" class="space-y-4">
                        <p class="text-sm text-muted-foreground">Pilih palette warna yang akan digunakan di seluruh undangan.</p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="space-y-2 border rounded-xl p-4 bg-muted/10 text-center">
                                <Label>Warna Primer</Label>
                                <input
                                    type="color"
                                    v-model="form.config.primary_color"
                                    class="w-16 h-16 mx-auto block border-0 rounded-xl cursor-pointer shadow-md"
                                />
                                <Input
                                    v-model="form.config.primary_color"
                                    class="font-mono uppercase text-center text-xs"
                                    required
                                />
                                <p class="text-[10px] text-muted-foreground">Judul, tombol utama</p>
                            </div>
                            <div class="space-y-2 border rounded-xl p-4 bg-muted/10 text-center">
                                <Label>Warna Sekunder</Label>
                                <input
                                    type="color"
                                    v-model="form.config.secondary_color"
                                    class="w-16 h-16 mx-auto block border-0 rounded-xl cursor-pointer shadow-md"
                                />
                                <Input
                                    v-model="form.config.secondary_color"
                                    class="font-mono uppercase text-center text-xs"
                                    required
                                />
                                <p class="text-[10px] text-muted-foreground">Sub-judul, teks info</p>
                            </div>
                            <div class="space-y-2 border rounded-xl p-4 bg-muted/10 text-center">
                                <Label>Warna Aksen / BG</Label>
                                <input
                                    type="color"
                                    v-model="form.config.accent_color"
                                    class="w-16 h-16 mx-auto block border-0 rounded-xl cursor-pointer shadow-md"
                                />
                                <Input
                                    v-model="form.config.accent_color"
                                    class="font-mono uppercase text-center text-xs"
                                    required
                                />
                                <p class="text-[10px] text-muted-foreground">Background halaman</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Tipografi & Gaya -->
                    <div v-show="activeSection === 'typography'" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="font_family">Font Family Utama</Label>
                                <select
                                    id="font_family"
                                    v-model="form.config.font_family"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-600"
                                    required
                                >
                                    <option v-for="font in fontOptions" :key="font" :value="font">{{ font }}</option>
                                </select>
                                <p class="text-xs text-muted-foreground mt-1" :style="{ fontFamily: form.config.font_family }">
                                    Contoh teks: Bismillah, Atas ijin Allah SWT...
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="animation_style">Gaya Animasi Transisi</Label>
                                <select
                                    id="animation_style"
                                    v-model="form.config.animation_style"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-600"
                                    required
                                >
                                    <option v-for="opt in animationOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="bg_pattern">URL Pola Latar Belakang</Label>
                            <Input
                                id="bg_pattern"
                                v-model="form.config.bg_pattern_url"
                                placeholder="Contoh: https://www.transparenttextures.com/patterns/cream-paper.png"
                            />
                            <p class="text-xs text-muted-foreground">Gambar akan diterapkan sebagai background berulang di preview sebelah kanan.</p>
                        </div>
                    </div>

                    <!-- Section: CSS Kustom -->
                    <div v-show="activeSection === 'advanced'" class="space-y-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 dark:bg-amber-950/20 dark:border-amber-800 dark:text-amber-300">
                            <strong>⚠️ Mode Lanjutan:</strong> CSS yang Anda tulis akan langsung diterapkan ke preview undangan di sebelah kanan secara real-time.
                        </div>
                        <div class="space-y-1.5">
                            <Label for="custom_css">CSS Styling Tambahan</Label>
                            <textarea
                                id="custom_css"
                                v-model="form.config.custom_css"
                                class="w-full min-h-[280px] rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm font-mono placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-600"
                                placeholder="/* Tulis CSS tambahan */&#10;.mock-text-primary {&#10;  text-transform: uppercase;&#10;  letter-spacing: 2px;&#10;}"
                            />
                        </div>
                    </div>

                    <!-- Next / Prev navigation -->
                    <div v-if="visibleSections.length > 1" class="flex items-center justify-between pt-4 border-t border-border">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-1.5"
                            :disabled="visibleSections.findIndex(s=>s.key===activeSection) === 0"
                            @click="changeSection(visibleSections[visibleSections.findIndex(s=>s.key===activeSection) - 1]?.key)"
                        >
                            <IconComponent name="ArrowLeft" class="h-3.5 w-3.5" />
                            Sebelumnya
                        </Button>
                        <span class="text-xs text-muted-foreground">
                            {{ visibleSections.findIndex(s=>s.key===activeSection) + 1 }} / {{ visibleSections.length }}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-1.5"
                            :disabled="visibleSections.findIndex(s=>s.key===activeSection) === visibleSections.length - 1"
                            @click="changeSection(visibleSections[visibleSections.findIndex(s=>s.key===activeSection) + 1]?.key)"
                        >
                            Selanjutnya
                            <IconComponent name="ArrowRight" class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            </form>

            <!-- ③ Right live preview (sticky) -->
            <aside class="w-[380px] shrink-0 border-l border-border bg-muted/10 flex flex-col overflow-y-auto">
                <div class="px-4 py-3 border-b border-border flex items-center gap-2">
                    <IconComponent name="Eye" class="h-4 w-4 text-emerald-600" />
                    <span class="text-sm font-semibold">Live Preview</span>
                    <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-mono">
                        Real-time
                    </span>
                </div>
                <div class="flex-1 p-6 flex flex-col gap-6 items-center">
                    
                    <!-- iPhone 15 Pro mockup frame -->
                    <div class="relative w-[320px] h-[640px] rounded-[50px] border-[10px] border-slate-900 bg-slate-950 shadow-2xl overflow-hidden flex flex-col shrink-0 select-none">
                        <!-- Dynamic Island -->
                        <div class="absolute top-3 left-1/2 -translate-x-1/2 w-24 h-5 bg-black rounded-full z-50 flex items-center justify-between px-3">
                            <div class="w-2 h-2 rounded-full bg-[#050505] border border-slate-800" />
                            <div class="w-1 h-1 rounded-full bg-[#080808]" />
                        </div>
                           <!-- Realistic Screen Content or Coded Scaffold Preview -->
                        <div v-if="!form.is_custom" class="w-full h-full rounded-[40px] overflow-hidden bg-slate-950 text-slate-100 flex flex-col p-6 pt-12 space-y-4">
                            <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
                                <span class="text-blue-400 font-bold">💻</span>
                                <span class="font-mono text-[9px] uppercase font-bold text-slate-400">Developer Scaffold</span>
                            </div>
                            <div class="flex-1 space-y-4 font-mono text-[9px] leading-relaxed overflow-y-auto">
                                <p class="text-slate-400 select-none">// Sistem akan membuat file boilerplate berikut secara otomatis:</p>
                                
                                <div class="space-y-1.5 bg-black/40 p-3 rounded-xl border border-slate-800">
                                    <div class="text-blue-300 font-semibold flex items-center gap-1.5">
                                        <span>📂</span> Folder Baru
                                    </div>
                                    <div class="text-[8px] text-slate-300 break-all select-all font-mono leading-normal pl-5">
                                        resources/js/Pages/Landing/invitation/templates/{{ form.slug || 'slug-template' }}
                                    </div>
                                </div>

                                <div class="space-y-1.5 bg-black/40 p-3 rounded-xl border border-slate-800">
                                    <div class="text-emerald-300 font-semibold flex items-center gap-1.5">
                                        <span>📄</span> Vue Component Boilerplate
                                    </div>
                                    <div class="text-[8px] text-slate-300 break-all select-all font-mono leading-normal pl-5">
                                        resources/js/Pages/Landing/invitation/templates/{{ form.slug || 'slug-template' }}/{{ form.slug ? pascalName(form.slug) : 'PascalName' }}Template.vue
                                    </div>
                                </div>

                                <div class="space-y-1.5 bg-black/40 p-3 rounded-xl border border-slate-800">
                                    <div class="text-amber-300 font-semibold flex items-center gap-1.5">
                                        <span>🔗</span> Registry Index
                                    </div>
                                    <div class="text-[8px] text-slate-300 break-all select-all font-mono leading-normal pl-5">
                                        resources/js/Pages/Landing/invitation/templates/index.js
                                    </div>
                                </div>

                                <p class="text-slate-500 mt-2 select-none">// Klik 'Simpan' untuk membuat dan membuka Developer Portal.</p>
                            </div>
                        </div>

                        <!-- Realistic Screen Content -->
                        <div v-else class="mock-invitation-container w-full h-full rounded-[40px] overflow-hidden flex flex-col justify-between relative p-4 pt-10">
                            <!-- Overlay lines border -->
                            <div class="absolute inset-3.5 border border-white/10 pointer-events-none rounded-[28px] z-25" />

                            <!-- 1. Step: Info Dasar -->
                            <div v-if="activeSection === 'basic'" class="flex-1 flex flex-col justify-between text-center py-6 animate-in fade-in duration-300 z-10 relative">
                                <div class="space-y-3 pt-6">
                                    <span class="text-[8px] tracking-[0.2em] uppercase text-white/80 bg-black/40 px-2.5 py-0.5 rounded-full">
                                        Wedding Template
                                    </span>
                                    <h4 class="text-xl font-bold mock-text-primary mt-3">
                                        {{ form.name || 'Nama Template' }}
                                    </h4>
                                    <p class="text-[10px] text-white/80 italic max-w-[180px] mx-auto line-clamp-2">
                                        {{ form.description || 'Deskripsi template yang akan digunakan di undangan Anda.' }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <p class="text-[8px] text-white/60 font-semibold uppercase tracking-wider">ID Template / Slug</p>
                                    <p class="font-mono text-[10px] text-white bg-black/55 px-2.5 py-1 rounded-lg inline-block">
                                        {{ form.slug || 'belum-diisi' }}
                                    </p>
                                </div>
                            </div>

                            <!-- 2. Step: Skema Warna -->
                            <div v-else-if="activeSection === 'colors'" class="flex-1 flex flex-col justify-between p-2 animate-in fade-in duration-300 z-10 relative">
                                <div class="space-y-4 pt-4">
                                    <h4 class="text-xs font-bold text-center mock-text-primary uppercase tracking-wide">Skema Warna Pilihan</h4>
                                    <div class="space-y-2">
                                        <div class="p-2.5 rounded-xl border border-border/40 bg-card/85 text-[10px] flex items-center justify-between shadow-sm">
                                            <span class="text-muted-foreground font-medium">Judul Utama (Primer)</span>
                                            <div class="w-5 h-5 rounded-full border border-border shadow-sm" :style="{ backgroundColor: form.config.primary_color }" />
                                        </div>
                                        <div class="p-2.5 rounded-xl border border-border/40 bg-card/85 text-[10px] flex items-center justify-between shadow-sm">
                                            <span class="text-muted-foreground font-medium">Sub-Teks (Sekunder)</span>
                                            <div class="w-5 h-5 rounded-full border border-border shadow-sm" :style="{ backgroundColor: form.config.secondary_color }" />
                                        </div>
                                        <div class="p-2.5 rounded-xl border border-border/40 bg-card/85 text-[10px] flex items-center justify-between shadow-sm">
                                            <span class="text-muted-foreground font-medium">Warna Aksen (Latar)</span>
                                            <div class="w-5 h-5 rounded-full border border-border shadow-sm" :style="{ backgroundColor: form.config.accent_color }" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <button type="button" class="mock-btn-primary w-full py-2 rounded-xl text-[10px] font-bold shadow-md">
                                        Tombol Utama
                                    </button>
                                    <button type="button" class="mock-btn-outline w-full py-2 rounded-xl text-[10px] font-bold bg-transparent">
                                        Tombol Garis Tepi
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Step: Tipografi & Gaya -->
                            <div v-else-if="activeSection === 'typography'" class="flex-1 flex flex-col justify-between p-2 text-center animate-in fade-in duration-300 z-10 relative">
                                <div class="space-y-4 pt-6">
                                    <h4 class="text-[9px] tracking-widest uppercase text-white/70">Pratinjau Font</h4>
                                    <div class="space-y-3 bg-card/95 backdrop-blur-md rounded-2xl p-4 border border-border shadow-sm">
                                        <h5 class="text-lg font-bold mock-text-primary" :style="{ fontFamily: form.config.font_family }">
                                            Reza & Reza
                                        </h5>
                                        <p class="text-[11px] text-muted-foreground leading-relaxed italic" :style="{ fontFamily: form.config.font_family }">
                                            "Menikah adalah ibadah terpanjang dalam menyatukan dua hati dan pikiran."
                                        </p>
                                    </div>
                                </div>
                                <div class="bg-black/45 rounded-xl p-2.5 text-left border border-white/5 space-y-0.5">
                                    <p class="text-[9px] text-white/70">Animasi: <span class="font-bold text-white uppercase">{{ form.config.animation_style }}</span></p>
                                    <p class="text-[9px] text-white/70 truncate">Pattern: <span class="font-mono text-white text-[8px]">{{ form.config.bg_pattern_url || 'None' }}</span></p>
                                </div>
                            </div>

                            <!-- 4. Step: CSS Kustom -->
                            <div v-else class="flex-1 flex flex-col justify-between animate-in fade-in duration-300 z-10 relative">
                                <div class="text-center space-y-1.5 pt-4">
                                    <span class="text-[8px] tracking-[0.2em] uppercase text-white/80 bg-black/40 px-2 py-0.5 rounded-full">
                                        The Wedding
                                    </span>
                                    <h4 class="text-lg font-bold mock-text-primary mt-2">
                                        Reza & Reza
                                    </h4>
                                    <p class="text-[9px] text-white/80">Selasa, 18 Agustus 2026</p>
                                </div>

                                <div class="bg-card/95 backdrop-blur-md rounded-2xl p-3 border border-border/40 space-y-2 shadow-md">
                                    <div class="text-center space-y-0.5">
                                        <h5 class="font-bold text-[10px] mock-text-primary">Akad & Resepsi Pernikahan</h5>
                                        <p class="text-[8px] text-muted-foreground leading-relaxed">
                                            Gedung Pernikahan Agung Indah<br>Jl. Melati Raya No. 45, Jakarta
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" class="mock-btn-primary text-[8px] py-1.5 px-2 rounded-lg font-bold flex-1 flex items-center justify-center gap-1 shadow-sm">
                                            Kalender
                                        </button>
                                        <button type="button" class="mock-btn-outline bg-transparent text-[8px] py-1.5 px-2 rounded-lg font-bold flex-1 flex items-center justify-center gap-1">
                                            Peta
                                        </button>
                                    </div>
                                </div>

                                <div class="text-center pb-2">
                                    <p class="text-[8px] text-white/70 font-medium">Kepada Yth. Tamu Undangan</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Color swatches quick view -->
                    <div v-if="form.is_custom" class="bg-card rounded-xl border border-border p-4 space-y-3 w-full shadow-sm">
                        <p class="text-xs font-bold text-foreground uppercase tracking-wider text-muted-foreground/90">Palette Warna</p>
                        <div class="flex gap-3">
                            <div class="flex-1 text-center">
                                <div class="h-8 rounded-lg border border-border shadow-inner" :style="{ background: form.config.primary_color }" />
                                <p class="text-[9px] text-muted-foreground mt-1.5 font-mono uppercase tracking-tight">{{ form.config.primary_color }}</p>
                                <p class="text-[9px] text-muted-foreground/70 font-semibold">Primer</p>
                            </div>
                            <div class="flex-1 text-center">
                                <div class="h-8 rounded-lg border border-border shadow-inner" :style="{ background: form.config.secondary_color }" />
                                <p class="text-[9px] text-muted-foreground mt-1.5 font-mono uppercase tracking-tight">{{ form.config.secondary_color }}</p>
                                <p class="text-[9px] text-muted-foreground/70 font-semibold">Sekunder</p>
                            </div>
                            <div class="flex-1 text-center">
                                <div class="h-8 rounded-lg border border-border shadow-inner" :style="{ background: form.config.accent_color }" />
                                <p class="text-[9px] text-muted-foreground mt-1.5 font-mono uppercase tracking-tight">{{ form.config.accent_color }}</p>
                                <p class="text-[9px] text-muted-foreground/70 font-semibold">Aksen</p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-border/80 flex items-center justify-between text-[10px]">
                            <span class="text-muted-foreground">Font Utama:</span>
                            <span class="font-bold text-foreground" :style="{ fontFamily: form.config.font_family }">{{ form.config.font_family }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
