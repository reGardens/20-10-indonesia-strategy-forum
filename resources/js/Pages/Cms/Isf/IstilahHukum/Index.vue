<script setup>
import { useCmsPagination } from '@/Composables/useCmsPagination'
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import {
  Button,
  Input,
  Badge,
  Table,
  TableHeader,
  TableBody,
  TableHead,
  TableRow,
  TableCell,
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
    const res = await cmsResources.istilahHukum.list({
      q: search.value || undefined,
      status: statusFilter.value || undefined,
      per_page: 100,
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat kategori istilah hukum.')
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

async function handleDelete(item) {
  const termName = item.nama || item.istilah
  const confirmed = await confirmDelete(
    `Hapus kategori/istilah "${termName}"? Istilah ini mungkin digunakan di glosarium hukum.`
  )
  if (!confirmed) return

  try {
    await cmsResources.istilahHukum.remove(item.id)
    alertSuccess('Kategori/istilah hukum berhasil dihapus.')
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus kategori/istilah hukum.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kategori Istilah Hukum — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Kategori Istilah Hukum</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ items.length }} Kategori
          </Badge>
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola master kategori kata/istilah hukum yang digunakan sebagai pilihan dropdown pada Glosarium Hukum.
        </p>
      </div>
      <Link href="/cms/istilah-hukum/create">
        <Button class="bg-red-600 hover:bg-red-700 text-white font-semibold text-xs h-9 px-4 shadow-sm inline-flex items-center gap-1.5">
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
          placeholder="Cari kategori / istilah hukum..."
          class="pl-9 h-9 text-xs"
        />
        <IconComponent
          name="Search"
          :size="16"
          class="absolute left-3 top-2.5 text-muted-foreground pointer-events-none"
        />
      </div>
    </div>

    <!-- Data Table Container -->
    <LoadingIndicator v-if="loading && items.length === 0" text="Memuat kategori istilah hukum..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

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
            <TableHead class="min-w-[220px] text-xs">Nama Istilah / Kategori</TableHead>
            <TableHead class="min-w-[260px] text-xs">Keterangan</TableHead>
            <TableHead class="w-24 text-center text-xs">Status</TableHead>
            <TableHead class="w-32 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="items.length === 0">
            <TableCell colspan="5" class="text-center py-10 text-muted-foreground text-xs">
              Belum ada kategori istilah hukum yang sesuai pencarian.
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
              <div class="font-semibold text-foreground text-xs line-clamp-1">
                {{ item.nama || item.istilah }}
              </div>
              <div class="text-[11px] text-muted-foreground font-mono mt-0.5 truncate">
                {{ item.slug }}
              </div>
            </TableCell>
            <TableCell class="text-xs text-muted-foreground">
              <p class="line-clamp-2 max-w-md leading-relaxed">
                {{ item.keterangan || '-' }}
              </p>
            </TableCell>
            <TableCell class="text-center">
              <StatusBadge :status="item.status || 'terbit'" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/istilah-hukum/${item.id}/edit`">
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
