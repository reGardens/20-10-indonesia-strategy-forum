<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, hotNewsService } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { activeCategories as CATEGORIES, loadCategories, categoryLabel } from '@/Pages/Cms/Isf/options'
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
  CardContent,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save } = useCmsForm({
  resource: cmsResources.hotNews,
  id: props.id,
  indexHref: '/cms/hot-news',
  defaults: {
    title: '',
    to: '/beranda',
    category: 'Kebijakan',
    published_at: new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }),
    status: 'terbit'
  }
})

const articleOptions = ref([])
const loadingArticles = ref(false)
const selectedArticleId = ref('')

async function fetchArticleOptions() {
  loadingArticles.value = true
  try {
    const list = await hotNewsService.getArticleOptions()
    articleOptions.value = Array.isArray(list) ? list : []
  } catch (e) {
    console.error('Gagal mengambil daftar artikel:', e)
  } finally {
    loadingArticles.value = false
  }
}

function onArticleChange(val) {
  selectedArticleId.value = val === '__NONE__' ? '' : val
  if (!selectedArticleId.value) return

  const selected = articleOptions.value.find((a) => String(a.id) === String(selectedArticleId.value))
  if (selected) {
    form.title = selected.title
    form.to = selected.url || `/isu/${selected.slug || ''}`
    form.category = categoryLabel(selected.category) || selected.category || 'Berita Utama'
    if (selected.published_at) {
      form.published_at = selected.published_at
    }
  }
}

onMounted(() => {
  loadCategories()
  fetchArticleOptions()
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Hot News' : 'Tambah Hot News') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/hot-news">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Hot News' : 'Tambah Hot News Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data berita kilat #${props.id}` : 'Tambahkan berita kilat yang akan berputar di ticker bawah banner sorotan.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/hot-news">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Hot News' }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" class="py-12" />
    <ErrorMessage v-else-if="loadError" :message="loadError" />

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Form -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Quick autofill from published article -->
        <Card v-if="!isEdit" class="border-red-200 bg-red-50/40 dark:border-red-950/60 dark:bg-red-950/20">
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-semibold flex items-center gap-2 text-red-700 dark:text-red-400">
              <IconComponent name="Sparkles" :size="16" />
              <span>Opsi Cepat: Pilih dari Artikel Terbit</span>
            </CardTitle>
            <CardDescription class="text-xs">
              Pilih artikel yang sudah terbit untuk mengisi judul, kategori, dan tautan secara otomatis.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-1.5">
              <Select
                :model-value="selectedArticleId || '__NONE__'"
                :disabled="loadingArticles"
                @update:model-value="onArticleChange"
              >
                <SelectTrigger class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="-- Pilih Artikel untuk Mengisi Otomatis --" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__NONE__" class="text-xs font-medium cursor-pointer">
                    -- Pilih Artikel untuk Mengisi Otomatis --
                  </SelectItem>
                  <SelectItem
                    v-for="art in articleOptions"
                    :key="art.id"
                    :value="String(art.id)"
                    class="text-xs font-medium cursor-pointer"
                  >
                    [{{ art.category }}] {{ art.title }} ({{ art.published_at }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-base font-semibold">Konten Hot News</CardTitle>
            <CardDescription class="text-xs">
              Teks headline dan tujuan tautan berita kilat.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Judul -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">
                Judul Headline Berita <span class="text-red-600">*</span>
              </Label>
              <textarea
                v-model="form.title"
                rows="3"
                placeholder="Contoh: Pemerintah Matangkan Kebijakan Hilirisasi Energi Hijau dan Insentif Fiskal 2026"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-xs shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              ></textarea>
              <p v-if="err('title')" class="text-xs text-red-600">{{ err('title') }}</p>
            </div>

            <!-- Tautan Target -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">
                Target Tautan (URL / Route)
              </Label>
              <Input
                v-model="form.to"
                placeholder="/isu/kuhp-baru-pelaku-usaha atau https://..."
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">
                Dapat berupa rute internal (contoh: <code>/isu/slug</code>, <code>/beranda</code>) atau URL eksternal lengkap.
              </p>
              <p v-if="err('to')" class="text-xs text-red-600">{{ err('to') }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Sidebar Form -->
      <div class="space-y-6">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-base font-semibold">Metadata & Publikasi</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Kategori -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <Label for="category" class="text-xs font-semibold">Kategori <span class="text-red-600">*</span></Label>
                <Link href="/cms/kategori" class="text-[11px] text-blue-500 hover:underline inline-flex items-center gap-0.5">
                  Kelola Kategori &rarr;
                </Link>
              </div>
              <Select v-model="form.category">
                <SelectTrigger id="category" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="-- Pilih Kategori Konten --" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="cat in CATEGORIES"
                    :key="cat.value || cat.id"
                    :value="cat.label || cat.name || cat.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ cat.label || cat.name }}
                  </SelectItem>
                  <SelectItem
                    v-if="form.category && !CATEGORIES.some((c) => (c.label || c.name || c.value) === form.category)"
                    :value="form.category"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ form.category }} (Kategori Kustom)
                  </SelectItem>
                </SelectContent>
              </Select>
              <p class="text-[11px] text-muted-foreground">
                Kategori terhubung langsung dengan menu master <strong>Kategori</strong>.
              </p>
              <p v-if="err('category')" class="text-xs text-red-600">{{ err('category') }}</p>
            </div>

            <!-- Tanggal Tayang -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">Label Tanggal</Label>
              <Input
                v-model="form.published_at"
                placeholder="Contoh: 10 Nov 2026"
                class="h-9 text-xs"
              />
              <p v-if="err('published_at')" class="text-xs text-red-600">{{ err('published_at') }}</p>
            </div>

            <!-- Status -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">Status Publikasi</Label>
              <StatusSelectWithBadge v-model="form.status" />
              <p v-if="err('status')" class="text-xs text-red-600">{{ err('status') }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>

