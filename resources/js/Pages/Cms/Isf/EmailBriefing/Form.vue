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
  DatePicker
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const ringkasanDataFields = [
  { key: 'label', label: 'Label Statistik', type: 'text', span: 'sm:col-span-2', required: true, placeholder: 'e.g. Alert Prioritas Tinggi Terbit' },
  { key: 'nilai', label: 'Jumlah / Nilai', type: 'number', required: true }
]

const poinFields = [
  { key: 'judul', label: 'Judul Poin Utama', type: 'text', span: 'sm:col-span-2 lg:col-span-3', required: true },
  { key: 'deskripsi', label: 'Uraian Analisis Poin', type: 'textarea', span: 'sm:col-span-2 lg:col-span-3', required: true },
  { key: 'url', label: 'Tautan Arah Laporan (URL/Path)', type: 'url', span: 'sm:col-span-2 lg:col-span-3', required: true, placeholder: 'https://... atau /isu/...' }
]

const tautanFields = [
  { key: 'label', label: 'Label Tautan Penting', type: 'text', required: true },
  { key: 'url', label: 'Target URL', type: 'url', span: 'sm:col-span-2', required: true }
]

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.emailBriefing,
  id: props.id,
  indexHref: '/cms/email-briefing',
  defaults: {
    tanggal: new Date().toISOString().slice(0, 10),
    penerima: 'Anggota Terhormat',
    organisasi: 'Korporasi Mitra ISF',
    ringkasan_judul: 'Briefing Intelijen Regulasi Mingguan',
    ringkasan_catatan: 'Catatan ringkasan dinamika kebijakan dan implikasi bisnis sepekan terakhir.',
    ringkasan_data: [
      { label: 'Alert Aktif Pekan Ini', nilai: 8 },
      { label: 'RUU dalam Tahap Krusial', nilai: 3 }
    ],
    pembuka: 'Berikut adalah intisari analisis regulasi strategis yang telah kami siapkan khusus untuk jajaran pengambil keputusan.',
    poin: [
      { judul: 'Poin Strategis 1', deskripsi: 'Uraian poin kebijakan pertama...', url: '/isu/kuhp-baru-pelaku-usaha' },
      { judul: 'Poin Strategis 2', deskripsi: 'Uraian poin kebijakan kedua...', url: '/beranda' },
      { judul: 'Poin Strategis 3', deskripsi: 'Uraian poin kebijakan ketiga...', url: '/anggota' }
    ],
    penutup: 'Pastikan tim kepatuhan Anda mengkaji detail alert dan matriks risiko melalui tautan di bawah.',
    cta_url: '/anggota',
    tautan: [
      { label: 'Buka Portal Anggota', url: '/anggota' },
      { label: 'Lihat Analisis Isu Terkini', url: '/isu/kuhp-baru-pelaku-usaha' }
    ],
    status: 'draf'
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Email Briefing' : 'Buat Email Briefing') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/email-briefing">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Email Briefing' : 'Buat Email Briefing Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Format email briefing HTML elegan yang dikirimkan kepada pembuat keputusan.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a
          v-if="isEdit"
          :href="`/cms/email-briefing/${props.id}/preview`"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border border-border bg-card text-foreground hover:bg-accent transition"
        >
          <IconComponent name="ExternalLink" :size="14" />
          <span>Pratinjau Email</span>
        </a>
        <Link href="/cms/email-briefing">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="save"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Briefing' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data briefing..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Header & Penerima</CardTitle>
          <CardDescription class="text-xs">Identitas edisi briefing, tanggal rilis, dan status publikasi.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="ringkasan_judul" class="text-xs font-medium">Judul Edisi Briefing <span class="text-red-500">*</span></Label>
            <Input
              id="ringkasan_judul"
              v-model="form.ringkasan_judul"
              placeholder="e.g. ISF Executive Briefing — Dampak Kebijakan Moneter & RUU Sektoral"
              class="h-9 text-sm font-semibold"
            />
            <p v-if="err('ringkasan_judul')" class="text-xs text-red-500 font-medium">{{ err('ringkasan_judul') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="tanggal" class="text-xs font-medium">Tanggal Briefing <span class="text-red-500">*</span></Label>
              <DatePicker
                id="tanggal"
                v-model="form.tanggal"
              />
            </div>

            <div class="space-y-1.5">
              <Label for="penerima" class="text-xs font-medium">Penerima Contoh <span class="text-red-500">*</span></Label>
              <Input
                id="penerima"
                v-model="form.penerima"
                placeholder="Direksi & Manajer Risiko"
                class="h-9 text-xs"
              />
            </div>

            <StatusSelectWithBadge
              id="status-briefing"
              v-model="form.status"
              label="Status"
              :required="true"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="organisasi" class="text-xs font-medium">Nama Organisasi / Kelompok Anggota <span class="text-red-500">*</span></Label>
            <Input
              id="organisasi"
              v-model="form.organisasi"
              placeholder="e.g. Anggota Korporasi Strategis ISF"
              class="h-9 text-xs"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="ringkasan_catatan" class="text-xs font-medium">Catatan Ringkasan Pembuka <span class="text-red-500">*</span></Label>
            <textarea
              id="ringkasan_catatan"
              v-model="form.ringkasan_catatan"
              rows="2"
              placeholder="Ikhtisar utama minggu ini..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Ringkasan Data Anggota -->
      <RepeatableRows
        v-model="form.ringkasan_data"
        :fields="ringkasanDataFields"
        legend="Ringkasan Data & Indikator Anggota"
        description="Data ringkas yang ditampilkan dalam kotak sorotan numerik surel."
        addLabel="Tambah Indikator"
        :max="10"
      />

      <!-- Poin-Poin Utama (Tepat 3) -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Tiga Poin Analisis Utama (Poin Briefing)</CardTitle>
          <CardDescription class="text-xs">Surel briefing mewajibkan tepat 3 poin analisis substansial.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="pembuka" class="text-xs font-medium">Kalimat Pembuka Bagian Analisis <span class="text-red-500">*</span></Label>
            <textarea
              id="pembuka"
              v-model="form.pembuka"
              rows="2"
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>

          <!-- The 3 items -->
          <RepeatableRows
            v-model="form.poin"
            :fields="poinFields"
            legend="Daftar 3 Poin Utama"
            addLabel="Tambah Poin"
            :min="3"
            :max="3"
          />

          <div class="space-y-1.5 pt-2">
            <Label for="penutup" class="text-xs font-medium">Kalimat Penutup Briefing <span class="text-red-500">*</span></Label>
            <textarea
              id="penutup"
              v-model="form.penutup"
              rows="2"
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="cta_url" class="text-xs font-medium">Target URL Tombol Utama (CTA URL) <span class="text-red-500">*</span></Label>
            <Input
              id="cta_url"
              v-model="form.cta_url"
              placeholder="/anggota atau https://..."
              class="h-9 text-xs font-mono"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Tautan Tambahan -->
      <RepeatableRows
        v-model="form.tautan"
        :fields="tautanFields"
        legend="Tautan Langsung Tambahan di Footer Surel"
        addLabel="Tambah Tautan"
        :max="10"
      />

      <!-- Bottom buttons removed per requirement (top bar only) -->
    </form>
  </div>
</template>
