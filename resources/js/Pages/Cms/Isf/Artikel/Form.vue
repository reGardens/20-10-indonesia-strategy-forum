<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { CATEGORIES, STATUSES } from '@/Pages/Cms/Isf/options'
import RepeatableRows from '@/Pages/Cms/components/shared/RepeatableRows.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  RichTextEditor
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const factBlockFields = [
  { key: 'claim', label: 'Klaim / Poin Fakta', type: 'textarea', span: 'sm:col-span-2 lg:col-span-3', required: true, placeholder: 'Klaim atau ringkasan data penting...' },
  { key: 'sourceTitle', label: 'Nama Sumber', type: 'text', required: true, placeholder: 'e.g. Kementerian ESDM' },
  { key: 'sourceUrl', label: 'URL Sumber (https://)', type: 'url', required: true, placeholder: 'https://...' },
  { key: 'sourceDate', label: 'Tanggal Sumber', type: 'date', required: true }
]

const { form, errors, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.artikel,
  id: props.id,
  indexHref: '/cms/artikel',
  defaults: {
    slug: '',
    title: '',
    category: 'kebijakan',
    published_at: new Date().toISOString().slice(0, 10),
    summary: '',
    body: '',
    author_name: '',
    author_role: '',
    reading_minutes: 5,
    image_alt: '',
    image_url: '',
    link_url: '',
    fact_blocks: [],
    is_hero: false,
    show_in_terkini: true,
    is_popular: false,
    popular_rank: null,
    status: 'draf'
  }
})

function onTitleInput(e) {
  if (!isEdit.value && !form.slug) {
    form.slug = e.target.value
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)+/g, '')
  }
}
</script>

<template>
  <Head :title="(isEdit ? 'Edit Artikel' : 'Tambah Artikel') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/artikel">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Artikel' : 'Tambah Artikel Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data artikel #${props.id}` : 'Isi formulir berikut untuk menerbitkan artikel baru.' }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/artikel">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Artikel' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data artikel..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <!-- Informasi Utama -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Informasi Utama</CardTitle>
          <CardDescription class="text-xs">Judul, slug unik, kategori dan status publikasi artikel.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium">Judul Artikel <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              @input="onTitleInput"
              placeholder="e.g. Arah Kebijakan Energi Baru Terbarukan 2026"
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
                placeholder="arah-kebijakan-energi-2026"
                class="h-9 font-mono text-xs"
              />
              <p v-if="err('slug')" class="text-xs text-red-500 font-medium">{{ err('slug') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="category" class="text-xs font-medium">Kategori <span class="text-red-500">*</span></Label>
              <select
                id="category"
                v-model="form.category"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option v-for="cat in CATEGORIES" :key="cat.value" :value="cat.value">
                  {{ cat.label }}
                </option>
              </select>
              <p v-if="err('category')" class="text-xs text-red-500 font-medium">{{ err('category') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="published_at" class="text-xs font-medium">Tanggal Publikasi <span class="text-red-500">*</span></Label>
              <Input
                id="published_at"
                type="date"
                v-model="form.published_at"
                class="h-9 text-xs"
              />
              <p v-if="err('published_at')" class="text-xs text-red-500 font-medium">{{ err('published_at') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium">Status <span class="text-red-500">*</span></Label>
              <select
                id="status"
                v-model="form.status"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring font-medium"
              >
                <option v-for="st in STATUSES" :key="st.value" :value="st.value">
                  {{ st.label }}
                </option>
              </select>
              <p v-if="err('status')" class="text-xs text-red-500 font-medium">{{ err('status') }}</p>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="summary" class="text-xs font-medium">Ringkasan Eksekutif (Summary)</Label>
            <textarea
              id="summary"
              v-model="form.summary"
              rows="3"
              placeholder="Ringkasan singkat artikel (tampil di kartu Beranda / hero)..."
              class="w-full rounded-md border border-input bg-background p-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring leading-relaxed"
            />
            <p v-if="err('summary')" class="text-xs text-red-500 font-medium">{{ err('summary') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Penulis & Tautan -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Penulis, Tautan & Media</CardTitle>
          <CardDescription class="text-xs">Detail atribusi penulis, target navigasi, dan aset grafis artikel.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="author_name" class="text-xs font-medium">Nama Penulis / Tim Riset</Label>
              <Input
                id="author_name"
                v-model="form.author_name"
                placeholder="e.g. Tim Riset Kebijakan ISF"
                class="h-9 text-xs"
              />
              <p v-if="err('author_name')" class="text-xs text-red-500 font-medium">{{ err('author_name') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="author_role" class="text-xs font-medium">Jabatan / Keahlian</Label>
              <Input
                id="author_role"
                v-model="form.author_role"
                placeholder="e.g. Analis Regulasi"
                class="h-9 text-xs"
              />
              <p v-if="err('author_role')" class="text-xs text-red-500 font-medium">{{ err('author_role') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="reading_minutes" class="text-xs font-medium">Estimasi Waktu Baca (Menit)</Label>
              <Input
                id="reading_minutes"
                type="number"
                v-model.number="form.reading_minutes"
                min="1"
                class="h-9 text-xs"
              />
              <p v-if="err('reading_minutes')" class="text-xs text-red-500 font-medium">{{ err('reading_minutes') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="link_url" class="text-xs font-medium">Tautan Arah (Link URL Target)</Label>
              <Input
                id="link_url"
                v-model="form.link_url"
                placeholder="e.g. /isu/kuhp-baru-pelaku-usaha atau https://..."
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">Path internal (/isu/...) atau URL lengkap https://</p>
              <p v-if="err('link_url')" class="text-xs text-red-500 font-medium">{{ err('link_url') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="image_url" class="text-xs font-medium">URL Gambar Sampul</Label>
              <Input
                id="image_url"
                v-model="form.image_url"
                placeholder="https://..."
                class="h-9 text-xs font-mono"
              />
              <p v-if="err('image_url')" class="text-xs text-red-500 font-medium">{{ err('image_url') }}</p>
            </div>
          </div>

          <div v-if="form.image_url" class="space-y-1.5">
            <Label for="image_alt" class="text-xs font-medium">Deskripsi Gambar (Alt Text) <span class="text-red-500">*</span></Label>
            <Input
              id="image_alt"
              v-model="form.image_alt"
              placeholder="Deskripsi singkat gambar untuk aksesibilitas..."
              class="h-9 text-xs"
            />
            <p v-if="err('image_alt')" class="text-xs text-red-500 font-medium">{{ err('image_alt') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Penempatan di Halaman Publik -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Penempatan Halaman Publik</CardTitle>
          <CardDescription class="text-xs">Tentukan letak artikel ditampilkan di Beranda dan sidebar.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-emerald-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_hero"
                class="mt-0.5 rounded border-input text-emerald-600 focus:ring-emerald-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Artikel Utama (Hero)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampil sebagai headline utama di Beranda.</div>
              </div>
            </label>

            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-emerald-500/50 transition">
              <input
                type="checkbox"
                v-model="form.show_in_terkini"
                class="mt-0.5 rounded border-input text-emerald-600 focus:ring-emerald-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Tampilkan di Terkini</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Muncul di daftar 6 artikel terkini Beranda.</div>
              </div>
            </label>

            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-emerald-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_popular"
                class="mt-0.5 rounded border-input text-emerald-600 focus:ring-emerald-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Artikel Populer</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampil di sidebar isu terpopuler.</div>
              </div>
            </label>
          </div>

          <div v-if="form.is_popular" class="w-full sm:w-60 pt-2 space-y-1">
            <Label for="popular_rank" class="text-xs font-medium">Peringkat Populer (1 - 99)</Label>
            <Input
              id="popular_rank"
              type="number"
              v-model.number="form.popular_rank"
              min="1"
              max="99"
              placeholder="1"
              class="h-8 text-xs"
            />
            <p v-if="err('popular_rank')" class="text-xs text-red-500 font-medium">{{ err('popular_rank') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Blok Fakta Kunci (Repeatable) -->
      <RepeatableRows
        v-model="form.fact_blocks"
        :fields="factBlockFields"
        legend="Blok Fakta Kunci (Key Fact Blocks)"
        description="Fakta atau temuan penting yang disorot dengan rujukan sumber primer."
        addLabel="Tambah Fakta Kunci"
        :max="10"
      />

      <!-- Isi Artikel (RichTextEditor) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Konten Lengkap Artikel</CardTitle>
          <CardDescription class="text-xs">Gunakan editor rich-text untuk menyusun naskah analisis.</CardDescription>
        </CardHeader>
        <CardContent>
          <RichTextEditor
            v-model="form.body"
            placeholder="Tulis naskah lengkap artikel di sini..."
          />
          <p v-if="err('body')" class="text-xs text-red-500 font-medium mt-1">{{ err('body') }}</p>
        </CardContent>
      </Card>

      <!-- Bottom Save Action -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/artikel">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-5 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Artikel' }}
        </Button>
      </div>
    </form>
  </div>
</template>
