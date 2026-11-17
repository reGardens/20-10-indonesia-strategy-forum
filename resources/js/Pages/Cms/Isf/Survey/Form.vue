<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import StatusSelectWithBadge from '@/Components/Cms/StatusSelectWithBadge.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: {
    type: Number,
    default: null,
  },
})

const isEdit = computed(() => Boolean(props.id))

const topicOptions = [
  { value: 'politik', label: 'Politik & Tata Kelola' },
  { value: 'ekonomi', label: 'Ekonomi & Kesejahteraan' },
  { value: 'sosial', label: 'Sosial & Budaya' },
  { value: 'hukum', label: 'Hukum & HAM' },
]

const regionOptions = [
  { value: 'nasional', label: 'Nasional' },
  { value: 'jawa', label: 'Pulau Jawa' },
  { value: 'sumatera', label: 'Sumatera' },
  { value: 'kalimantan', label: 'Kalimantan' },
  { value: 'sulawesi', label: 'Sulawesi' },
  { value: 'bali_nusra', label: 'Bali & Nusa Tenggara' },
  { value: 'maluku_papua', label: 'Maluku & Papua' },
]

const defaultChartData = {
  segments: [
    {
      key: 'overall',
      label: 'Keseluruhan',
      items: [
        { label: 'Puas / Positif', value: 72.5, color: '#16a34a' },
        { label: 'Tidak Puas / Negatif', value: 23.8, color: '#dc2626' },
        { label: 'Tidak Tahu / Rahasia', value: 3.7, color: '#94a3b8' },
      ],
    },
    {
      key: 'usia',
      label: 'Berdasarkan Usia',
      groups: [
        {
          name: 'Gen Z (17-27 thn)',
          items: [
            { label: 'Puas', value: 68.4, color: '#16a34a' },
            { label: 'Tidak Puas', value: 28.1, color: '#dc2626' },
          ],
        },
        {
          name: 'Milenial (28-43 thn)',
          items: [
            { label: 'Puas', value: 74.2, color: '#16a34a' },
            { label: 'Tidak Puas', value: 22.5, color: '#dc2626' },
          ],
        },
      ],
    },
  ],
}

const form = ref({
  title: '',
  slug: '',
  institution: '',
  is_isf: false,
  topic: 'politik',
  region: 'nasional',
  survey_period: '',
  release_date: new Date().toISOString().slice(0, 10),
  sample_size: 1200,
  margin_of_error: 2.8,
  confidence_level: 95.0,
  sampling_method: 'Multistage Random Sampling',
  interview_method: 'Wawancara Tatap Muka (Face to Face)',
  headline_stat_value: '',
  headline_stat_label: '',
  headline_stat_comparison: '',
  summary: '',
  expert_analysis: '',
  pdf_path: '',
  pdf_size: '',
  order: 0,
  is_featured: false,
  status: 'terbit',
})

const rawChartJson = ref(JSON.stringify(defaultChartData, null, 2))
const jsonError = ref('')

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

function autoSlug() {
  if (!isEdit.value && form.value.title && !form.value.slug) {
    form.value.slug = form.value.title
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
  }
}

function formatJson() {
  try {
    const parsed = JSON.parse(rawChartJson.value)
    rawChartJson.value = JSON.stringify(parsed, null, 2)
    jsonError.value = ''
  } catch (err) {
    jsonError.value = `JSON tidak valid: ${err.message}`
  }
}

function setTemplate(templateType) {
  if (templateType === 'overall') {
    const data = {
      segments: [
        {
          key: 'overall',
          label: 'Keseluruhan Responden',
          items: [
            { label: 'Setuju / Puas', value: 65.5, color: '#16a34a' },
            { label: 'Kurang / Tidak Puas', value: 28.0, color: '#dc2626' },
            { label: 'Tidak Tahu / Rahasia', value: 6.5, color: '#94a3b8' },
          ],
        },
      ],
    }
    rawChartJson.value = JSON.stringify(data, null, 2)
    jsonError.value = ''
  } else if (templateType === 'segments') {
    rawChartJson.value = JSON.stringify(defaultChartData, null, 2)
    jsonError.value = ''
  }
}

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.survei.get(props.id)
    form.value = {
      title: data.title || '',
      slug: data.slug || '',
      institution: data.institution || '',
      is_isf: Boolean(data.is_isf),
      topic: data.topic || 'politik',
      region: data.region || 'nasional',
      survey_period: data.survey_period || '',
      release_date: data.release_date ? data.release_date.slice(0, 10) : '',
      sample_size: data.sample_size !== null && data.sample_size !== undefined ? Number(data.sample_size) : 1200,
      margin_of_error: data.margin_of_error !== null && data.margin_of_error !== undefined ? Number(data.margin_of_error) : 2.8,
      confidence_level: data.confidence_level !== null && data.confidence_level !== undefined ? Number(data.confidence_level) : 95.0,
      sampling_method: data.sampling_method || '',
      interview_method: data.interview_method || '',
      headline_stat_value: data.headline_stat_value || '',
      headline_stat_label: data.headline_stat_label || '',
      headline_stat_comparison: data.headline_stat_comparison || '',
      summary: data.summary || '',
      expert_analysis: data.expert_analysis || '',
      pdf_path: data.pdf_path || '',
      pdf_size: data.pdf_size || '',
      order: Number(data.order) || 0,
      is_featured: Boolean(data.is_featured),
      status: data.status || 'terbit',
    }

    if (data.chart_data && typeof data.chart_data === 'object') {
      rawChartJson.value = JSON.stringify(data.chart_data, null, 2)
    } else if (typeof data.chart_data === 'string') {
      try {
        rawChartJson.value = JSON.stringify(JSON.parse(data.chart_data), null, 2)
      } catch {
        rawChartJson.value = data.chart_data
      }
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data survei.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}
  jsonError.value = ''

  let parsedChart = null
  if (rawChartJson.value.trim()) {
    try {
      parsedChart = JSON.parse(rawChartJson.value)
    } catch (err) {
      jsonError.value = `Format JSON visualisasi tidak valid: ${err.message}`
      saving.value = false
      alertError('Harap perbaiki format data grafik JSON sebelum menyimpan.')
      return
    }
  }

  const payload = {
    ...form.value,
    sample_size: form.value.sample_size ? Number(form.value.sample_size) : 0,
    margin_of_error: form.value.margin_of_error ? Number(form.value.margin_of_error) : null,
    confidence_level: form.value.confidence_level ? Number(form.value.confidence_level) : null,
    order: Number(form.value.order) || 0,
    chart_data: parsedChart,
  }

  try {
    if (isEdit.value) {
      await cmsResources.survei.update(props.id, payload)
      alertSuccess('Data survei berhasil diperbarui.')
    } else {
      await cmsResources.survei.create(payload)
      alertSuccess('Survei baru berhasil ditambahkan.')
    }
    router.visit('/cms/survei')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
      alertError('Harap periksa kembali inputan form yang bertanda merah.')
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan data survei.'))
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Head :title="`${isEdit ? 'Edit' : 'Tambah'} Survei — CMS ISF`" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/survei">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Survei Opini Publik' : 'Tambah Survei Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Kelola data survei opini publik, metodologi riset, visualisasi grafik, dan ulasan analis ISF.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/survei">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          size="sm"
          class="h-9 text-xs bg-red-700 hover:bg-red-800 text-white"
          :disabled="saving || loading"
          @click="handleSubmit"
        >
          <IconComponent v-if="saving" name="Loader2" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Data' }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" label="Memuat formulir survei..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" />

    <form v-else class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Section 1: Identitas & Sumber -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <IconComponent name="Info" :size="18" class="text-red-700" />
            Identitas & Lembaga Survei
          </CardTitle>
          <CardDescription>
            Informasi judul, lembaga penyelenggara, kategori isu, dan wilayah cakupan survei.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <Label for="title" class="text-xs font-semibold">Judul Survei <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="e.g. Evaluasi Publik terhadap 100 Hari Kinerja Kabinet & Perekonomian"
              class="mt-1"
              :class="{ 'border-red-500': errors.title }"
              required
              @blur="autoSlug"
            />
            <p v-if="errors.title" class="text-xs text-red-500 mt-1">{{ errors.title[0] }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="slug" class="text-xs font-semibold">Slug URL <span class="text-red-500">*</span></Label>
              <Input
                id="slug"
                v-model="form.slug"
                placeholder="e.g. evaluasi-publik-100-hari-kabinet"
                class="mt-1"
                :class="{ 'border-red-500': errors.slug }"
                required
              />
              <p v-if="errors.slug" class="text-xs text-red-500 mt-1">{{ errors.slug[0] }}</p>
            </div>

            <div>
              <Label for="institution" class="text-xs font-semibold">Lembaga Survei <span class="text-red-500">*</span></Label>
              <Input
                id="institution"
                v-model="form.institution"
                placeholder="e.g. Litbang Kompas, Indikator Politik, atau ISF Riset"
                class="mt-1"
                :class="{ 'border-red-500': errors.institution }"
                required
              />
              <p v-if="errors.institution" class="text-xs text-red-500 mt-1">{{ errors.institution[0] }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              id="is_isf"
              v-model="form.is_isf"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-red-600"
            />
            <Label for="is_isf" class="text-xs cursor-pointer select-none font-medium">
              Survei Independen Internal ISF (akan ditampilkan dengan badge khusus <span class="text-red-700 font-bold">ISF Riset</span>)
            </Label>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="topic" class="text-xs font-semibold">Kategori Isu <span class="text-red-500">*</span></Label>
              <Select v-model="form.topic">
                <SelectTrigger id="topic" class="mt-1 h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Kategori Isu..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="t in topicOptions"
                    :key="t.value"
                    :value="t.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ t.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.topic" class="text-xs text-red-500 mt-1">{{ errors.topic[0] }}</p>
            </div>

            <div>
              <Label for="region" class="text-xs font-semibold">Cakupan Wilayah <span class="text-red-500">*</span></Label>
              <Select v-model="form.region">
                <SelectTrigger id="region" class="mt-1 h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Cakupan Wilayah..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="r in regionOptions"
                    :key="r.value"
                    :value="r.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ r.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.region" class="text-xs text-red-500 mt-1">{{ errors.region[0] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="survey_period" class="text-xs font-semibold">Periode Lapangan</Label>
              <Input
                id="survey_period"
                v-model="form.survey_period"
                placeholder="e.g. 10 - 22 Oktober 2026"
                class="mt-1"
              />
            </div>

            <div>
              <Label for="release_date" class="text-xs font-semibold">Tanggal Rilis Publik</Label>
              <Input
                id="release_date"
                v-model="form.release_date"
                type="date"
                class="mt-1"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Section 2: Headline Statistic -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <IconComponent name="TrendingUp" :size="18" class="text-red-700" />
            Headline Statistic (Temuan Utama)
          </CardTitle>
          <CardDescription>
            Angka metrik sorotan yang langsung menarik perhatian pembaca di bagian atas halaman detail.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label for="headline_stat_value" class="text-xs font-semibold">Angka / Persentase</Label>
              <Input
                id="headline_stat_value"
                v-model="form.headline_stat_value"
                placeholder="e.g. 73.5% atau 6.8 / 10"
                class="mt-1"
              />
            </div>

            <div class="md:col-span-2">
              <Label for="headline_stat_label" class="text-xs font-semibold">Label Temuan Kunci</Label>
              <Input
                id="headline_stat_label"
                v-model="form.headline_stat_label"
                placeholder="e.g. Tingkat Kepuasan Publik terhadap Stabilitas Pasokan Energi"
                class="mt-1"
              />
            </div>
          </div>

          <div>
            <Label for="headline_stat_comparison" class="text-xs font-semibold">Keterangan Pembanding / Tren</Label>
            <Input
              id="headline_stat_comparison"
              v-model="form.headline_stat_comparison"
              placeholder="e.g. Naik +4.2% dibandingkan survei kuartal sebelumnya"
              class="mt-1"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Section 3: Parameter Metodologi -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <IconComponent name="ShieldCheck" :size="18" class="text-red-700" />
            Parameter Metodologi Riset
          </CardTitle>
          <CardDescription>
            Transparansi statistik untuk menjaga akuntabilitas dan kredibilitas riset.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label for="sample_size" class="text-xs font-semibold">Jumlah Sampel (Responden) <span class="text-red-500">*</span></Label>
              <Input
                id="sample_size"
                v-model.number="form.sample_size"
                type="number"
                min="0"
                placeholder="1200"
                class="mt-1"
                required
              />
            </div>

            <div>
              <Label for="margin_of_error" class="text-xs font-semibold">Margin of Error (± %)</Label>
              <Input
                id="margin_of_error"
                v-model.number="form.margin_of_error"
                type="number"
                step="0.01"
                placeholder="2.83"
                class="mt-1"
              />
            </div>

            <div>
              <Label for="confidence_level" class="text-xs font-semibold">Tingkat Kepercayaan (%)</Label>
              <Input
                id="confidence_level"
                v-model.number="form.confidence_level"
                type="number"
                step="0.1"
                placeholder="95.0"
                class="mt-1"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="sampling_method" class="text-xs font-semibold">Metode Penarikan Sampel</Label>
              <Input
                id="sampling_method"
                v-model="form.sampling_method"
                placeholder="e.g. Multistage Random Sampling"
                class="mt-1"
              />
            </div>

            <div>
              <Label for="interview_method" class="text-xs font-semibold">Metode Pengumpulan Data</Label>
              <Input
                id="interview_method"
                v-model="form.interview_method"
                placeholder="e.g. Wawancara Tatap Muka dengan Kuesioner Terstruktur"
                class="mt-1"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Section 4: Visualisasi Grafik Interaktif (chart_data) -->
      <Card>
        <CardHeader class="pb-3">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-base flex items-center gap-2">
                <IconComponent name="ChartBar" :size="18" class="text-red-700" />
                Data Visualisasi Grafik Interaktif (JSON Segmen)
              </CardTitle>
              <CardDescription>
                Struktur JSON untuk grafik segmen (keseluruhan, usia, gender, wilayah) dan tabel alternatif screen reader.
              </CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <Button type="button" variant="outline" size="sm" class="text-xs h-7" @click="setTemplate('overall')">
                Template Keseluruhan
              </Button>
              <Button type="button" variant="outline" size="sm" class="text-xs h-7" @click="setTemplate('segments')">
                Template Multi-Segmen
              </Button>
              <Button type="button" variant="outline" size="sm" class="text-xs h-7" @click="formatJson">
                Format JSON
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="jsonError" class="p-2.5 rounded-lg bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 text-xs border border-red-200 dark:border-red-900/60 flex items-center gap-2">
            <IconComponent name="AlertCircle" :size="14" />
            <span>{{ jsonError }}</span>
          </div>

          <textarea
            v-model="rawChartJson"
            rows="12"
            class="w-full rounded-md border border-input bg-muted/20 px-3 py-2 text-xs font-mono shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            placeholder="{ segments: [ ... ] }"
            @blur="formatJson"
          ></textarea>
          <p class="text-[11px] text-muted-foreground">
            Tips: Gunakan properti <code>key</code>, <code>label</code>, dan <code>items</code> (dengan label, value, color) atau <code>groups</code> untuk sub-segmen.
          </p>
        </CardContent>
      </Card>

      <!-- Section 5: Ringkasan & Analisis Pakar -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <IconComponent name="FileText" :size="18" class="text-red-700" />
            Ringkasan & Analisis Pakar ISF
          </CardTitle>
          <CardDescription>
            Naskah analitis mendalam dari tim periset ISF mengenai signifikansi data survei.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <Label for="summary" class="text-xs font-semibold">Ringkasan Eksekutif Temuan</Label>
            <textarea
              id="summary"
              v-model="form.summary"
              rows="3"
              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              placeholder="Deskripsi ringkas gambaran umum temuan survei..."
            ></textarea>
          </div>

          <div>
            <Label for="expert_analysis" class="text-xs font-semibold">Analisis Pakar / Ulasan Peneliti ISF</Label>
            <textarea
              id="expert_analysis"
              v-model="form.expert_analysis"
              rows="6"
              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
              placeholder="Ulasan kritis, implikasi strategis kebijakan, dan dinamika opini masyarakat..."
            ></textarea>
          </div>
        </CardContent>
      </Card>

      <!-- Section 6: Berkas PDF & Publikasi -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <IconComponent name="Download" :size="18" class="text-red-700" />
            Laporan PDF & Pengaturan Publikasi
          </CardTitle>
          <CardDescription>
            Dokumen laporan unduhan, urutan prioritas, dan status terbit.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="pdf_path" class="text-xs font-semibold">Path / URL Berkas PDF</Label>
              <Input
                id="pdf_path"
                v-model="form.pdf_path"
                placeholder="e.g. /docs/survei/laporan-lengkap-evaluasi-kabinet.pdf"
                class="mt-1"
              />
            </div>

            <div>
              <Label for="pdf_size" class="text-xs font-semibold">Ukuran Berkas PDF</Label>
              <Input
                id="pdf_size"
                v-model="form.pdf_size"
                placeholder="e.g. 3.4 MB"
                class="mt-1"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            <div>
              <Label for="order" class="text-xs font-semibold">Urutan Prioritas</Label>
              <Input
                id="order"
                v-model.number="form.order"
                type="number"
                min="0"
                class="mt-1"
              />
            </div>

            <div>
              <Label for="status" class="text-xs font-semibold">Status Publikasi</Label>
              <StatusSelectWithBadge
                id="status"
                v-model="form.status"
                class="mt-1"
              />
            </div>

            <div class="flex items-center gap-2 pt-6">
              <input
                id="is_featured"
                v-model="form.is_featured"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-red-600"
              />
              <Label for="is_featured" class="text-xs cursor-pointer select-none font-medium">
                Jadikan Survei Utama (Featured Hero)
              </Label>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom action bar -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
        <Link href="/cms/survei">
          <Button type="button" variant="outline" size="sm">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5"
          :disabled="saving"
        >
          <IconComponent v-if="saving" name="Refresh" :size="16" class="animate-spin" />
          <IconComponent v-else name="DeviceFloppy" :size="16" />
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Survei' : 'Simpan Data Survei') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>

