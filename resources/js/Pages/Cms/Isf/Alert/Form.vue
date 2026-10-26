<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { PRIORITIES, STATUSES } from '@/Pages/Cms/Isf/options'
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

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.alert,
  id: props.id,
  indexHref: '/cms/alert',
  defaults: {
    title: '',
    summary: '',
    priority: 'kuning',
    date: new Date().toISOString().slice(0, 10),
    sektor: 'Perbankan & Jasa Keuangan',
    link_url: '',
    status: 'draf'
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Alert' : 'Tambah Alert Baru') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/alert">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Alert Peringatan' : 'Tambah Alert Peringatan Dini' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            Umpan intelijen peringatan dini regulasi untuk Portal Anggota ISF.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/cms/alert">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Alert' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat alert..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Detail Peringatan Intelijen</CardTitle>
          <CardDescription class="text-xs">Judul peringatan, tingkat urgensi prioritas, dan sektor sasaran.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium">Judul Peringatan Regulasi <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="e.g. Pembatasan Transaksi Valas Tertentu Berpotensi Diterbitkan Akhir Kuartal"
              class="h-9 text-sm"
            />
            <p v-if="err('title')" class="text-xs text-red-500 font-medium">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="priority" class="text-xs font-medium">Tingkat Prioritas <span class="text-red-500">*</span></Label>
              <select
                id="priority"
                v-model="form.priority"
                class="w-full h-9 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring font-medium"
              >
                <option v-for="p in PRIORITIES" :key="p.value" :value="p.value">
                  {{ p.label }}
                </option>
              </select>
            </div>

            <div class="space-y-1.5">
              <Label for="sektor" class="text-xs font-medium">Sektor Terkait <span class="text-red-500">*</span></Label>
              <Input
                id="sektor"
                v-model="form.sektor"
                placeholder="Perbankan & Keuangan"
                class="h-9 text-xs"
              />
              <p v-if="err('sektor')" class="text-xs text-red-500 font-medium">{{ err('sektor') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="date" class="text-xs font-medium">Tanggal Alert <span class="text-red-500">*</span></Label>
              <Input
                id="date"
                type="date"
                v-model="form.date"
                class="h-9 text-xs"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="summary" class="text-xs font-medium">Uraian Ringkasan Peringatan <span class="text-red-500">*</span></Label>
            <textarea
              id="summary"
              v-model="form.summary"
              rows="3"
              placeholder="Jelaskan secara ringkas implikasi regulasi bagi operasional dan kepatuhan anggota..."
              class="w-full rounded-md border border-input bg-background p-2.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <p v-if="err('summary')" class="text-xs text-red-500 font-medium">{{ err('summary') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="link_url" class="text-xs font-medium">Tautan Dokumen / Regulasi (URL)</Label>
              <Input
                id="link_url"
                v-model="form.link_url"
                placeholder="e.g. /isu/kuhp-baru-pelaku-usaha atau https://..."
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">Tautan detail rujukan analisis atau dokumen primer.</p>
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
        </CardContent>
      </Card>

      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/cms/alert">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Alert' }}
        </Button>
      </div>
    </form>
  </div>
</template>
