<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { STATUSES } from '@/Pages/Cms/Isf/options'
import RepeatableRows from '@/Pages/Cms/components/shared/RepeatableRows.vue'
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
  CardContent
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const dataFields = [
  { key: 'label', label: 'Label Kategori / Sumbu X', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Pidana Korporasi' },
  { key: 'value', label: 'Nilai Numerik', type: 'number', required: true, placeholder: '42' }
]

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.dataGrafik,
  id: props.id,
  indexHref: '/cms/data-grafik',
  defaults: {
    title: '',
    description: '',
    unit: 'persen',
    data: [
      { label: 'Kategori A', value: 35 },
      { label: 'Kategori B', value: 25 },
      { label: 'Kategori C', value: 40 }
    ],
    source_title: '',
    source_url: '',
    is_beranda: false,
    status: 'draf'
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Data Grafik' : 'Tambah Data Grafik') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/data-grafik">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Data Grafik' : 'Tambah Data Grafik Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Dataset grafik batang interaktif beserta rujukan sumber data.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/data-grafik">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Grafik' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat grafik..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Informasi Grafik</CardTitle>
          <CardDescription class="text-xs">Judul grafik, penjelasan konteks, dan satuan ukuran.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium">Judul Grafik <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="e.g. Topik Regulasi yang Paling Banyak Dipertanyakan Pelaku Usaha"
              class="h-9 text-sm"
            />
            <p v-if="err('title')" class="text-xs text-red-500 font-medium">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="unit" class="text-xs font-medium">Satuan Unit (e.g. %, responden, triliun)</Label>
              <Input
                id="unit"
                v-model="form.unit"
                placeholder="persen"
                class="h-9 text-xs font-mono"
              />
              <p v-if="err('unit')" class="text-xs text-red-500 font-medium">{{ err('unit') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="status" class="text-xs font-medium">Status <span class="text-red-500">*</span></Label>
              <select
                id="status"
                v-model="form.status"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring font-medium"
              >
                <option v-for="st in STATUSES" :key="st.value" :value="st.value">
                  {{ st.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="description" class="text-xs font-medium">Keterangan / Konteks Data</Label>
            <textarea
              id="description"
              v-model="form.description"
              rows="2"
              placeholder="Penjelasan latar belakang metodologi survei atau pengumpulan data..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p v-if="err('description')" class="text-xs text-red-500 font-medium">{{ err('description') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="source_title" class="text-xs font-medium">Nama Sumber Data</Label>
              <Input
                id="source_title"
                v-model="form.source_title"
                placeholder="e.g. Survei Asosiasi Pengusaha Indonesia"
                class="h-9 text-xs"
              />
            </div>

            <div class="space-y-1.5">
              <Label for="source_url" class="text-xs font-medium">URL Sumber Data (https://)</Label>
              <Input
                id="source_url"
                v-model="form.source_url"
                placeholder="https://..."
                class="h-9 text-xs font-mono"
              />
            </div>
          </div>

          <div class="pt-2">
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-emerald-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_beranda"
                class="mt-0.5 rounded border-input text-emerald-600 focus:ring-emerald-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Tampilkan di Beranda Publik</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Jadikan grafik ini sebagai sorotan data interaktif di halaman Beranda.</div>
              </div>
            </label>
          </div>
        </CardContent>
      </Card>

      <!-- Seri Data (Repeatable) -->
      <RepeatableRows
        v-model="form.data"
        :fields="dataFields"
        legend="Seri Data Batang (Bar Chart Data Points)"
        description="Daftar pasangan label kategori dan nilai numeriknya (minimal 1 baris)."
        addLabel="Tambah Titik Data"
        :min="1"
        :max="30"
      />

      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/data-grafik">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-5 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Grafik' }}
        </Button>
      </div>
    </form>
  </div>
</template>
