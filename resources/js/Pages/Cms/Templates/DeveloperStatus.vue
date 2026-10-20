<script setup>
import { Head, Link } from "@inertiajs/vue3";
import CmsLayout from "@/Layouts/CmsLayout.vue";
import { Button } from "@/Components/UI";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

defineOptions({ layout: CmsLayout });

const props = defineProps({
    template: { type: Object, required: true },
    developer_info: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Developer Portal: ${template.name}`" />

    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center gap-4 px-6 py-4 border-b border-border bg-card/50">
            <Link href="/cms/templates">
                <Button variant="outline" size="sm" class="gap-1.5">
                    <IconComponent name="ArrowLeft" class="h-3.5 w-3.5" />
                    Kembali ke Katalog
                </Button>
            </Link>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold tracking-tight truncate">Code Theme: {{ template.name }}</h1>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 font-semibold font-mono">
                        Developed Template
                    </span>
                </div>
                <p class="text-xs text-muted-foreground mt-0.5">Pantau dan kelola boilerplate component untuk template berkode murni ini.</p>
            </div>
        </div>

        <!-- Body -->
        <div class="flex flex-1 min-h-0 overflow-hidden">
            <!-- Left Info Panel -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                
                <!-- Status Checklist -->
                <div class="bg-card border border-border/80 rounded-2xl p-6 space-y-4 shadow-sm">
                    <h2 class="text-base font-bold flex items-center gap-2">
                        <IconComponent name="Checklist" class="h-5 w-5 text-blue-600" />
                        Component Status Checklist
                    </h2>

                    <div class="grid gap-3">
                        <div class="flex items-center justify-between p-3 rounded-xl border border-border/60 bg-muted/5 text-sm">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1 rounded-lg" :class="developer_info.file_exists ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400'">
                                    <IconComponent :name="developer_info.file_exists ? 'Check' : 'X'" class="h-4.5 w-4.5 stroke-[2.5]" />
                                </div>
                                <div class="font-medium text-foreground">Vue Template Component File</div>
                            </div>
                            <span class="text-xs font-mono text-muted-foreground">{{ developer_info.file_exists ? 'Terdeteksi' : 'Tidak Ditemukan' }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl border border-border/60 bg-muted/5 text-sm">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1 rounded-lg" :class="developer_info.registry_registered ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400'">
                                    <IconComponent :name="developer_info.registry_registered ? 'Check' : 'X'" class="h-4.5 w-4.5 stroke-[2.5]" />
                                </div>
                                <div class="font-medium text-foreground">Terdaftar di index.js</div>
                            </div>
                            <span class="text-xs font-mono text-muted-foreground">{{ developer_info.registry_registered ? 'Terdaftar' : 'Belum Terdaftar' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Paths and Paths Copy -->
                <div class="bg-card border border-border/80 rounded-2xl p-6 space-y-4 shadow-sm">
                    <h2 class="text-base font-bold flex items-center gap-2">
                        <IconComponent name="Folder" class="h-5 w-5 text-blue-600" />
                        Lokasi File Proyek
                    </h2>

                    <div class="space-y-3.5 text-sm">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-muted-foreground">Path Component Vue:</span>
                            <div class="flex items-center gap-2 bg-muted/30 p-2.5 rounded-xl border border-border/60 font-mono text-xs text-foreground select-all">
                                {{ developer_info.file_path }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-muted-foreground">Path Registry Index:</span>
                            <div class="flex items-center gap-2 bg-muted/30 p-2.5 rounded-xl border border-border/60 font-mono text-xs text-foreground select-all">
                                resources/js/Pages/Landing/invitation/templates/index.js
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Developer Instructions -->
                <div class="bg-card border border-border/80 rounded-2xl p-6 space-y-4 shadow-sm">
                    <h2 class="text-base font-bold flex items-center gap-2">
                        <IconComponent name="Book" class="h-5 w-5 text-blue-600" />
                        Petunjuk Pengembangan (Developer Guide)
                    </h2>

                    <div class="text-sm leading-relaxed text-muted-foreground space-y-3">
                        <p>
                            Guna memodifikasi tampilan template ini secara penuh, silakan ikuti petunjuk berikut:
                        </p>
                        <ol class="list-decimal pl-5 space-y-2">
                            <li>
                                Buka IDE Anda (VS Code, PhpStorm, dll), lalu arahkan ke file 
                                <code class="font-mono text-xs bg-muted/60 px-1 py-0.5 rounded text-foreground">{{ developer_info.file_path }}</code>.
                            </li>
                            <li>
                                Edit layout HTML & styling Tailwind CSS di dalam template sesuai konsep tema yang dirancang.
                            </li>
                            <li>
                                Gunakan properti standard props yang dikirim dari sistem ke component:
                                <ul class="list-disc pl-5 mt-1 text-xs space-y-0.5">
                                    <li><code class="font-mono bg-muted/60 px-1 py-0.5 rounded text-foreground">couple</code>: Data pengantin (groom, bride, cerita cinta, dll)</li>
                                    <li><code class="font-mono bg-muted/60 px-1 py-0.5 rounded text-foreground">events</code>: Data akad, resepsi, rundown, lokasi</li>
                                    <li><code class="font-mono bg-muted/60 px-1 py-0.5 rounded text-foreground">settings</code>: Pengaturan SEO & konfigurasi undangan</li>
                                    <li><code class="font-mono bg-muted/60 px-1 py-0.5 rounded text-foreground">media</code>: Foto galeri & berkas musik</li>
                                </ul>
                            </li>
                            <li>
                                Pastikan server development <code>npm run dev</code> sedang berjalan agar perubahan langsung ter-compile di preview sebelah kanan.
                            </li>
                        </ol>
                    </div>
                </div>

            </div>

            <!-- Right Preview Panel (Mockup iPhone with iframe) -->
            <aside class="w-[380px] shrink-0 border-l border-border bg-muted/10 flex flex-col overflow-y-auto">
                <div class="px-4 py-3 border-b border-border flex items-center gap-2">
                    <IconComponent name="Eye" class="h-4 w-4 text-blue-600" />
                    <span class="text-sm font-semibold">Live Theme Output</span>
                    <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-mono">
                        Active Compile
                    </span>
                </div>
                <div class="flex-1 p-6 flex flex-col items-center justify-center">
                    
                    <!-- iPhone 15 Pro mockup frame -->
                    <div class="relative w-[320px] h-[640px] rounded-[50px] border-[10px] border-slate-900 bg-slate-950 shadow-2xl overflow-hidden flex flex-col shrink-0">
                        <!-- Dynamic Island -->
                        <div class="absolute top-3 left-1/2 -translate-x-1/2 w-24 h-5 bg-black rounded-full z-50 flex items-center justify-between px-3 pointer-events-none">
                            <div class="w-2 h-2 rounded-full bg-[#050505] border border-slate-800" />
                            <div class="w-1 h-1 rounded-full bg-[#080808]" />
                        </div>
                        
                        <!-- Screen Iframe -->
                        <div class="w-full h-full rounded-[40px] overflow-hidden bg-white">
                            <iframe
                                v-if="developer_info.file_exists && developer_info.registry_registered"
                                :src="`/wedding/demo-${template.slug}`"
                                class="w-full h-full border-0 select-none pt-2"
                                style="pointer-events: auto;"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center p-6 text-center bg-slate-50 text-slate-500">
                                <span class="text-3xl mb-2">⚠️</span>
                                <h4 class="font-bold text-xs text-slate-800">Boilerplate Tidak Siap</h4>
                                <p class="text-[10px] text-slate-400 mt-1 leading-normal">Pastikan file Vue template dibuat dan teregistrasi di index.js.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </aside>
        </div>
    </div>
</template>
