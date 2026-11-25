<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { useCmsForm } from '@/Composables/useCmsForm'
import { activeCategories as CATEGORIES, loadCategories, getSubcategoriesFor } from '@/Pages/Cms/Isf/options'
import RepeatableRows from '@/Pages/Cms/components/shared/RepeatableRows.vue'
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
  RichTextEditor,
  DatePicker,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const inertiaPage = usePage()
const authUser = computed(() => inertiaPage.props.auth?.user)
const isMember = computed(() => {
  const roles = authUser.value?.roles || []
  const roleNames = Array.isArray(roles) ? roles.map((r) => typeof r === 'string' ? r : (r?.name || '')) : []
  const hasMemberRole = roleNames.includes('member')
  const hasStaffRole = roleNames.some((r) => ['admin', 'super-admin', 'editor'].includes(r))
  return hasMemberRole && !hasStaffRole
})

const props = defineProps({
  id: { type: Number, default: null }
})

const isEdit = computed(() => Boolean(props.id))

// Track whether the user has manually edited the slug (to stop auto-gen)
let slugTouched = false

const factBlockFields = [
  { key: 'claim', label: 'Klaim / Poin Fakta', type: 'textarea', span: 'sm:col-span-2 lg:col-span-3', required: true, placeholder: 'Klaim atau ringkasan data penting...' },
  { key: 'sourceTitle', label: 'Nama Sumber', type: 'text', required: true, placeholder: 'e.g. Kementerian ESDM' },
  { key: 'sourceUrl', label: 'URL Sumber (https://)', type: 'url', required: true, placeholder: 'https://...' },
  { key: 'sourceDate', label: 'Tanggal Sumber', type: 'date', required: true }
]

const { form, errors, err, loading, loadError, saving, save, load } = useCmsForm({
  resource: cmsResources.artikel,
  id: props.id,
  indexHref: '/cms/artikel',
  defaults: {
    slug: '',
    title: '',
    category: 'kebijakan',
    subcategory: '',
    published_at: new Date().toISOString().slice(0, 10),
    summary: '',
    body: '',
    author_name: '',
    author_role: '',
    author_avatar: '',
    reading_minutes: 5,
    image_alt: '',
    image_url: '',
    link_url: '',
    fact_blocks: [],
    is_hero: false,
    show_in_running_text: false,
    running_text_order: null,
    show_in_top_rank: false,
    top_rank_order: null,
    is_expert_opinion: false,
    expert_name: '',
    expert_title: '',
    expert_avatar: '',
    show_in_terkini: true,
    is_popular: false,
    popular_rank: null,
    status: 'draf'
  }
})

const isSlugManual = ref(false)
function getAuthorInitials(name) {
  if (!name) return 'M'
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
}

function getCanonicalDetailUrl(cat, slug) {
  if (!slug) return ''
  const c = cat || 'kebijakan'
  return `/artikel/${c}/${slug}`
}

function generateSlug(val) {
  if (!val) return ''
  return val
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)+/g, '')
}

function onTitleInput() {
  if (isEdit.value) return
  if (!form.title || !form.title.trim()) {
    form.slug = ''
    isSlugManual.value = false
    form.link_url = ''
    return
  }
  if (!isSlugManual.value || !form.slug) {
    form.slug = generateSlug(form.title)
  }
  form.link_url = getCanonicalDetailUrl(form.category, form.slug)
}

function onSlugInput() {
  if (!form.slug || !form.slug.trim()) {
    isSlugManual.value = false
    form.slug = form.title ? generateSlug(form.title) : ''
  } else {
    isSlugManual.value = form.slug !== generateSlug(form.title)
  }
  form.link_url = getCanonicalDetailUrl(form.category, form.slug)
}

async function handleSave() {
  if (isMember.value) {
    if (!form.author_name) form.author_name = authUser.value?.name || ''
    if (!form.author_role) form.author_role = authUser.value?.author_role || 'Member ISF'
    form.author_avatar = authUser.value?.author_avatar || ''
  }
  form.link_url = getCanonicalDetailUrl(form.category, form.slug)
  await save()
}

onMounted(async () => {
  await loadCategories()
  if (isMember.value) {
    if (!form.author_name) {
      form.author_name = authUser.value?.name || ''
    }
    if (!form.author_role) {
      form.author_role = authUser.value?.author_role || 'Member ISF'
    }
    form.author_avatar = authUser.value?.author_avatar || ''
  }
  if (!isEdit.value && !form.link_url && form.slug) {
    form.link_url = getCanonicalDetailUrl(form.category, form.slug)
  }
})

watch(
  () => authUser.value,
  (user) => {
    if (isMember.value && user) {
      if (!form.author_name) form.author_name = user.name || ''
      if (!form.author_role) form.author_role = user.author_role || 'Member ISF'
      form.author_avatar = user.author_avatar || ''
    }
  },
  { immediate: true }
)

const availableSubcategories = computed(() => getSubcategoriesFor(form.category))

const subcategoryValue = computed({
  get: () => form.subcategory || '__NONE__',
  set: (val) => {
    form.subcategory = val === '__NONE__' ? '' : val
  }
})

watch(
  () => form.category,
  (newCat) => {
    const validValues = availableSubcategories.value.map((s) => s.value)
    if (form.subcategory && !validValues.includes(form.subcategory)) {
      form.subcategory = ''
    }
    if (form.slug) {
      form.link_url = getCanonicalDetailUrl(newCat, form.slug)
    }
  }
)

watch(
  () => form.title,
  (newVal) => {
    if (isEdit.value) return
    if (!newVal || !newVal.trim()) {
      form.slug = ''
      isSlugManual.value = false
      form.link_url = ''
      return
    }
    if (!isSlugManual.value || !form.slug) {
      form.slug = generateSlug(newVal)
    }
    form.link_url = getCanonicalDetailUrl(form.category, form.slug)
  }
)

watch(
  [() => form.slug, () => form.category],
  ([newSlug, newCat]) => {
    if (!isEdit.value && newSlug) {
      form.link_url = getCanonicalDetailUrl(newCat, newSlug)
    }
  }
)
</script>

<template>
  <Head :title="(isEdit ? 'Edit Artikel' : 'Tambah Artikel') + ' — Indonesia Strategy Forum'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/artikel">
          <Button variant="outline" size="sm" class="h-8 w-8 p-0">
            <IconComponent name="ChevronLeft" :size="16" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ isEdit ? 'Edit Artikel' : 'Tambah Artikel Baru' }}
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            {{ isEdit ? `Mengubah data artikel #${props.id}` : (isMember ? 'Tulis naskah artikel Anda untuk diajukan ke redaksi Indonesia Strategy Forum.' : 'Isi formulir berikut untuk menerbitkan artikel baru.') }}
          </p>
        </div>
      </div>
      <!-- TOP action buttons only -->
      <div class="flex items-center gap-2">
        <Link href="/cms/artikel">
          <Button variant="outline" size="sm" class="h-9 text-xs">
            Batal
          </Button>
        </Link>
        <Button
          type="button"
          @click="handleSave"
          :disabled="saving"
          class="h-9 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 shadow-sm"
        >
          <IconComponent v-if="saving" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
          <IconComponent v-else name="Check" :size="14" class="mr-1.5" />
          {{ saving ? 'Menyimpan...' : 'Simpan Artikel' }}
        </Button>
      </div>
    </div>

    <!-- Loading / Error states -->
    <LoadingIndicator v-if="loading" text="Memuat data artikel..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="load" />

    <!-- Form Content -->
    <form v-else @submit.prevent="handleSave" class="space-y-6">
      <!-- Informasi Utama -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Informasi Utama</CardTitle>
          <CardDescription class="text-xs">Judul, slug unik, kategori dan status publikasi artikel.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="title" class="text-xs font-medium">Judul Artikel <span class="text-red-500">*</span></Label>
            <Input
              id="title"
              v-model="form.title"
              @input="onTitleInput"
              placeholder="e.g. Arah Kebijakan Energi Baru Terbarukan 2026"
              class="h-9 text-sm"
            />
            <p v-if="err('title')" class="text-xs text-red-500 font-medium">{{ err('title') }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="slug" class="text-xs font-medium">Slug URL <span class="text-red-500">*</span></Label>
              <Input
                id="slug"
                v-model="form.slug"
                @input="onSlugInput"
                placeholder="Dibuat otomatis dari judul..."
                class="h-9 font-mono text-xs"
              />
              <p class="text-[11px] text-muted-foreground">Otomatis dari judul. Bisa diubah manual.</p>
              <p v-if="err('slug')" class="text-xs text-red-500 font-medium">{{ err('slug') }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="category" class="text-xs font-medium">Kategori <span class="text-red-500">*</span></Label>
              <Select v-model="form.category">
                <SelectTrigger id="category" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Select Kategori" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="cat in CATEGORIES"
                    :key="cat.value"
                    :value="cat.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ cat.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="err('category')" class="text-xs text-red-500 font-medium">{{ err('category') }}</p>
            </div>

            <!-- Sub-Kategori (jika ada) -->
            <div v-if="availableSubcategories.length > 0" class="space-y-1.5 sm:col-span-2">
              <Label for="subcategory" class="text-xs font-medium">Sub-Kategori</Label>
              <Select v-model="subcategoryValue">
                <SelectTrigger id="subcategory" class="h-9 text-xs font-medium bg-background border-input">
                  <SelectValue placeholder="Select Sub-Kategori" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__NONE__" class="text-xs font-medium cursor-pointer">
                    -- Tanpa Sub-Kategori (Utama) --
                  </SelectItem>
                  <SelectItem
                    v-for="sub in availableSubcategories"
                    :key="sub.value"
                    :value="sub.value"
                    class="text-xs font-medium cursor-pointer"
                  >
                    {{ sub.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p class="text-[11px] text-muted-foreground">
                Tentukan sub-rubrik artikel (mis. Pemilu & Pilkada, DPR/DPD/MPR, Eksekutif, Dinamika Partai).
              </p>
              <p v-if="err('subcategory')" class="text-xs text-red-500 font-medium">{{ err('subcategory') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5" :class="{ 'sm:col-span-2': isMember }">
              <Label for="published_at" class="text-xs font-medium">Tanggal Publikasi <span class="text-red-500">*</span></Label>
              <DatePicker
                id="published_at"
                v-model="form.published_at"
              />
              <p v-if="err('published_at')" class="text-xs text-red-500 font-medium">{{ err('published_at') }}</p>
            </div>

            <StatusSelectWithBadge
              v-if="!isMember"
              id="status-artikel"
              v-model="form.status"
              label="Status"
              :required="true"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="summary" class="text-xs font-medium">Ringkasan Eksekutif (Summary)</Label>
            <textarea
              id="summary"
              v-model="form.summary"
              rows="3"
              placeholder="Ringkasan singkat artikel (tampil di kartu Beranda / hero)..."
              class="w-full rounded-md border border-input bg-background p-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring leading-relaxed"
            />
            <p v-if="err('summary')" class="text-xs text-red-500 font-medium">{{ err('summary') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Penulis & Tautan -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Penulis, Tautan & Media</CardTitle>
          <CardDescription class="text-xs">Detail atribusi penulis, target navigasi, dan aset grafis artikel.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Estimasi Waktu Baca (untuk member, identitas profil penulis otomatis di latar belakang) -->
          <div v-if="isMember" class="w-full sm:w-64 space-y-1.5">
            <Label for="reading_minutes_member" class="text-xs font-medium">Estimasi Waktu Baca (Menit)</Label>
            <Input
              id="reading_minutes_member"
              type="number"
              v-model.number="form.reading_minutes"
              min="1"
              class="h-9 text-xs"
            />
            <p v-if="err('reading_minutes')" class="text-xs text-red-500 font-medium">{{ err('reading_minutes') }}</p>
          </div>

          <!-- JIKA ROLE BUKAN MEMBER: Tampilkan input manual lengkap -->
          <template v-else>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="space-y-1.5">
                <Label for="author_name" class="text-xs font-medium">Nama Penulis / Tim Riset</Label>
                <Input
                  id="author_name"
                  v-model="form.author_name"
                  placeholder="e.g. Tim Riset Kebijakan ISF"
                  class="h-9 text-xs"
                />
                <p v-if="err('author_name')" class="text-xs text-red-500 font-medium">{{ err('author_name') }}</p>
              </div>

              <div class="space-y-1.5">
                <Label for="author_role" class="text-xs font-medium">Jabatan / Keahlian</Label>
                <Input
                  id="author_role"
                  v-model="form.author_role"
                  placeholder="e.g. Analis Regulasi"
                  class="h-9 text-xs"
                />
                <p v-if="err('author_role')" class="text-xs text-red-500 font-medium">{{ err('author_role') }}</p>
              </div>

              <div class="space-y-1.5">
                <Label for="reading_minutes" class="text-xs font-medium">Estimasi Waktu Baca (Menit)</Label>
                <Input
                  id="reading_minutes"
                  type="number"
                  v-model.number="form.reading_minutes"
                  min="1"
                  class="h-9 text-xs"
                />
                <p v-if="err('reading_minutes')" class="text-xs text-red-500 font-medium">{{ err('reading_minutes') }}</p>
              </div>
            </div>

            <!-- Foto Penulis Kecil Ukuran 1:1 -->
            <div class="space-y-1.5 p-3.5 rounded-lg border border-border bg-muted/20">
              <Label class="text-xs font-semibold flex items-center gap-1.5">
                <IconComponent name="User" :size="14" class="text-isf-red" />
                <span>Foto Penulis (Ukuran 1:1 Kecil)</span>
              </Label>
              <p class="text-[11px] text-muted-foreground">
                Unggah foto profil penulis dengan rasio aspek persegi 1:1 untuk identitas artikel.
              </p>
              <ImageUploadTab
                v-model="form.author_avatar"
                aspect-class="aspect-square w-24 h-24 max-h-24"
                preview-label="Pratinjau Foto Penulis (1:1)"
                placeholder="https://... (URL foto penulis 1:1)"
              />
              <p v-if="err('author_avatar')" class="text-xs text-red-500 font-medium">{{ err('author_avatar') }}</p>
            </div>
          </template>

          <!-- Image URL — tabbed URL/Upload with preview -->
          <div class="space-y-1.5">
            <Label class="text-xs font-medium">URL Gambar Sampul</Label>
            <ImageUploadTab
              v-model="form.image_url"
              aspect-class="aspect-[16/9]"
              placeholder="https://..."
            />
            <p v-if="err('image_url')" class="text-xs text-red-500 font-medium">{{ err('image_url') }}</p>
          </div>

          <div v-if="form.image_url" class="space-y-1.5">
            <Label for="image_alt" class="text-xs font-medium">Deskripsi Gambar (Alt Text) <span class="text-red-500">*</span></Label>
            <Input
              id="image_alt"
              v-model="form.image_alt"
              placeholder="Deskripsi singkat gambar untuk aksesibilitas..."
              class="h-9 text-xs"
            />
            <p v-if="err('image_alt')" class="text-xs text-red-500 font-medium">{{ err('image_alt') }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Penempatan di Halaman Publik (khusus staf redaksi/admin) -->
      <Card v-if="!isMember" class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Penempatan Halaman Publik</CardTitle>
          <CardDescription class="text-xs">Tentukan letak artikel ditampilkan di Beranda.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="grid grid-cols-1 gap-4">
            <!-- Artikel Utama (Hero) -->
            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-border bg-card cursor-pointer hover:border-red-500/50 transition">
              <input
                type="checkbox"
                v-model="form.is_hero"
                class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
              />
              <div>
                <div class="text-xs font-semibold text-foreground">Artikel Utama (Hero)</div>
                <div class="text-[11px] text-muted-foreground mt-0.5">Tampil sebagai headline utama di Beranda.</div>
              </div>
            </label>

            <!-- Sorotan / Teks Berjalan (Running Text) -->
            <div class="p-3 rounded-lg border border-border bg-card space-y-2.5">
              <label class="flex items-start gap-2.5 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.show_in_running_text"
                  class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
                />
                <div>
                  <div class="text-xs font-semibold text-foreground">Sorotan (Teks Berjalan / Running Text)</div>
                  <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan judul artikel ini pada pita teks berjalan Beranda (maksimal 5 artikel aktif).</div>
                </div>
              </label>
              <div v-if="form.show_in_running_text" class="pl-6.5 pt-1 flex items-center gap-2">
                <label for="running_text_order" class="text-xs text-muted-foreground whitespace-nowrap">Urutan Running Text:</label>
                <select
                  id="running_text_order"
                  v-model.number="form.running_text_order"
                  class="h-8 px-2.5 text-xs rounded-md border border-input bg-background text-foreground"
                >
                  <option :value="null">Otomatis (Sesuai Slot Tersedia)</option>
                  <option :value="1">Running Text 1</option>
                  <option :value="2">Running Text 2</option>
                  <option :value="3">Running Text 3</option>
                  <option :value="4">Running Text 4</option>
                  <option :value="5">Running Text 5</option>
                </select>
              </div>
            </div>

            <!-- Top Artikel (Top 1 s/d 3 of the Week) -->
            <div class="p-3 rounded-lg border border-border bg-card space-y-2.5">
              <label class="flex items-start gap-2.5 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.show_in_top_rank"
                  class="mt-0.5 rounded border-input text-red-600 focus:ring-red-500 h-4 w-4"
                />
                <div>
                  <div class="text-xs font-semibold text-foreground">Top Artikel (Top of the Week)</div>
                  <div class="text-[11px] text-muted-foreground mt-0.5">Tampilkan pada modul slider Top Artikel di Beranda (maksimal 3 artikel aktif).</div>
                </div>
              </label>
              <div v-if="form.show_in_top_rank" class="pl-6.5 pt-1 flex items-center gap-2">
                <label for="top_rank_order" class="text-xs text-muted-foreground whitespace-nowrap">Peringkat:</label>
                <select
                  id="top_rank_order"
                  v-model.number="form.top_rank_order"
                  class="h-8 px-2.5 text-xs rounded-md border border-input bg-background text-foreground"
                >
                  <option :value="null">Otomatis (Sesuai Slot Tersedia)</option>
                  <option :value="1">Top Artikel 1 (Gold)</option>
                  <option :value="2">Top Artikel 2 (Red)</option>
                  <option :value="3">Top Artikel 3 (Silver)</option>
                </select>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Blok Fakta Kunci -->
      <RepeatableRows
        v-model="form.fact_blocks"
        :fields="factBlockFields"
        legend="Blok Fakta Kunci (Key Fact Blocks)"
        description="Fakta atau temuan penting yang disorot dengan rujukan sumber primer."
        addLabel="Tambah Fakta Kunci"
        :max="10"
      />

      <!-- Isi Artikel -->
      <Card class="border border-border/80 shadow-xs">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Konten Lengkap Artikel</CardTitle>
          <CardDescription class="text-xs">Gunakan editor rich-text untuk menyusun naskah analisis.</CardDescription>
        </CardHeader>
        <CardContent>
          <RichTextEditor
            v-model="form.body"
            placeholder="Tulis naskah lengkap artikel di sini..."
          />
          <p v-if="err('body')" class="text-xs text-red-500 font-medium mt-1">{{ err('body') }}</p>
        </CardContent>
      </Card>
      <!-- No bottom buttons per requirement -->
    </form>
  </div>
</template>
