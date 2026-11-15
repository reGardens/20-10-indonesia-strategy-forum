<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
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
  CardContent
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const presetColors = [
  { name: 'Merah (PDIP)', hex: '#D61C24' },
  { name: 'Kuning (Golkar)', hex: '#FFD700' },
  { name: 'Jingga/Krem (Gerindra)', hex: '#D97706' },
  { name: 'Biru Tua (NasDem)', hex: '#1E3A8A' },
  { name: 'Hijau (PKB)', hex: '#059669' },
  { name: 'Oranye (PKS)', hex: '#EA580C' },
  { name: 'Biru (PAN)', hex: '#2563EB' },
  { name: 'Biru Terang (Demokrat)', hex: '#1D4ED8' },
  { name: 'Merah ISF', hex: '#E10600' },
  { name: 'Abu-abu (Independen)', hex: '#6B7280' },
]

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.kursiParlemen,
  id: props.id,
  indexHref: '/cms/kursi-parlemen',
  defaults: {
    periode: '2024-2029',
    lembaga: 'DPR',
    partai: '',
    singkatan: '',
    jumlah_kursi: 0,
    warna_partai: '#D61C24',
    urutan: 0,
    status: 'terbit'
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Kursi Parlemen' : 'Tambah Kursi Parlemen') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/kursi-parlemen">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Kursi Parlemen' : 'Tambah Kursi Parlemen' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data kursi parlemen #${props.id}` : 'Tambahkan komposisi kursi parlemen untuk visualisasi infografis interaktif.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/kursi-parlemen">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Kursi' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data kursi parlemen..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Lembaga & Periode</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Tentukan lembaga parlemen dan periode masa jabatan.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Lembaga -->
            <div class="space-y-1.5">
              <Label for="lembaga" class="text-xs font-medium text-foreground">Lembaga Parlemen *</Label>
              <div class="relative">
                <select
                  id="lembaga"
                  v-model="form.lembaga"
                  class="w-full h-9 rounded-md border border-input bg-background pl-3 pr-9 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring appearance-none"
                >
                  <option value="DPR">DPR RI (Dewan Perwakilan Rakyat)</option>
                  <option value="DPD">DPD RI (Dewan Perwakilan Daerah)</option>
                  <option value="MPR">MPR RI (Majelis Permusyawaratan Rakyat)</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p v-if="err('lembaga')" class="text-xs text-red-400 mt-1">{{ err('lembaga') }}</p>
            </div>

            <!-- Periode -->
            <div class="space-y-1.5">
              <Label for="periode" class="text-xs font-medium text-foreground">Periode Masa Jabatan *</Label>
              <Input
                id="periode"
                v-model="form.periode"
                placeholder="Contoh: 2024-2029"
                class="h-9 text-xs font-mono"
              />
              <p v-if="err('periode')" class="text-xs text-red-400 mt-1">{{ err('periode') }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Data Partai & Perolehan Kursi</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Nama partai politik / fraksi, jumlah perolehan kursi, dan warna representasi grafis.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Nama Partai -->
            <div class="space-y-1.5">
              <Label for="partai" class="text-xs font-medium text-foreground">Nama Partai / Fraksi *</Label>
              <Input
                id="partai"
                v-model="form.partai"
                placeholder="Contoh: Partai Demokrasi Indonesia Perjuangan"
                class="h-9 text-xs"
              />
              <p v-if="err('partai')" class="text-xs text-red-400 mt-1">{{ err('partai') }}</p>
            </div>

            <!-- Singkatan -->
            <div class="space-y-1.5">
              <Label for="singkatan" class="text-xs font-medium text-foreground">Singkatan / Label Ringkas</Label>
              <Input
                id="singkatan"
                v-model="form.singkatan"
                placeholder="Contoh: PDI Perjuangan, Golkar, Gerindra..."
                class="h-9 text-xs"
              />
              <p v-if="err('singkatan')" class="text-xs text-red-400 mt-1">{{ err('singkatan') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Jumlah Kursi -->
            <div class="space-y-1.5">
              <Label for="jumlah_kursi" class="text-xs font-medium text-foreground">Jumlah Perolehan Kursi *</Label>
              <Input
                id="jumlah_kursi"
                type="number"
                min="0"
                max="10000"
                v-model.number="form.jumlah_kursi"
                class="h-9 text-xs font-semibold"
              />
              <p v-if="err('jumlah_kursi')" class="text-xs text-red-400 mt-1">{{ err('jumlah_kursi') }}</p>
            </div>

            <!-- Urutan -->
            <div class="space-y-1.5">
              <Label for="urutan" class="text-xs font-medium text-foreground">Urutan Tampilan</Label>
              <Input
                id="urutan"
                type="number"
                min="0"
                v-model.number="form.urutan"
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">Urutan posisi di legenda dan hemicycle (angka lebih kecil = lebih depan).</p>
            </div>
          </div>

          <!-- Warna Partai -->
          <div class="space-y-2 pt-2 border-t border-border">
            <Label for="warna_partai" class="text-xs font-medium text-foreground">Warna Partai (Kode Hex / Visualisasi) *</Label>
            <div class="flex items-center gap-3">
              <input
                type="color"
                v-model="form.warna_partai"
                class="h-9 w-12 rounded border border-border cursor-pointer bg-card p-0.5"
              />
              <Input
                id="warna_partai"
                v-model="form.warna_partai"
                placeholder="#D61C24"
                class="h-9 text-xs font-mono w-36 uppercase"
              />
              <div
                class="h-9 px-3 rounded flex items-center text-xs font-bold text-white shadow-2xs"
                :style="{ backgroundColor: form.warna_partai }"
              >
                Pratinjau Warna
              </div>
            </div>

            <!-- Preset color pills -->
            <div class="pt-1.5">
              <span class="text-[11px] text-muted-foreground block mb-1.5">Pilihan Cepat Warna Partai:</span>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="p in presetColors"
                  :key="p.hex"
                  type="button"
                  @click="form.warna_partai = p.hex"
                  class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-[11px] border border-border bg-card/60 hover:bg-muted transition"
                >
                  <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: p.hex }"></span>
                  <span>{{ p.name }}</span>
                </button>
              </div>
            </div>
            <p v-if="err('warna_partai')" class="text-xs text-red-400 mt-1">{{ err('warna_partai') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Status Publikasi -->
      <Card class="bg-card border-border shadow-sm">
        <CardContent class="py-4">
          <div class="flex items-center justify-between">
            <div>
              <Label class="text-xs font-medium text-foreground">Status Publikasi</Label>
              <p class="text-[11px] text-muted-foreground">Hanya data berstatus "Terbit" yang ditampilkan di infografis publik.</p>
            </div>
            <StatusSelectWithBadge v-model="form.status" />
          </div>
          <p v-if="err('status')" class="text-xs text-red-400 mt-1">{{ err('status') }}</p>
        </CardContent>
      </Card>
    </form>
  </div>
</template>

