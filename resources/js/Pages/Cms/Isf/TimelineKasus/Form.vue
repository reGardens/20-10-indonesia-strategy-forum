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
  judul_kasus: '',
  slug: '',
  kategori_kasus: 'Sengketa Konstitusi',
  deskripsi: '',
  status_kasus: 'Proses Sidang',
  gambar: '',
  urutan: 0,
  status: 'terbit',
  events: [],
})

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

function addEvent() {
  form.value.events.push({
    id: null,
    tanggal_peristiwa: new Date().toISOString().substring(0, 10),
    tahap: 'Sidang Pembuktian',
    judul: '',
    deskripsi: '',
    sumber_tautan: '',
    urutan: form.value.events.length + 1,
    status: 'terbit',
  })
}

function removeEvent(index) {
  form.value.events.splice(index, 1)
}

async function loadData() {
  if (!isEdit.value) {
    // Tambahkan 1 default event row
    addEvent()
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.timelineKasus.get(props.id)
    form.value = {
      judul_kasus: data.judul_kasus || '',
      slug: data.slug || '',
      kategori_kasus: data.kategori_kasus || 'Sengketa Konstitusi',
      deskripsi: data.deskripsi || '',
      status_kasus: data.status_kasus || 'Proses Sidang',
      gambar: data.gambar || '',
      urutan: Number(data.urutan) || 0,
      status: data.status || 'terbit',
      events: Array.isArray(data.events)
        ? data.events.map((e, idx) => ({
            id: e.id,
            tanggal_peristiwa: e.tanggal_peristiwa ? e.tanggal_peristiwa.substring(0, 10) : '',
            tahap: e.tahap || '',
            judul: e.judul || '',
            deskripsi: e.deskripsi || '',
            sumber_tautan: e.sumber_tautan || '',
            urutan: Number(e.urutan) || (idx + 1),
            status: e.status || 'terbit',
          }))
        : [],
    }
    if (form.value.events.length === 0) {
      addEvent()
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data timeline kasus.')
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  errors.value = {}

  const payload = {
    ...form.value,
    urutan: Number(form.value.urutan) || 0,
    events: form.value.events.map((e, idx) => ({
      ...e,
      urutan: Number(e.urutan) || (idx + 1),
    })),
  }

  try {
    if (isEdit.value) {
      await cmsResources.timelineKasus.update(props.id, payload)
      alertSuccess('Timeline kasus berhasil diperbarui.')
    } else {
      await cmsResources.timelineKasus.create(payload)
      alertSuccess('Timeline kasus baru berhasil dibuat.')
    }
    router.visit('/cms/timeline-kasus')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan timeline kasus.'))
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
  <Head :title="isEdit ? 'Edit Timeline Kasus — CMS ISF' : 'Tambah Timeline Kasus — CMS ISF'" />

  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs text-neutral-500 mb-1">
          <Link href="/cms/timeline-kasus" class="hover:text-neutral-900 transition-colors">
            Timeline Kasus
          </Link>
          <span>/</span>
          <span class="text-neutral-900 font-medium">
            {{ isEdit ? 'Edit Kasus' : 'Tambah Kasus' }}
          </span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-neutral-900">
          {{ isEdit ? 'Edit Timeline Kasus' : 'Tambah Timeline Kasus' }}
        </h1>
      </div>
      <Link href="/cms/timeline-kasus">
        <Button variant="outline" size="sm" class="inline-flex items-center gap-1.5">
          <IconComponent name="IconArrowLeft" class="w-4 h-4" />
          <span>Kembali</span>
        </Button>
      </Link>
    </div>

    <LoadingIndicator v-if="loading" message="Memuat formulir timeline..." />

    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      :retry="loadData"
      class="m-6"
    />

    <!-- Form Container -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-8">
      <!-- Section 1: Kasus Induk -->
      <div class="bg-white p-6 sm:p-8 rounded-xl border border-neutral-200/80 shadow-2xs space-y-5">
        <h2 class="text-base font-bold text-neutral-900 border-b border-neutral-200 pb-3">
          1. Data Kasus Induk
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
          <!-- Judul Kasus (Col 12) -->
          <div class="md:col-span-12 space-y-1.5">
            <Label for="judul_kasus" class="font-medium text-xs">
              Nama / Judul Kasus Besar <span class="text-red-500">*</span>
            </Label>
            <Input
              id="judul_kasus"
              v-model="form.judul_kasus"
              placeholder="Contoh: Uji Materiil Ambang Batas Parlemen di Mahkamah Konstitusi"
              required
            />
            <p v-if="errors.judul_kasus" class="text-xs text-red-600 mt-1">
              {{ errors.judul_kasus[0] }}
            </p>
          </div>

          <!-- Kategori Kasus (Col 6) -->
          <div class="md:col-span-6 space-y-1.5">
            <Label for="kategori_kasus" class="font-medium text-xs">
              Kategori Perkara
            </Label>
            <Input
              id="kategori_kasus"
              v-model="form.kategori_kasus"
              placeholder="Contoh: Sengketa Konstitusi, Tindak Pidana Korupsi, Hukum Bisnis"
            />
            <p v-if="errors.kategori_kasus" class="text-xs text-red-600 mt-1">
              {{ errors.kategori_kasus[0] }}
            </p>
          </div>

          <!-- Status Kasus Saat Ini (Col 6) -->
          <div class="md:col-span-6 space-y-1.5">
            <Label for="status_kasus" class="font-medium text-xs">
              Status Kasus Terkini <span class="text-red-500">*</span>
            </Label>
            <Input
              id="status_kasus"
              v-model="form.status_kasus"
              placeholder="Contoh: Proses Sidang, Putusan Inkracht, Tahap Kasasi"
              required
            />
            <p v-if="errors.status_kasus" class="text-xs text-red-600 mt-1">
              {{ errors.status_kasus[0] }}
            </p>
          </div>

          <!-- Deskripsi Ringkas Kasus (Col 12) -->
          <div class="md:col-span-12 space-y-1.5">
            <Label for="deskripsi" class="font-medium text-xs">
              Ringkasan Latar Belakang Perkara
            </Label>
            <textarea
              id="deskripsi"
              v-model="form.deskripsi"
              rows="3"
              placeholder="Jelaskan pokok persoalan hukum, pihak terkait, dan esensi perkaranya..."
              class="w-full rounded-md border border-neutral-300 p-3 text-sm focus:border-isf-red focus:ring-1 focus:ring-isf-red outline-none"
            ></textarea>
            <p v-if="errors.deskripsi" class="text-xs text-red-600 mt-1">
              {{ errors.deskripsi[0] }}
            </p>
          </div>

          <!-- Urutan & Status Publikasi -->
          <div class="md:col-span-6 space-y-1.5">
            <Label for="urutan" class="font-medium text-xs">
              Urutan Prioritas
            </Label>
            <Input
              id="urutan"
              type="number"
              v-model.number="form.urutan"
              min="0"
              placeholder="0"
            />
          </div>

          <div class="md:col-span-6 space-y-1.5">
            <Label for="status" class="font-medium text-xs">
              Status Publikasi Kasus
            </Label>
            <select
              id="status"
              v-model="form.status"
              class="w-full rounded-md border border-neutral-300 p-2 text-sm focus:border-isf-red focus:ring-1 focus:ring-isf-red outline-none bg-white"
            >
              <option value="terbit">Terbit (Tampil di rubrik hukum)</option>
              <option value="draft">Draft (Disembunyikan)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section 2: Kronologi Peristiwa -->
      <div class="bg-white p-6 sm:p-8 rounded-xl border border-neutral-200/80 shadow-2xs space-y-5">
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
          <div>
            <h2 class="text-base font-bold text-neutral-900">
              2. Alur Waktu / Peristiwa Kronologis
            </h2>
            <p class="text-xs text-neutral-500 mt-0.5">
              Urutan sidang, putusan, pelimpahan berkas, atau penetapan penting dalam perkara ini.
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="addEvent"
            class="inline-flex items-center gap-1.5"
          >
            <IconComponent name="IconPlus" class="w-4 h-4 text-isf-red" />
            <span>Tambah Peristiwa</span>
          </Button>
        </div>

        <div class="space-y-4">
          <div
            v-for="(event, index) in form.events"
            :key="index"
            class="p-4 rounded-lg border border-neutral-200 bg-neutral-50/60 relative space-y-3"
          >
            <div class="flex items-center justify-between pb-2 border-b border-neutral-200">
              <span class="font-mono text-xs font-bold text-isf-red">
                Peristiwa #{{ index + 1 }}
              </span>
              <button
                v-if="form.events.length > 1"
                type="button"
                @click="removeEvent(index)"
                class="text-xs text-red-600 hover:text-red-700 hover:underline flex items-center gap-1 cursor-pointer"
              >
                <IconComponent name="IconTrash" class="w-3.5 h-3.5" />
                <span>Hapus</span>
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
              <!-- Tanggal Peristiwa (Col 4) -->
              <div class="md:col-span-4 space-y-1">
                <Label class="text-[11px] font-medium">Tanggal Peristiwa <span class="text-red-500">*</span></Label>
                <Input
                  type="date"
                  v-model="event.tanggal_peristiwa"
                  required
                />
              </div>

              <!-- Tahap (Col 4) -->
              <div class="md:col-span-4 space-y-1">
                <Label class="text-[11px] font-medium">Tahap Sidang / Proses <span class="text-red-500">*</span></Label>
                <Input
                  v-model="event.tahap"
                  placeholder="Contoh: Sidang Pleno, Putusan MK, Dakwaan"
                  required
                />
              </div>

              <!-- Status Peristiwa (Col 4) -->
              <div class="md:col-span-4 space-y-1">
                <Label class="text-[11px] font-medium">Status Tampil</Label>
                <select
                  v-model="event.status"
                  class="w-full rounded-md border border-neutral-300 p-2 text-xs focus:border-isf-red outline-none bg-white"
                >
                  <option value="terbit">Terbit</option>
                  <option value="draft">Draft</option>
                </select>
              </div>

              <!-- Judul Peristiwa (Col 12) -->
              <div class="md:col-span-12 space-y-1">
                <Label class="text-[11px] font-medium">Judul Peristiwa <span class="text-red-500">*</span></Label>
                <Input
                  v-model="event.judul"
                  placeholder="Contoh: MK Mengabulkan Sebagian Uji Materiil Ambang Batas Parlemen"
                  required
                />
              </div>

              <!-- Deskripsi Ringkas (Col 12) -->
              <div class="md:col-span-12 space-y-1">
                <Label class="text-[11px] font-medium">Keterangan / Ringkasan Putusan</Label>
                <textarea
                  v-model="event.deskripsi"
                  rows="2"
                  placeholder="Ringkasan pertimbangan hakim, dalil pemohon, atau keterangan amar putusan..."
                  class="w-full rounded-md border border-neutral-300 p-2 text-xs focus:border-isf-red outline-none"
                ></textarea>
              </div>

              <!-- Sumber / Dokumen Link (Col 12) -->
              <div class="md:col-span-12 space-y-1">
                <Label class="text-[11px] font-medium">Tautan Sumber Resmi / Risalah Persidangan (Opsional)</Label>
                <Input
                  v-model="event.sumber_tautan"
                  placeholder="https://www.mkri.id/public/content/persidangan/putusan/..."
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Bar -->
      <div class="flex items-center justify-end gap-3 pt-4">
        <Link href="/cms/timeline-kasus">
          <Button type="button" variant="outline" :disabled="saving">
            Batal
          </Button>
        </Link>
        <Button type="submit" :disabled="saving" class="inline-flex items-center gap-2">
          <IconComponent name="IconDeviceFloppy" class="w-4 h-4" />
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Timeline Kasus' : 'Simpan Timeline Kasus') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>

