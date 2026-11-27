<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import CmsLayout from '@/Layouts/CmsLayout.vue'
import http from '@/api/http'
import { useIsfPushNotification } from '@/Composables/useIsfPushNotification'
import { alertSuccess, alertError } from '@/Composables/useAlert'
import LoadingIndicator from '@/Pages/Cms/components/shared/LoadingIndicator.vue'
import {
  Button,
  Input,
  Label,
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

defineOptions({ layout: CmsLayout })

const {
  isSupported,
  permissionState,
  isSubscribed,
  isLoading: isSubLoading,
  initPush,
  subscribe,
} = useIsfPushNotification()

const loadingStatus = ref(true)
const statusData = ref({
  vapid_configured: false,
  public_key: '',
  total_subscriptions: 0,
  recent_subscriptions: [],
})

const isSecureContext = ref(typeof window !== 'undefined' ? window.isSecureContext : false)
const currentOrigin = ref(typeof window !== 'undefined' ? window.location.origin : '')
const swActive = ref(false)
const clientHasSub = ref(false)

const form = ref({
  title: 'Indonesia Strategy Forum',
  body: 'Uji coba notifikasi web push berhasil terhubung dari CMS ISF!',
  url: '/',
})

const sending = ref(false)
const lastResult = ref(null)

async function loadDiagnostics() {
  loadingStatus.value = true
  try {
    await initPush()

    if (typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
      const reg = await navigator.serviceWorker.getRegistration()
      swActive.value = Boolean(reg?.active)
      if (reg?.pushManager) {
        const sub = await reg.pushManager.getSubscription()
        clientHasSub.value = Boolean(sub)
      }
    }

    const res = await http.get('/isf/push/status')
    statusData.value = res.data || statusData.value
  } catch (err) {
    console.error('Gagal memuat diagnostik push:', err)
  } finally {
    loadingStatus.value = false
  }
}

onMounted(loadDiagnostics)

async function handleSubscribeDevice() {
  const res = await subscribe()
  if (res?.success) {
    alertSuccess('Perangkat ini berhasil didaftarkan ke database notifikasi!')
    await loadDiagnostics()
  } else {
    const reasonMap = {
      unsupported: 'Browser atau origin saat ini tidak mendukung Web Push (pastikan pakai HTTPS atau localhost).',
      denied: 'Izin notifikasi ditolak di browser. Ubah izin ke Allow di pengaturan situs browser.',
      no_key: 'VAPID Public Key belum dikonfigurasi di server.',
      no_sw: 'Service Worker belum aktif.',
    }
    alertError(reasonMap[res?.reason] || 'Gagal mendaftarkan perangkat ke push notification.')
  }
}

async function handleSendTest() {
  if (!form.value.title.trim() || !form.value.body.trim()) {
    alertError('Judul dan isi pesan tidak boleh kosong.')
    return
  }

  sending.value = true
  lastResult.value = null
  try {
    const res = await http.post('/isf/push/test', {
      title: form.value.title,
      body: form.value.body,
      url: form.value.url || '/',
    })
    lastResult.value = res.data
    if (res.data?.result?.sent > 0) {
      alertSuccess(res.data?.message || 'Notifikasi berhasil dikirim!')
    } else {
      alertError(res.data?.message || 'Tidak ada perangkat yang menerima notifikasi.')
    }
    await loadDiagnostics()
  } catch (err) {
    alertError(err?.response?.data?.message || 'Gagal mengirim test notifikasi.')
  } finally {
    sending.value = false
  }
}

async function handleLocalBrowserTest() {
  if (typeof Notification === 'undefined') {
    alertError('Browser tidak mendukung Notification API.')
    return
  }
  if (Notification.permission !== 'granted') {
    const perm = await Notification.requestPermission()
    permissionState.value = perm
    if (perm !== 'granted') {
      alertError('Izin notifikasi belum berstatus Allow/Granted.')
      return
    }
  }

  try {
    if ('serviceWorker' in navigator) {
      const reg = await navigator.serviceWorker.getRegistration()
      if (reg) {
        await reg.showNotification(form.value.title || 'Tes Lokal ISF', {
          body: form.value.body || 'Ini tes popup langsung dari browser tanpa melewati server.',
          icon: '/icons/icon-192x192.png',
          badge: '/icons/badge-72x72.png',
        })
        alertSuccess('Popup lokal dikirim ke sistem operasi! Jika tetap tidak tampil, periksa Do Not Disturb / Notification Settings di Windows/HP Anda.')
        return
      }
    }
    new Notification(form.value.title || 'Tes Lokal ISF', {
      body: form.value.body || 'Ini tes popup langsung dari browser.',
      icon: '/icons/icon-192x192.png',
    })
    alertSuccess('Popup lokal dikirim ke sistem operasi!')
  } catch (err) {
    alertError('Gagal memicu popup lokal: ' + (err?.message || err))
  }
}
</script>

<template>
  <Head title="Test Push Notifikasi — CMS ISF" />

  <div class="p-6 space-y-6 w-full max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <IconComponent name="Send" :size="24" class="text-red-700" />
          Uji Coba & Diagnostik Push Notifikasi
        </h1>
        <p class="text-xs text-muted-foreground mt-0.5">
          Periksa kesiapan Service Worker, daftarkan perangkat ini, dan kirim uji coba Web Push Notification secara langsung.
        </p>
      </div>

      <Button
        variant="outline"
        size="sm"
        class="h-9 text-xs inline-flex items-center gap-1.5"
        :disabled="loadingStatus"
        @click="loadDiagnostics"
      >
        <IconComponent name="Refresh" :size="14" :class="{ 'animate-spin': loadingStatus }" />
        Refresh Status
      </Button>
    </div>

    <!-- Warning if not HTTPS / localhost -->
    <div
      v-if="!isSecureContext"
      class="p-4 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-300 dark:border-red-900 text-red-900 dark:text-red-200 text-xs flex items-start gap-3"
    >
      <IconComponent name="AlertTriangle" :size="20" class="text-red-600 shrink-0 mt-0.5" />
      <div class="space-y-1">
        <p class="font-bold">Origin Tidak Aman (Bukan HTTPS / Localhost): {{ currentOrigin }}</p>
        <p class="leading-relaxed">
          Standar keamanan browser memblokir <code>ServiceWorker</code> dan <code>PushManager</code> di HTTP non-localhost. Silakan buka CMS melalui <code>http://localhost:8000</code> atau aktifkan HTTPS SSL agar notifikasi bisa berjalan.
        </p>
      </div>
    </div>

    <LoadingIndicator v-if="loadingStatus && statusData.total_subscriptions === 0" label="Memeriksa status Web Push..." />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Left: Form Kirim Test Notifikasi -->
      <div class="lg:col-span-7 space-y-6">
        <Card>
          <CardHeader class="pb-3 border-b border-border">
            <CardTitle class="text-base flex items-center gap-2">
              <IconComponent name="Send" :size="18" class="text-red-700" />
              Kirim Uji Coba Notifikasi
            </CardTitle>
            <CardDescription>
              Mengirim notifikasi ke perangkat Anda (atau broadcast ke seluruh perangkat terdaftar jika belum login/terikat).
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4 pt-4">
            <div>
              <Label for="notif-title" class="text-xs font-semibold">Judul Notifikasi</Label>
              <Input
                id="notif-title"
                v-model="form.title"
                placeholder="Indonesia Strategy Forum"
                class="mt-1 text-xs"
              />
            </div>

            <div>
              <Label for="notif-body" class="text-xs font-semibold">Isi Pesan Notifikasi</Label>
              <textarea
                id="notif-body"
                v-model="form.body"
                rows="3"
                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                placeholder="Tulis pesan singkat yang akan muncul di popup notifikasi..."
              ></textarea>
            </div>

            <div>
              <Label for="notif-url" class="text-xs font-semibold">URL Tujuan Saat Diklik</Label>
              <Input
                id="notif-url"
                v-model="form.url"
                placeholder="/"
                class="mt-1 text-xs"
              />
            </div>

            <div class="flex flex-wrap items-center gap-2.5 pt-2">
              <Button
                type="button"
                class="bg-red-700 hover:bg-red-800 text-white text-xs font-semibold inline-flex items-center gap-1.5"
                :disabled="sending"
                @click="handleSendTest"
              >
                <IconComponent v-if="sending" name="Refresh" :size="14" class="animate-spin" />
                <IconComponent v-else name="Send" :size="14" />
                <span>{{ sending ? 'Mengirim...' : 'Kirim Test via Server (FCM)' }}</span>
              </Button>

              <Button
                type="button"
                variant="outline"
                class="text-xs font-semibold inline-flex items-center gap-1.5"
                @click="handleLocalBrowserTest"
              >
                <IconComponent name="Sparkles" :size="14" />
                <span>Tes Popup Lokal Browser</span>
              </Button>
            </div>

            <!-- Last Test Result -->
            <div
              v-if="lastResult"
              class="mt-4 p-3.5 rounded-lg border text-xs space-y-1.5"
              :class="lastResult.result?.sent > 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-900 dark:text-emerald-200' : 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/30 dark:border-amber-900 dark:text-amber-200'"
            >
              <p class="font-bold">{{ lastResult.message }}</p>
              <div class="font-mono text-[11px] opacity-85">
                Total di DB: {{ lastResult.subscribers_in_db }} | Terkirim: {{ lastResult.result?.sent ?? 0 }} | Gagal: {{ lastResult.result?.failed ?? 0 }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Daftar Perangkat Terdaftar -->
        <Card>
          <CardHeader class="pb-3 border-b border-border">
            <CardTitle class="text-sm">Perangkat Terdaftar Terbaru ({{ statusData.total_subscriptions }})</CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader class="bg-muted/40">
                <TableRow>
                  <TableHead class="text-xs w-14 text-center">ID</TableHead>
                  <TableHead class="text-xs">User ID</TableHead>
                  <TableHead class="text-xs">Push Gateway</TableHead>
                  <TableHead class="text-xs">Terdaftar</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-if="!statusData.recent_subscriptions || statusData.recent_subscriptions.length === 0">
                  <TableCell colspan="4" class="text-center text-xs text-muted-foreground py-6">
                    Belum ada perangkat yang terdaftar di database.
                  </TableCell>
                </TableRow>
                <TableRow v-for="sub in statusData.recent_subscriptions" :key="sub.id">
                  <TableCell class="text-center font-mono text-xs">{{ sub.id }}</TableCell>
                  <TableCell class="text-xs font-medium">{{ sub.user_id || 'Guest' }}</TableCell>
                  <TableCell class="text-xs font-mono text-muted-foreground">{{ sub.endpoint_host || '-' }}</TableCell>
                  <TableCell class="text-xs text-muted-foreground">
                    {{ sub.created_at ? new Date(sub.created_at).toLocaleString('id-ID') : '-' }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <!-- Right: Status Diagnostik Browser & Server -->
      <div class="lg:col-span-5 space-y-6">
        <Card>
          <CardHeader class="pb-3 border-b border-border">
            <CardTitle class="text-base flex items-center gap-2">
              <IconComponent name="InfoCircle" :size="18" class="text-red-700" />
              Status Perangkat & Server
            </CardTitle>
            <CardDescription>
              Pastikan seluruh indikator di bawah ini berwarna hijau.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-3 pt-4 text-xs">
            <div class="flex items-center justify-between py-1.5 border-b border-border/60">
              <span class="text-muted-foreground">Secure Context (HTTPS/Localhost)</span>
              <span :class="isSecureContext ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold'">
                {{ isSecureContext ? 'Ya (Aman)' : 'Tidak (Diblokir)' }}
              </span>
            </div>

            <div class="flex items-center justify-between py-1.5 border-b border-border/60">
              <span class="text-muted-foreground">Dukungan PushManager</span>
              <span :class="isSupported ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold'">
                {{ isSupported ? 'Didukung' : 'Tidak Didukung' }}
              </span>
            </div>

            <div class="flex items-center justify-between py-1.5 border-b border-border/60">
              <span class="text-muted-foreground">Izin Browser (Permission)</span>
              <span
                :class="{
                  'text-emerald-600 font-bold': permissionState === 'granted',
                  'text-red-600 font-bold': permissionState === 'denied',
                  'text-amber-600 font-bold': permissionState !== 'granted' && permissionState !== 'denied',
                }"
              >
                {{ permissionState }}
              </span>
            </div>

            <div class="flex items-center justify-between py-1.5 border-b border-border/60">
              <span class="text-muted-foreground">Service Worker (/sw.js)</span>
              <span :class="swActive ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold'">
                {{ swActive ? 'Aktif' : 'Belum Aktif' }}
              </span>
            </div>

            <div class="flex items-center justify-between py-1.5 border-b border-border/60">
              <span class="text-muted-foreground">Langganan Browser Ini</span>
              <span :class="clientHasSub && isSubscribed ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold'">
                {{ clientHasSub && isSubscribed ? 'Terdaftar' : 'Belum Terdaftar' }}
              </span>
            </div>

            <div class="flex items-center justify-between py-1.5 border-b border-border/60">
              <span class="text-muted-foreground">VAPID Key di Server</span>
              <span :class="statusData.vapid_configured ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold'">
                {{ statusData.vapid_configured ? 'Aktif' : 'Kosong' }}
              </span>
            </div>

            <div class="pt-3">
              <Button
                type="button"
                class="w-full text-xs font-semibold"
                :variant="clientHasSub ? 'outline' : 'default'"
                :disabled="isSubLoading || !isSupported"
                @click="handleSubscribeDevice"
              >
                <IconComponent v-if="isSubLoading" name="Refresh" :size="14" class="mr-1.5 animate-spin" />
                {{ clientHasSub ? 'Sinkronkan Ulang Perangkat Ini' : 'Daftarkan Perangkat Ini Sekarang' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
