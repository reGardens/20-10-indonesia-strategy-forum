<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError, confirmDelete } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Badge,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const page = usePage()
const VALID_TABS = ['pitch', 'channels', 'workflow', 'faq']

function getTabFromUrl() {
  const url = page.url || (typeof window !== 'undefined' ? window.location.href : '')
  try {
    const searchParams = new URL(url, 'http://localhost').searchParams
    const tab = searchParams.get('tab')
    return VALID_TABS.includes(tab) ? tab : 'pitch'
  } catch {
    return 'pitch'
  }
}

const activeTab = ref(getTabFromUrl())
const loading = ref(true)
const loadError = ref('')

const tabMeta = computed(() => {
  if (activeTab.value === 'channels') {
    return {
      title: 'Saluran Kemitraan',
      desc: 'Kelola format dan saluran penempatan kemitraan strategis (Display Banner, Advertorial, Event, Riset).',
    }
  }
  if (activeTab.value === 'workflow') {
    return {
      title: 'Alur Kerjasama',
      desc: 'Kelola tahapan alur kolaborasi kemitraan yang dipresentasikan kepada calon mitra.',
    }
  }
  if (activeTab.value === 'faq') {
    return {
      title: 'Tanya Jawab (FAQ)',
      desc: 'Kelola daftar pertanyaan dan jawaban seputar kebijakan kemitraan ISF.',
    }
  }
  return {
    title: 'Pitch & Profil Audiens',
    desc: 'Kelola teks pengantar hero section dan metrik kunci audiens eksekutif halaman /media-kit/.',
  }
})

watch(() => page.url, () => {
  activeTab.value = getTabFromUrl()
})

const form = ref({
  pitch: {
    badge: 'Informasi Iklan & Kemitraan',
    title: 'Menghubungkan Gagasan, Kebijakan, dan Pengambil Keputusan Nasional',
    deck: 'Indonesia Strategy Forum (ISF) adalah kanal referensi independen untuk para pembuat kebijakan, eksekutif puncak korporasi, akademisi, dan profesional hukum yang membentuk arah pembangunan Indonesia.',
  },
  audiens: {
    total_monthly_readers: '150.000+',
    total_monthly_readers_label: 'Pembaca Terverifikasi / Bulan',
    executive_share: '45.000+',
    executive_share_label: 'Eksekutif & C-Suite',
    retention_rate: '85%',
    retention_rate_label: 'Tingkat Retensi Pembaca',
  },
  channels: [],
  workflow: [],
  faq: [],
})

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kemitraan.getSections()
    const data = res?.data || {}
    if (data.pitch) form.value.pitch = { ...form.value.pitch, ...data.pitch }
    if (data.audiens) form.value.audiens = { ...form.value.audiens, ...data.audiens }
    if (Array.isArray(data.channels) && data.channels.length > 0) form.value.channels = data.channels
    if (Array.isArray(data.workflow) && data.workflow.length > 0) form.value.workflow = data.workflow
    if (Array.isArray(data.faq) && data.faq.length > 0) form.value.faq = data.faq
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat pengaturan konten kemitraan.')
  } finally {
    loading.value = false
  }
}

async function handleSaveSections() {
  try {
    await cmsResources.kemitraan.updateSections({
      pitch: form.value.pitch,
      audiens: form.value.audiens,
      channels: form.value.channels,
      workflow: form.value.workflow,
      faq: form.value.faq,
    })
    alertSuccess('Data kemitraan berhasil diperbarui.')
  } catch (err) {
    alertError(errorMessage(err, 'Gagal memperbarui data kemitraan.'))
  }
}

async function handleDeleteChannel(index) {
  const item = form.value.channels[index]
  const confirmed = await confirmDelete(item.title || item.kicker || 'Saluran Kemitraan')
  if (!confirmed) return
  form.value.channels.splice(index, 1)
  await handleSaveSections()
}

async function handleDeleteWorkflow(index) {
  const item = form.value.workflow[index]
  const confirmed = await confirmDelete(item.title || `Langkah ${item.step}`)
  if (!confirmed) return
  form.value.workflow.splice(index, 1)
  form.value.workflow.forEach((w, idx) => {
    w.step = idx + 1
  })
  await handleSaveSections()
}

async function handleDeleteFaq(index) {
  const item = form.value.faq[index]
  const confirmed = await confirmDelete(item.q || 'Pertanyaan FAQ')
  if (!confirmed) return
  form.value.faq.splice(index, 1)
  await handleSaveSections()
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head :title="`${tabMeta.title} — Indonesia Strategy Forum`" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button & EXACTLY ONE Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kemitraan">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
            <span>{{ tabMeta.title }}</span>
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ tabMeta.desc }}
          </p>
        </div>
      </div>

      <!-- Single Action Button per active tab -->
      <div>
        <template v-if="activeTab === 'pitch'">
          <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=pitch">
            <Button
              class="bg-red-600 hover:bg-red-700 text-white font-medium text-xs shadow-sm inline-flex items-center gap-1.5 h-9 px-3.5"
            >
              <IconComponent name="Edit" :size="15" />
              <span>Edit Hero Pitch</span>
            </Button>
          </Link>
        </template>

        <template v-else-if="activeTab === 'channels'">
          <Link href="/cms/kemitraan/sections/create?tab=channels">
            <Button
              class="bg-red-600 hover:bg-red-700 text-white font-medium text-xs shadow-sm inline-flex items-center gap-1.5 h-9 px-3.5"
            >
              <IconComponent name="Plus" :size="15" />
              <span>Tambah Saluran</span>
            </Button>
          </Link>
        </template>

        <template v-else-if="activeTab === 'workflow'">
          <Link href="/cms/kemitraan/sections/create?tab=workflow&type=workflow">
            <Button
              class="bg-red-600 hover:bg-red-700 text-white font-medium text-xs shadow-sm inline-flex items-center gap-1.5 h-9 px-3.5"
            >
              <IconComponent name="Plus" :size="15" />
              <span>Tambah Tahapan</span>
            </Button>
          </Link>
        </template>

        <template v-else-if="activeTab === 'faq'">
          <Link href="/cms/kemitraan/sections/create?tab=faq&type=faq">
            <Button
              class="bg-red-600 hover:bg-red-700 text-white font-medium text-xs shadow-sm inline-flex items-center gap-1.5 h-9 px-3.5"
            >
              <IconComponent name="Plus" :size="15" />
              <span>Tambah FAQ</span>
            </Button>
          </Link>
        </template>
      </div>
    </div>

    <!-- Loading & Error -->
    <LoadingIndicator v-if="loading" text="Memuat data kemitraan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <div v-else class="space-y-8">

      <!-- ======================================================== -->
      <!-- TAB 1: PITCH & PROFIL AUDIENS (TABLES)                   -->
      <!-- ======================================================== -->
      <div v-if="activeTab === 'pitch'" class="space-y-8">
        <!-- Table 1: Konten Hero Pitch -->
        <div class="space-y-3">
          <div>
            <h2 class="text-sm font-bold text-foreground tracking-tight">Teks Hero Pitch Halaman /media-kit/</h2>
            <p class="text-xs text-muted-foreground">Komponen teks pengantar utama untuk meyakinkan calon mitra institusional.</p>
          </div>
          <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
            <Table>
              <TableHeader class="bg-muted/40">
                <TableRow class="hover:bg-muted/40 border-b border-border">
                  <TableHead class="w-48 text-xs font-semibold">Elemen</TableHead>
                  <TableHead class="text-xs font-semibold">Isi Teks Konten</TableHead>
                  <TableHead class="w-28 text-right text-xs font-semibold">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow class="hover:bg-muted/30 transition border-b border-border/50">
                  <TableCell class="text-xs font-semibold text-foreground">Badge Kicker</TableCell>
                  <TableCell class="text-xs text-foreground">
                    <Badge variant="outline" class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 font-bold text-[10px]">
                      {{ form.pitch.badge || 'Informasi Iklan & Kemitraan' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=pitch">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
                <TableRow class="hover:bg-muted/30 transition border-b border-border/50">
                  <TableCell class="text-xs font-semibold text-foreground">Judul Utama (Headline)</TableCell>
                  <TableCell class="text-xs font-medium text-foreground">
                    {{ form.pitch.title }}
                  </TableCell>
                  <TableCell class="text-right">
                    <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=pitch">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
                <TableRow class="hover:bg-muted/30 transition">
                  <TableCell class="text-xs font-semibold text-foreground">Deskripsi Penjelas (Deck)</TableCell>
                  <TableCell class="text-xs text-muted-foreground leading-relaxed">
                    {{ form.pitch.deck }}
                  </TableCell>
                  <TableCell class="text-right">
                    <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=pitch">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>

        <!-- Table 2: Metrik Audiens Utama -->
        <div class="space-y-3">
          <div>
            <h2 class="text-sm font-bold text-foreground tracking-tight">Tabel Metrik Audiens Eksekutif</h2>
            <p class="text-xs text-muted-foreground">Tiga angka statistik kunci yang ditampilkan pada baris metrik audiens.</p>
          </div>
          <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
            <Table>
              <TableHeader class="bg-muted/40">
                <TableRow class="hover:bg-muted/40 border-b border-border">
                  <TableHead class="w-12 text-center text-xs font-semibold">#</TableHead>
                  <TableHead class="w-64 text-xs font-semibold">Label / Keterangan Metrik</TableHead>
                  <TableHead class="w-48 text-xs font-semibold">Nilai Statistik</TableHead>
                  <TableHead class="text-xs font-semibold">Deskripsi Ringkas</TableHead>
                  <TableHead class="w-28 text-right text-xs font-semibold">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow class="hover:bg-muted/30 transition border-b border-border/50">
                  <TableCell class="text-center text-xs font-mono text-muted-foreground">1</TableCell>
                  <TableCell class="text-xs font-medium text-foreground">
                    {{ form.audiens.total_monthly_readers_label }}
                  </TableCell>
                  <TableCell class="text-xs font-bold text-red-600 dark:text-red-400 font-mono">
                    {{ form.audiens.total_monthly_readers }}
                  </TableCell>
                  <TableCell class="text-xs text-muted-foreground">Total pembaca terverifikasi bulanan</TableCell>
                  <TableCell class="text-right">
                    <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=metric&key=total_monthly_readers">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
                <TableRow class="hover:bg-muted/30 transition border-b border-border/50">
                  <TableCell class="text-center text-xs font-mono text-muted-foreground">2</TableCell>
                  <TableCell class="text-xs font-medium text-foreground">
                    {{ form.audiens.executive_share_label }}
                  </TableCell>
                  <TableCell class="text-xs font-bold text-blue-600 dark:text-blue-400 font-mono">
                    {{ form.audiens.executive_share }}
                  </TableCell>
                  <TableCell class="text-xs text-muted-foreground">Porsi pengambil keputusan C-Suite &amp; regulator</TableCell>
                  <TableCell class="text-right">
                    <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=metric&key=executive_share">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
                <TableRow class="hover:bg-muted/30 transition">
                  <TableCell class="text-center text-xs font-mono text-muted-foreground">3</TableCell>
                  <TableCell class="text-xs font-medium text-foreground">
                    {{ form.audiens.retention_rate_label }}
                  </TableCell>
                  <TableCell class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ form.audiens.retention_rate }}
                  </TableCell>
                  <TableCell class="text-xs text-muted-foreground">Tingkat retensi pembaca artikel mendalam</TableCell>
                  <TableCell class="text-right">
                    <Link href="/cms/kemitraan/sections/edit?tab=pitch&type=metric&key=retention_rate">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- TAB 2: SALURAN KEMITRAAN (TABLE)                         -->
      <!-- ======================================================== -->
      <div v-if="activeTab === 'channels'" class="space-y-4">
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow class="hover:bg-muted/40 border-b border-border">
                <TableHead class="w-14 text-center text-xs font-semibold">#</TableHead>
                <TableHead class="w-44 text-xs font-semibold">Kategori / Kicker</TableHead>
                <TableHead class="w-64 text-xs font-semibold">Judul Format Kemitraan</TableHead>
                <TableHead class="text-xs font-semibold">Deskripsi Penempatan</TableHead>
                <TableHead class="w-36 text-right text-xs font-semibold">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!form.channels || form.channels.length === 0">
                <TableCell colspan="5" class="h-32 text-center text-xs text-muted-foreground">
                  <div class="flex flex-col items-center justify-center gap-1.5">
                    <IconComponent name="Slideshow" :size="24" class="text-muted-foreground/60" />
                    <span>Belum ada saluran kemitraan. Klik "Tambah Saluran" untuk menambahkan.</span>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow
                v-for="(chan, idx) in form.channels"
                :key="chan.id || idx"
                class="hover:bg-muted/30 transition border-b border-border/50"
              >
                <TableCell class="text-center text-xs font-mono text-muted-foreground">
                  {{ idx + 1 }}
                </TableCell>
                <TableCell>
                  <Badge variant="outline" class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 font-bold text-[10px]">
                    {{ chan.kicker || 'Format Kemitraan' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-xs font-bold text-foreground">
                  {{ chan.title }}
                </TableCell>
                <TableCell class="text-xs text-muted-foreground leading-relaxed max-w-md">
                  {{ chan.desc }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link :href="`/cms/kemitraan/sections/${idx}/edit?tab=channels`">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="handleDeleteChannel(idx)"
                      class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
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

      <!-- ======================================================== -->
      <!-- TAB 3: ALUR KERJASAMA (TABLE)                            -->
      <!-- ======================================================== -->
      <div v-if="activeTab === 'workflow'" class="space-y-4">
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow class="hover:bg-muted/40 border-b border-border">
                <TableHead class="w-20 text-center text-xs font-semibold">Langkah</TableHead>
                <TableHead class="w-64 text-xs font-semibold">Judul Tahapan</TableHead>
                <TableHead class="text-xs font-semibold">Deskripsi Alur</TableHead>
                <TableHead class="w-36 text-right text-xs font-semibold">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!form.workflow || form.workflow.length === 0">
                <TableCell colspan="4" class="h-28 text-center text-xs text-muted-foreground">
                  Belum ada tahapan alur kerjasama. Klik "Tambah Tahapan" untuk menambahkan.
                </TableCell>
              </TableRow>
              <TableRow
                v-for="(st, idx) in form.workflow"
                :key="idx"
                class="hover:bg-muted/30 transition border-b border-border/50"
              >
                <TableCell class="text-center">
                  <span class="inline-flex w-7 h-7 rounded-full bg-red-600 text-white font-mono text-xs items-center justify-center font-bold shadow-xs">
                    {{ st.step || (idx + 1) }}
                  </span>
                </TableCell>
                <TableCell class="text-xs font-bold text-foreground">
                  {{ st.title }}
                </TableCell>
                <TableCell class="text-xs text-muted-foreground leading-relaxed max-w-md">
                  {{ st.desc }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link :href="`/cms/kemitraan/sections/${idx}/edit?tab=workflow&type=workflow`">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="handleDeleteWorkflow(idx)"
                      class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
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

      <!-- ======================================================== -->
      <!-- TAB 4: FAQ (TABLE)                                       -->
      <!-- ======================================================== -->
      <div v-if="activeTab === 'faq'" class="space-y-4">
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-xs">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow class="hover:bg-muted/40 border-b border-border">
                <TableHead class="w-14 text-center text-xs font-semibold">#</TableHead>
                <TableHead class="w-72 text-xs font-semibold">Pertanyaan (FAQ)</TableHead>
                <TableHead class="text-xs font-semibold">Jawaban</TableHead>
                <TableHead class="w-36 text-right text-xs font-semibold">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!form.faq || form.faq.length === 0">
                <TableCell colspan="4" class="h-28 text-center text-xs text-muted-foreground">
                  Belum ada data FAQ. Klik "Tambah FAQ" untuk menambahkan pertanyaan baru.
                </TableCell>
              </TableRow>
              <TableRow
                v-for="(item, idx) in form.faq"
                :key="idx"
                class="hover:bg-muted/30 transition border-b border-border/50"
              >
                <TableCell class="text-center text-xs font-mono text-muted-foreground">
                  {{ idx + 1 }}
                </TableCell>
                <TableCell class="text-xs font-bold text-foreground leading-snug">
                  {{ item.q }}
                </TableCell>
                <TableCell class="text-xs text-muted-foreground leading-relaxed max-w-md">
                  {{ item.a }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link :href="`/cms/kemitraan/sections/${idx}/edit?tab=faq&type=faq`">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-2.5 text-xs font-medium hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
                      >
                        <IconComponent name="Edit" :size="14" class="mr-1" />
                        Edit
                      </Button>
                    </Link>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      @click="handleDeleteFaq(idx)"
                      class="h-8 px-2.5 text-xs font-medium text-red-500 border-red-500/30 hover:bg-red-500/10 hover:text-red-600 hover:border-red-500 inline-flex items-center"
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

    </div>
  </div>
</template>
