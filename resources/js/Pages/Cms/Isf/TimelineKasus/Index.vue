<script setup>
import { ref, onMounted } from 'vue'
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
let searchTimeout = null

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
    loadData()
  }, 350)
}

function onStatusChange(status) {
  statusFilter.value = statusFilter.value === status ? '' : status
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
  <Head title="Timeline Kasus Hukum — CMS ISF" />

  <div class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
          Timeline Kasus Hukum
        </h1>
        <p class="text-sm text-neutral-500 mt-1">
          Kelola alur waktu perkara hukum besar, sengketa konstitusi, tindak pidana korupsi, dan kronologi sidangnya.
        </p>
      </div>
      <Link href="/cms/timeline-kasus/create">
        <Button class="inline-flex items-center gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Kasus Baru</span>
        </Button>
      </Link>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="p-4 bg-white rounded-xl border border-neutral-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Search Input -->
      <div class="relative w-full md:w-80">
        <IconComponent
          name="IconSearch"
          class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none"
        />
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul kasus, kategori, deskripsi..."
          class="pl-9 text-xs"
        />
      </div>

      <!-- Status Filter Buttons -->
      <div class="flex items-center gap-2 shrink-0">
        <Button
          size="sm"
          :variant="statusFilter === '' ? 'default' : 'outline'"
          @click="onStatusChange('')"
          class="text-xs"
        >
          Semua
        </Button>
        <Button
          size="sm"
          :variant="statusFilter === 'terbit' ? 'default' : 'outline'"
          @click="onStatusChange('terbit')"
          class="text-xs"
        >
          Terbit
        </Button>
        <Button
          size="sm"
          :variant="statusFilter === 'draft' ? 'default' : 'outline'"
          @click="onStatusChange('draft')"
          class="text-xs"
        >
          Draft
        </Button>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-2xs overflow-hidden">
      <LoadingIndicator v-if="loading" message="Memuat data timeline kasus..." />

      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        :retry="loadData"
        class="m-6"
      />

      <div v-else-if="items.length === 0" class="py-16 text-center text-neutral-500">
        <IconComponent name="IconClock" class="w-10 h-10 mx-auto text-neutral-300 mb-2" />
        <p class="font-medium text-neutral-700">Belum ada timeline kasus hukum</p>
        <p class="text-xs text-neutral-400 mt-1">
          Buat timeline alur perkara untuk menyajikan perjalanan kasus hukum besar secara visual.
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead class="min-w-[240px]">Judul Kasus</TableHead>
              <TableHead class="min-w-[140px]">Kategori</TableHead>
              <TableHead class="min-w-[160px]">Status Kasus</TableHead>
              <TableHead class="w-20 text-center">Urutan</TableHead>
              <TableHead class="w-24 text-center">Status</TableHead>
              <TableHead class="w-28 text-right pr-4">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in items" :key="item.id" class="hover:bg-neutral-50/70">
              <TableCell class="text-center font-mono text-xs text-neutral-400">
                {{ idx + 1 }}
              </TableCell>
              <TableCell>
                <div class="font-semibold text-neutral-900 text-sm">
                  {{ item.judul_kasus }}
                </div>
                <div class="text-xs text-neutral-500 font-mono mt-0.5">
                  /hukum#timeline-{{ item.slug }}
                </div>
              </TableCell>
              <TableCell>
                <Badge v-if="item.kategori_kasus" variant="secondary" class="text-xs">
                  {{ item.kategori_kasus }}
                </Badge>
                <span v-else class="text-xs text-neutral-400">-</span>
              </TableCell>
              <TableCell class="text-xs font-semibold text-amber-600">
                {{ item.status_kasus || 'Proses Berjalan' }}
              </TableCell>
              <TableCell class="text-center font-mono text-xs text-neutral-600">
                {{ item.urutan }}
              </TableCell>
              <TableCell class="text-center">
                <StatusBadge :status="item.status" />
              </TableCell>
              <TableCell class="text-right pr-4">
                <div class="inline-flex items-center gap-1 justify-end">
                  <Link :href="`/cms/timeline-kasus/${item.id}/edit`">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="Edit Kasus & Peristiwa">
                      <IconComponent name="IconEdit" class="w-4 h-4 text-neutral-600" />
                    </Button>
                  </Link>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                    title="Hapus Kasus"
                    @click="handleDelete(item)"
                  >
                    <IconComponent name="IconTrash" class="w-4 h-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </div>
</template>

