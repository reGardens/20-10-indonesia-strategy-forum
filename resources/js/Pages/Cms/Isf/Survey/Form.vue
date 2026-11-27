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
  editorial_intro: '',
  estimated_minutes: 5,
  is_member_active: false,
  questions: [],
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
      editorial_intro: data.editorial_intro || '',
      estimated_minutes: data.estimated_minutes !== null && data.estimated_minutes !== undefined ? Number(data.estimated_minutes) : 5,
      is_member_active: Boolean(data.is_member_active),
      questions: Array.isArray(data.questions) ? JSON.parse(JSON.stringify(data.questions)) : [],
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

function addQuestion(type = 'scale') {
  if (!Array.isArray(form.value.questions)) {
    form.value.questions = []
  }
  const id = 'q_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4)
  const newQ = {
    id,
    type,
    text: '',
    intro: '',
    required: true,
    min_label: 'Sangat Tidak Setuju',
    max_label: 'Sangat Setuju',
    options: type === 'radio' || type === 'checkbox' ? ['Pilihan 1', 'Pilihan 2'] : [],
  }
  form.value.questions.push(newQ)
}

function removeQuestion(index) {
  form.value.questions.splice(index, 1)
}

function moveQuestion(index, direction) {
  const target = index + direction
  if (target < 0 || target >= form.value.questions.length) return
  const temp = form.value.questions[index]
  form.value.questions[index] = form.value.questions[target]
  form.value.questions[target] = temp
}

function addOption(q) {
  if (!Array.isArray(q.options)) q.options = []
  q.options.push(`Pilihan ${q.options.length + 1}`)
}

function removeOption(q, optIndex) {
  q.options.splice(optIndex, 1)
}

function loadSampleQuestions() {
  form.value.editorial_intro = 'Berdasarkan hasil survei ISF sebelumnya, dinamika kepemimpinan nasional menuju 2029 menjadi perhatian utama publik. Kami mengundang partisipasi Anda untuk memberikan penilaian objektif dan berbasis fakta.'
  form.value.estimated_minutes = 5
  form.value.is_member_active = true
  form.value.questions = [
    {
      id: 'q1',
      type: 'scale',
      text: 'Bagaimana penilaian Anda terhadap kesiapan dan kepemimpinan figur muda dalam memimpin kebijakan strategis nasional menuju 2029?',
      intro: 'Gunakan skala 1 (Sangat Tidak Setuju / Kurang Siap) hingga 10 (Sangat Setuju / Sangat Siap).',
      required: true,
      min_label: 'Sangat Tidak Setuju',
      max_label: 'Sangat Setuju',
      options: [],
    },
    {
      id: 'q2',
      type: 'radio',
      text: 'Menurut Anda, sektor manakah yang paling mendesak membutuhkan reformasi kebijakan dalam 5 tahun mendatang?',
      intro: 'Pilih satu fokus sektor utama yang paling krusial menurut Anda.',
      required: true,
      options: [
        'Ekonomi & Lapangan Kerja',
        'Penegakan Hukum & Pemberantasan Korupsi',
        'Pendidikan & Kualitas SDM',
        'Kedaulatan Energi & Lingkungan Hidup',
        'Kesehatan & Kesejahteraan Sosial',
      ],
    },
    {
      id: 'q3',
      type: 'checkbox',
      text: 'Karakter atau kriteria apa yang paling Anda harapkan dari calon pemimpin nasional mendatang? (Bisa pilih lebih dari satu)',
      intro: 'Pilih seluruh kriteria yang relevan bagi Anda.',
      required: true,
      options: [
        'Integritas & Bebas Korupsi',
        'Pemahaman Geopolitik & Diplomasi Global',
        'Kemampuan Eksekusi Kebijakan Cepat',
        'Kedekatan & Empati terhadap Rakyat Kecil',
        'Visi Teknologi & Ekonomi Digital',
      ],
    },
    {
      id: 'q4',
      type: 'text',
      text: 'Apa saran atau aspirasi strategis yang ingin Anda sampaikan kepada para perumus kebijakan publik nasional?',
      intro: 'Tuliskan pandangan objektif Anda secara ringkas dan konstruktif.',
      required: false,
      options: [],
    },
  ]
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

      <!-- Section: Kuisioner Partisipatif Dinamis (Member & Publik) -->
      <Card>
        <CardHeader class="pb-3 border-b border-border">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
              <CardTitle class="text-base flex items-center gap-2">
                <IconComponent name="HelpCircle" :size="18" class="text-red-700" />
                Kuisioner Partisipatif Dinamis (Survei Pengunjung & Member)
              </CardTitle>
              <CardDescription>
                Konfigurasikan kuisioner survei interaktif yang dapat diisi oleh pembaca atau member di Landing Page & Portal Anggota.
              </CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="text-xs border-dashed text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20"
                @click="loadSampleQuestions"
              >
                <IconComponent name="Sparkles" :size="14" class="mr-1.5 text-red-600" />
                Muat Contoh Kuisioner ISF
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-5 pt-4">
          <!-- Toggle Active & Estimasi Waktu -->
          <div class="p-4 rounded-lg bg-muted/40 border border-border/80 space-y-4">
            <div class="flex items-start gap-3">
              <input
                id="is_member_active"
                v-model="form.is_member_active"
                type="checkbox"
                class="mt-1 h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-red-600"
              />
              <div class="flex-1">
                <Label for="is_member_active" class="text-sm font-semibold cursor-pointer select-none">
                  Aktifkan Formulir Pengisian untuk Member & Pembaca
                </Label>
                <p class="text-xs text-muted-foreground mt-0.5">
                  Jika dicentang, survei ini akan memunculkan tab/halaman pengisian interaktif di Landing Page (/survei/{slug}) dan katalog survei member.
                </p>
              </div>
            </div>

            <div v-if="form.is_member_active" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-border/60">
              <div>
                <Label for="estimated_minutes" class="text-xs font-semibold">Estimasi Waktu Pengisian (Menit)</Label>
                <Input
                  id="estimated_minutes"
                  v-model.number="form.estimated_minutes"
                  type="number"
                  min="1"
                  max="60"
                  class="mt-1"
                  placeholder="5"
                />
              </div>
              <div class="md:col-span-2">
                <Label for="editorial_intro" class="text-xs font-semibold">Paragraf Konteks Editorial (Intro)</Label>
                <textarea
                  id="editorial_intro"
                  v-model="form.editorial_intro"
                  rows="2"
                  class="mt-1 w-full rounded-md border border-input bg-background px-3 py-1.5 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                  placeholder="Contoh: Berdasarkan survei ISF sebelumnya, 60% publik... Bagaimana pandangan Anda?"
                ></textarea>
                <p class="text-[11px] text-muted-foreground mt-0.5">
                  Konteks objektif dari dewan redaksi sebelum responden menjawab kuisioner.
                </p>
              </div>
            </div>
          </div>

          <!-- Question Builder List -->
          <div v-if="form.is_member_active" class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                Daftar Pertanyaan ({{ form.questions?.length || 0 }})
              </h4>
              <div class="flex items-center gap-1.5">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-7 text-[11px]"
                  @click="addQuestion('scale')"
                >
                  + Skala Linier (1-10)
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-7 text-[11px]"
                  @click="addQuestion('radio')"
                >
                  + Pilihan Ganda
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-7 text-[11px]"
                  @click="addQuestion('checkbox')"
                >
                  + Ceklis (Multi)
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-7 text-[11px]"
                  @click="addQuestion('text')"
                >
                  + Isian Singkat
                </Button>
              </div>
            </div>

            <div v-if="!form.questions || form.questions.length === 0" class="p-8 text-center border-2 border-dashed border-border rounded-lg">
              <IconComponent name="HelpCircle" :size="32" class="mx-auto text-muted-foreground mb-2" />
              <p class="text-sm font-medium text-foreground">Belum ada pertanyaan pada survei ini</p>
              <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
                Tambahkan pertanyaan baru di atas atau klik tombol "Muat Contoh Kuisioner ISF" untuk memulai dari template standar redaksi.
              </p>
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(q, qIndex) in form.questions"
                :key="q.id || qIndex"
                class="p-4 rounded-lg border border-border bg-card shadow-sm space-y-3"
              >
                <!-- Question Top Bar -->
                <div class="flex items-center justify-between gap-2 pb-2 border-b border-border/60">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-700 text-white text-xs font-bold">
                      {{ qIndex + 1 }}
                    </span>
                    <select
                      v-model="q.type"
                      class="h-8 rounded-md border border-input bg-background px-2 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                      <option value="scale">Skala Linier (1-10)</option>
                      <option value="radio">Pilihan Ganda (Satu Pilihan)</option>
                      <option value="checkbox">Ceklis (Banyak Pilihan)</option>
                      <option value="text">Jawaban Singkat / Esai</option>
                    </select>
                    <label class="flex items-center gap-1 text-xs text-muted-foreground cursor-pointer select-none">
                      <input
                        type="checkbox"
                        v-model="q.required"
                        class="h-3.5 w-3.5 rounded border-gray-300 text-red-700"
                      />
                      Wajib diisi
                    </label>
                  </div>

                  <div class="flex items-center gap-1">
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-7 w-7 p-0"
                      :disabled="qIndex === 0"
                      @click="moveQuestion(qIndex, -1)"
                      title="Geser ke atas"
                    >
                      <IconComponent name="ChevronUp" :size="14" />
                    </Button>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-7 w-7 p-0"
                      :disabled="qIndex === form.questions.length - 1"
                      @click="moveQuestion(qIndex, 1)"
                      title="Geser ke bawah"
                    >
                      <IconComponent name="ChevronDown" :size="14" />
                    </Button>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-7 w-7 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20"
                      @click="removeQuestion(qIndex)"
                      title="Hapus pertanyaan"
                    >
                      <IconComponent name="Trash2" :size="14" />
                    </Button>
                  </div>
                </div>

                <!-- Question Text & Intro -->
                <div class="space-y-2">
                  <div>
                    <Label class="text-[11px] font-semibold">Teks Pertanyaan <span class="text-red-500">*</span></Label>
                    <Input
                      v-model="q.text"
                      placeholder="e.g. Seberapa setuju Anda dengan arah kebijakan hilirisasi saat ini?"
                      class="mt-0.5 text-xs"
                      required
                    />
                  </div>
                  <div>
                    <Label class="text-[11px] font-semibold text-muted-foreground">Catatan / Petunjuk Konteks (Opsional)</Label>
                    <Input
                      v-model="q.intro"
                      placeholder="e.g. Nilai 1 berarti Sangat Tidak Setuju, 10 berarti Sangat Setuju"
                      class="mt-0.5 text-xs"
                    />
                  </div>
                </div>

                <!-- Question Config based on Type -->
                <!-- Type: Scale (1-10) -->
                <div v-if="q.type === 'scale'" class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 bg-muted/20 p-2.5 rounded border border-border/40">
                  <div>
                    <Label class="text-[11px] font-medium text-muted-foreground">Label Nilai Minimum (Skala 1)</Label>
                    <Input
                      v-model="q.min_label"
                      placeholder="Sangat Tidak Setuju"
                      class="mt-0.5 text-xs h-8"
                    />
                  </div>
                  <div>
                    <Label class="text-[11px] font-medium text-muted-foreground">Label Nilai Maksimum (Skala 10)</Label>
                    <Input
                      v-model="q.max_label"
                      placeholder="Sangat Setuju"
                      class="mt-0.5 text-xs h-8"
                    />
                  </div>
                </div>

                <!-- Type: Radio or Checkbox Options -->
                <div v-else-if="q.type === 'radio' || q.type === 'checkbox'" class="space-y-2 pt-2 bg-muted/20 p-2.5 rounded border border-border/40">
                  <div class="flex items-center justify-between">
                    <Label class="text-[11px] font-semibold">Daftar Pilihan Jawaban</Label>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-6 text-[11px] text-red-700 hover:text-red-800"
                      @click="addOption(q)"
                    >
                      + Tambah Pilihan
                    </Button>
                  </div>

                  <div class="space-y-1.5">
                    <div
                      v-for="(opt, optIdx) in q.options"
                      :key="optIdx"
                      class="flex items-center gap-2"
                    >
                      <span class="text-xs text-muted-foreground font-mono w-4">{{ optIdx + 1 }}.</span>
                      <Input
                        v-model="q.options[optIdx]"
                        placeholder="Ketik teks opsi jawaban..."
                        class="h-8 text-xs flex-1"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0 text-muted-foreground hover:text-red-600"
                        :disabled="q.options.length <= 1"
                        @click="removeOption(q, optIdx)"
                      >
                        <IconComponent name="X" :size="14" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
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

