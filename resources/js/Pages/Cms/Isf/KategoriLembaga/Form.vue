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
  nama: '',
  slug: '',
  deskripsi: '',
  urutan: 0,
  status: 'terbit',
})

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const errors = ref({})

function autoSlug() {
  if (!isEdit.value && form.value.nama && !form.value.slug) {
    form.value.slug = form.value.nama
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
    const data = await cmsResources.kategoriLembaga.get(props.id)
    form.value = {
      nama: data.nama || '',
      slug: data.slug || '',
      deskripsi: data.deskripsi || '',
      urutan: Number(data.urutan) || 0,
      status: data.status || 'terbit',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data kategori lembaga.')
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
  }

  try {
    if (isEdit.value) {
      await cmsResources.kategoriLembaga.update(props.id, payload)
      alertSuccess('Kategori lembaga berhasil diperbarui.')
    } else {
      await cmsResources.kategoriLembaga.create(payload)
      alertSuccess('Kategori lembaga baru berhasil ditambahkan.')
    }
    router.visit('/cms/kategori-lembaga')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan kategori lembaga.'))
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
  <Head :title="(isEdit ? 'Edit Kategori Lembaga' : 'Tambah Kategori Lembaga') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kategori-lembaga">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kategori Lembaga' : 'Tambah Kategori Lembaga' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah kategori lembaga #${props.id}` : 'Tambahkan kategori lembaga baru untuk klasifikasi figur sosok.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kategori-lembaga">
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
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Simpan Kategori') }}
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat data kategori lembaga..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <form v-else class="space-y-6" @submit.prevent="handleSubmit">
      <Card class="border border-border shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50">
          <CardTitle class="text-base font-semibold">Informasi Kategori Lembaga</CardTitle>
          <CardDescription class="text-xs">
            Data klasifikasi lembaga yang akan digunakan sebagai pilihan pada formulir sosok dan dropdown filter.
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-5 space-y-4">
          <!-- Nama Lembaga -->
          <div class="space-y-1.5">
            <Label for="nama" class="text-xs font-medium">Nama Lembaga <span class="text-red-500">*</span></Label>
            <Input
              id="nama"
              v-model="form.nama"
              type="text"
              placeholder="e.g. Legislatif, Eksekutif, Pakar & Akademisi"
              class="h-9 text-xs"
              required
              @blur="autoSlug"
            />
            <p v-if="errors.nama" class="text-xs text-red-500 font-medium">{{ errors.nama[0] }}</p>
          </div>

          <!-- Slug -->
          <div class="space-y-1.5">
            <Label for="slug" class="text-xs font-medium">Slug Identifier <span class="text-red-500">*</span></Label>
            <Input
              id="slug"
              v-model="form.slug"
              type="text"
              placeholder="e.g. legislatif, eksekutif, pakar"
              class="h-9 text-xs font-mono"
              required
            />
            <p class="text-[11px] text-muted-foreground">
              Slug unik digunakan sebagai nilai klasifikasi internal (contoh: legislatif, eksekutif, pakar).
            </p>
            <p v-if="errors.slug" class="text-xs text-red-500 font-medium">{{ errors.slug[0] }}</p>
          </div>

          <!-- Deskripsi -->
          <div class="space-y-1.5">
            <Label for="deskripsi" class="text-xs font-medium">Deskripsi Cakupan Lembaga</Label>
            <textarea
              id="deskripsi"
              v-model="form.deskripsi"
              rows="3"
              placeholder="Tuliskan keterangan cakupan atau institusi terkait..."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-xs ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            ></textarea>
            <p v-if="errors.deskripsi" class="text-xs text-red-500 font-medium">{{ errors.deskripsi[0] }}</p>
          </div>

          <!-- Grid: Urutan & Status -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Urutan -->
            <div class="space-y-1.5">
              <Label for="urutan" class="text-xs font-medium">Urutan Tampil</Label>
              <Input
                id="urutan"
                v-model="form.urutan"
                type="number"
                min="0"
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">Angka lebih kecil tampil lebih awal pada daftar.</p>
              <p v-if="errors.urutan" class="text-xs text-red-500 font-medium">{{ errors.urutan[0] }}</p>
            </div>

            <!-- Status Publikasi -->
            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium">Status Publikasi <span class="text-red-500">*</span></Label>
              <StatusSelectWithBadge
                id="status"
                v-model="form.status"
              />
              <p v-if="errors.status" class="text-xs text-red-500 font-medium">{{ errors.status[0] }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>

