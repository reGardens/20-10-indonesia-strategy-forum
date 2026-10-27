<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { activeCategories as CATEGORIES, loadCategories } from '@/Pages/Cms/Isf/options'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
import StatusSelectWithBadge from '@/Components/Cms/StatusSelectWithBadge.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const targetType = ref('/isu')
const targetPath = ref('')

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.banner,
  id: props.id,
  indexHref: '/cms/banner',
  defaults: {
    kicker: 'Sorotan',
    title: '',
    to: '',
    image_url: '',
    image_label: '',
    order: 0,
    status: 'draf'
  }
})

watch(() => form.to, (val) => {
  if (!val) {
    targetType.value = '/isu'
    targetPath.value = ''
    return
  }
  if (val.startsWith('/isu/')) {
    targetType.value = '/isu'
    targetPath.value = val.replace(/^\/isu\//, '')
  } else if (val.startsWith('/tokoh/')) {
    targetType.value = '/tokoh'
    targetPath.value = val.replace(/^\/tokoh\//, '')
  } else if (val.startsWith('/artikel/')) {
    targetType.value = '/artikel'
    targetPath.value = val.replace(/^\/artikel\//, '')
  } else if (val.startsWith('/infografik')) {
    targetType.value = '/infografik'
    targetPath.value = val.replace(/^\/infografik\/?/, '')
  } else if (val.startsWith('http://') || val.startsWith('https://')) {
    targetType.value = 'external'
    targetPath.value = val
  } else {
    targetType.value = 'custom'
    targetPath.value = val
  }
}, { immediate: true })

function updateFormTo() {
  if (!targetPath.value || !targetPath.value.trim()) {
    if (targetType.value === '/infografik') {
      form.to = '/infografik'
    } else {
      form.to = ''
    }
    return
  }
  const cleanPath = targetPath.value.trim().replace(/^\/+/, '')
  if (targetType.value === '/isu') {
    form.to = `/isu/${cleanPath}`
  } else if (targetType.value === '/tokoh') {
    form.to = `/tokoh/${cleanPath}`
  } else if (targetType.value === '/artikel') {
    form.to = `/artikel/${cleanPath}`
  } else if (targetType.value === '/infografik') {
    form.to = cleanPath ? `/infografik/${cleanPath}` : '/infografik'
  } else if (targetType.value === 'external') {
    form.to = targetPath.value.trim().startsWith('http') ? targetPath.value.trim() : `https://${cleanPath}`
  } else {
    form.to = targetPath.value.trim()
  }
}

onMounted(loadCategories)
</script>

<template>
  <Head :title="(isEdit ? 'Edit Banner Sorotan' : 'Tambah Banner Baru') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/banner">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Banner Sorotan' : 'Tambah Banner Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data banner #${props.id}` : 'Tambahkan slide baru untuk carousel banner sorotan Beranda.' }}
          </p>
        </div>
      </div>
      <!-- TOP action buttons only -->
      <div class="flex items-center gap-2">
        <Link href="/cms/banner">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Banner' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data banner..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Banner</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Kategori sorotan, nama banner untuk identifikasi di CMS serta tautan tujuan saat banner diklik.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Kategori / Kicker -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label for="kicker" class="text-xs font-medium text-foreground">Kategori / Label Sorotan (Kicker)</Label>
              <div class="flex gap-1 overflow-x-auto">
                <button
                  v-for="cat in CATEGORIES"
                  :key="cat.value"
                  type="button"
                  @click="form.kicker = cat.label || cat.name"
                  class="text-[10px] px-1.5 py-0.5 rounded border border-border bg-muted/40 hover:bg-muted text-muted-foreground hover:text-foreground transition"
                >
                  {{ cat.label || cat.name }}
                </button>
              </div>
            </div>
            <Input
              id="kicker"
              v-model="form.kicker"
              placeholder="Contoh: Sorotan, Anatomi Isu, Tokoh Pekan Ini, Politik..."
              class="h-9 text-xs font-medium"
            />
            <p v-if="err('kicker')" class="text-xs text-red-400 mt-1">{{ err('kicker') }}</p>
          </div>

          <!-- Title / Banner Name -->
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium text-foreground">Nama / Judul Banner</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="Masukkan nama banner untuk identifikasi..."
              class="h-9 text-xs font-medium"
            />
            <p v-if="err('title')" class="text-xs text-red-400 mt-1">{{ err('title') }}</p>
          </div>

          <!-- Tautan Tujuan Banner -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label for="target-path" class="text-xs font-medium text-foreground">Tautan Tujuan Banner (Opsional)</Label>
              <span class="text-[11px] text-muted-foreground">Pilih kategori / jenis tujuan di depan</span>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
              <div class="relative w-full sm:w-44 shrink-0">
                <select
                  v-model="targetType"
                  @change="updateFormTo"
                  class="w-full h-9 rounded-md border border-input bg-background pl-3 pr-8 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring appearance-none font-medium cursor-pointer"
                >
                  <option value="/isu">/isu (Isu Mingguan)</option>
                  <option value="/tokoh">/tokoh (Profil Tokoh)</option>
                  <option value="/artikel">/artikel (Artikel)</option>
                  <option value="/infografik">/infografik (Infografik)</option>
                  <option value="external">https:// (Tautan Luar)</option>
                  <option value="custom">Kustom (Lainnya)</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-muted-foreground">
                  <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              <div class="flex-1 relative flex items-center">
                <span
                  v-if="['/isu', '/tokoh', '/artikel', '/infografik'].includes(targetType)"
                  class="absolute left-3 text-xs font-mono text-muted-foreground/80 pointer-events-none select-none font-medium"
                >
                  {{ targetType }}/
                </span>
                <Input
                  id="target-path"
                  v-model="targetPath"
                  @input="updateFormTo"
                  :placeholder="targetType === '/isu' ? 'kuhp-baru-pelaku-usaha' : (targetType === '/tokoh' ? 'budi-santoso' : (targetType === 'external' ? 'https://example.com' : 'Masukkan slug atau path...'))"
                  :class="[
                    'h-9 text-xs font-mono w-full',
                    ['/isu', '/tokoh', '/artikel', '/infografik'].includes(targetType) ? 'pl-20 sm:pl-20' : 'pl-3'
                  ]"
                />
              </div>
            </div>
            <p class="text-[11px] text-muted-foreground">
              Ketika pengunjung mengklik gambar banner di Beranda, browser akan menuju tautan ini. Kosongkan jika banner hanya untuk gambar pajangan.
            </p>
            <p v-if="err('to')" class="text-xs text-red-400 mt-1">{{ err('to') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Gambar & SEO Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Gambar & Optimasi SEO</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Visual banner (disarankan rasio 21:9 atau 16:9), metadata SEO gambar, urutan slide, dan status publikasi.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Image URL — tabbed URL/Upload with preview -->
          <div class="space-y-1.5">
            <Label class="text-xs font-medium text-foreground">URL Gambar Banner</Label>
            <ImageUploadTab
              v-model="form.image_url"
              aspect-class="aspect-[21/9]"
              placeholder="https://images.unsplash.com/... atau /images/..."
            />
            <p v-if="err('image_url')" class="text-xs text-red-400 mt-1">{{ err('image_url') }}</p>
          </div>

          <!-- Alt Text / SEO -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label for="image_label" class="text-xs font-medium text-foreground">Alt Text Gambar & SEO Title</Label>
              <span class="text-[10px] bg-red-600/20 text-red-400 border border-red-500/30 px-1.5 py-0.5 rounded font-mono">SEO Friendly</span>
            </div>
            <Input
              id="image_label"
              v-model="form.image_label"
              placeholder="Contoh: Banner Sorotan Analisis Regulasi dan Bisnis 2026..."
              class="h-9 text-xs"
            />
            <p class="text-[11px] text-muted-foreground">
              Deskripsi gambar untuk mesin pencari (Google Images) dan aksesibilitas screen reader.
            </p>
            <p v-if="err('image_label')" class="text-xs text-red-400 mt-1">{{ err('image_label') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Urutan -->
            <div class="space-y-1.5">
              <Label for="order" class="text-xs font-medium text-foreground">Urutan Rotasi (Order)</Label>
              <Input
                id="order"
                type="number"
                min="0"
                max="999"
                v-model.number="form.order"
                class="h-9 text-xs w-full"
              />
              <p class="text-[11px] text-muted-foreground">
                Urutan slide dalam carousel (dimulai dari angka terkecil, misal: 1, 2, 3...).
              </p>
              <p v-if="err('order')" class="text-xs text-red-400 mt-1">{{ err('order') }}</p>
            </div>

            <!-- Status with badge -->
            <StatusSelectWithBadge
              id="status-banner"
              v-model="form.status"
              label="Status Publikasi"
              :required="true"
            />
          </div>
        </CardContent>
      </Card>
      <!-- No bottom buttons per requirement -->
    </form>
  </div>
</template>
