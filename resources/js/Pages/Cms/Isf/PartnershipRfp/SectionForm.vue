<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const page = usePage()

// Read query params & path params
function getParams() {
  const url = page.url || (typeof window !== 'undefined' ? window.location.href : '')
  try {
    const u = new URL(url, 'http://localhost')
    const pathParts = u.pathname.split('/').filter(Boolean)
    // /cms/kemitraan/sections/:id/edit
    let pathId = null
    const editIndex = pathParts.indexOf('edit')
    if (editIndex > 0 && /^\d+$/.test(pathParts[editIndex - 1])) {
      pathId = parseInt(pathParts[editIndex - 1], 10)
    }

    return {
      tab: u.searchParams.get('tab') || 'channels',
      type: u.searchParams.get('type') || (
        u.searchParams.get('tab') === 'channels' ? 'channel' : (
          u.searchParams.get('tab') === 'pitch' ? 'pitch' : (
            u.searchParams.get('tab') === 'workflow' ? 'workflow' : 'faq'
          )
        )
      ),
      id: pathId !== null ? pathId : (u.searchParams.has('id') ? parseInt(u.searchParams.get('id'), 10) : null),
      key: u.searchParams.get('key') || '',
    }
  } catch {
    return { tab: 'channels', type: 'channel', id: null, key: '' }
  }
}

const params = getParams()
const tab = ref(params.tab)
const formType = ref(params.type)
const editIndex = ref(params.id)
const metricKey = ref(params.key)

const isEdit = computed(() => editIndex.value !== null || (formType.value === 'pitch' || formType.value === 'metric'))

const backHref = computed(() => {
  return `/cms/kemitraan/sections?tab=${tab.value}`
})

const meta = computed(() => {
  if (formType.value === 'channel') {
    return {
      title: isEdit.value ? 'Edit Saluran Kemitraan' : 'Tambah Saluran Kemitraan Baru',
      desc: isEdit.value ? `Perbarui format penempatan kemitraan strategis.` : 'Tambahkan paket format kemitraan strategis baru untuk ditampilkan di /media-kit/.',
      cardTitle: 'Informasi Saluran & Format Kemitraan',
      cardDesc: 'Format promosi independen untuk para pemangku kepentingan kebijakan.',
    }
  }
  if (formType.value === 'workflow') {
    return {
      title: isEdit.value ? 'Edit Tahapan Alur Kerjasama' : 'Tambah Tahapan Alur Kerjasama',
      desc: isEdit.value ? `Perbarui langkah alur kerjasama kemitraan.` : 'Tambahkan tahapan alur kolaborasi transparan baru.',
      cardTitle: 'Informasi Tahapan Alur',
      cardDesc: 'Langkah terstruktur mulai dari RFP hingga penayangan materi.',
    }
  }
  if (formType.value === 'faq') {
    return {
      title: isEdit.value ? 'Edit Pertanyaan FAQ' : 'Tambah Pertanyaan FAQ Baru',
      desc: isEdit.value ? `Perbarui pertanyaan dan jawaban kemitraan.` : 'Tambahkan entri tanya jawab baru seputar kebijakan kemitraan ISF.',
      cardTitle: 'Informasi Tanya Jawab (FAQ)',
      cardDesc: 'Penjelasan transparan bagi calon mitra pengaju proposal.',
    }
  }
  if (formType.value === 'pitch') {
    return {
      title: 'Edit Hero Pitch Halaman Kemitraan',
      desc: 'Perbarui judul headline dan teks deskripsi utama halaman /media-kit/.',
      cardTitle: 'Konten Hero Pitch',
      cardDesc: 'Pesan pengantar pembuka untuk meyakinkan calon mitra institusional.',
    }
  }
  if (formType.value === 'metric') {
    return {
      title: 'Edit Metrik Audiens Eksekutif',
      desc: 'Perbarui angka statistik dan label metrik pembaca terverifikasi.',
      cardTitle: 'Statistik Metrik Audiens',
      cardDesc: 'Angka kredibel yang ditampilkan pada baris metrik kunci.',
    }
  }
  return {
    title: 'Form Konten Kemitraan',
    desc: 'Kelola data konten kemitraan.',
    cardTitle: 'Data Konten',
    cardDesc: '',
  }
})

// Entire raw sections from DB
const rawSections = ref({
  pitch: { badge: '', title: '', deck: '' },
  audiens: {
    total_monthly_readers: '',
    total_monthly_readers_label: '',
    executive_share: '',
    executive_share_label: '',
    retention_rate: '',
    retention_rate_label: '',
  },
  channels: [],
  workflow: [],
  faq: [],
})

// Specific form models
const channelForm = ref({ id: '', kicker: '', title: '', desc: '' })
const workflowForm = ref({ step: 1, title: '', desc: '' })
const faqForm = ref({ q: '', a: '' })
const pitchForm = ref({ badge: '', title: '', deck: '' })
const metricForm = ref({ key: '', label: '', value: '' })

const loading = ref(true)
const saving = ref(false)
const loadError = ref('')

async function loadData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await cmsResources.kemitraan.getSections()
    const data = res?.data || {}
    rawSections.value = {
      pitch: data.pitch || rawSections.value.pitch,
      audiens: data.audiens || rawSections.value.audiens,
      channels: Array.isArray(data.channels) ? data.channels : [],
      workflow: Array.isArray(data.workflow) ? data.workflow : [],
      faq: Array.isArray(data.faq) ? data.faq : [],
    }

    // Populate active subform
    if (formType.value === 'channel') {
      if (editIndex.value !== null && rawSections.value.channels[editIndex.value]) {
        channelForm.value = { ...rawSections.value.channels[editIndex.value] }
      } else {
        channelForm.value = {
          id: `channel_${Date.now()}`,
          kicker: '',
          title: '',
          desc: '',
        }
      }
    } else if (formType.value === 'workflow') {
      if (editIndex.value !== null && rawSections.value.workflow[editIndex.value]) {
        workflowForm.value = { ...rawSections.value.workflow[editIndex.value] }
      } else {
        workflowForm.value = {
          step: rawSections.value.workflow.length + 1,
          title: '',
          desc: '',
        }
      }
    } else if (formType.value === 'faq') {
      if (editIndex.value !== null && rawSections.value.faq[editIndex.value]) {
        faqForm.value = { ...rawSections.value.faq[editIndex.value] }
      } else {
        faqForm.value = { q: '', a: '' }
      }
    } else if (formType.value === 'pitch') {
      pitchForm.value = { ...rawSections.value.pitch }
    } else if (formType.value === 'metric') {
      const k = metricKey.value || 'total_monthly_readers'
      metricForm.value = {
        key: k,
        label: rawSections.value.audiens[`${k}_label`] || '',
        value: rawSections.value.audiens[k] || '',
      }
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data formulir kemitraan.')
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    if (formType.value === 'channel') {
      if (!channelForm.value.title.trim()) {
        alertError('Judul format kemitraan wajib diisi.')
        saving.value = false
        return
      }
      if (editIndex.value !== null && editIndex.value >= 0) {
        rawSections.value.channels[editIndex.value] = { ...channelForm.value }
      } else {
        rawSections.value.channels.push({ ...channelForm.value })
      }
    } else if (formType.value === 'workflow') {
      if (!workflowForm.value.title.trim()) {
        alertError('Judul tahapan alur wajib diisi.')
        saving.value = false
        return
      }
      if (editIndex.value !== null && editIndex.value >= 0) {
        rawSections.value.workflow[editIndex.value] = { ...workflowForm.value }
      } else {
        rawSections.value.workflow.push({ ...workflowForm.value })
      }
    } else if (formType.value === 'faq') {
      if (!faqForm.value.q.trim()) {
        alertError('Pertanyaan FAQ wajib diisi.')
        saving.value = false
        return
      }
      if (!faqForm.value.a.trim()) {
        alertError('Jawaban FAQ wajib diisi.')
        saving.value = false
        return
      }
      if (editIndex.value !== null && editIndex.value >= 0) {
        rawSections.value.faq[editIndex.value] = { ...faqForm.value }
      } else {
        rawSections.value.faq.push({ ...faqForm.value })
      }
    } else if (formType.value === 'pitch') {
      if (!pitchForm.value.title.trim()) {
        alertError('Judul utama headline wajib diisi.')
        saving.value = false
        return
      }
      rawSections.value.pitch = { ...pitchForm.value }
    } else if (formType.value === 'metric') {
      if (!metricForm.value.value.trim()) {
        alertError('Nilai angka statistik wajib diisi.')
        saving.value = false
        return
      }
      const k = metricForm.value.key
      rawSections.value.audiens[k] = metricForm.value.value
      rawSections.value.audiens[`${k}_label`] = metricForm.value.label
    }

    await cmsResources.kemitraan.updateSections({
      pitch: rawSections.value.pitch,
      audiens: rawSections.value.audiens,
      channels: rawSections.value.channels,
      workflow: rawSections.value.workflow,
      faq: rawSections.value.faq,
    })

    alertSuccess('Data kemitraan berhasil disimpan.')
    router.visit(backHref.value)
  } catch (err) {
    alertError(errorMessage(err, 'Gagal menyimpan data kemitraan.'))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head :title="`${meta.title} — Indonesia Strategy Forum`" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button & Action Buttons -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link :href="backHref">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ meta.title }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ meta.desc }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <Link :href="backHref">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSave"
          :disabled="saving || loading"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm inline-flex items-center"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Data' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat formulir kemitraan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Form Content Container -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card class="bg-card border-border shadow-sm">
          <CardHeader class="pb-4">
            <CardTitle class="text-base font-semibold text-foreground">
              {{ meta.cardTitle }}
            </CardTitle>
            <CardDescription class="text-xs text-muted-foreground">
              {{ meta.cardDesc }}
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">

            <!-- FORM 1: SALURAN KEMITRAAN -->
            <template v-if="formType === 'channel'">
              <div class="space-y-1.5">
                <Label for="channel_kicker" class="text-xs font-medium text-foreground">
                  Kategori / Kicker <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="channel_kicker"
                  v-model="channelForm.kicker"
                  placeholder="Contoh: Display Banner / Advertorial / Executive Roundtable"
                  class="text-xs"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="channel_title" class="text-xs font-medium text-foreground">
                  Judul Format Kemitraan <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="channel_title"
                  v-model="channelForm.title"
                  placeholder="Contoh: Display Banner Strategis"
                  class="text-xs font-semibold"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="channel_desc" class="text-xs font-medium text-foreground">
                  Deskripsi Format Penempatan <span class="text-red-500">*</span>
                </Label>
                <textarea
                  id="channel_desc"
                  rows="4"
                  v-model="channelForm.desc"
                  placeholder="Jelaskan spesifikasi, audiens sasaran, dan ketentuan format kemitraan..."
                  class="w-full bg-background border border-border focus:border-red-500 focus:ring-1 focus:ring-red-500 p-3 text-xs rounded-md outline-none transition"
                ></textarea>
              </div>
            </template>

            <!-- FORM 2: WORKFLOW (ALUR KERJASAMA) -->
            <template v-else-if="formType === 'workflow'">
              <div class="space-y-1.5">
                <Label for="workflow_step" class="text-xs font-medium text-foreground">
                  Nomor Langkah (Step) <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="workflow_step"
                  type="number"
                  v-model.number="workflowForm.step"
                  class="text-xs font-mono w-28"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="workflow_title" class="text-xs font-medium text-foreground">
                  Judul Tahapan <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="workflow_title"
                  v-model="workflowForm.title"
                  placeholder="Contoh: Pengajuan RFP & Kebutuhan Strategis"
                  class="text-xs font-semibold"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="workflow_desc" class="text-xs font-medium text-foreground">
                  Deskripsi Alur Tahapan <span class="text-red-500">*</span>
                </Label>
                <textarea
                  id="workflow_desc"
                  rows="4"
                  v-model="workflowForm.desc"
                  placeholder="Jelaskan secara ringkas proses yang berlangsung pada tahapan ini..."
                  class="w-full bg-background border border-border focus:border-red-500 focus:ring-1 focus:ring-red-500 p-3 text-xs rounded-md outline-none transition"
                ></textarea>
              </div>
            </template>

            <!-- FORM 3: FAQ -->
            <template v-else-if="formType === 'faq'">
              <div class="space-y-1.5">
                <Label for="faq_q" class="text-xs font-medium text-foreground">
                  Pertanyaan (FAQ) <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="faq_q"
                  v-model="faqForm.q"
                  placeholder="Contoh: Mengapa ISF tidak menyediakan rate card instan?"
                  class="text-xs font-semibold"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="faq_a" class="text-xs font-medium text-foreground">
                  Jawaban Lengkap <span class="text-red-500">*</span>
                </Label>
                <textarea
                  id="faq_a"
                  rows="5"
                  v-model="faqForm.a"
                  placeholder="Tuliskan jawaban penjelasan yang transparan dan informatif..."
                  class="w-full bg-background border border-border focus:border-red-500 focus:ring-1 focus:ring-red-500 p-3 text-xs rounded-md outline-none transition"
                ></textarea>
              </div>
            </template>

            <!-- FORM 4: PITCH -->
            <template v-else-if="formType === 'pitch'">
              <div class="space-y-1.5">
                <Label for="pitch_badge" class="text-xs font-medium text-foreground">
                  Badge Kicker
                </Label>
                <Input
                  id="pitch_badge"
                  v-model="pitchForm.badge"
                  placeholder="Contoh: Informasi Iklan & Kemitraan"
                  class="text-xs"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="pitch_title" class="text-xs font-medium text-foreground">
                  Judul Utama (Headline) <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="pitch_title"
                  v-model="pitchForm.title"
                  placeholder="Contoh: Menghubungkan Gagasan, Kebijakan, dan Pengambil Keputusan Nasional"
                  class="text-xs font-semibold"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="pitch_deck" class="text-xs font-medium text-foreground">
                  Deskripsi Penjelas (Deck) <span class="text-red-500">*</span>
                </Label>
                <textarea
                  id="pitch_deck"
                  rows="4"
                  v-model="pitchForm.deck"
                  placeholder="Tuliskan paragraf pengantar deck..."
                  class="w-full bg-background border border-border focus:border-red-500 focus:ring-1 focus:ring-red-500 p-3 text-xs rounded-md outline-none transition"
                ></textarea>
              </div>
            </template>

            <!-- FORM 5: METRIC -->
            <template v-else-if="formType === 'metric'">
              <div class="space-y-1.5">
                <Label for="metric_label" class="text-xs font-medium text-foreground">
                  Label / Nama Keterangan Metrik <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="metric_label"
                  v-model="metricForm.label"
                  placeholder="Contoh: Pembaca Terverifikasi / Bulan"
                  class="text-xs font-medium"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="metric_value" class="text-xs font-medium text-foreground">
                  Nilai Statistik (Angka / Persentase) <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="metric_value"
                  v-model="metricForm.value"
                  placeholder="Contoh: 150.000+"
                  class="text-xs font-mono font-bold"
                />
              </div>
            </template>

          </CardContent>
        </Card>
      </div>

      <!-- Right Column: Sidebar Info / Guide Card -->
      <div class="space-y-6">
        <Card class="bg-card border-border shadow-sm">
          <CardHeader class="pb-3">
            <CardTitle class="text-sm font-semibold text-foreground flex items-center gap-2">
              <IconComponent name="InfoCircle" :size="16" class="text-red-500" />
              <span>Petunjuk Standar</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="text-xs text-muted-foreground space-y-2.5 leading-relaxed">
            <p>
              Data yang Anda simpan akan langsung disinkronkan ke halaman publik <code class="text-foreground font-mono">/media-kit/</code>.
            </p>
            <p>
              Pastikan penulisan judul dan deskripsi ringkas, lugas, serta mencerminkan etika independensi editorial ISF tanpa rate card instan.
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>

