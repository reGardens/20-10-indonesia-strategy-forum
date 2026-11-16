<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources, errorMessage } from '@/api/services/isfCmsService'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Input,
  Label,
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

const form = ref({
  program_name: '',
  slug: '',
  sector: 'ekonomi',
  promise: '',
  realization: '',
  progress_percent: 0,
  status_label: 'dalam_proses',
  source_title: '',
  source_url: '',
  last_updated_date: new Date().toISOString().slice(0, 10),
  order: 0,
  status: 'terbit',
})

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

const sectorOptions = [
  { value: 'ekonomi', label: 'Ekonomi & Fiskal' },
  { value: 'kesehatan', label: 'Kesehatan Masyarakat' },
  { value: 'pendidikan', label: 'Pendidikan & Riset' },
  { value: 'pertahanan', label: 'Pertahanan & Keamanan' },
  { value: 'lingkungan', label: 'Lingkungan & Energi' },
]

const statusLabelOptions = [
  { value: 'tercapai', label: 'Tercapai (Memenuhi / Melampaui Target)' },
  { value: 'dalam_proses', label: 'Dalam Proses (Sesuai Jadwal Berjalan)' },
  { value: 'tertunda', label: 'Tertunda (Mengalami Hambatan Realisasi)' },
  { value: 'perlu_evaluasi', label: 'Perlu Evaluasi (Kritikal / Deviasi Signifikan)' },
]

function autoSlug() {
  if (!isEdit.value && form.value.program_name && !form.value.slug) {
    form.value.slug = form.value.program_name
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
  }
}

async function loadData() {
  if (!isEdit.value) return
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.monitoringKebijakan.get(props.id)
    form.value = {
      program_name: data.program_name || '',
      slug: data.slug || '',
      sector: data.sector || 'ekonomi',
      promise: data.promise || '',
      realization: data.realization || '',
      progress_percent: Number(data.progress_percent) || 0,
      status_label: data.status_label || 'dalam_proses',
      source_title: data.source_title || '',
      source_url: data.source_url || '',
      last_updated_date: data.last_updated_date ? data.last_updated_date.slice(0, 10) : '',
      order: Number(data.order) || 0,
      status: data.status || 'terbit',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data monitoring kebijakan.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  const payload = {
    ...form.value,
    progress_percent: Number(form.value.progress_percent) || 0,
    order: Number(form.value.order) || 0,
  }

  try {
    if (isEdit.value) {
      await cmsResources.monitoringKebijakan.update(props.id, payload)
      alertSuccess('Program monitoring kebijakan berhasil diperbarui.')
    } else {
      await cmsResources.monitoringKebijakan.create(payload)
      alertSuccess('Program monitoring kebijakan baru berhasil ditambahkan.')
    }
    router.visit('/cms/monitoring-kebijakan')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
      alertError('Harap periksa kembali inputan form yang bertanda merah.')
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan program monitoring kebijakan.'))
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
  <Head :title="`${isEdit ? 'Edit' : 'Tambah'} Program Monitoring — CMS ISF`" />

  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2 text-xs text-neutral-500">
        <Link href="/cms/monitoring-kebijakan" class="hover:text-neutral-900 transition-colors">
          Monitoring Kebijakan
        </Link>
        <span>/</span>
        <span class="text-neutral-900 font-medium">
          {{ isEdit ? 'Edit Program' : 'Tambah Baru' }}
        </span>
      </div>
      <Link href="/cms/monitoring-kebijakan">
        <Button variant="outline" size="sm" class="text-xs">
          Kembali
        </Button>
      </Link>
    </div>

    <!-- Main Card Form -->
    <div class="bg-white rounded-xl border border-neutral-200/80 shadow-2xs p-6">
      <div class="mb-6 pb-4 border-b border-neutral-100">
        <h1 class="text-xl font-bold tracking-tight text-neutral-900">
          {{ isEdit ? 'Edit Program Monitoring Kebijakan' : 'Form Program Monitoring Baru' }}
        </h1>
        <p class="text-xs text-neutral-500 mt-1">
          Lengkapi data komparasi janji kampanye/RPJMN vs realisasi lapangan, progres persentase, dan status evaluasi.
        </p>
      </div>

      <LoadingIndicator v-if="loading" message="Memuat formulir..." />

      <ErrorMessage
        v-else-if="loadError"
        :message="loadError"
        :retry="loadData"
        class="mb-6"
      />

      <form v-else @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Nama Program & Slug -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5 md:col-span-2">
            <Label for="program_name" class="text-xs font-semibold text-neutral-700">
              Nama Program Pemerintah <span class="text-red-500">*</span>
            </Label>
            <Input
              id="program_name"
              v-model="form.program_name"
              @blur="autoSlug"
              placeholder="Contoh: Pembangunan 3 Juta Unit Rumah Subsidi & Layak Huni"
              required
              class="text-sm font-medium"
            />
            <p v-if="errors.program_name" class="text-xs text-red-600 font-medium">{{ errors.program_name[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="slug" class="text-xs font-semibold text-neutral-700">
              Slug URL (opsional, auto)
            </Label>
            <Input
              id="slug"
              v-model="form.slug"
              placeholder="program-3-juta-rumah-subsidi"
              class="text-xs font-mono"
            />
            <p v-if="errors.slug" class="text-xs text-red-600 font-medium">{{ errors.slug[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="sector" class="text-xs font-semibold text-neutral-700">
              Sektor Kebijakan <span class="text-red-500">*</span>
            </Label>
            <select
              id="sector"
              v-model="form.sector"
              required
              class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            >
              <option v-for="s in sectorOptions" :key="s.value" :value="s.value">
                {{ s.label }}
              </option>
            </select>
            <p v-if="errors.sector" class="text-xs text-red-600 font-medium">{{ errors.sector[0] }}</p>
          </div>
        </div>

        <!-- Janji vs Realisasi (Side by Side Comparison) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-neutral-50/70 border border-neutral-200/70">
          <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-neutral-800">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              <Label for="promise" class="cursor-pointer">Target Janji / Komitmen Pemerintah <span class="text-red-500">*</span></Label>
            </div>
            <textarea
              id="promise"
              v-model="form.promise"
              rows="4"
              placeholder="Jelaskan target angka, tenggat waktu, atau janji resmi yang dicantumkan dalam visi-misi / RPJMN..."
              required
              class="w-full rounded-md border border-neutral-300 p-2.5 text-xs leading-relaxed text-neutral-800 shadow-xs focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 font-sans"
            ></textarea>
            <p v-if="errors.promise" class="text-xs text-red-600 font-medium">{{ errors.promise[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-neutral-800">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <Label for="realization" class="cursor-pointer">Realisasi & Kondisi Lapangan <span class="text-red-500">*</span></Label>
            </div>
            <textarea
              id="realization"
              v-model="form.realization"
              rows="4"
              placeholder="Jelaskan capaian riil di lapangan berdasarkan data BPS, audit BPK, atau laporan kementerian terkini..."
              required
              class="w-full rounded-md border border-neutral-300 p-2.5 text-xs leading-relaxed text-neutral-800 shadow-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 font-sans"
            ></textarea>
            <p v-if="errors.realization" class="text-xs text-red-600 font-medium">{{ errors.realization[0] }}</p>
          </div>
        </div>

        <!-- Progres & Status Evaluasi -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <Label for="progress_percent" class="text-xs font-semibold text-neutral-700">
                Progres Capaian (%) <span class="text-red-500">*</span>
              </Label>
              <span class="text-xs font-mono font-bold text-neutral-900">{{ form.progress_percent }}%</span>
            </div>
            <div class="flex items-center gap-3">
              <input
                type="range"
                v-model.number="form.progress_percent"
                min="0"
                max="100"
                step="1"
                class="w-full accent-isf-red cursor-pointer"
              />
              <Input
                id="progress_percent"
                v-model.number="form.progress_percent"
                type="number"
                min="0"
                max="100"
                required
                class="w-20 text-xs font-mono text-center"
              />
            </div>
            <p v-if="errors.progress_percent" class="text-xs text-red-600 font-medium">{{ errors.progress_percent[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="status_label" class="text-xs font-semibold text-neutral-700">
              Status Evaluasi <span class="text-red-500">*</span>
            </Label>
            <select
              id="status_label"
              v-model="form.status_label"
              required
              class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            >
              <option v-for="sl in statusLabelOptions" :key="sl.value" :value="sl.value">
                {{ sl.label }}
              </option>
            </select>
            <p v-if="errors.status_label" class="text-xs text-red-600 font-medium">{{ errors.status_label[0] }}</p>
          </div>
        </div>

        <!-- Sumber Data & Tautan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="source_title" class="text-xs font-semibold text-neutral-700">
              Judul Sumber Data / Dokumen Resmi
            </Label>
            <Input
              id="source_title"
              v-model="form.source_title"
              placeholder="Contoh: Laporan Kinerja Kementerian PUPR 2025 & Data BPS"
              class="text-xs"
            />
            <p v-if="errors.source_title" class="text-xs text-red-600 font-medium">{{ errors.source_title[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="source_url" class="text-xs font-semibold text-neutral-700">
              Tautan URL Sumber Resmi
            </Label>
            <Input
              id="source_url"
              v-model="form.source_url"
              placeholder="https://..."
              class="text-xs font-mono"
            />
            <p v-if="errors.source_url" class="text-xs text-red-600 font-medium">{{ errors.source_url[0] }}</p>
          </div>
        </div>

        <!-- Tanggal Pembaruan, Urutan, Status -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-neutral-100">
          <div class="space-y-1.5">
            <Label for="last_updated_date" class="text-xs font-semibold text-neutral-700">
              Tanggal Pembaruan Data <span class="text-red-500">*</span>
            </Label>
            <Input
              id="last_updated_date"
              v-model="form.last_updated_date"
              type="date"
              required
              class="text-xs"
            />
            <p v-if="errors.last_updated_date" class="text-xs text-red-600 font-medium">{{ errors.last_updated_date[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="order" class="text-xs font-semibold text-neutral-700">
              Urutan Prioritas
            </Label>
            <Input
              id="order"
              v-model.number="form.order"
              type="number"
              min="0"
              placeholder="0"
              class="text-xs font-mono"
            />
            <p v-if="errors.order" class="text-xs text-red-600 font-medium">{{ errors.order[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="status" class="text-xs font-semibold text-neutral-700">
              Status Publikasi <span class="text-red-500">*</span>
            </Label>
            <select
              id="status"
              v-model="form.status"
              required
              class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-800 shadow-xs focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            >
              <option value="terbit">Terbit (Aktif)</option>
              <option value="draft">Draft (Arsip)</option>
            </select>
            <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-neutral-100">
          <Link href="/cms/monitoring-kebijakan">
            <Button type="button" variant="outline" class="text-xs">
              Batal
            </Button>
          </Link>
          <Button
            type="submit"
            :disabled="saving"
            class="text-xs font-semibold inline-flex items-center gap-2"
          >
            <IconComponent v-if="saving" name="IconRefresh" class="w-4 h-4 animate-spin" />
            <IconComponent v-else name="IconDeviceFloppy" class="w-4 h-4" />
            <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Program' : 'Simpan Program') }}</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

