<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
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
const loadError = ref('')
const search = ref('')
const statusFilter = ref('')
const currentPage = ref(1)
const perPage = ref(10)
let searchTimeout = null

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return items.value.slice(start, start + perPage.value)
})

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.hotNews.list({
      per_page: 500,
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
    currentPage.value = 1
    loadData()
  }, 350)
}

function onStatusChange(status) {
  statusFilter.value = status
  currentPage.value = 1
  loadData()
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
      <div>
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
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <div class="relative w-full lg:w-80">
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

      <div class="flex items-center gap-1.5 overflow-x-auto py-0.5">
        <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
        <div class="flex gap-1">
          <button
            type="button"
            @click="onStatusChange('')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors cursor-pointer',
              statusFilter === ''
                ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Semua
          </button>
          <button
            type="button"
            @click="onStatusChange('terbit')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors cursor-pointer',
              statusFilter === 'terbit'
                ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Terbit
          </button>
          <button
            type="button"
            @click="onStatusChange('draf')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors cursor-pointer',
              statusFilter === 'draf'
                ? 'bg-amber-600/20 text-amber-400 border border-amber-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Draf
          </button>
        </div>
      </div>
    </div>

    <ErrorMessage v-if="loadError" :message="loadError" />

    <!-- Table -->
    <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <LoadingIndicator v-if="loading" class="py-12" />

      <Table
        v-else
        :current-page="currentPage"
        :per-page="perPage"
        :total="items.length"
        @update:current-page="currentPage = $event"
        @update:per-page="perPage = $event; currentPage = 1"
      >
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs font-semibold">#</TableHead>
            <TableHead class="text-xs font-semibold">Judul Headline</TableHead>
            <TableHead class="w-40 text-xs font-semibold">Kategori</TableHead>
            <TableHead class="w-48 text-xs font-semibold">Target Tautan</TableHead>
            <TableHead class="w-32 text-xs font-semibold">Tanggal</TableHead>
            <TableHead class="w-24 text-center text-xs font-semibold">Status</TableHead>
            <TableHead class="w-36 text-right text-xs font-semibold">Aksi</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-if="paginatedItems.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-xs">
              Belum ada data hot news. Klik <strong>"Tambah Hot News"</strong> atau <strong>"Sinkronkan Artikel Terbit"</strong> untuk mengisi secara otomatis.
            </TableCell>
          </TableRow>

          <TableRow
            v-for="(item, index) in paginatedItems"
            :key="item.id || index"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center text-xs text-muted-foreground font-mono">
              {{ index + 1 + (currentPage - 1) * perPage }}
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

            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/hot-news/${item.id}/edit`">
                  <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center">
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>
                <Button
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  @click="handleDelete(item)"
                >
                  <IconComponent name="Trash" :size="14" class="mr-1" />
                  Hapus
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>

