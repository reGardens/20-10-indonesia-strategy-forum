<script setup>
import { ref, onMounted, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LiveBadge from '@/Pages/Cms/components/shared/LiveBadge.vue'
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
  TableCell,
  Pagination
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
const liveId = ref(null)
const loading = ref(true)
const errorMsg = ref('')

const search = ref('')
const statusFilter = ref('')
const page = ref(1)

let searchTimeout = null

async function loadData() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await cmsResources.artikel.list({
      page: page.value,
      q: search.value || undefined,
      status: statusFilter.value || undefined
    })
    items.value = res.data || []
    meta.value = {
      current_page: res.current_page || 1,
      last_page: res.last_page || 1,
      total: res.total || 0,
      per_page: res.per_page || 15
    }
    liveId.value = res.live_id || null
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Gagal memuat daftar artikel.'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    loadData()
  }, 350)
}

function onStatusChange(val) {
  statusFilter.value = val
  page.value = 1
  loadData()
}

function onPageChange(newPage) {
  page.value = newPage
  loadData()
}

const settingHeroId = ref(null)

async function handleSetHero(item) {
  settingHeroId.value = item.id
  try {
    await cmsResources.artikel.setHero(item.id)
    alertSuccess(`"${item.title}" berhasil ditayangkan sebagai Hero Beranda.`)
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal mengubah artikel tayang.')
  } finally {
    settingHeroId.value = null
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title)
  if (!confirmed) return
  try {
    await cmsResources.artikel.remove(item.id)
    alertSuccess('Artikel berhasil dihapus.')
    loadData()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus artikel.')
  }
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Artikel — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Artikel</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ meta.total }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola berita, opini kebijakan, artikel hero, serta blok fakta kunci untuk halaman publik.
        </p>
      </div>
      <Link href="/cms/artikel/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Artikel</span>
        </Button>
      </Link>
    </div>

    <!-- Filter and Search -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul atau slug..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex items-center gap-2 overflow-x-auto">
        <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
        <div class="flex gap-1">
          <button
            type="button"
            @click="onStatusChange('')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === ''
                ? 'bg-red-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            Semua
          </button>
          <button
            type="button"
            @click="onStatusChange('terbit')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === 'terbit'
                ? 'bg-emerald-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            Terbit
          </button>
          <button
            type="button"
            @click="onStatusChange('draf')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === 'draf'
                ? 'bg-amber-600 text-white'
                : 'bg-card text-muted-foreground hover:bg-accent border border-border'
            ]"
          >
            Draf
          </button>
        </div>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat daftar artikel..." />
    <ErrorMessage v-else-if="errorMsg" :message="errorMsg" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-[40%]">Judul & Slug</TableHead>
            <TableHead>Kategori</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Penempatan</TableHead>
            <TableHead>Tanggal</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="6" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada artikel ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="item in items"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug line-clamp-2">
                {{ item.title }}
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5">
                /{{ item.slug }}
              </div>
            </TableCell>
            <TableCell>
              <Badge class="bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 text-[9.5px] px-1.5 py-0.5 capitalize">
                {{ item.category }}
              </Badge>
            </TableCell>
            <TableCell>
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell>
              <div class="flex flex-wrap gap-1 items-center">
                <LiveBadge v-if="item.id === liveId" label="Hero Tayang" />
                <span
                  v-else-if="item.is_hero"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium"
                >
                  Flag Hero
                </span>
                <span
                  v-if="item.show_in_terkini"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium"
                >
                  Terkini
                </span>
                <span
                  v-if="item.is_popular"
                  class="text-[9px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-medium"
                >
                  Populer
                </span>
              </div>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground">
              {{ formatDate(item.published_at) }}
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <!-- Trigger Tayang Hero -->
                <span
                  v-if="item.id === liveId"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Tayang
                </span>
                <Button
                  v-else
                  type="button"
                  variant="outline"
                  size="sm"
                  :disabled="settingHeroId === item.id"
                  class="h-8 px-2.5 text-xs font-medium text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-700 hover:border-emerald-500 transition-colors inline-flex items-center"
                  @click="handleSetHero(item)"
                  title="Tayangkan sebagai Hero Beranda"
                >
                  <IconComponent v-if="settingHeroId === item.id" name="Refresh" :size="13" class="mr-1 animate-spin" />
                  <IconComponent v-else name="Check" :size="13" class="mr-1 text-emerald-600" />
                  Tayangkan
                </Button>

                <!-- Edit -->
                <Link :href="`/cms/artikel/${item.id}/edit`">
                  <Button variant="outline" size="sm" class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center">
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>

                <!-- Hapus -->
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

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="p-4 border-t border-border flex justify-between items-center text-xs text-muted-foreground">
        <div>Halaman {{ meta.current_page }} dari {{ meta.last_page }} ({{ meta.total }} data)</div>
        <Pagination
          :currentPage="meta.current_page"
          :totalPages="meta.last_page"
          @update:currentPage="onPageChange"
        />
      </div>
    </div>
  </div>
</template>
