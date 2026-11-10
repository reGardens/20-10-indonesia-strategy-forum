<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, hotNewsService, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Badge,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const loading = ref(true)
const syncing = ref(false)
const loadError = ref('')
const search = ref('')
const statusFilter = ref('')
let searchTimeout = null

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.hotNews.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data hot news.')
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadData()
  }, 350)
}

function onStatusChange(status) {
  statusFilter.value = status
  loadData()
}

async function handleSyncArticles() {
  syncing.value = true
  try {
    const res = await hotNewsService.syncArticles()
    alertSuccess(`Sinkronisasi berhasil! ${res.synced_count ?? res.length ?? ''} artikel terbaru ditambahkan ke Hot News.`)
    await loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyinkronkan artikel.'))
  } finally {
    syncing.value = false
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title || 'Item Hot News')
  if (!confirmed) return

  try {
    await cmsResources.hotNews.remove(item.id)
    alertSuccess('Hot news berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus hot news.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Hot News — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Sparkles" :size="24" class="text-red-500" />
          Kelola Hot News (Berita Kilat)
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola berita kilat interaktif yang tampil tepat pada bilah bawah banner sorotan Beranda publik ISF.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button
          type="button"
          variant="outline"
          :disabled="syncing"
          @click="handleSyncArticles"
          class="text-xs font-semibold h-9 px-3 shadow-sm inline-flex items-center gap-1.5"
          title="Tarik 5 artikel terbit terbaru menjadi headline hot news"
        >
          <IconComponent
            name="Refresh"
            :size="15"
            :class="['text-muted-foreground', syncing ? 'animate-spin' : '']"
          />
          <span>{{ syncing ? 'Menyinkronkan...' : 'Sinkronkan Artikel Terbit' }}</span>
        </Button>
        <Link href="/cms/hot-news/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Hot News</span>
          </Button>
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="relative flex-1">
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
        />
        <Input
          v-model="search"
          placeholder="Cari berdasarkan judul atau kategori..."
          class="pl-9 h-9 text-xs"
          @input="onSearchInput"
        />
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md border transition-colors',
            statusFilter === ''
              ? 'bg-neutral-900 text-white border-neutral-900 dark:bg-white dark:text-neutral-900'
              : 'bg-background text-muted-foreground border-border hover:bg-muted'
          ]"
          @click="onStatusChange('')"
        >
          Semua Status
        </button>
        <button
          type="button"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md border transition-colors',
            statusFilter === 'terbit'
              ? 'bg-green-600 text-white border-green-600'
              : 'bg-background text-muted-foreground border-border hover:bg-muted'
          ]"
          @click="onStatusChange('terbit')"
        >
          Terbit
        </button>
        <button
          type="button"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md border transition-colors',
            statusFilter === 'draf'
              ? 'bg-amber-600 text-white border-amber-600'
              : 'bg-background text-muted-foreground border-border hover:bg-muted'
          ]"
          @click="onStatusChange('draf')"
        >
          Draf
        </button>
      </div>
    </div>

    <ErrorMessage v-if="loadError" :message="loadError" />

    <!-- Table -->
    <div class="rounded-lg border border-border bg-card overflow-hidden shadow-sm">
      <LoadingIndicator v-if="loading" class="py-12" />

      <Table v-else>
        <TableHeader>
          <TableRow class="bg-muted/50 hover:bg-muted/50">
            <TableHead class="w-12 text-center text-xs font-semibold">#</TableHead>
            <TableHead class="text-xs font-semibold">Judul Headline</TableHead>
            <TableHead class="w-40 text-xs font-semibold">Kategori</TableHead>
            <TableHead class="w-48 text-xs font-semibold">Target Tautan</TableHead>
            <TableHead class="w-32 text-xs font-semibold">Tanggal</TableHead>
            <TableHead class="w-24 text-center text-xs font-semibold">Status</TableHead>
            <TableHead class="w-24 text-right text-xs font-semibold pr-4">Aksi</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-xs">
              Belum ada data hot news. Klik <strong>"Tambah Hot News"</strong> atau <strong>"Sinkronkan Artikel Terbit"</strong> untuk mengisi secara otomatis.
            </TableCell>
          </TableRow>

          <TableRow
            v-for="(item, index) in items"
            :key="item.id || index"
            class="hover:bg-muted/30 transition-colors"
          >
            <TableCell class="text-center text-xs text-muted-foreground font-mono">
              {{ index + 1 }}
            </TableCell>

            <TableCell class="font-medium text-xs text-foreground">
              <span class="line-clamp-2" :title="item.title">{{ item.title }}</span>
            </TableCell>

            <TableCell>
              <Badge
                variant="outline"
                class="pointer-events-none select-none bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[9.5px] px-1.5 py-0.5 capitalize font-medium"
              >
                {{ item.category || 'Berita Utama' }}
              </Badge>
            </TableCell>

            <TableCell class="text-xs text-muted-foreground font-mono truncate max-w-[180px]" :title="item.to || item.url">
              {{ item.to || item.url || '-' }}
            </TableCell>

            <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
              {{ item.published_at || '-' }}
            </TableCell>

            <TableCell class="text-center">
              <StatusBadge :status="item.status || 'terbit'" />
            </TableCell>

            <TableCell class="text-right pr-4 whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                <Link :href="`/cms/hot-news/${item.id}/edit`">
                  <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground">
                    <IconComponent name="Edit" :size="15" />
                  </Button>
                </Link>
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30"
                  @click="handleDelete(item)"
                >
                  <IconComponent name="Trash" :size="15" />
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>

