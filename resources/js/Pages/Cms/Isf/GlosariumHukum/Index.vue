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
  TableCell,
  FilterSelect
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const items = ref([])
const loading = ref(true)
const loadError = ref('')
const search = ref('')
const abjadFilter = ref('')
const statusFilter = ref('')
const currentPage = ref(1)
const perPage = ref(10)
let searchTimeout = null

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return items.value.slice(start, start + perPage.value)
})

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')
const abjadOptions = [
  { value: '', label: 'Semua Abjad (A-Z)' },
  ...alphabet.map((char) => ({ value: char, label: `Huruf ${char}` }))
]

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.glosariumHukum.list({
      q: search.value || undefined,
      abjad: abjadFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data glosarium hukum.')
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

function onAbjadChange() {
  currentPage.value = 1
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = status
  currentPage.value = 1
  loadData()
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(
    `Hapus istilah "${item.istilah}"? Tindakan ini tidak dapat dibatalkan.`
  )
  if (!confirmed) return

  try {
    await cmsResources.glosariumHukum.remove(item.id)
    alertSuccess('Istilah glosarium berhasil dihapus.')
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus istilah glosarium.'))
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head title="Kelola Glosarium Hukum — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kelola Glosarium Hukum</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Total
          </Badge>
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Kamus istilah hukum interaktif, definisi yuridis, dasar hukum perundang-undangan, dan istilah terkait.
        </p>
      </div>
      <Link href="/cms/glosarium-hukum/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5">
          <IconComponent name="Plus" :size="16" />
          <span>Tambah Istilah</span>
        </Button>
      </Link>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
      <div class="relative w-full sm:w-80">
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari istilah, definisi, dasar hukum..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 w-full sm:w-auto justify-start py-0.5">
        <div class="flex items-center gap-2">
          <label for="filter-abjad" class="text-xs text-muted-foreground whitespace-nowrap">Abjad:</label>
          <FilterSelect
            id="filter-abjad"
            v-model="abjadFilter"
            placeholder="Semua Abjad"
            :options="abjadOptions"
            @change="onAbjadChange"
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
    </div>

    <!-- Data Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat data glosarium hukum..." />
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
            <TableHead class="w-16 text-center text-xs">Abjad</TableHead>
            <TableHead class="min-w-[280px]">Istilah & Definisi</TableHead>
            <TableHead class="min-w-[180px]">Dasar Hukum</TableHead>
            <TableHead class="min-w-[150px]">Istilah Terkait</TableHead>
            <TableHead class="w-24 text-center">Status</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="7" class="text-center py-10 text-muted-foreground text-xs">
              Belum ada istilah glosarium hukum yang sesuai filter.
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
            <TableCell class="text-center">
              <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-amber-500/10 text-amber-600 font-mono font-bold text-xs border border-amber-500/20">
                {{ item.abjad }}
              </span>
            </TableCell>
            <TableCell class="py-3">
              <div class="font-semibold text-foreground text-sm line-clamp-1">
                {{ item.istilah }}
              </div>
              <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed mt-0.5 max-w-md">
                {{ item.definisi }}
              </p>
            </TableCell>
            <TableCell class="py-3">
              <p class="text-xs text-muted-foreground line-clamp-2 max-w-xs">
                {{ item.dasar_hukum || '-' }}
              </p>
            </TableCell>
            <TableCell class="py-3">
              <div v-if="Array.isArray(item.istilah_terkait) && item.istilah_terkait.length > 0" class="flex flex-wrap gap-1 max-h-10 overflow-hidden">
                <Badge
                  v-for="rel in item.istilah_terkait"
                  :key="rel"
                  variant="secondary"
                  class="text-[10px] px-1.5 py-0"
                >
                  {{ rel }}
                </Badge>
              </div>
              <span v-else class="text-xs text-muted-foreground">-</span>
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/glosarium-hukum/${item.id}/edit`">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs inline-flex items-center gap-1 border-border hover:bg-muted"
                  >
                    <IconComponent name="Edit" :size="13" />
                    <span>Edit</span>
                  </Button>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs inline-flex items-center gap-1 text-red-400 hover:text-red-300 hover:bg-red-500/10 border-red-500/20"
                  @click="handleDelete(item)"
                >
                  <IconComponent name="Trash" :size="13" />
                  <span>Hapus</span>
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
