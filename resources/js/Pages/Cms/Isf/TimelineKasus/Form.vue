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
        ? data.events.map((e) => ({
            id: e.id,
            tanggal_peristiwa: e.tanggal_peristiwa ? String(e.tanggal_peristiwa).substring(0, 10) : '',
            tahap: e.tahap || '',
            judul: e.judul || '',
            deskripsi: e.deskripsi || '',
            sumber_tautan: e.sumber_tautan || '',
            urutan: Number(e.urutan) || 0,
            status: e.status || 'terbit',
          }))
        : [],
    }
    if (form.value.events.length === 0) {
      addEvent()
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat detail timeline kasus.')
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
      urutan: idx + 1,
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
  <Head :title="isEdit ? 'Edit Timeline Kasus — Indonesia Strategy Forum' : 'Tambah Timeline Kasus — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/timeline-kasus">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Timeline Kasus' : 'Tambah Timeline Kasus' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data timeline perkara #${props.id}` : 'Kelola perkara hukum besar, sengketa konstitusi, dan kronologi sidang persidangan.' }}
          </p>
        </div>
      </div>

      <!-- Top action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/timeline-kasus">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSubmit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Timeline Kasus' : 'Simpan Timeline Kasus') }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat formulir timeline..." />

    <ErrorMessage
      v-else-if="loadError"
      :message="loadError"
      @retry="loadData"
    />

    <!-- Form Container -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Section 1: Kasus Induk -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">1. Data Kasus Induk</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Informasi pokok perkara, status hukum, dan pengelompokan kasus.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
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
              class="w-full rounded-md border border-input bg-background text-foreground p-3 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
            ></textarea>
            <p v-if="errors.deskripsi" class="text-xs text-red-600 mt-1">
              {{ errors.deskripsi[0] }}
            </p>
          </div>

          <!-- Slug Kasus (Col 6) -->
          <div class="md:col-span-6 space-y-1.5">
            <Label for="slug" class="font-medium text-xs">
              Slug URL / Anchor
            </Label>
            <Input
              id="slug"
              v-model="form.slug"
              placeholder="Dihasilkan otomatis jika dikosongkan (contoh: ambang-batas-parlemen)"
            />
            <p v-if="errors.slug" class="text-xs text-red-600 mt-1">
              {{ errors.slug[0] }}
            </p>
          </div>

          <!-- Status Publikasi Kasus (Col 6) -->
          <div class="md:col-span-6 space-y-1.5">
            <Label for="status" class="font-medium text-xs">
              Status Publikasi
            </Label>
            <StatusSelectWithBadge
              id="status"
              v-model="form.status"
            />
            <p v-if="errors.status" class="text-xs text-red-600 mt-1">
              {{ errors.status[0] }}
            </p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Section 2: Kronologi Peristiwa (Events) -->
    <Card class="bg-card border-border shadow-sm">
      <CardHeader class="pb-4 flex flex-row items-center justify-between">
        <div>
          <CardTitle class="text-base font-semibold text-foreground">
            2. Alur Kronologi & Tahapan Sidang
          </CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Tambahkan setiap peristiwa bersejarah atau tahapan sidang secara kronologis.
          </CardDescription>
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="addEvent"
          class="inline-flex items-center gap-1.5 text-xs h-8"
        >
          <IconComponent name="Plus" :size="14" />
          <span>Tambah Peristiwa</span>
        </Button>
      </CardHeader>
      <CardContent class="space-y-4">
        <div v-if="form.events.length === 0" class="py-8 text-center text-muted-foreground text-xs">
          Belum ada peristiwa dalam timeline ini. Klik tombol di atas untuk menambahkan peristiwa pertama.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="(event, eIdx) in form.events"
            :key="eIdx"
            class="p-4 rounded-lg border border-border/80 bg-muted/20 relative space-y-3"
          >
            <div class="flex items-center justify-between border-b border-border/60 pb-2">
              <span class="inline-flex items-center gap-1.5 font-bold text-xs text-foreground">
                <span class="w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center text-[10px]">
                  {{ eIdx + 1 }}
                </span>
                Peristiwa #{{ eIdx + 1 }}
              </span>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="removeEvent(eIdx)"
                class="h-7 px-2 text-xs text-red-500 hover:text-red-600 hover:bg-red-500/10 inline-flex items-center"
              >
                <IconComponent name="Trash" :size="13" class="mr-1" />
                Hapus
              </Button>
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

              <!-- Tahap Sidang (Col 4) -->
              <div class="md:col-span-4 space-y-1">
                <Label class="text-[11px] font-medium">Tahap / Agenda Sidang <span class="text-red-500">*</span></Label>
                <Input
                  v-model="event.tahap"
                  placeholder="Contoh: Sidang Pleno, Putusan Akhir"
                  required
                />
              </div>

              <!-- Status Event (Col 4) -->
              <div class="md:col-span-4 space-y-1">
                <Label class="text-[11px] font-medium">Status Event</Label>
                <StatusSelectWithBadge
                  v-model="event.status"
                />
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
                  class="w-full rounded-md border border-input bg-background text-foreground p-2.5 text-xs focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
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
      </CardContent>
    </Card>

      <!-- Submit Bar -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
        <Link href="/cms/timeline-kasus">
          <Button type="button" variant="outline" size="sm" :disabled="saving">
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
          <span>{{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Timeline Kasus' : 'Simpan Timeline Kasus') }}</span>
        </Button>
      </div>
    </form>
  </div>
</template>
