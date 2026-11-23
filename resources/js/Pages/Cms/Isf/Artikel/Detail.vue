<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import { cmsResources } from '@/api/services/isfCmsService'
import { confirmDelete, alertSuccess, alertError } from '@/Composables/useAlert'
import StatusBadge from '@/Pages/Cms/components/shared/StatusBadge.vue'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '@/Pages/Cms/components/shared/ErrorMessage.vue'
import {
  Button,
  Badge,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const page = usePage()
const authUser = computed(() => page.props.auth?.user)
const isMember = computed(() => {
  const roles = authUser.value?.roles || []
  const roleNames = Array.isArray(roles) ? roles.map((r) => typeof r === 'string' ? r : (r?.name || '')) : []
  const hasMemberRole = roleNames.includes('member')
  const hasStaffRole = roleNames.some((r) => ['admin', 'super-admin', 'editor'].includes(r))
  return hasMemberRole && !hasStaffRole
})

const props = defineProps({
  id: { type: Number, required: true }
})

const item = ref(null)
const loading = ref(true)
const loadError = ref('')

async function fetchArticle() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await cmsResources.artikel.get(props.id)
    item.value = data
  } catch (err) {
    loadError.value = err?.response?.data?.message || 'Gagal memuat detail artikel.'
  } finally {
    loading.value = false
  }
}

async function handleDelete() {
  if (!item.value) return
  const confirmed = await confirmDelete(item.value.title)
  if (!confirmed) return
  try {
    await cmsResources.artikel.remove(item.value.id)
    alertSuccess('Artikel berhasil dihapus.')
    router.visit('/cms/artikel')
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal menghapus artikel.')
  }
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

onMounted(fetchArticle)
</script>

<template>
  <Head :title="item ? `${item.title} — Detail Artikel` : 'Detail Artikel'" />

  <div class="p-6 space-y-6 w-full">
    <!-- Header / Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
      <div class="flex items-center gap-3">
        <Link href="/cms/artikel">
          <Button variant="outline" size="sm" class="h-9 gap-1.5 text-xs">
            <IconComponent name="ChevronLeft" :size="14" />
            Kembali
          </Button>
        </Link>
        <div>
          <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
            <span>Detail Artikel</span>
            <span
              v-if="item?.is_by_member && !isMember"
              class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20"
            >
              by member
            </span>
          </h1>
          <p class="text-xs text-muted-foreground mt-0.5">
            ID #{{ id }} • Pratinjau lengkap naskah dan konfigurasi publikasi
          </p>
        </div>
      </div>

      <div v-if="item && !isMember && !item.is_by_member" class="flex items-center gap-2">
        <Link :href="`/cms/artikel/${item.id}/edit`">
          <Button variant="outline" size="sm" class="h-9 text-xs gap-1.5">
            <IconComponent name="Edit" :size="14" />
            Edit Artikel
          </Button>
        </Link>
        <Button
          type="button"
          variant="destructive"
          size="sm"
          @click="handleDelete"
          class="h-9 text-xs gap-1.5"
        >
          <IconComponent name="Trash" :size="14" />
          Hapus
        </Button>
      </div>
    </div>

    <LoadingIndicator v-if="loading" text="Memuat detail artikel..." />
    <ErrorMessage v-else-if="loadError" :message="loadError" @retry="fetchArticle" />

    <div v-else-if="item" class="space-y-6">
      <!-- Overview Card -->
      <Card class="border border-border/80 shadow-xs">
        <CardContent class="p-6 space-y-5">
          <!-- Cover Image if exists -->
          <div v-if="item.image_url" class="relative rounded-xl overflow-hidden aspect-video max-h-80 w-full bg-muted/40 border border-border/60">
            <img
              :src="item.image_url"
              :alt="item.image_alt || item.title"
              class="w-full h-full object-cover"
            />
            <div
              v-if="item.image_alt"
              class="absolute bottom-2 left-2 bg-black/70 text-white text-[11px] px-2.5 py-1 rounded backdrop-blur-xs"
            >
              Alt: {{ item.image_alt }}
            </div>
          </div>

          <!-- Badges & Metadata -->
          <div class="flex flex-wrap items-center gap-2 pt-1">
            <Badge class="bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 text-xs font-semibold capitalize">
              {{ item.category }}
            </Badge>
            <Badge v-if="item.subcategory" variant="outline" class="text-xs font-mono">
              /{{ item.subcategory }}
            </Badge>
            <StatusBadge :status="item.status" />
            <span class="text-xs text-muted-foreground ml-auto whitespace-nowrap">
              Tayang: {{ formatDate(item.published_at) }}
            </span>
          </div>

          <!-- Title -->
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground leading-snug">
              {{ item.title }}
            </h2>
            <div class="flex items-center gap-2 mt-1.5 text-xs text-muted-foreground font-mono">
              <span>Slug:</span>
              <span class="text-foreground bg-muted/60 px-1.5 py-0.5 rounded">{{ item.slug }}</span>
            </div>
          </div>

          <!-- Author Info with 1:1 Photo -->
          <div class="flex items-center gap-3.5 p-3.5 rounded-xl bg-muted/30 border border-border/60">
            <div class="w-12 h-12 rounded-lg overflow-hidden shrink-0 bg-muted border border-border/80 flex items-center justify-center">
              <img
                v-if="item.author_avatar"
                :src="item.author_avatar"
                :alt="item.author_name || 'Penulis'"
                class="w-full h-full object-cover"
              />
              <IconComponent v-else name="User" :size="20" class="text-muted-foreground" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-semibold text-foreground flex items-center gap-2">
                <span>{{ item.author_name || 'Dewan Redaksi ISF' }}</span>
                <span v-if="item.is_by_member && !isMember" class="text-[10px] px-1.5 py-0.2 rounded bg-red-500/10 text-red-600 font-semibold border border-red-500/20">Member</span>
              </div>
              <div class="text-xs text-muted-foreground truncate">
                {{ item.author_role || 'Penulis' }} • Estimasi Baca: {{ item.reading_minutes || 5 }} Menit
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div v-if="item.summary" class="p-4 rounded-xl bg-muted/40 border-l-4 border-l-red-600 space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Ringkasan Eksekutif</span>
            <p class="text-xs sm:text-sm text-foreground leading-relaxed italic">
              "{{ item.summary }}"
            </p>
          </div>

          <!-- Key Fact Blocks -->
          <div v-if="item.fact_blocks && item.fact_blocks.length > 0" class="space-y-2.5">
            <h3 class="text-xs font-bold font-mono uppercase tracking-wider text-muted-foreground">
              Blok Fakta Kunci ({{ item.fact_blocks.length }})
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div
                v-for="(fact, fIdx) in item.fact_blocks"
                :key="fIdx"
                class="p-3 rounded-lg bg-card border border-border/80 text-xs space-y-1.5"
              >
                <div class="font-semibold text-foreground">
                  {{ fact.claim }}
                </div>
                <div class="text-[11px] text-muted-foreground flex items-center justify-between gap-2 pt-1 border-t border-border/40">
                  <span class="truncate font-medium">{{ fact.sourceTitle }}</span>
                  <a
                    v-if="fact.sourceUrl"
                    :href="fact.sourceUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-red-500 hover:underline shrink-0"
                  >
                    Buka Rujukan ↗
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Link Url -->
          <div v-if="item.link_url" class="text-xs text-muted-foreground">
            <span class="font-medium text-foreground">Tautan Eksternal:</span>
            <a :href="item.link_url" target="_blank" rel="noopener noreferrer" class="ml-1 text-red-500 hover:underline">
              {{ item.link_url }}
            </a>
          </div>

          <!-- Body Content -->
          <div class="pt-4 border-t border-border/70 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Naskah Lengkap Artikel
            </h3>
            <div class="prose prose-sm dark:prose-invert max-w-none text-foreground leading-relaxed whitespace-pre-line bg-card/60 p-5 rounded-xl border border-border/60">
              {{ item.body }}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

