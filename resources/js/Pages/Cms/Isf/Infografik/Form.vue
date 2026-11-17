<script setup>
import { computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { activeCategories as CATEGORIES, STATUSES, loadCategories } from '@/Pages/Cms/Isf/options'
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
  DatePicker,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.infografik,
  id: props.id,
  indexHref: '/cms/infografik',
  defaults: {
    slug: '',
    title: '',
    category: 'kebijakan',
    published_at: new Date().toISOString().slice(0, 10),
    image_alt: '',
    image_url: '',
    status: 'draf'
  }
})

let slugTouched = false

function generateSlug(val) {
  return val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '')
}

function onTitleInput(e) {
  // Edit: slug tidak ikut berubah dari judul (URL infografik yang sudah terbit tetap).
  if (isEdit.value) return
  const val = e.target.value
  if (!val || !val.trim()) {
    form.slug = ''
    return
  }
  if (!slugTouched) {
    form.slug = generateSlug(val)
  }
}

function onSlugInput(e) {
  slugTouched = true
  if (!e.target.value || !e.target.value.trim()) {
    slugTouched = false
    form.slug = form.title ? generateSlug(form.title) : ''
  }
}

onMounted(loadCategories)
</script>

<template>
  <Head :title="(isEdit ? 'Edit Infografik' : 'Tambah Infografik') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/infografik">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Infografik' : 'Tambah Infografik Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data infografik #${props.id}` : 'Isi formulir berikut untuk menerbitkan infografik baru.' }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/infografik">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Infografik' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data infografik..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Informasi Infografik</CardTitle>
          <CardDescription class="text-xs">Detail judul visual, kategori, dan tautan gambar infografik.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium">Judul Infografik <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              @input="onTitleInput"
              placeholder="e.g. Distribusi Alokasi Anggaran Pendidikan 2026"
              class="h-9 text-sm"
            />
            <p v-if="err('title')" class="text-xs text-red-500 font-medium">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium">Slug URL <span class="text-red-500">*</span></Label>
              <Input
                id="slug"
                v-model="form.slug"
                @input="onSlugInput"
                placeholder="Dibuat otomatis dari judul..."
                class="h-9 font-mono text-xs"
              />
              <p class="text-[11px] text-muted-foreground">Otomatis dari judul. Bisa diubah manual.</p>
              <p v-if="err('slug')" class="text-xs text-red-500 font-medium">{{ err('slug') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="category" class="text-xs font-medium">Kategori <span class="text-red-500">*</span></Label>
              <Select v-model="form.category">
                <SelectTrigger id="category" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Kategori..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="cat in CATEGORIES"
                    :key="cat.value"
                    :value="cat.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ cat.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="err('category')" class="text-xs text-red-500 font-medium">{{ err('category') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="published_at" class="text-xs font-medium">Tanggal Publikasi <span class="text-red-500">*</span></Label>
              <DatePicker
                id="published_at"
                v-model="form.published_at"
              />
              <p v-if="err('published_at')" class="text-xs text-red-500 font-medium">{{ err('published_at') }}</p>
            </div>

            <StatusSelectWithBadge
              id="status-infografik"
              v-model="form.status"
              label="Status"
              :required="true"
            />
          </div>

          <div class="space-y-1.5">
            <Label class="text-xs font-medium">URL Gambar Infografik</Label>
            <ImageUploadTab
              v-model="form.image_url"
              aspect-class="aspect-[4/3]"
              placeholder="https://..."
              preview-label="Pratinjau Infografik"
            />
            <p v-if="err('image_url')" class="text-xs text-red-500 font-medium">{{ err('image_url') }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="image_alt" class="text-xs font-medium">Deskripsi Gambar (Alt Text) <span class="text-red-500">*</span></Label>
            <Input
              id="image_alt"
              v-model="form.image_alt"
              placeholder="Grafik batang proporsi belanja APBN..."
              class="h-9 text-xs"
            />
            <p v-if="err('image_alt')" class="text-xs text-red-500 font-medium">{{ err('image_alt') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom buttons removed per requirement (top bar only) -->
    </form>
  </div>
</template>
