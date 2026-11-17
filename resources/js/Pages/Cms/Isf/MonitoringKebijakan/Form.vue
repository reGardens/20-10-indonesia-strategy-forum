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
      last_updated_date: data.last_updated_date ? data.last_updated_date.slice(0, 10) : new Date().toISOString().slice(0, 10),
      order: Number(data.order) || 0,
      status: data.status || 'terbit',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data program monitoring kebijakan.')
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
  <Head :title="isEdit ? 'Edit Monitoring Kebijakan — Indonesia Strategy Forum' : 'Tambah Monitoring Kebijakan — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto w-full">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          {{ isEdit ? 'Edit Program Monitoring Kebijakan' : 'Tambah Program Monitoring Kebijakan' }}
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          Pantau implementasi program strategis, janji kampanye, indikator capaian, dan sumber data resmi.
        </p>
      </div>
      <Link href="/cms/monitoring-kebijakan">
        <Button variant="outline" size="sm" class="inline-flex items-center gap-1.5">
          <IconComponent name="ArrowLeft" :size="14" />
          <span>Kembali</span>
        </Button>
      </Link>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat formulir monitoring kebijakan..." />

    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <!-- Form Card -->
    <div v-else class="bg-card rounded-xl border border-border p-6 sm:p-8 shadow-xs">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Nama Program & Slug -->
        <div class="space-y-4">
          <div class="space-y-1.5">
            <Label for="program_name" class="font-medium text-xs">
              Nama Program Strategis / Janji Kebijakan <span class="text-red-500">*</span>
            </Label>
            <Input
              id="program_name"
              v-model="form.program_name"
              @blur="autoSlug"
              placeholder="Contoh: Pembangunan 3 Juta Rumah per Tahun"
              required
            />
            <p v-if="errors.program_name" class="text-xs text-red-600 font-medium">{{ errors.program_name[0] }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="slug" class="font-medium text-xs">
                Slug URL / Identifier
              </Label>
              <Input
                id="slug"
                v-model="form.slug"
                placeholder="pembangunan-3-juta-rumah"
                class="font-mono text-xs"
              />
              <p v-if="errors.slug" class="text-xs text-red-600 font-medium">{{ errors.slug[0] }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="sector" class="font-medium text-xs">
                Sektor Kebijakan <span class="text-red-500">*</span>
              </Label>
              <Select v-model="form.sector">
                <SelectTrigger id="sector" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Pilih Sektor Kebijakan..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="sec in sectorOptions"
                    :key="sec.value"
                    :value="sec.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ sec.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.sector" class="text-xs text-red-600 font-medium">{{ errors.sector[0] }}</p>
            </div>
          </div>
        </div>

        <!-- Janji & Realisasi -->
        <div class="space-y-4">
          <div class="space-y-1.5">
            <Label for="promise" class="font-medium text-xs">
              Janji / Target Awal Pemerintah <span class="text-red-500">*</span>
            </Label>
            <textarea
              id="promise"
              v-model="form.promise"
              rows="3"
              required
              placeholder="Uraikan janji kampanye, target RPJMN, atau target kuantitatif yang dicanangkan..."
              class="w-full rounded-md border border-input bg-background text-foreground p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            ></textarea>
            <p v-if="errors.promise" class="text-xs text-red-600 font-medium">{{ errors.promise[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="realization" class="font-medium text-xs">
              Realisasi Saat Ini / Temuan Lapangan <span class="text-red-500">*</span>
            </Label>
            <textarea
              id="realization"
              v-model="form.realization"
              rows="3"
              required
              placeholder="Uraikan fakta pencapaian riil, jumlah unit terbangun, anggaran terserap, dll..."
              class="w-full rounded-md border border-input bg-background text-foreground p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            ></textarea>
            <p v-if="errors.realization" class="text-xs text-red-600 font-medium">{{ errors.realization[0] }}</p>
          </div>
        </div>

        <!-- Persentase & Status Label -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="progress_percent" class="font-medium text-xs">
              Persentase Capaian (%) (0 - 100) <span class="text-red-500">*</span>
            </Label>
            <Input
              id="progress_percent"
              v-model.number="form.progress_percent"
              type="number"
              min="0"
              max="100"
              step="1"
              required
              class="text-xs font-mono"
            />
            <p v-if="errors.progress_percent" class="text-xs text-red-600 font-medium">{{ errors.progress_percent[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="status_label" class="font-medium text-xs">
              Status Capaian / Progres <span class="text-red-500">*</span>
            </Label>
            <Select v-model="form.status_label">
              <SelectTrigger id="status_label" class="h-9 text-xs font-medium bg-background border-input">
                <SelectValue placeholder="Pilih Status Capaian..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="st in statusLabelOptions"
                  :key="st.value"
                  :value="st.value"
                  class="text-xs font-medium cursor-pointer"
                >
                  {{ st.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors.status_label" class="text-xs text-red-600 font-medium">{{ errors.status_label[0] }}</p>
          </div>
        </div>

        <!-- Sumber & Verifikasi Data -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="source_title" class="font-medium text-xs">
              Nama Sumber Data Resmi <span class="text-red-500">*</span>
            </Label>
            <Input
              id="source_title"
              v-model="form.source_title"
              placeholder="Contoh: Laporan Kinerja Kementerian PUPR Q3 2026"
              required
            />
            <p v-if="errors.source_title" class="text-xs text-red-600 font-medium">{{ errors.source_title[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="source_url" class="font-medium text-xs">
              Tautan URL Sumber Resmi
            </Label>
            <Input
              id="source_url"
              v-model="form.source_url"
              placeholder="https://pu.go.id/publikasi/..."
              class="font-mono text-xs"
            />
            <p v-if="errors.source_url" class="text-xs text-red-600 font-medium">{{ errors.source_url[0] }}</p>
          </div>
        </div>

        <!-- Tanggal Update, Urutan, Status -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-border">
          <div class="space-y-1.5">
            <Label for="last_updated_date" class="font-medium text-xs">
              Tanggal Pemutakhiran Data <span class="text-red-500">*</span>
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
            <Label for="order" class="font-medium text-xs">
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
            <Label for="status" class="font-medium text-xs">
              Status Publikasi <span class="text-red-500">*</span>
            </Label>
            <StatusSelectWithBadge
              id="status"
              v-model="form.status"
            />
            <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
          <Link href="/cms/monitoring-kebijakan">
            <Button type="button" variant="outline" size="sm">
              Batal
            </Button>
          </Link>
          <Button
            type="submit"
            :disabled="saving"
            class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm shadow-sm inline-flex items-center gap-1.5"
          >
            <IconComponent v-if="saving" name="Refresh" :size="16" class="animate-spin" />
            <IconComponent v-else name="DeviceFloppy" :size="16" />
            <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Program' : 'Simpan Program') }}</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>
