<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
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
let slugTouched = false

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.kategori,
  id: props.id,
  indexHref: '/cms/kategori',
  defaults: {
    name: '',
    slug: '',
    description: '',
    color: '#ef4444',
    order: 0,
    status: 'terbit'
  }
})

function generateSlug(val) {
  if (!val) return ''
  return val
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)+/g, '')
}

function onNameInput(e) {
  const val = e.target.value
  if (!val || !val.trim()) {
    form.slug = ''
    return
  }
  if (!slugTouched && !isEdit.value) {
    form.slug = generateSlug(val)
  }
}

function onSlugInput() {
  slugTouched = true
}

const colorPresets = [
  { label: 'Merah', hex: '#dc2626' },
  { label: 'Biru', hex: '#2563eb' },
  { label: 'Hijau', hex: '#16a34a' },
  { label: 'Ungu', hex: '#9333ea' },
  { label: 'Oranye', hex: '#ea580c' },
  { label: 'Kuning', hex: '#eab308' },
  { label: 'Teal', hex: '#0d9488' },
  { label: 'Abu-abu', hex: '#64748b' },
]
</script>

<template>
  <Head :title="(isEdit ? 'Edit Kategori' : 'Tambah Kategori') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kategori">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data kategori #${props.id}` : 'Tambahkan kategori baru untuk pengelompokan konten artikel, isu, infografik, dan banner.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kategori">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Kategori' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data kategori..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Kategori</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Nama kategori, slug sistem, deskripsi, dan preferensi warna badge kategori.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Nama Kategori -->
            <div class="space-y-1.5">
              <Label for="name" class="text-xs font-medium text-foreground">Nama Kategori *</Label>
              <Input
                id="name"
                v-model="form.name"
                @input="onNameInput"
                placeholder="Contoh: Politik, Ekonomi, Kebijakan..."
                class="h-9 text-xs font-medium"
              />
              <p v-if="err('name')" class="text-xs text-red-400 mt-1">{{ err('name') }}</p>
            </div>

            <!-- Slug -->
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium text-foreground">Slug Identifikasi *</Label>
              <Input
                id="slug"
                v-model="form.slug"
                @input="onSlugInput"
                placeholder="Contoh: politik, ekonomi, kebijakan..."
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">
                Pengenal unik kategori dalam sistem (huruf kecil dan tanda hubung).
              </p>
              <p v-if="err('slug')" class="text-xs text-red-400 mt-1">{{ err('slug') }}</p>
            </div>
          </div>

          <!-- Deskripsi -->
          <div class="space-y-1.5">
            <Label for="description" class="text-xs font-medium text-foreground">Deskripsi Kategori (Opsional)</Label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              placeholder="Jelaskan cakupan topik atau fokus dari kategori ini..."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
            <p v-if="err('description')" class="text-xs text-red-400 mt-1">{{ err('description') }}</p>
          </div>

          <!-- Warna Badge & Urutan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Warna -->
            <div class="space-y-1.5">
              <Label class="text-xs font-medium text-foreground">Warna Badge Kategori</Label>
              <div class="flex items-center gap-2">
                <input
                  type="color"
                  v-model="form.color"
                  class="w-9 h-9 p-0.5 rounded border border-border bg-background cursor-pointer"
                />
                <Input
                  v-model="form.color"
                  placeholder="#dc2626"
                  class="h-9 text-xs font-mono w-28"
                />
                <div class="flex gap-1 overflow-x-auto flex-1">
                  <button
                    v-for="p in colorPresets"
                    :key="p.hex"
                    type="button"
                    @click="form.color = p.hex"
                    :title="p.label"
                    class="w-5 h-5 rounded-full border border-border/80 transition-transform hover:scale-110"
                    :style="{ backgroundColor: p.hex }"
                  ></button>
                </div>
              </div>
              <p v-if="err('color')" class="text-xs text-red-400 mt-1">{{ err('color') }}</p>
            </div>

            <!-- Urutan -->
            <div class="space-y-1.5">
              <Label for="order" class="text-xs font-medium text-foreground">Urutan Tampil (Order)</Label>
              <Input
                id="order"
                type="number"
                min="0"
                max="999"
                v-model.number="form.order"
                class="h-9 text-xs w-full"
              />
              <p class="text-[11px] text-muted-foreground">
                Urutan dalam menu dan pilihan dropdown (dimulai dari angka terkecil).
              </p>
              <p v-if="err('order')" class="text-xs text-red-400 mt-1">{{ err('order') }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Status Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Status Publikasi</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Hanya kategori berstatus "Terbit" yang muncul pada pilihan publik dan modul CMS lainnya.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="max-w-xs">
            <StatusSelectWithBadge
              id="status-kategori"
              v-model="form.status"
              label="Status Kategori"
              :required="true"
            />
            <p v-if="err('status')" class="text-xs text-red-400 mt-1">{{ err('status') }}</p>
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>
