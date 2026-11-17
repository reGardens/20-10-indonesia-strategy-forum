<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
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
  CardContent,
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const defaultKickerOptions = [
  'Sorotan',
  'Politik',
  'Kebijakan Publik',
  'Hukum',
  'Tokoh Pekan Ini',
  'Riset Khusus',
  'Opini Publik',
  'Survei',
  'Pengumuman',
  'Agenda'
]

const kickerOptions = computed(() => {
  const list = [...defaultKickerOptions]
  if (form.kicker && !list.includes(form.kicker)) {
    list.unshift(form.kicker)
  }
  return list.map(item => ({ value: item, label: item }))
})

const targetType = ref('/tokoh')
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
    targetType.value = '/tokoh'
    targetPath.value = ''
    return
  }
  if (val.startsWith('/tokoh/')) {
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
  if (targetType.value === '/tokoh') {
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

onMounted(async () => {
  if (!isEdit.value) {
    try {
      const res = await cmsResources.banner.list({ per_page: 100 })
      const list = res?.data || []
      const maxOrder = list.reduce((max, it) => Math.max(max, Number(it.order) || 0), 0)
      form.order = maxOrder + 1
    } catch {
      // fallback
    }
  }
})
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
      <!-- Informasi Banner Card -->
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
            <Label for="kicker" class="text-xs font-medium text-foreground">Kategori / Label Sorotan (Kicker)</Label>
            <Select v-model="form.kicker">
              <SelectTrigger id="kicker" class="h-9 text-xs font-medium">
                <SelectValue placeholder="Pilih Kategori / Label Sorotan..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="opt in kickerOptions"
                  :key="opt.value"
                  :value="opt.value"
                  class="text-xs font-medium"
                >
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
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
              <div class="w-full sm:w-48 shrink-0">
                <Select :model-value="targetType" @update:model-value="targetType = $event; updateFormTo()">
                  <SelectTrigger class="h-9 text-xs font-medium bg-background border-input">
                    <SelectValue placeholder="Pilih Jenis Tautan" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="/tokoh" class="text-xs font-medium">/tokoh (Profil Tokoh)</SelectItem>
                    <SelectItem value="/artikel" class="text-xs font-medium">/artikel (Artikel)</SelectItem>
                    <SelectItem value="/infografik" class="text-xs font-medium">/infografik (Infografik)</SelectItem>
                    <SelectItem value="external" class="text-xs font-medium">https:// (Tautan Luar)</SelectItem>
                    <SelectItem value="custom" class="text-xs font-medium">Kustom (Lainnya)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="flex-1 relative flex items-center">
                <span
                  v-if="['/tokoh', '/artikel', '/infografik'].includes(targetType)"
                  class="absolute left-3 text-xs font-mono text-muted-foreground/80 pointer-events-none select-none font-medium"
                >
                  {{ targetType }}/
                </span>
                <Input
                  id="target-path"
                  v-model="targetPath"
                  @input="updateFormTo"
                  :placeholder="targetType === '/tokoh' ? 'budi-santoso' : (targetType === 'external' ? 'https://example.com' : 'Masukkan slug atau path...')"
                  :class="[
                    'h-9 text-xs font-mono w-full',
                    ['/tokoh', '/artikel', '/infografik'].includes(targetType) ? 'pl-20 sm:pl-20' : 'pl-3'
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
            <p class="text-[11px] text-muted-foreground">
              Masukkan URL gambar eksternal atau beralih ke tab Pratinjau Tampilan untuk mengunggah file.
            </p>
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
              <div class="flex items-center justify-between">
                <Label for="order" class="text-xs font-medium text-foreground">Urutan Rotasi (Order)</Label>
                <span v-if="!isEdit" class="text-[11px] text-muted-foreground font-normal">
                  (Otomatis nomor berikutnya)
                </span>
              </div>
              <Input
                id="order"
                type="number"
                min="1"
                max="999"
                v-model.number="form.order"
                class="h-9 text-xs w-full font-mono"
              />
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
