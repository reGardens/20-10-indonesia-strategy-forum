<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    /**
     * Display a list of custom templates.
     */
    public function index(): Response
    {
        $readyTemplates = CustomTemplate::where('is_custom', true)->orderBy('name', 'asc')->get()->map(function ($t) {
            return [
                'id' => $t->slug,
                'db_id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'thumbnail_url' => $t->thumbnail_url ?: '/templates/default-custom.png',
                'is_custom' => true,
                'config' => $t->config,
            ];
        })->toArray();

        $ongoingTemplates = CustomTemplate::where('is_custom', false)->orderBy('name', 'asc')->get()->map(function ($t) {
            return [
                'id' => $t->slug,
                'db_id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'thumbnail_url' => $t->thumbnail_url ?: '/templates/default-custom.png',
                'is_custom' => false,
                'config' => $t->config,
            ];
        })->toArray();

        return Inertia::render('Cms/Templates/Index', [
            'readyTemplates' => $readyTemplates,
            'ongoingTemplates' => $ongoingTemplates,
        ]);
    }

    /**
     * Create a template instantly with a unique developer ID and redirect to editor.
     */
    public function quickCreate()
    {
        $uniqueId = 'tmpl_' . bin2hex(random_bytes(6)); // e.g. tmpl_2f89c4

        $template = CustomTemplate::create([
            'name' => 'Template Baru (' . $uniqueId . ')',
            'slug' => $uniqueId,
            'description' => 'Template baru dibuat untuk design kolaboratif.',
            'thumbnail_url' => '',
            'is_custom' => true,
            'config' => [
                'primary_color' => '#1b4332',
                'secondary_color' => '#40916c',
                'accent_color' => '#d8f3dc',
                'font_family' => 'Playfair Display',
                'animation_style' => 'fade-up',
                'bg_pattern_url' => '',
                'custom_css' => ''
            ]
        ]);

        return redirect()->route('cms.templates.edit', $template->id)
            ->with('success', 'Template kolaboratif berhasil dibuat dengan ID: ' . $uniqueId);
    }

    /**
     * Show the form for creating a new custom template.
     */
    public function create(): Response
    {
        return Inertia::render('Cms/Templates/Create');
    }

    /**
     * Store a newly created custom template in the database.
     */
    public function store(Request $request)
    {
        $isCustom = filter_var($request->input('is_custom', true), FILTER_VALIDATE_BOOLEAN);

        if (!$isCustom) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('custom_templates', 'slug')],
                'description' => ['nullable', 'string'],
            ]);

            $name = $validated['name'];
            $slug = $validated['slug'];
            $description = $validated['description'] ?? '';

            // Generate component name: beta-modern -> BetaModernTemplate
            $pascalName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
            $componentName = "{$pascalName}Template";

            // 1. Create directory in resources/js/Pages/Landing/invitation/templates/{slug}
            $dirPath = resource_path("js/Pages/Landing/invitation/templates/{$slug}");
            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0755, true);
            }

            // 2. Create the Vue component template
            $vueBoilerplate = $this->getVueBoilerplate($pascalName);
            file_put_contents("{$dirPath}/{$componentName}.vue", $vueBoilerplate);

            // 3. Register in index.js
            $indexPath = resource_path("js/Pages/Landing/invitation/templates/index.js");
            if (file_exists($indexPath)) {
                $indexContent = file_get_contents($indexPath);
                $pos = strpos($indexContent, '// [REGISTER_TEMPLATES]');
                if ($pos !== false) {
                    $registryEntry = "  {\n" .
                                     "    id: '{$slug}',\n" .
                                     "    name: '{$name}',\n" .
                                     "    description: '{$description}',\n" .
                                     "    thumbnail: '/templates/{$slug}/thumbnail.webp',\n" .
                                     "    component: () => import('./{$slug}/{$componentName}.vue'),\n" .
                                     "    defaultConfig: {\n" .
                                     "      primaryColor: '#1B3A5C',\n" .
                                     "      secondaryColor: '#2E6B9E',\n" .
                                     "      accentColor: '#C9A84C',\n" .
                                     "      fontFamily: 'Playfair Display',\n" .
                                     "      galleryLayout: 'grid',\n" .
                                     "      animationStyle: 'fade-up',\n" .
                                     "    },\n" .
                                     "  },\n";
                    
                    $before = substr($indexContent, 0, $pos);
                    $after = substr($indexContent, $pos);
                    $newIndexContent = $before . $registryEntry . "  " . $after;
                    file_put_contents($indexPath, $newIndexContent);
                }
            }

            // 4. Save to database with is_custom = false
            $template = CustomTemplate::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'thumbnail_url' => "/templates/{$slug}/thumbnail.webp",
                'is_custom' => false,
                'config' => [
                    'primary_color' => '#1B3A5C',
                    'secondary_color' => '#2E6B9E',
                    'accent_color' => '#C9A84C',
                    'font_family' => 'Playfair Display',
                    'animation_style' => 'fade-up',
                    'bg_pattern_url' => '',
                    'custom_css' => ''
                ]
            ]);

            return redirect()->route('cms.templates.developer-status', $template->id)
                ->with('success', 'Boilerplate template kustom developer berhasil di-scaffold!');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('custom_templates', 'slug')],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'string'],
            'config' => ['required', 'array'],
            'config.primary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'config.secondary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'config.accent_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'config.font_family' => ['required', 'string'],
            'config.animation_style' => ['required', 'string'],
            'config.bg_pattern_url' => ['nullable', 'string'],
            'config.custom_css' => ['nullable', 'string'],
        ], [
            'config.primary_color.regex' => 'Format warna primer tidak valid (contoh: #ff0000)',
            'config.secondary_color.regex' => 'Format warna sekunder tidak valid (contoh: #ff0000)',
            'config.accent_color.regex' => 'Format warna aksen tidak valid (contoh: #ff0000)',
        ]);

        CustomTemplate::create(array_merge($validated, ['is_custom' => true]));

        return redirect()->route('cms.templates.index')
            ->with('success', 'Template kustom berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified custom template.
     */
    public function edit(CustomTemplate $template): Response
    {
        return Inertia::render('Cms/Templates/Edit', [
            'template' => $template,
        ]);
    }

    /**
     * Update the specified custom template in the database.
     */
    public function update(Request $request, CustomTemplate $template)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('custom_templates', 'slug')->ignore($template->id)],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'string'],
            'config' => ['required', 'array'],
            'config.primary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'config.secondary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'config.accent_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'config.font_family' => ['required', 'string'],
            'config.animation_style' => ['required', 'string'],
            'config.bg_pattern_url' => ['nullable', 'string'],
            'config.custom_css' => ['nullable', 'string'],
        ], [
            'config.primary_color.regex' => 'Format warna primer tidak valid (contoh: #ff0000)',
            'config.secondary_color.regex' => 'Format warna sekunder tidak valid (contoh: #ff0000)',
            'config.accent_color.regex' => 'Format warna aksen tidak valid (contoh: #ff0000)',
        ]);

        $template->update($validated);

        return redirect()->route('cms.templates.index')
            ->with('success', 'Template kustom berhasil diperbarui.');
    }

    /**
     * Delete the specified custom template from the database.
     */
    public function destroy(CustomTemplate $template)
    {
        $template->delete();

        return redirect()->route('cms.templates.index')
            ->with('success', 'Template kustom berhasil dihapus.');
    }

    /**
     * Show status developer portal for coded templates.
     */
    public function developerStatus(CustomTemplate $template): Response
    {
        $pascalName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $template->slug)));
        $componentName = "{$pascalName}Template";
        $relativePath = "resources/js/Pages/Landing/invitation/templates/{$template->slug}/{$componentName}.vue";
        $absolutePath = resource_path("js/Pages/Landing/invitation/templates/{$template->slug}/{$componentName}.vue");
        
        $file_exists = file_exists($absolutePath);
        
        $indexPath = resource_path("js/Pages/Landing/invitation/templates/index.js");
        $registry_registered = false;
        if (file_exists($indexPath)) {
            $indexContent = file_get_contents($indexPath);
            if (str_contains($indexContent, "'{$template->slug}'") || str_contains($indexContent, "\"{$template->slug}\"")) {
                $registry_registered = true;
            }
        }

        return Inertia::render('Cms/Templates/DeveloperStatus', [
            'template' => $template,
            'developer_info' => [
                'component_name' => $componentName,
                'file_path' => $relativePath,
                'file_exists' => $file_exists,
                'registry_registered' => $registry_registered,
                'absolute_path' => $absolutePath
            ]
        ]);
    }

    /**
     * Release a developer coded template to be ready.
     */
    public function release(CustomTemplate $template)
    {
        $template->update(['is_custom' => true]);

        return redirect()->route('cms.templates.index')
            ->with('success', "Template '{$template->name}' berhasil dirilis ke Ready!");
    }

    /**
     * Helper to get Vue Boilerplate Template code.
     */
    private function getVueBoilerplate($pascalName): string
    {
        return <<<VUE
<template>
  <div class="invitation-container min-h-screen text-slate-800" :style="customStyles">
    <!-- Cover Section -->
    <section class="min-h-screen flex flex-col justify-between items-center p-6 text-center bg-slate-50 relative animate-in fade-in duration-500">
      <div class="pt-20 space-y-4">
        <span class="text-xs uppercase tracking-widest text-slate-500">The Wedding of</span>
        <h1 class="text-4xl md:text-6xl font-serif text-slate-900" :style="{ fontFamily: settings?.seoMeta?.fontFamily }">
          {{ couple?.groom?.nickname || 'Groom' }} & {{ couple?.bride?.nickname || 'Bride' }}
        </h1>
        <p class="text-sm text-slate-600">Kami Mengundang Anda ke Acara Pernikahan Kami</p>
      </div>

      <div class="pb-10 space-y-2">
        <p class="text-xs text-slate-500">Kepada Yth. Bapak/Ibu/Saudara/i</p>
        <div class="bg-white/80 backdrop-blur-sm px-6 py-2.5 rounded-lg border shadow-sm font-semibold">
          {{ guestName || 'Nama Tamu Undangan' }}
        </div>
        <button @click="openInvitation" class="mt-4 bg-slate-900 text-white text-xs px-6 py-2.5 rounded-full font-bold uppercase tracking-wider hover:bg-slate-800 transition">
          Buka Undangan
        </button>
      </div>
    </section>

    <!-- Content Sections (Only visible after open) -->
    <div v-if="isOpen" class="animate-fade-in">
      <!-- Couple Details -->
      <section class="py-20 px-6 text-center space-y-8 bg-white">
        <h2 class="text-2xl font-serif">Mempelai</h2>
        <div class="grid md:grid-cols-2 gap-10 max-w-4xl mx-auto">
          <div class="space-y-3">
            <h3 class="text-xl font-bold">{{ couple?.groom?.fullName || 'Nama Lengkap Groom' }}</h3>
            <p class="text-xs text-slate-500">Putra dari Ayah {{ couple?.groom?.fatherName || '...' }} & Ibu {{ couple?.groom?.motherName || '...' }}</p>
          </div>
          <div class="space-y-3">
            <h3 class="text-xl font-bold">{{ couple?.bride?.fullName || 'Nama Lengkap Bride' }}</h3>
            <p class="text-xs text-slate-500">Putri dari Ayah {{ couple?.bride?.fatherName || '...' }} & Ibu {{ couple?.bride?.motherName || '...' }}</p>
          </div>
        </div>
      </section>

      <!-- Events Details -->
      <section class="py-20 px-6 bg-slate-50 text-center space-y-8">
        <h2 class="text-2xl font-serif">Acara</h2>
        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
          <div v-for="event in events" :key="event.id" class="bg-white p-6 rounded-xl border shadow-sm space-y-4">
            <h3 class="text-lg font-bold text-slate-900">{{ event.name }}</h3>
            <div class="text-sm text-slate-600 space-y-1">
              <p>📅 {{ event.date }}</p>
              <p>⏰ {{ event.startTime }} - {{ event.endTime || 'Selesai' }}</p>
              <p>📍 {{ event.location }}</p>
              <p class="text-xs mt-2 italic text-slate-500">{{ event.address }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Wishes & RSVP -->
      <section class="py-20 px-6 bg-white text-center space-y-8 max-w-2xl mx-auto">
        <h2 class="text-2xl font-serif">RSVP & Ucapan</h2>
        <p class="text-sm text-slate-600">Mohon konfirmasi kehadiran Anda melalui formulir di bawah ini.</p>
        <div class="p-6 border rounded-xl bg-slate-50/50 space-y-2">
          <p class="text-xs text-slate-400">RSVP & Wishes Forms are managed dynamically by the system.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  couple: { type: Object, default: () => ({}) },
  events: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) },
  media: { type: Array, default: () => [] },
  slug: { type: String, default: "" },
  templateRecord: { type: Object, default: null }
});

const isOpen = ref(false);
const guestName = ref("");

// Read guest name from URL query parameter
const urlParams = new URLSearchParams(window.location.search);
guestName.value = urlParams.get("to") || "";

function openInvitation() {
  isOpen.value = true;
  // Scroll to content
  setTimeout(() => {
    window.scrollTo({
      top: window.innerHeight,
      behavior: "smooth"
    });
  }, 100);
}

// Compute theme colors dynamically
const customStyles = computed(() => {
  const cfg = props.templateRecord?.config || {};
  return {
    '--primary-color': cfg.primary_color || '#1B3A5C',
    '--secondary-color': cfg.secondary_color || '#2E6B9E',
    '--accent-color': cfg.accent_color || '#C9A84C',
  };
});
</script>

<style scoped>
.invitation-container {
  min-height: 100vh;
}
.animate-fade-in {
  animation: fadeIn 1s ease-in-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
VUE;
    }
}
