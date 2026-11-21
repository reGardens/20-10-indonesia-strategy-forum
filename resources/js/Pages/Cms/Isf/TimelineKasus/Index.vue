<script setup>
import { useCmsPagination } from '@/Composables/useCmsPagination'
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
const currentPage = useCmsPagination()
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
    const res = await cmsResources.timelineKasus.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data timeline kasus.')
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
  const confirmed = await confirmDelete(
    `Hapus timeline kasus "${item.judul_kasus}" beserta seluruh peristiwanya? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.timelineKasus.remove(item.id)
    alertSuccess('Timeline kasus berhasil dihapus.')
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus timeline kasus.'))
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Timeline Kasus Hukum — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Timeline Kasus Hukum</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kelola alur waktu perkara hukum besar, sengketa konstitusi, tindak pidana korupsi, dan kronologi sidangnya.
        </p>
      </div>
      <Link href="/cms/timeline-kasus/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Kasus Baru</span>
        </Button>
      </Link>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul kasus, kategori, deskripsi..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex items-center gap-2 overflow-x-auto justify-start">
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

    <!-- Data Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat data timeline kasus..." />
    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table
        :current-page="currentPage"
        :per-page="perPage"
        :total="items.length"
        @update:current-page="currentPage = $event"
        @update:per-page="perPage = $event; currentPage = 1"
      >
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="min-w-[220px]">Judul Kasus & Slug</TableHead>
            <TableHead class="w-40">Kategori Perkara</TableHead>
            <TableHead class="w-36">Status Kasus</TableHead>
            <TableHead class="w-24 text-center">Peristiwa</TableHead>
            <TableHead class="w-24 text-center">Status</TableHead>
            <TableHead class="text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-sm">
              Tidak ada data timeline kasus ditemukan.
            </TableCell>
          </TableRow>
          <TableRow
            v-for="(item, idx) in paginatedItems"
            :key="item.id"
            class="hover:bg-muted/30 transition border-b border-border/50"
          >
            <TableCell class="text-center font-mono text-xs text-muted-foreground">
              {{ idx + 1 + (currentPage - 1) * perPage }}
            </TableCell>
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm leading-snug">
                {{ item.judul_kasus }}
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5 truncate">
                #kasus-{{ item.slug }}
              </div>
            </TableCell>
            <TableCell>
              <Badge variant="outline" class="text-xs font-normal">
                {{ item.kategori_kasus || 'Umum' }}
              </Badge>
            </TableCell>
            <TableCell>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-500/10 text-blue-600 border border-blue-500/20">
                {{ item.status_kasus }}
              </span>
            </TableCell>
            <TableCell class="text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-bold text-xs bg-muted text-foreground">
                {{ Array.isArray(item.events) ? item.events.length : 0 }} alur
              </span>
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/timeline-kasus/${item.id}/edit`">
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
