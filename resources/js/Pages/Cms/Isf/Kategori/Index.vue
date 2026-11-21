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

// Set of expanded parent category IDs (accordion/dropdown toggle)
const expandedRows = ref(new Set())

function isExpanded(id) {
  return expandedRows.value.has(id)
}

function toggleDropdown(id) {
  if (expandedRows.value.has(id)) {
    expandedRows.value.delete(id)
  } else {
    expandedRows.value.add(id)
  }
}

function expandAll() {
  groupedCategories.value.forEach(parent => {
    expandedRows.value.add(parent.id)
  })
}

function collapseAll() {
  expandedRows.value.clear()
}

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kategori.list({
      per_page: 500
    })
    items.value = Array.isArray(res) ? res : (res?.data || [])
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat daftar kategori.')
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
  }, 200)
}

function onStatusChange(status) {
  statusFilter.value = status
  currentPage.value = 1
}

const groupedCategories = computed(() => {
  const all = items.value || []
  const parents = all.filter(c => !c.parent_id)
  const q = search.value.trim().toLowerCase()
  const st = statusFilter.value

  const groups = parents.map(parent => {
    let children = all.filter(c => c.parent_id === parent.id)

    // Filter children by status if active
    if (st) {
      children = children.filter(c => (c.status || 'terbit') === st)
    }

    return {
      ...parent,
      children,
      childCount: children.length
    }
  })

  // Handle any orphaned subcategories (whose parent_id is missing or doesn't match parents)
  const parentIds = new Set(parents.map(p => p.id))
  let orphans = all.filter(c => c.parent_id && !parentIds.has(c.parent_id))
  if (st) {
    orphans = orphans.filter(c => (c.status || 'terbit') === st)
  }
  if (orphans.length > 0) {
    groups.push({
      id: '__orphans__',
      name: 'Sub-Kategori Lainnya (Tanpa Induk)',
      slug: '-',
      description: 'Daftar sub-kategori yang induk kategorinya tidak ditemukan.',
      status: 'terbit',
      isOrphan: true,
      children: orphans,
      childCount: orphans.length
    })
  }

  // Filter groups by status & search
  return groups.filter(group => {
    // If status filter is set, parent itself must match status OR have children matching status
    if (st && (group.status || 'terbit') !== st && group.childCount === 0) {
      return false
    }

    // Search query matching
    if (q) {
      const parentMatches = (group.name && group.name.toLowerCase().includes(q)) ||
                            (group.slug && group.slug.toLowerCase().includes(q)) ||
                            (group.description && group.description.toLowerCase().includes(q))
      const matchingChildren = group.children.filter(c =>
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.slug && c.slug.toLowerCase().includes(q)) ||
        (c.description && c.description.toLowerCase().includes(q))
      )

      if (parentMatches || matchingChildren.length > 0) {
        // Automatically expand so user sees matched subcategory
        expandedRows.value.add(group.id)
        return true
      }
      return false
    }

    return true
  })
})

const paginatedCategories = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return groupedCategories.value.slice(start, start + perPage.value)
})

async function handleDelete(item) {
  const isSub = Boolean(item.parent_id)
  const itemType = isSub ? 'Sub-Kategori' : 'Kategori'
  const confirmed = await confirmDelete(item.name || itemType)
  if (!confirmed) return

  try {
    await cmsResources.kategori.remove(item.id)
    alertSuccess(`${itemType} berhasil dihapus.`)
    loadData()
  } catch (err) {
    alertError(errorMessage(err, `Gagal menghapus ${itemType.toLowerCase()}.`))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Kategori — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Category" :size="24" class="text-red-500" />
          <span>Kelola Kategori Konten</span>
          <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs">
            {{ groupedCategories.length }} Kategori Utama
          </Badge>
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola daftar kategori dan sub-kategori hierarki yang digunakan pada Artikel, Infografik, Isu Mingguan, dan Banner.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/kategori/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Kategori</span>
          </Button>
        </Link>
      </div>
    </div>

    <!-- Filters, Search & Bulk Toggle -->
    <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
      <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center flex-1 max-w-xl">
        <div class="relative w-full sm:w-80">
          <IconComponent name="Search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <Input
            v-model="search"
            @input="onSearchInput"
            placeholder="Cari kategori atau sub-kategori..."
            class="pl-9 h-9 text-xs bg-card/60"
          />
        </div>

        <!-- Expand / Collapse All Toggles -->
        <div class="flex items-center gap-1.5 shrink-0">
          <Button
            type="button"
            variant="outline"
            size="sm"
            class="h-9 text-xs gap-1.5 px-3"
            @click="expandAll"
            title="Buka semua dropdown sub-kategori"
          >
            <IconComponent name="ChevronDown" :size="14" />
            <span>Buka Semua</span>
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            class="h-9 text-xs gap-1.5 px-3"
            @click="collapseAll"
            title="Tutup semua dropdown sub-kategori"
          >
            <IconComponent name="ChevronUp" :size="14" />
            <span>Tutup Semua</span>
          </Button>
        </div>
      </div>

      <div class="flex items-center gap-2 overflow-x-auto w-full lg:w-auto justify-start py-0.5">
        <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
        <div class="flex gap-1">
          <button
            type="button"
            @click="onStatusChange('')"
            :class="[
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
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
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
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
              'px-2.5 py-1 text-xs rounded-md font-medium transition-colors',
              statusFilter === 'draf'
                ? 'bg-red-600/20 text-red-400 border border-red-500/40'
                : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
            ]"
          >
            Draft
          </button>
        </div>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat daftar kategori & sub-kategori..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Table -->
    <div v-else class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table
        :current-page="currentPage"
        :per-page="perPage"
        :total="groupedCategories.length"
        @update:current-page="currentPage = $event"
        @update:per-page="perPage = $event; currentPage = 1"
      >
        <TableHeader>
          <TableRow class="bg-muted/40 hover:bg-muted/40 border-b border-border">
            <TableHead class="w-12 text-center text-xs">#</TableHead>
            <TableHead class="min-w-[200px] text-xs">Nama Kategori</TableHead>
            <TableHead class="w-48 text-center text-xs">Sub-Kategori (Dropdown)</TableHead>
            <TableHead class="w-32 text-xs">Slug</TableHead>
            <TableHead class="text-xs">Deskripsi</TableHead>
            <TableHead class="w-24 text-center text-xs">Status</TableHead>
            <TableHead class="w-48 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="paginatedCategories.length === 0">
            <TableCell colspan="7" class="text-center py-12 text-xs text-muted-foreground">
              <div class="flex flex-col items-center justify-center gap-2">
                <IconComponent name="Category" :size="32" class="text-muted-foreground/40" />
                <p>Belum ada kategori yang sesuai.</p>
                <Link href="/cms/kategori/create">
                  <Button
                    variant="outline"
                    size="sm"
                    class="mt-2 text-xs"
                  >
                    Tambah Kategori Baru
                  </Button>
                </Link>
              </div>
            </TableCell>
          </TableRow>

          <template v-for="(parent, idx) in paginatedCategories" :key="parent.id">
            <!-- Parent Category Row -->
            <TableRow
              :class="[
                'transition border-b border-border/50 cursor-pointer',
                isExpanded(parent.id) ? 'bg-muted/20' : 'hover:bg-muted/30'
              ]"
              @click="toggleDropdown(parent.id)"
            >
              <TableCell class="text-center text-xs font-mono text-muted-foreground" @click.stop>
                {{ idx + 1 + (currentPage - 1) * perPage }}
              </TableCell>

              <!-- Nama Kategori -->
              <TableCell>
                <div class="flex items-center gap-2">
                  <div>
                    <span class="font-bold text-sm text-foreground">
                      {{ parent.name }}
                    </span>
                    <span v-if="parent.isOrphan" class="block text-[10px] text-amber-500">
                      Perlu ditautkan ke Kategori Utama
                    </span>
                  </div>
                </div>
              </TableCell>

              <!-- Dedicated Dropdown Button in Column -->
              <TableCell class="text-center" @click.stop>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="toggleDropdown(parent.id)"
                  :class="[
                    'h-7 px-2.5 text-xs font-medium gap-1.5 transition-colors',
                    isExpanded(parent.id)
                      ? 'bg-red-500/15 text-red-600 dark:text-red-400 border-red-500/30 shadow-xs'
                      : 'text-muted-foreground hover:text-foreground hover:bg-muted/40'
                  ]"
                  :title="isExpanded(parent.id) ? 'Tutup daftar sub-kategori' : 'Buka daftar sub-kategori'"
                >
                  <IconComponent name="Category" :size="13" />
                  <span>{{ parent.children.length }} Sub-Kategori</span>
                  <IconComponent
                    name="ChevronDown"
                    :size="13"
                    :class="['transition-transform duration-200', isExpanded(parent.id) ? 'rotate-180 text-red-500' : '']"
                  />
                </Button>
              </TableCell>

              <!-- Slug -->
              <TableCell @click.stop>
                <span class="font-mono text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded">
                  {{ parent.slug }}
                </span>
              </TableCell>

              <!-- Deskripsi -->
              <TableCell class="text-xs text-muted-foreground max-w-xs">
                <div class="line-clamp-2">{{ parent.description || '-' }}</div>
              </TableCell>

              <!-- Status -->
              <TableCell class="text-center" @click.stop>
                <StatusBadge :status="parent.status || 'terbit'" />
              </TableCell>

              <!-- Aksi Parent -->
              <TableCell class="text-right" @click.stop>
                <div class="flex items-center justify-end gap-1.5">
                  <Link
                    v-if="!parent.isOrphan"
                    :href="`/cms/kategori/${parent.id}/edit`"
                  >
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                    >
                      <IconComponent name="Edit" :size="14" class="mr-1" />
                      Edit
                    </Button>
                  </Link>
                  <Button
                    v-if="!parent.isOrphan"
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="handleDelete(parent)"
                    class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                  >
                    <IconComponent name="Trash" :size="14" class="mr-1" />
                    Hapus
                  </Button>
                </div>
              </TableCell>
            </TableRow>

            <!-- Expanded Dropdown Panel: Nested Sub-Categories Table -->
            <TableRow v-if="isExpanded(parent.id)" class="bg-muted/10 border-b border-border/80">
              <TableCell colspan="7" class="p-0">
                <div class="border-l-4 border-l-red-600 bg-neutral-900/60 dark:bg-neutral-950/70 p-4 sm:p-5 space-y-3.5">
                  <!-- Header Dropdown Panel -->
                  <div class="flex items-center justify-between pb-2.5 border-b border-border/60">
                    <div class="flex items-center gap-2">
                      <span class="w-2 h-2 rounded-full bg-red-500"></span>
                      <h4 class="text-xs font-bold text-foreground tracking-wide font-space-grotesk">
                        Daftar Sub-Kategori: <span class="text-red-500">{{ parent.name }}</span>
                        <span class="text-muted-foreground font-normal ml-1">({{ parent.children.length }} sub-kategori)</span>
                      </h4>
                    </div>
                  </div>

                  <!-- Empty State if Parent has no Children -->
                  <div
                    v-if="parent.children.length === 0"
                    class="py-6 text-center text-xs text-muted-foreground bg-muted/20 rounded-lg border border-dashed border-border"
                  >
                    <p>Kategori <strong class="text-foreground">{{ parent.name }}</strong> belum memiliki sub-kategori.</p>
                  </div>

                  <!-- Sub-Categories Table -->
                  <div v-else class="rounded-lg border border-border bg-card/80 overflow-hidden shadow-xs">
                    <table class="w-full text-left text-xs">
                      <thead class="bg-muted/40 border-b border-border text-muted-foreground font-semibold">
                        <tr>
                          <th class="w-10 px-3 py-2 text-center font-mono">#</th>
                          <th class="px-3 py-2 min-w-[180px]">Nama Sub-Kategori</th>
                          <th class="w-32 px-3 py-2">Slug</th>
                          <th class="px-3 py-2">Deskripsi</th>
                          <th class="w-24 px-3 py-2 text-center">Status</th>
                          <th class="w-32 px-3 py-2 text-right">Aksi</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-border/50">
                        <tr
                          v-for="(sub, subIdx) in parent.children"
                          :key="sub.id"
                          class="hover:bg-muted/30 transition"
                        >
                          <td class="px-3 py-2 text-center font-mono text-muted-foreground">
                            {{ subIdx + 1 }}
                          </td>
                          <td class="px-3 py-2 font-medium text-foreground">
                            <div class="flex items-center gap-1.5">
                              <span class="w-1.5 h-1.5 rounded-full bg-red-500/80"></span>
                              <span>{{ sub.name }}</span>
                            </div>
                          </td>
                          <td class="px-3 py-2 font-mono text-[11px] text-muted-foreground">
                            <span class="bg-muted px-1.5 py-0.5 rounded">/{{ sub.slug }}</span>
                          </td>
                          <td class="px-3 py-2 text-muted-foreground max-w-sm truncate">
                            {{ sub.description || '-' }}
                          </td>
                          <td class="px-3 py-2 text-center">
                            <StatusBadge :status="sub.status || 'terbit'" />
                          </td>
                          <td class="px-3 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                              <Link :href="`/cms/kategori/${sub.id}/edit`">
                                <Button
                                  type="button"
                                  variant="outline"
                                  size="sm"
                                  class="h-7 px-2 text-[11px] font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center gap-1"
                                >
                                  <IconComponent name="Edit" :size="12" />
                                  Edit
                                </Button>
                              </Link>
                              <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="handleDelete(sub)"
                                class="h-7 px-2 text-[11px] font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center gap-1"
                              >
                                <IconComponent name="Trash" :size="12" />
                                Hapus
                              </Button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
