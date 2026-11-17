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
    const data = await cmsResources.kategoriSektor.get(props.id)
    form.value = {
      nama: data.nama || '',
      slug: data.slug || '',
      deskripsi: data.deskripsi || '',
      urutan: Number(data.urutan) || 0,
      status: data.status || 'terbit',
    }
  } catch (err) {
    loadError.value = errorMessage(err, 'Gagal memuat data kategori sektor.')
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
      await cmsResources.kategoriSektor.update(props.id, payload)
      alertSuccess('Kategori sektor berhasil diperbarui.')
    } else {
      await cmsResources.kategoriSektor.create(payload)
      alertSuccess('Kategori sektor baru berhasil ditambahkan.')
    }
    router.visit('/cms/kategori-sektor')
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      alertError(errorMessage(err, 'Gagal menyimpan kategori sektor.'))
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
  <Head :title="isEdit ? 'Edit Kategori Sektor — Indonesia Strategy Forum' : 'Tambah Kategori Sektor — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kategori-sektor">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kategori Sektor' : 'Tambah Kategori Sektor' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data kategori sektor #${props.id}` : 'Tambahkan kategori sektor baru untuk pengelompokan Policy Brief.' }}
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kategori-sektor">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Sektor' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data kategori sektor..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="loadData" />

    <!-- Form Content -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Sektor Kebijakan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Data pokok klasifikasi sektor kebijakan publik.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Nama Sektor -->
            <div class="space-y-1.5">
              <Label for="nama" class="text-xs font-medium text-foreground">Nama Sektor Kebijakan *</Label>
              <Input
                id="nama"
                v-model="form.nama"
                @blur="autoSlug"
                placeholder="Contoh: Ekonomi & Fiskal"
                required
                class="text-xs"
              />
              <p v-if="errors.nama" class="text-xs text-red-600 font-medium">{{ errors.nama[0] }}</p>
            </div>

            <!-- Slug -->
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium text-foreground">Slug URL / Identifier *</Label>
              <Input
                id="slug"
                v-model="form.slug"
                placeholder="Contoh: ekonomi"
                required
                class="text-xs font-mono"
              />
              <p v-if="errors.slug" class="text-xs text-red-600 font-medium">{{ errors.slug[0] }}</p>
            </div>
          </div>

          <!-- Deskripsi -->
          <div class="space-y-1.5">
            <Label for="deskripsi" class="text-xs font-medium text-foreground">Deskripsi Cakupan Isu</Label>
            <textarea
              id="deskripsi"
              v-model="form.deskripsi"
              rows="3"
              placeholder="Jelaskan cakupan sektor ini (contoh: APBN, perpajakan, moneter, belanja negara)..."
              class="w-full rounded-md border border-input bg-background p-3 text-xs leading-relaxed focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
            ></textarea>
            <p v-if="errors.deskripsi" class="text-xs text-red-600 font-medium">{{ errors.deskripsi[0] }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-border">
            <!-- Urutan -->
            <div class="space-y-1.5">
              <Label for="urutan" class="text-xs font-medium text-foreground">Urutan Prioritas</Label>
              <Input
                id="urutan"
                v-model.number="form.urutan"
                type="number"
                min="0"
                class="text-xs font-mono"
              />
              <p v-if="errors.urutan" class="text-xs text-red-600 font-medium">{{ errors.urutan[0] }}</p>
            </div>

            <!-- Status Publikasi -->
            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium text-foreground">Status Publikasi *</Label>
              <StatusSelectWithBadge
                id="status"
                v-model="form.status"
              />
              <p v-if="errors.status" class="text-xs text-red-600 font-medium">{{ errors.status[0] }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Bottom action button -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/kategori-sektor">
          <Button type="button" variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="submit"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Sektor' : 'Simpan Sektor') }}
        </Button>
      </div>
    </form>
  </div>
</template>

