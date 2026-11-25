<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import http from '@/api/http'
import { cmsResources } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
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
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)

const items = ref([
  { rank: 1, article_id: '', title: '', summary: '', author: '', image_url: '', url: '', status: 'terbit' },
  { rank: 2, article_id: '', title: '', summary: '', author: '', image_url: '', url: '', status: 'terbit' },
  { rank: 3, article_id: '', title: '', summary: '', author: '', image_url: '', url: '', status: 'terbit' },
])

const articleOptions = ref([])
const loadingArticles = ref(false)

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const [resRank, resArticles] = await Promise.all([
      cmsResources.topRank.get(),
      cmsResources.topRank.getArticleOptions()
    ])

    if (Array.isArray(resRank) && resRank.length > 0) {
      // Pastikan ada 3 slot terurut
      items.value = [1, 2, 3].map((r) => {
        const found = resRank.find((it) => Number(it.rank) === r)
        return found ? { ...found } : { rank: r, article_id: '', title: '', summary: '', author: '', image_url: '', url: '', status: 'terbit' }
      })
    }

    articleOptions.value = Array.isArray(resArticles) ? resArticles : []
  } catch (err) {
    loadError.value = err.response?.data?.message || 'Gagal memuat pengaturan Top Artikel.'
  } finally {
    loading.value = false
  }
}

function onArticleSelected(rankIndex, articleId) {
  if (!articleId || articleId === '__NONE__') return

  const selected = articleOptions.value.find((a) => String(a.id) === String(articleId))
  if (selected) {
    items.value[rankIndex].article_id = selected.id
    items.value[rankIndex].title = selected.title
    items.value[rankIndex].summary = selected.summary || ''
    items.value[rankIndex].author = selected.author || 'Redaksi ISF'
    items.value[rankIndex].image_url = selected.image_url || ''
    items.value[rankIndex].url = selected.url || `/isu/${selected.slug || ''}`
  }
}

async function handleSave() {
  saving.value = true
  try {
    const res = await cmsResources.topRank.update(items.value)
    alertSuccess(res?.message || 'Top Artikel 1-3 of the Week berhasil disimpan.')
  } catch (err) {
    alertError(err.response?.data?.message || 'Gagal menyimpan pengaturan Top Artikel.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Top Artikel (Pekan Ini) — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full max-w-6xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Trophy" :size="24" class="text-amber-500" />
          Top Artikel 1 s/d 3 of the Week
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Atur dan kurasi 3 artikel terbaik/terpopuler pekan ini yang tampil dalam carousel khusus di bawah ISF Insight pada Beranda publik.
        </p>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Button
          type="button"
          @click="handleSave"
          :disabled="saving || loading"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center cursor-pointer"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Top Artikel' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat konfigurasi Top Artikel..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- 3 Slot Form Cards -->
    <div v-else class="space-y-6">
      <Card
        v-for="(slot, idx) in items"
        :key="slot.rank"
        class="bg-card border-border shadow-sm overflow-hidden"
      >
        <CardHeader class="pb-3 border-b border-border bg-muted/30 flex flex-row items-center justify-between">
          <div class="flex items-center gap-3">
            <span
              :class="[
                'flex h-7 w-7 items-center justify-center rounded-full text-xs font-extrabold text-white',
                slot.rank === 1 ? 'bg-amber-500 ring-2 ring-amber-400/40' :
                slot.rank === 2 ? 'bg-neutral-600 ring-2 ring-neutral-500/40' :
                'bg-amber-700 ring-2 ring-amber-600/40'
              ]"
            >
              #{{ slot.rank }}
            </span>
            <div>
              <CardTitle class="text-sm font-bold text-foreground">
                Peringkat {{ slot.rank }} (Rank #{{ slot.rank }} of the Week)
              </CardTitle>
              <CardDescription class="text-[11px] text-muted-foreground">
                Artikel unggulan posisi ke-{{ slot.rank }} pada carousel beranda.
              </CardDescription>
            </div>
          </div>

          <!-- Status Slot -->
          <div class="flex items-center gap-2">
            <label class="text-[11px] font-medium text-muted-foreground">Status:</label>
            <select
              v-model="slot.status"
              class="h-7 text-xs rounded border border-border bg-background px-2 font-medium"
            >
              <option value="terbit">Terbit</option>
              <option value="draf">Draf</option>
            </select>
          </div>
        </CardHeader>

        <CardContent class="p-5 space-y-4">
          <!-- Dropdown Cepat Pilih Artikel -->
          <div class="p-3 bg-muted/40 rounded-lg border border-border/80 space-y-1.5">
            <Label class="text-xs font-semibold text-foreground flex items-center gap-1.5">
              <IconComponent name="Sparkles" :size="14" class="text-red-500" />
              <span>Pilih Cepat dari Artikel Terbit</span>
            </Label>
            <select
              class="w-full h-9 rounded border border-border bg-background px-3 text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-red-500"
              @change="(e) => onArticleSelected(idx, e.target.value)"
            >
              <option value="__NONE__">-- Pilih artikel untuk mengisi otomatis data Rank #{{ slot.rank }} --</option>
              <option
                v-for="art in articleOptions"
                :key="art.id"
                :value="art.id"
                :selected="String(slot.article_id) === String(art.id)"
              >
                {{ art.title }} ({{ art.published_at }})
              </option>
            </select>
            <p class="text-[11px] text-muted-foreground">
              Memilih artikel akan mengisi judul, ringkasan, penulis, thumbnail, dan link secara otomatis. Anda tetap dapat mengeditnya di bawah.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Judul Artikel -->
            <div class="space-y-1.5 md:col-span-2">
              <Label :for="`title-${slot.rank}`" class="text-xs font-medium text-foreground">
                Judul Artikel *
              </Label>
              <Input
                :id="`title-${slot.rank}`"
                v-model="slot.title"
                placeholder="Masukkan judul artikel peringkat..."
                class="h-9 text-xs font-medium"
              />
            </div>

            <!-- Ringkasan / Deskripsi -->
            <div class="space-y-1.5 md:col-span-2">
              <Label :for="`summary-${slot.rank}`" class="text-xs font-medium text-foreground">
                Deskripsi Ringkas (Summary)
              </Label>
              <textarea
                :id="`summary-${slot.rank}`"
                v-model="slot.summary"
                rows="2"
                placeholder="Deskripsi singkat artikel untuk kartu rank..."
                class="w-full rounded border border-border bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-red-500"
              ></textarea>
            </div>

            <!-- Nama Penulis -->
            <div class="space-y-1.5">
              <Label :for="`author-${slot.rank}`" class="text-xs font-medium text-foreground">
                Nama Penulis
              </Label>
              <Input
                :id="`author-${slot.rank}`"
                v-model="slot.author"
                placeholder="Contoh: Tim Riset ISF / Nama Penulis"
                class="h-9 text-xs"
              />
            </div>

            <!-- URL Tautan Artikel -->
            <div class="space-y-1.5">
              <Label :for="`url-${slot.rank}`" class="text-xs font-medium text-foreground">
                URL Tautan Artikel
              </Label>
              <Input
                :id="`url-${slot.rank}`"
                v-model="slot.url"
                placeholder="/artikel/... atau /isu/..."
                class="h-9 text-xs font-mono"
              />
            </div>

            <!-- URL Thumbnail Gambar -->
            <div class="space-y-1.5 md:col-span-2">
              <Label :for="`image-${slot.rank}`" class="text-xs font-medium text-foreground">
                URL Gambar / Thumbnail
              </Label>
              <div class="flex items-center gap-3">
                <Input
                  :id="`image-${slot.rank}`"
                  v-model="slot.image_url"
                  placeholder="https://... atau /images/..."
                  class="h-9 text-xs font-mono flex-1"
                />
                <div
                  v-if="slot.image_url"
                  class="h-9 w-12 rounded border border-border bg-neutral-100 overflow-hidden shrink-0"
                >
                  <img :src="slot.image_url" alt="Preview" class="h-full w-full object-cover" />
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom Save Button -->
      <div class="flex justify-end pt-2 pb-8">
        <Button
          type="button"
          @click="handleSave"
          :disabled="saving"
          class="h-10 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-6 shadow-sm inline-flex items-center cursor-pointer"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Semua Top Artikel' }}
        </Button>
      </div>
    </div>
  </div>
</template>
