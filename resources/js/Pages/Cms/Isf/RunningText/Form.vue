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
  CardContent,
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.runningText,
  id: props.id,
  indexHref: '/cms/running-text',
  defaults: {
    badge: 'Sorotan',
    announcement: '',
    info: 'Edisi Pekan Ini',
    url: '',
    status: 'terbit'
  }
})

const defaultBadgeOptions = ['Sorotan', 'Politik', 'Kebijakan Publik', 'Hukum', 'Pengumuman', 'Agenda', 'Opini Publik', 'Survei', 'Informasi']

const badgeOptions = computed(() => {
  const list = [...defaultBadgeOptions]
  if (form.badge && !list.includes(form.badge)) {
    list.unshift(form.badge)
  }
  return list.map(item => ({ value: item, label: item }))
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Running Text' : 'Tambah Running Text') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/running-text">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Running Text' : 'Tambah Running Text Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data teks running #${props.id}` : 'Tambahkan baris teks berjalan baru untuk marquee di bagian paling atas Beranda.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <Link href="/cms/running-text">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Teks Running' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data running text..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Konten Pengumuman</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Teks yang akan berjalan di marquee Beranda, label badge penanda, dan keterangan edisi.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Badge -->
          <div class="space-y-1.5">
            <Label for="badge" class="text-xs font-medium text-foreground">Badge / Label Kategori</Label>
            <Select v-model="form.badge">
              <SelectTrigger id="badge" class="h-9 text-xs font-medium">
                <SelectValue placeholder="Pilih Badge / Label Kategori..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="opt in badgeOptions"
                  :key="opt.value"
                  :value="opt.value"
                  class="text-xs font-medium"
                >
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="err('badge')" class="text-xs text-red-400 mt-1">{{ err('badge') }}</p>
          </div>

          <!-- Teks Pengumuman -->
          <div class="space-y-1.5">
            <Label for="announcement" class="text-xs font-medium text-foreground">Teks Pengumuman (Running Text) *</Label>
            <textarea
              id="announcement"
              v-model="form.announcement"
              rows="3"
              placeholder="Masukkan teks pengumuman yang akan ditampilkan di baris marquee..."
              class="w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
            <p class="text-[11px] text-muted-foreground">
              Teks ini akan bergulir dari kanan ke kiri pada marquee Beranda publik ISF.
            </p>
            <p v-if="err('announcement')" class="text-xs text-red-400 mt-1">{{ err('announcement') }}</p>
          </div>

          <!-- Info / Keterangan Waktu -->
          <div class="space-y-1.5">
            <Label for="info" class="text-xs font-medium text-foreground">Keterangan / Waktu (Opsional)</Label>
            <Input
              id="info"
              v-model="form.info"
              placeholder="Contoh: Edisi Pekan Ini, Diperbarui setiap Senin, Pembaruan Terkini..."
              class="h-9 text-xs"
            />
            <p class="text-[11px] text-muted-foreground">
              Keterangan pelengkap yang akan tampil dalam tanda kurung di samping pengumuman.
            </p>
            <p v-if="err('info')" class="text-xs text-red-400 mt-1">{{ err('info') }}</p>
          </div>

          <!-- Link / URL Tautan Tujuan -->
          <div class="space-y-1.5">
            <Label for="url" class="text-xs font-medium text-foreground">Link / Tautan Tujuan (Opsional)</Label>
            <Input
              id="url"
              v-model="form.url"
              placeholder="Contoh: /artikel/kebijakan/... atau https://..."
              class="h-9 text-xs"
            />
            <p class="text-[11px] text-muted-foreground">
              Tautan yang akan terbuka saat teks berjalan diklik di Beranda publik.
            </p>
            <p v-if="err('url')" class="text-xs text-red-400 mt-1">{{ err('url') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Status Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Status Penayangan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Tentukan apakah baris teks running ini langsung tayang ke publik atau disimpan sebagai draf.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="max-w-xs">
            <StatusSelectWithBadge
              id="status-running-text"
              v-model="form.status"
              label="Status Publikasi"
              :required="true"
            />
            <p v-if="err('status')" class="text-xs text-red-400 mt-1">{{ err('status') }}</p>
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>
