<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { iklanService, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete, confirmAction } from '@/Composables/useAlert'
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
const statusFilter = ref('')    // '' | 'aktif' | 'non-aktif'
const activeTab = ref('body')    // 'body' | 'popup'
const reordering = ref(false)
let searchTimeout = null

const isFiltered = computed(() => Boolean(search.value || statusFilter.value))

function isBodyAd(item) {
  const p = (item.placement || '').toLowerCase()
  return p === 'spanduk' || p === 'banner'
}

function isPopupAd(item) {
  const p = (item.placement || '').toLowerCase()
  return p === 'popup' || p === 'popup_banner' || p === 'popup_video' || p === 'popup_duration'
}

const allBodyItems = computed(() => items.value.filter(isBodyAd))
const allPopupItems = computed(() => items.value.filter(isPopupAd))

function applySearchAndStatus(list) {
  let res = [...list]
  if (statusFilter.value === 'aktif') {
    res = res.filter((it) => Boolean(it.is_active))
  } else if (statusFilter.value === 'non-aktif') {
    res = res.filter((it) => !it.is_active)
  } else if (statusFilter.value) {
    res = res.filter((it) => (it.status || 'terbit') === statusFilter.value)
  }

  if (search.value) {
    const q = search.value.toLowerCase()
    res = res.filter((it) => {
      const matchTitle = (it.title || '').toLowerCase().includes(q)
      const matchKicker = (it.kicker || '').toLowerCase().includes(q)
      const matchTarget = (it.target_url || '').toLowerCase().includes(q)
      return matchTitle || matchKicker || matchTarget
    })
  }
  return res.sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0))
}

const filteredBodyItems = computed(() => applySearchAndStatus(allBodyItems.value))
const filteredPopupItems = computed(() => applySearchAndStatus(allPopupItems.value))

function getPopupScheduleInfo(item) {
  if (!item.is_active) {
    return {
      isFirst: false,
      label: 'Non-Aktif',
      badgeClass: 'bg-neutral-800 text-neutral-400 border-neutral-700'
    }
  }

  const activePopups = allPopupItems.value
    .filter((it) => Boolean(it.is_active))
    .sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0))

  const isFirst = activePopups.length > 0 && activePopups[0].id === item.id

  if (isFirst) {
    return {
      isFirst: true,
      label: 'Default #1 (Muncul Pertama)',
      badgeClass: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
    }
  }

  const mins = item.interval_minutes ?? 10
  return {
    isFirst: false,
    label: `Jeda ${mins} Menit`,
    badgeClass: 'bg-blue-500/10 text-blue-400 border-blue-500/30'
  }
}

function formatDateShort(dateStr) {
  if (!dateStr) return '-'
  try {
    const parts = dateStr.split('-')
    if (parts.length === 3) {
      const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    }
    const d = new Date(dateStr)
    return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch {
    return dateStr
  }
}

function getPeriodStatusInfo(item) {
  const today = new Date().toISOString().split('T')[0]

  // Jika iklan berstatus Non-Aktif, jangan tampilkan Aktif Tayang
  if (!item.is_active) {
    if (item.end_date && item.end_date < today) {
      return {
        statusLabel: 'Berakhir',
        statusBadgeClass: 'bg-neutral-800 text-neutral-400 border-neutral-700',
        durationText: 'Periode Berakhir',
        durationBadgeClass: 'bg-neutral-700/30 text-neutral-400 border-neutral-600/30'
      }
    }

    if (item.start_date && item.start_date > today) {
      const start = new Date(item.start_date)
      const now = new Date()
      start.setHours(0, 0, 0, 0)
      now.setHours(0, 0, 0, 0)
      const diffDays = Math.ceil((start.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

      return {
        statusLabel: 'Terjadwal',
        statusBadgeClass: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
        durationText: `Mulai ${diffDays} hari lagi`,
        durationBadgeClass: 'bg-amber-500/10 text-amber-400 border-amber-500/30'
      }
    }

    let durationText = 'Non-Aktif'
    if (item.end_date) {
      const end = new Date(item.end_date)
      const now = new Date()
      end.setHours(0, 0, 0, 0)
      now.setHours(0, 0, 0, 0)
      const diffDays = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
      if (diffDays >= 0) {
        durationText = `${diffDays} hari tersisa`
      }
    }

    return {
      statusLabel: 'Non-Aktif',
      statusBadgeClass: 'bg-neutral-800 text-neutral-400 border-neutral-700',
      durationText: durationText,
      durationBadgeClass: 'bg-neutral-800 text-neutral-400 border-neutral-700'
    }
  }

  // Jika iklan is_active === true
  if (item.end_date && item.end_date < today) {
    return {
      statusLabel: 'Berakhir',
      statusBadgeClass: 'bg-neutral-800 text-neutral-400 border-neutral-700',
      durationText: 'Periode Berakhir',
      durationBadgeClass: 'bg-neutral-700/30 text-neutral-400 border-neutral-600/30'
    }
  }

  if (item.start_date && item.start_date > today) {
    const start = new Date(item.start_date)
    const now = new Date()
    start.setHours(0, 0, 0, 0)
    now.setHours(0, 0, 0, 0)
    const diffDays = Math.ceil((start.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

    return {
      statusLabel: 'Terjadwal',
      statusBadgeClass: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
      durationText: `Mulai ${diffDays} hari lagi`,
      durationBadgeClass: 'bg-amber-500/10 text-amber-400 border-amber-500/30'
    }
  }

  // Currently within period or permanent
  if (!item.end_date) {
    return {
      statusLabel: 'Aktif Tayang',
      statusBadgeClass: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
      durationText: 'Permanen',
      durationBadgeClass: 'bg-neutral-800 text-neutral-300 border-neutral-700'
    }
  }

  const end = new Date(item.end_date)
  const now = new Date()
  end.setHours(0, 0, 0, 0)
  now.setHours(0, 0, 0, 0)
  const diffDays = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

  if (diffDays === 0) {
    return {
      statusLabel: 'Hari Terakhir',
      statusBadgeClass: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
      durationText: 'Hari Terakhir',
      durationBadgeClass: 'bg-rose-500/10 text-rose-400 border-rose-500/30'
    }
  }

  return {
    statusLabel: 'Aktif Tayang',
    statusBadgeClass: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    durationText: `${diffDays} hari left`,
    durationBadgeClass: 'bg-blue-500/10 text-blue-400 border-blue-500/30'
  }
}

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await iklanService.get()
    const list = Array.isArray(res) ? res : (res?.data || res?.items || [])
    items.value = list
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data iklan.')
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // Client-side instant filter via computed
  }, 200)
}

async function toggleActive(item) {
  const willBeActive = !item.is_active
  const isBody = isBodyAd(item)

  if (willBeActive && isBody) {
    // Di Iklan Body, hanya boleh ada maksimal 1 iklan aktif
    const currentlyActive = allBodyItems.value.find((it) => it.id !== item.id && it.is_active)
    if (currentlyActive) {
      const confirmed = await confirmAction({
        title: 'Aktifkan Iklan Body?',
        text: `Hanya 1 iklan body yang dapat aktif. Mengaktifkan "${item.title}" akan menonaktifkan iklan "${currentlyActive.title}" secara otomatis. Yakin ingin melanjutkan?`,
        confirmText: 'Ya, Aktifkan',
        cancelText: 'Batal',
        icon: 'warning'
      })
      if (!confirmed) return
    } else {
      const today = new Date().toISOString().split('T')[0]
      const isFuture = item.start_date && item.start_date > today
      let confirmText = `Yakin ingin mengaktifkan iklan "${item.title}"?`
      if (isFuture) {
        confirmText = `Iklan "${item.title}" dijadwalkan mulai ${formatDateShort(item.start_date)}. Yakin ingin mengaktifkannya sekarang?`
      }
      const confirmed = await confirmAction({
        title: 'Aktifkan Iklan?',
        text: confirmText,
        confirmText: 'Ya, Aktifkan',
        cancelText: 'Batal',
        icon: 'question'
      })
      if (!confirmed) return
    }
  } else if (willBeActive) {
    // Iklan Popup (bisa lebih dari 1 aktif)
    const confirmed = await confirmAction({
      title: 'Aktifkan Iklan?',
      text: `Yakin ingin mengaktifkan iklan "${item.title}"?`,
      confirmText: 'Ya, Aktifkan',
      cancelText: 'Batal',
      icon: 'question'
    })
    if (!confirmed) return
  } else {
    const confirmed = await confirmAction({
      title: 'Nonaktifkan Iklan?',
      text: `Yakin ingin menonaktifkan iklan "${item.title}"?`,
      confirmText: 'Ya, Nonaktifkan',
      cancelText: 'Batal',
      icon: 'question'
    })
    if (!confirmed) return
  }

  const prev = item.is_active
  item.is_active = willBeActive

  if (willBeActive && isBody) {
    allBodyItems.value.forEach((it) => {
      if (it.id !== item.id) {
        it.is_active = false
      }
    })
  }

  try {
    await iklanService.toggleActive(item.id)
    alertSuccess(`Iklan berhasil ${item.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`)
    await loadData()
  } catch (err) {
    item.is_active = prev
    alertError(errorMessage(err, 'Gagal mengubah status aktif.'))
    await loadData()
  }
}

async function moveOrder(currentIndex, direction, tabType) {
  if (isFiltered.value || reordering.value) return

  const list = tabType === 'body' ? [...filteredBodyItems.value] : [...filteredPopupItems.value]
  const targetIndex = currentIndex + direction
  if (targetIndex < 0 || targetIndex >= list.length) return

  const [moved] = list.splice(currentIndex, 1)
  list.splice(targetIndex, 0, moved)

  list.forEach((it, idx) => {
    it.order = idx + 1
  })

  const otherItems = items.value.filter((it) => !list.some((l) => l.id === it.id))
  const newItems = [...otherItems, ...list].sort((a, b) => (Number(a.order) || 0) - (Number(b.order) || 0))

  items.value = newItems
  reordering.value = true

  try {
    const payload = newItems.map((it, idx) => ({ id: it.id, order: idx + 1 }))
    await iklanService.reorder(payload)
    alertSuccess('Urutan iklan berhasil diperbarui.')
    await loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal mengubah urutan iklan.'))
    await loadData()
  } finally {
    reordering.value = false
  }
}

async function handleDelete(item) {
  const confirmed = await confirmDelete(item.title || 'Iklan')
  if (!confirmed) return

  try {
    await iklanService.remove(item.id)
    alertSuccess('Iklan berhasil dihapus.')
    await loadData()
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menghapus iklan.'))
  }
}

onMounted(loadData)
</script>

<template>
  <Head title="Kelola Iklan & Monetisasi — Indonesia Strategy Forum" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Tag" :size="24" class="text-red-500" />
          Kelola Iklan &amp; Monetisasi
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Kelola iklan spanduk Beranda dan modal popup sponsor saat akses website.
        </p>
      </div>

      <!-- Button Tambah Iklan (Navigasi ke Form Create) -->
      <div class="flex items-center gap-2">
        <Link href="/cms/iklan/create">
          <Button
            type="button"
            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold h-9 px-4 shadow-sm inline-flex items-center gap-1.5 cursor-pointer"
          >
            <IconComponent name="Plus" :size="16" />
            <span>Tambah Iklan</span>
          </Button>
        </Link>
      </div>
    </div>

    <!-- 2 Tab Pemisah Tabel Iklan -->
    <div class="flex items-center gap-2 border-b border-border pb-3">
      <button
        type="button"
        @click="activeTab = 'body'"
        :class="[
          'px-4 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer inline-flex items-center gap-2',
          activeTab === 'body'
            ? 'bg-red-600 text-white shadow-sm'
            : 'bg-card text-muted-foreground hover:bg-muted/40 hover:text-foreground border border-border'
        ]"
      >
        <IconComponent name="Layout" :size="15" />
        <span>Iklan Body (Spanduk Beranda)</span>
        <span
          :class="[
            'px-1.5 py-0.5 text-[10px] rounded-full font-mono font-bold',
            activeTab === 'body' ? 'bg-white/20 text-white' : 'bg-neutral-800 text-muted-foreground'
          ]"
        >
          {{ allBodyItems.length }}
        </span>
      </button>

      <button
        type="button"
        @click="activeTab = 'popup'"
        :class="[
          'px-4 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer inline-flex items-center gap-2',
          activeTab === 'popup'
            ? 'bg-red-600 text-white shadow-sm'
            : 'bg-card text-muted-foreground hover:bg-muted/40 hover:text-foreground border border-border'
        ]"
      >
        <IconComponent name="ExternalLink" :size="15" />
        <span>Iklan Popup (Modal Popup)</span>
        <span
          :class="[
            'px-1.5 py-0.5 text-[10px] rounded-full font-mono font-bold',
            activeTab === 'popup' ? 'bg-white/20 text-white' : 'bg-neutral-800 text-muted-foreground'
          ]"
        >
          {{ allPopupItems.length }}
        </span>
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
      <div class="relative w-full lg:w-80">
        <IconComponent name="Search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="search"
          @input="onSearchInput"
          placeholder="Cari judul iklan, label, tautan..."
          class="pl-9 h-9 text-xs bg-card/60"
        />
      </div>

      <div class="flex items-center gap-3">
        <!-- Filter Status Aktif -->
        <div class="flex items-center gap-1.5 overflow-x-auto py-0.5">
          <span class="text-xs text-muted-foreground whitespace-nowrap">Status:</span>
          <div class="flex gap-1">
            <button
              type="button"
              @click="statusFilter = ''"
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
              @click="statusFilter = 'aktif'"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors cursor-pointer',
                statusFilter === 'aktif'
                  ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/40'
                  : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
              ]"
            >
              Aktif
            </button>
            <button
              type="button"
              @click="statusFilter = 'non-aktif'"
              :class="[
                'px-2.5 py-1 text-xs rounded-md font-medium transition-colors cursor-pointer',
                statusFilter === 'non-aktif'
                  ? 'bg-neutral-600/20 text-neutral-400 border border-neutral-500/40'
                  : 'bg-card text-muted-foreground hover:bg-muted/40 border border-border'
              ]"
            >
              Non-Aktif
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" label="Memuat data iklan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- TAB 1: TABEL IKLAN BODY (SPANDUK BERANDA) -->
    <div v-else-if="activeTab === 'body'" class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader class="bg-muted/40">
          <TableRow>
            <TableHead class="w-24 text-center text-xs">Urutan</TableHead>
            <TableHead class="w-28 text-xs">Materi Media</TableHead>
            <TableHead class="text-xs">Judul Kampanye &amp; Tautan</TableHead>
            <TableHead class="w-60 text-xs">Status Periode &amp; Sisa Waktu</TableHead>
            <TableHead class="w-32 text-center text-xs">Status Aktif</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="filteredBodyItems.length === 0">
            <TableCell colspan="6" class="text-center py-12 text-xs text-muted-foreground">
              {{ isFiltered ? 'Tidak ada iklan body yang cocok dengan filter pencarian.' : 'Belum ada iklan body (spanduk beranda) yang terdaftar.' }}
            </TableCell>
          </TableRow>

          <TableRow
            v-for="(item, idx) in filteredBodyItems"
            :key="item.id"
            class="hover:bg-muted/20 transition-colors"
          >
            <!-- Urutan -->
            <TableCell class="text-center text-xs">
              <div class="inline-flex items-center justify-center gap-1.5">
                <span class="font-mono font-bold text-foreground text-xs min-w-[1.25rem] text-center">
                  {{ item.order ?? (idx + 1) }}
                </span>
                <div class="inline-flex flex-col gap-0.5">
                  <button
                    type="button"
                    :title="isFiltered ? 'Reset filter untuk mengubah urutan' : 'Pindah Urutan Naik'"
                    :disabled="idx === 0 || reordering || isFiltered"
                    @click="moveOrder(idx, -1, 'body')"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer"
                  >
                    <IconComponent name="ChevronUp" :size="13" />
                  </button>
                  <button
                    type="button"
                    :title="isFiltered ? 'Reset filter untuk mengubah urutan' : 'Pindah Urutan Turun'"
                    :disabled="idx === filteredBodyItems.length - 1 || reordering || isFiltered"
                    @click="moveOrder(idx, 1, 'body')"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer"
                  >
                    <IconComponent name="ChevronDown" :size="13" />
                  </button>
                </div>
              </div>
            </TableCell>

            <!-- Materi Media Preview -->
            <TableCell>
              <div class="w-24 h-14 rounded overflow-hidden bg-neutral-900 border border-border/60 flex items-center justify-center">
                <img
                  v-if="item.image_url"
                  :src="item.image_url"
                  :alt="item.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <IconComponent
                  v-else
                  name="Photo"
                  :size="20"
                  class="text-muted-foreground/60"
                />
              </div>
            </TableCell>

            <!-- Judul & Tautan -->
            <TableCell>
              <div class="space-y-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground font-mono">
                    {{ item.kicker || 'SPONSOR RESMI' }}
                  </span>
                </div>
                <p class="font-semibold text-sm text-foreground line-clamp-2">
                  {{ item.title }}
                </p>
                <div class="flex items-center gap-2 text-xs">
                  <a
                    v-if="item.target_url"
                    :href="item.target_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-mono text-red-400 hover:text-red-300 hover:underline flex items-center gap-0.5"
                  >
                    <span>{{ item.target_url }}</span>
                    <IconComponent name="ExternalLink" :size="11" />
                  </a>
                </div>
              </div>
            </TableCell>

            <!-- Status Periode & Sisa Waktu -->
            <TableCell>
              <div class="flex flex-col gap-1.5 items-start">
                <div class="flex items-center gap-1.5 text-muted-foreground text-[11px]">
                  <IconComponent name="Calendar" :size="12" class="shrink-0 text-muted-foreground" />
                  <span>
                    {{ item.start_date ? formatDateShort(item.start_date) : 'Mulai' }} – {{ item.end_date ? formatDateShort(item.end_date) : 'Seterusnya' }}
                  </span>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                  <Badge
                    variant="outline"
                    :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1', getPeriodStatusInfo(item).statusBadgeClass]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ getPeriodStatusInfo(item).statusLabel }}
                  </Badge>
                  <Badge
                    v-if="getPeriodStatusInfo(item).durationText"
                    variant="outline"
                    :class="['text-[10px] font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1', getPeriodStatusInfo(item).durationBadgeClass]"
                  >
                    <IconComponent name="Clock" :size="10" />
                    {{ getPeriodStatusInfo(item).durationText }}
                  </Badge>
                </div>
              </div>
            </TableCell>

            <!-- Toggle Aktif / Non-Aktif (Bisa aktif lebih dari 1) -->
            <TableCell class="text-center">
              <div class="flex items-center justify-center gap-2">
                <button
                  type="button"
                  role="switch"
                  :aria-checked="item.is_active"
                  :title="item.is_active ? 'Klik untuk non-aktifkan' : 'Klik untuk aktifkan'"
                  @click="toggleActive(item)"
                  :class="[
                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500',
                    item.is_active ? 'bg-emerald-500' : 'bg-neutral-700'
                  ]"
                >
                  <span
                    aria-hidden="true"
                    :class="[
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      item.is_active ? 'translate-x-4' : 'translate-x-0'
                    ]"
                  />
                </button>
                <span
                  :class="[
                    'text-xs font-semibold select-none min-w-[50px] text-left',
                    item.is_active ? 'text-emerald-400' : 'text-muted-foreground'
                  ]"
                >
                  {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </div>
            </TableCell>

            <!-- Aksi (Edit & Hapus) -->
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/iklan/${item.id}/edit`">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center cursor-pointer"
                  >
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center cursor-pointer"
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

    <!-- TAB 2: TABEL IKLAN POPUP (MODAL POPUP) -->
    <div v-else-if="activeTab === 'popup'" class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
      <Table>
        <TableHeader class="bg-muted/40">
          <TableRow>
            <TableHead class="w-24 text-center text-xs">Urutan</TableHead>
            <TableHead class="w-28 text-xs">Materi Media</TableHead>
            <TableHead class="text-xs">Judul Kampanye &amp; Tautan</TableHead>
            <TableHead class="w-52 text-xs">Format &amp; Penayangan</TableHead>
            <TableHead class="w-60 text-xs">Status Periode &amp; Sisa Waktu</TableHead>
            <TableHead class="w-32 text-center text-xs">Status Aktif</TableHead>
            <TableHead class="w-36 text-right text-xs">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="filteredPopupItems.length === 0">
            <TableCell colspan="7" class="text-center py-12 text-xs text-muted-foreground">
              {{ isFiltered ? 'Tidak ada iklan popup yang cocok dengan filter pencarian.' : 'Belum ada iklan modal popup yang terdaftar.' }}
            </TableCell>
          </TableRow>

          <TableRow
            v-for="(item, idx) in filteredPopupItems"
            :key="item.id"
            class="hover:bg-muted/20 transition-colors"
          >
            <!-- Urutan -->
            <TableCell class="text-center text-xs">
              <div class="inline-flex items-center justify-center gap-1.5">
                <span class="font-mono font-bold text-foreground text-xs min-w-[1.25rem] text-center">
                  {{ item.order ?? (idx + 1) }}
                </span>
                <div class="inline-flex flex-col gap-0.5">
                  <button
                    type="button"
                    :title="isFiltered ? 'Reset filter untuk mengubah urutan' : 'Pindah Urutan Naik'"
                    :disabled="idx === 0 || reordering || isFiltered"
                    @click="moveOrder(idx, -1, 'popup')"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer"
                  >
                    <IconComponent name="ChevronUp" :size="13" />
                  </button>
                  <button
                    type="button"
                    :title="isFiltered ? 'Reset filter untuk mengubah urutan' : 'Pindah Urutan Turun'"
                    :disabled="idx === filteredPopupItems.length - 1 || reordering || isFiltered"
                    @click="moveOrder(idx, 1, 'popup')"
                    class="p-0.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer"
                  >
                    <IconComponent name="ChevronDown" :size="13" />
                  </button>
                </div>
              </div>
            </TableCell>

            <!-- Materi Media Preview -->
            <TableCell>
              <div class="w-24 h-14 rounded overflow-hidden bg-neutral-900 border border-border/60 flex items-center justify-center">
                <img
                  v-if="item.image_url"
                  :src="item.image_url"
                  :alt="item.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <IconComponent
                  v-else-if="item.type === 'video' || item.placement === 'popup_video' || item.placement === 'popup_duration'"
                  name="Video"
                  :size="20"
                  class="text-amber-500"
                />
                <IconComponent
                  v-else
                  name="Photo"
                  :size="20"
                  class="text-muted-foreground/60"
                />
              </div>
            </TableCell>

            <!-- Judul & Tautan -->
            <TableCell>
              <div class="space-y-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground font-mono">
                    {{ item.kicker || 'SPONSOR RESMI' }}
                  </span>
                </div>
                <p class="font-semibold text-sm text-foreground line-clamp-2">
                  {{ item.title }}
                </p>
                <div class="flex items-center gap-2 text-xs">
                  <a
                    v-if="item.target_url"
                    :href="item.target_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-mono text-red-400 hover:text-red-300 hover:underline flex items-center gap-0.5"
                  >
                    <span>{{ item.target_url }}</span>
                    <IconComponent name="ExternalLink" :size="11" />
                  </a>
                </div>
              </div>
            </TableCell>

            <!-- Format & Penayangan (Popup bisa multi-aktif, urutan #1 aktif = default awal) -->
            <TableCell>
              <div class="flex flex-col gap-1 items-start">
                <div class="flex items-center gap-1.5">
                  <Badge
                    variant="outline"
                    :class="[
                      'text-[10px] font-semibold',
                      item.type === 'video' || item.placement === 'popup_video' || item.placement === 'popup_duration'
                        ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                        : 'bg-purple-500/10 text-purple-400 border-purple-500/30'
                    ]"
                  >
                    <IconComponent
                      :name="item.type === 'video' || item.placement === 'popup_video' || item.placement === 'popup_duration' ? 'Video' : 'Photo'"
                      :size="11"
                      class="mr-1 inline-block"
                    />
                    {{ (item.type === 'video' || item.placement === 'popup_video' || item.placement === 'popup_duration') ? `Video (${item.video_duration || 30}s)` : 'Gambar / Banner' }}
                  </Badge>
                </div>
                <div>
                  <Badge
                    variant="outline"
                    :class="['text-[10px] font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1', getPopupScheduleInfo(item).badgeClass]"
                  >
                    <IconComponent name="Clock" :size="10" />
                    {{ getPopupScheduleInfo(item).label }}
                  </Badge>
                </div>
              </div>
            </TableCell>

            <!-- Status Periode & Sisa Waktu -->
            <TableCell>
              <div class="flex flex-col gap-1.5 items-start">
                <div class="flex items-center gap-1.5 text-muted-foreground text-[11px]">
                  <IconComponent name="Calendar" :size="12" class="shrink-0 text-muted-foreground" />
                  <span>
                    {{ item.start_date ? formatDateShort(item.start_date) : 'Mulai' }} – {{ item.end_date ? formatDateShort(item.end_date) : 'Seterusnya' }}
                  </span>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                  <Badge
                    variant="outline"
                    :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1', getPeriodStatusInfo(item).statusBadgeClass]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ getPeriodStatusInfo(item).statusLabel }}
                  </Badge>
                  <Badge
                    v-if="getPeriodStatusInfo(item).durationText"
                    variant="outline"
                    :class="['text-[10px] font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1', getPeriodStatusInfo(item).durationBadgeClass]"
                  >
                    <IconComponent name="Clock" :size="10" />
                    {{ getPeriodStatusInfo(item).durationText }}
                  </Badge>
                </div>
              </div>
            </TableCell>

            <!-- Toggle Aktif / Non-Aktif (Modal Popup bisa aktif lebih dari 1) -->
            <TableCell class="text-center">
              <div class="flex items-center justify-center gap-2">
                <button
                  type="button"
                  role="switch"
                  :aria-checked="item.is_active"
                  :title="item.is_active ? 'Klik untuk non-aktifkan' : 'Klik untuk aktifkan'"
                  @click="toggleActive(item)"
                  :class="[
                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500',
                    item.is_active ? 'bg-emerald-500' : 'bg-neutral-700'
                  ]"
                >
                  <span
                    aria-hidden="true"
                    :class="[
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      item.is_active ? 'translate-x-4' : 'translate-x-0'
                    ]"
                  />
                </button>
                <span
                  :class="[
                    'text-xs font-semibold select-none min-w-[50px] text-left',
                    item.is_active ? 'text-emerald-400' : 'text-muted-foreground'
                  ]"
                >
                  {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </div>
            </TableCell>

            <!-- Aksi (Edit & Hapus) -->
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1.5">
                <Link :href="`/cms/iklan/${item.id}/edit`">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center cursor-pointer"
                  >
                    <IconComponent name="Edit" :size="14" class="mr-1" />
                    Edit
                  </Button>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center cursor-pointer"
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
