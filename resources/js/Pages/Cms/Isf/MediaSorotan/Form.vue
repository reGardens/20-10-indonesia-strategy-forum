<script setup>
import { computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import ImageUploadTab from '@/Components/Cms/ImageUploadTab.vue'
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
  DatePicker,
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue,
  RichTextEditor
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

const { form, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.mediaSorotan,
  id: props.id,
  indexHref: '/cms/media-sorotan',
  defaults: {
    title: '',
    imageUrl: '',
    mediaUrl: '',
    category: 'YouTube',
    publishedAt: new Date().toISOString().slice(0, 10),
    order: 1,
    status: 'terbit',
    author: 'Tim Redaksi & Dewan Pakar ISF',
    summary: '',
    content: '',
    is_popular: false,
    // Tampilan section "Multimedia & Video" di Beranda (kosong = nilai bawaan)
    label: '',
    duration: ''
  }
})

const isVertical = computed(() => {
  const cat = (form?.category || '').toLowerCase()
  return cat.includes('instagram') || cat.includes('tiktok')
})

onMounted(async () => {
  if (!isEdit.value) {
    try {
      const res = await cmsResources.mediaSorotan.list()
      const list = Array.isArray(res) ? res : (res?.data || [])
      const maxOrder = list.reduce((max, it) => Math.max(max, Number(it.order) || 0), 0)
      form.order = maxOrder + 1
    } catch {
      // Fallback
    }
  }
})
</script>

<template>
  <Head :title="(isEdit ? 'Edit Media Sorotan' : 'Tambah Media Sorotan') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/media-sorotan">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Media Sorotan' : 'Tambah Media Sorotan Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data media sorotan #${props.id}` : 'Tambahkan media video sorotan (YouTube / TikTok) dengan embed dan naskah ulasan pendamping.' }}
          </p>
        </div>
      </div>
      <!-- Action buttons -->
      <div class="flex items-center gap-2">
        <a
          v-if="isEdit && props.id"
          :href="`/media/${props.id}`"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md border border-border bg-card hover:bg-muted text-foreground transition shadow-2xs mr-1"
        >
          <IconComponent name="Eye" :size="14" />
          <span>Lihat Halaman Publik</span>
        </a>

        <Link href="/cms/media-sorotan">
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
          {{ saving ? 'Menyimpan...' : 'Simpan Media Sorotan' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data media sorotan..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="save" class="space-y-6">
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Informasi Media &amp; Tautan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Judul tayangan, kategori platform (YouTube / TikTok), analis penyusun, dan tautan video asli.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Title -->
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium text-foreground">Judul Media / Tayangan *</Label>
            <Input
              id="title"
              v-model="form.title"
              placeholder="Contoh: Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan..."
              class="h-9 text-xs font-medium"
            />
            <p v-if="err('title')" class="text-xs text-red-400 mt-1">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Category / Platform -->
            <div class="space-y-1.5">
              <Label for="category" class="text-xs font-medium text-foreground">Kategori Platform</Label>
              <Select v-model="form.category">
                <SelectTrigger id="category" class="h-9 text-xs">
                  <SelectValue placeholder="Pilih Kategori Platform..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="YouTube" class="text-xs font-medium">YouTube (Horizontal 16:9)</SelectItem>
                  <SelectItem value="TikTok" class="text-xs font-medium">TikTok (Vertikal 9:16)</SelectItem>
                  <SelectItem value="Instagram" class="text-xs font-medium">Instagram Reels (Vertikal 9:16)</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="err('category')" class="text-xs text-red-400 mt-1">{{ err('category') }}</p>
            </div>

            <!-- Published Date -->
            <div class="space-y-1.5">
              <Label class="text-xs font-medium text-foreground">Tanggal Publikasi</Label>
              <DatePicker
                v-model="form.publishedAt"
                placeholder="Pilih tanggal upload..."
                class="w-full"
              />
              <p v-if="err('publishedAt')" class="text-xs text-red-400 mt-1">{{ err('publishedAt') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Author / Analis -->
            <div class="space-y-1.5">
              <Label for="author" class="text-xs font-medium text-foreground">Nama Analis / Tim Redaksi</Label>
              <Input
                id="author"
                v-model="form.author"
                placeholder="Contoh: Tim Redaksi ISF, Dewan Pakar Hukum ISF..."
                class="h-9 text-xs"
              />
              <p v-if="err('author')" class="text-xs text-red-400 mt-1">{{ err('author') }}</p>
            </div>

            <!-- Popular Toggle -->
            <div class="space-y-1.5 flex flex-col justify-end">
              <label class="flex items-center gap-2 cursor-pointer pt-2">
                <input
                  type="checkbox"
                  v-model="form.is_popular"
                  class="rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
                />
                <span class="text-xs font-medium text-foreground">Tandai sebagai Populer (Tampil di Tab POPULAR)</span>
              </label>
              <p class="text-[11px] text-muted-foreground">
                Jika dicentang, tayangan ini akan diutamakan saat tab "Popular" dipilih di Beranda.
              </p>
            </div>
          </div>

          <!-- Label Konten & Durasi (section Multimedia & Video di Beranda) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="label" class="text-xs font-medium text-foreground">Label Konten</Label>
              <Input
                id="label"
                v-model="form.label"
                maxlength="40"
                placeholder="Contoh: Diskusi Strategis, Wawancara Eksklusif, Video Analisis..."
                class="h-9 text-xs"
              />
              <p class="text-[11px] text-muted-foreground">
                Tampil di kartu section Multimedia &amp; Video Beranda. Kosongkan untuk label bawaan.
              </p>
              <p v-if="err('label')" class="text-xs text-red-400 mt-1">{{ err('label') }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="duration" class="text-xs font-medium text-foreground">Durasi Video</Label>
              <Input
                id="duration"
                v-model="form.duration"
                inputmode="numeric"
                maxlength="8"
                placeholder="mm:ss, contoh 18:45"
                class="h-9 text-xs font-mono"
              />
              <p class="text-[11px] text-muted-foreground">
                Format mm:ss atau jj:mm:ss. Kosongkan untuk durasi bawaan.
              </p>
              <p v-if="err('duration')" class="text-xs text-red-400 mt-1">{{ err('duration') }}</p>
            </div>
          </div>

          <!-- Media URL -->
          <div class="space-y-1.5">
            <Label for="mediaUrl" class="text-xs font-medium text-foreground">Tautan Video Asli (YouTube / TikTok / Embed)</Label>
            <Input
              id="mediaUrl"
              v-model="form.mediaUrl"
              placeholder="https://www.youtube.com/watch?v=... atau https://www.tiktok.com/@.../video/..."
              class="h-9 text-xs font-mono"
            />
            <p class="text-[11px] text-muted-foreground">
              Sistem secara otomatis akan mengekstrak video ID untuk disematkan (embed player) di halaman detail publik ISF.
            </p>
            <p v-if="err('mediaUrl')" class="text-xs text-red-400 mt-1">{{ err('mediaUrl') }}</p>
          </div>

          <!-- Summary / Deskripsi Singkat Analisis -->
          <div class="space-y-1.5">
            <Label for="summary" class="text-xs font-medium text-foreground">Ringkasan Eksekutif &amp; Deskripsi Analisis</Label>
            <textarea
              id="summary"
              v-model="form.summary"
              rows="3"
              placeholder="Jelaskan ringkasan analisis, topik utama diskusi meja bundar, atau poin penting video..."
              class="w-full rounded-md border border-input bg-background p-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring leading-relaxed"
            />
            <p class="text-[11px] text-muted-foreground">
              Ringkasan ini akan tampil sebagai quote sorotan kunci di halaman detail video.
            </p>
            <p v-if="err('summary')" class="text-xs text-red-400 mt-1">{{ err('summary') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Card: Naskah Artikel Pendamping (Companion Article) -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold text-foreground">Naskah Ulasan &amp; Artikel Pendamping (Format Meja Bundar)</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            Artikel pendamping mendalam yang mendampingi pemutar video di halaman detail publik (format tata letak ala EU Reporter Roundtable Article). Jika dikosongkan, sistem akan menyediakan format analisis standar secara otomatis.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <RichTextEditor
            v-model="form.content"
            placeholder="Tuliskan naskah ulasan, poin perdebatan panelis, konteks regulasi, dan implikasi kebijakan di sini..."
          />
          <p v-if="err('content')" class="text-xs text-red-500 font-medium mt-1">{{ err('content') }}</p>
        </CardContent>
      </Card>

      <!-- Thumbnail & Display Settings Card -->
      <Card class="bg-card border-border shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold text-foreground">Thumbnail &amp; Pengaturan Tampilan</CardTitle>
          <CardDescription class="text-xs text-muted-foreground">
            {{ isVertical ? 'Visual cover thumbnail vertikal (rasio 9:16 untuk TikTok / Reels), nomor urut tampilan, dan status penayangan.' : 'Visual cover thumbnail video horizontal (1280x720 px — rasio 16:9 untuk YouTube), nomor urut tampilan, dan status penayangan.' }}
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Thumbnail Image Upload -->
          <div class="space-y-1.5">
            <Label class="text-xs font-medium text-foreground">
              Thumbnail Gambar {{ isVertical ? '(Rasio 9:16 Vertikal — TikTok / Reels)' : '(1280x720 px — Rasio 16:9 YouTube)' }}
            </Label>
            <ImageUploadTab
              v-model="form.imageUrl"
              :aspect-class="isVertical ? 'aspect-[9/16] max-w-[240px]' : 'aspect-video'"
              placeholder="https://... atau /images/..."
            />
            <p v-if="err('imageUrl')" class="text-xs text-red-400 mt-1">{{ err('imageUrl') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Order -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <Label for="order" class="text-xs font-medium text-foreground">Urutan Tampil (Order)</Label>
                <span v-if="!isEdit" class="text-[11px] text-muted-foreground font-normal">
                  (Otomatis nomor berikutnya)
                </span>
              </div>
              <Input
                id="order"
                type="number"
                min="1"
                max="99"
                v-model.number="form.order"
                class="h-9 text-xs w-full font-mono"
              />
              <p class="text-[11px] text-muted-foreground">
                Urutan prioritas penayangan di Beranda (1 = prioritas paling awal).
              </p>
              <p v-if="err('order')" class="text-xs text-red-400 mt-1">{{ err('order') }}</p>
            </div>

            <!-- Status with badge -->
            <StatusSelectWithBadge
              id="status-media"
              v-model="form.status"
              label="Status Publikasi"
              :required="true"
            />
          </div>
        </CardContent>
      </Card>
    </form>
  </div>
</template>
