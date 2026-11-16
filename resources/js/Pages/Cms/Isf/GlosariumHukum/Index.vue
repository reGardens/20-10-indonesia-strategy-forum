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
const abjadFilter = ref('')
const statusFilter = ref('')
let searchTimeout = null

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')

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
    loadData()
  }, 350)
}

function onAbjadChange(abjad) {
  abjadFilter.value = abjadFilter.value === abjad ? '' : abjad
  loadData()
}

function onStatusChange(status) {
  statusFilter.value = statusFilter.value === status ? '' : status
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
  <Head title="Glosarium Hukum — CMS ISF" />

  <div class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
          Glosarium Hukum
        </h1>
        <p class="text-sm text-neutral-500 mt-1">
          Kamus istilah hukum interaktif, definisi yuridis, dasar hukum perundang-undangan, dan istilah terkait.
        </p>
      </div>
      <Link href="/cms/glosarium-hukum/create">
        <Button class="inline-flex items-center gap-2">
          <IconComponent name="IconPlus" class="w-4 h-4" />
          <span>Tambah Istilah</span>
        </Button>
      </Link>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="p-4 bg-white rounded-xl border border-neutral-200/80 shadow-2xs space-y-3">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative w-full md:w-80">
          <IconComponent
            name="IconSearch"
            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none"
          />
          <Input
            v-model="search"
            @input="onSearchInput"
            placeholder="Cari istilah, definisi, dasar hukum..."
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

      <!-- Alphabet Filter Buttons -->
      <div class="pt-2 border-t border-neutral-100 flex items-center gap-1 overflow-x-auto pb-1 isf-no-scrollbar">
        <button
          type="button"
          @click="onAbjadChange('')"
          :class="[
            'px-2 py-0.5 rounded text-[11px] font-bold transition-all shrink-0 cursor-pointer',
            abjadFilter === ''
              ? 'bg-neutral-900 text-white'
              : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
          ]"
        >
          Semua Huruf
        </button>
        <button
          v-for="char in alphabet"
          :key="char"
          type="button"
          @click="onAbjadChange(char)"
          :class="[
            'w-6 h-6 flex items-center justify-center rounded text-[11px] font-mono font-bold transition-all shrink-0 cursor-pointer',
            abjadFilter === char
              ? 'bg-isf-red text-white'
              : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200'
          ]"
        >
          {{ char }}
        </button>
      </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-2xs overflow-hidden">
      <LoadingIndicator v-if="loading" message="Memuat data glosarium hukum..." />

      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        :retry="loadData"
        class="m-6"
      />

      <div v-else-if="items.length === 0" class="py-16 text-center text-neutral-500">
        <IconComponent name="IconBook" class="w-10 h-10 mx-auto text-neutral-300 mb-2" />
        <p class="font-medium text-neutral-700">Belum ada istilah glosarium hukum</p>
        <p class="text-xs text-neutral-400 mt-1">
          Tambahkan istilah baru untuk kamus hukum interaktif ISF.
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12 text-center">#</TableHead>
              <TableHead class="w-16 text-center">Abjad</TableHead>
              <TableHead class="min-w-[180px]">Istilah</TableHead>
              <TableHead class="min-w-[280px]">Definisi</TableHead>
              <TableHead class="min-w-[180px]">Dasar Hukum</TableHead>
              <TableHead class="min-w-[150px]">Istilah Terkait</TableHead>
              <TableHead class="w-24 text-center">Status</TableHead>
              <TableHead class="w-28 text-right pr-4">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in items" :key="item.id" class="hover:bg-neutral-50/70">
              <TableCell class="text-center font-mono text-xs text-neutral-400">
                {{ idx + 1 }}
              </TableCell>
              <TableCell class="text-center">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-amber-50 text-amber-700 font-mono font-bold text-xs border border-amber-200">
                  {{ item.abjad }}
                </span>
              </TableCell>
              <TableCell class="font-semibold text-neutral-900 text-sm">
                {{ item.istilah }}
              </TableCell>
              <TableCell class="text-xs text-neutral-600 line-clamp-2 max-w-xs leading-relaxed">
                {{ item.definisi }}
              </TableCell>
              <TableCell class="text-xs text-neutral-600">
                {{ item.dasar_hukum || '-' }}
              </TableCell>
              <TableCell>
                <div v-if="Array.isArray(item.istilah_terkait) && item.istilah_terkait.length > 0" class="flex flex-wrap gap-1">
                  <Badge
                    v-for="rel in item.istilah_terkait"
                    :key="rel"
                    variant="secondary"
                    class="text-[10px] px-1.5 py-0"
                  >
                    {{ rel }}
                  </Badge>
                </div>
                <span v-else class="text-xs text-neutral-400">-</span>
              </TableCell>
              <TableCell class="text-center">
                <StatusBadge :status="item.status" />
              </TableCell>
              <TableCell class="text-right pr-4">
                <div class="inline-flex items-center gap-1 justify-end">
                  <Link :href="`/cms/glosarium-hukum/${item.id}/edit`">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="Edit Istilah">
                      <IconComponent name="IconEdit" class="w-4 h-4 text-neutral-600" />
                    </Button>
                  </Link>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                    title="Hapus Istilah"
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

