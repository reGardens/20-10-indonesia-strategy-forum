<script setup>
/**
 * LegalPageView.vue — Halaman Statis & Dokumen Legalitas Publik ISF
 * Melayani rute:
 * - /syarat-ketentuan
 * - /privacy-policy / /kebijakan-privasi
 * - /pedoman-media-siber
 * - /cookie-policy
 * - /tentang-isf
 * - /kontak
 *
 * Mengonsumsi data dinamis dari /api/isf/footer dengan fallback komprehensif.
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import {
  IconChevronRight,
  IconArrowLeft,
  IconShieldCheck,
  IconFileText,
  IconMail,
  IconPhone,
  IconMapPin,
  IconClock,
} from '@tabler/icons-vue'
import IsfLink from '@/Components/Isf/IsfLink.vue'

const route = useRoute()
const loading = ref(true)
const footerData = ref(null)

const currentYear = new Date().getFullYear()

async function fetchFooter() {
  loading.value = true
  try {
    const res = await axios.get('/api/isf/footer')
    if (res.data?.data) {
      footerData.value = res.data.data
    }
  } catch {
    // Silently proceed with defaults
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchFooter()
})

const pageType = computed(() => {
  const p = route.path.toLowerCase()
  if (p.includes('privacy') || p.includes('kebijakan-privasi')) return 'privacy'
  if (p.includes('pedoman-media-siber')) return 'media-siber'
  if (p.includes('cookie')) return 'cookie'
  if (p.includes('tentang-isf')) return 'tentang'
  if (p.includes('kontak')) return 'kontak'
  return 'syarat'
})

const pageInfo = computed(() => {
  const t = pageType.value
  const data = footerData.value || {}

  if (t === 'privacy') {
    return {
      kicker: 'LEGALITAS & PRIVASI',
      title: 'Kebijakan Privasi (Privacy Policy)',
      subtitle: 'Komitmen perlindungan data pribadi, kerahasiaan informasi pengguna, dan standar keamanan portal Indonesia Strategy Forum.',
      body: data.privacy_policy || `
        <h2>1. Prinsip Utama</h2>
        <p><strong>Indonesia Strategy Forum (ISF)</strong> berkomitmen penuh untuk menghormati dan melindungi hak privasi setiap pengunjung situs serta anggota terdaftar. Kebijakan ini menjelaskan tata cara pengumpulan, pemanfaatan, dan perlindungan data pribadi Anda.</p>
        <h2>2. Data yang Dikumpulkan</h2>
        <p>Kami hanya mengumpulkan informasi yang diberikan secara sukarela, seperti nama lengkap, alamat email resmi, nama instansi/organisasi, dan jabatan profesional saat Anda mendaftar keanggotaan atau berlangganan buletin eksekutif.</p>
        <h2>3. Penggunaan Informasi</h2>
        <p>Informasi yang dihimpun digunakan khusus untuk keperluan verifikasi akun portal anggota, pengiriman materi briefing kebijakan berkala, serta personalisasi rujukan riset strategis. ISF tidak akan pernah memperjualbelikan data Anda kepada pihak ketiga.</p>
        <h2>4. Keamanan Informasi</h2>
        <p>Kami menerapkan standar keamanan enkripsi terkini dan kontrol akses berlapis guna mengantisipasi akses tidak sah, kebocoran, atau manipulasi data.</p>
      `,
    }
  }

  if (t === 'media-siber') {
    return {
      kicker: 'STANDAR REDAKSI',
      title: 'Pedoman Pemberitaan Media Siber',
      subtitle: 'Kepatuhan standar jurnalistik dan regulasi Dewan Pers No. 1/Peraturan-DP/III/2012 dalam operasional redaksi digital ISF.',
      body: `
        <h2>1. Ruang Lingkup</h2>
        <p>Media Siber adalah segala bentuk media yang menggunakan wahana internet dan melaksanakan kegiatan jurnalistik, serta memenuhi persyaratan Undang-Undang Pers dan Standar Perusahaan Pers yang ditetapkan Dewan Pers.</p>
        <h2>2. Verifikasi dan Keberimbangan Berita</h2>
        <p>Pada prinsipnya setiap artikel, rilis data, dan analisis kebijakan di portal ISF harus melalui proses verifikasi fakta primer. Pemuatan opini atau analisis yang berpotensi merugikan pihak tertentu wajib mengedepankan asas praduga tak bersalah dan perimbangan sumber.</p>
        <h2>3. Ralat, Koreksi, dan Hak Jawab</h2>
        <p>Ralat, koreksi, dan hak jawab mengacu pada Undang-Undang Pers. Redaksi ISF wajib menautkan ralat atau hak jawab pada artikel yang bersangkutan dengan keterangan waktu pembaruan yang transparan.</p>
        <h2>4. Pencabutan Berita</h2>
        <p>Berita yang sudah dipublikasikan tidak dapat dicabut karena alasan penyensoran dari pihak luar, kecuali terkait masalah SARA, kesusilaan, masa depan anak, atau pertimbangan khusus yang ditetapkan Dewan Pers.</p>
      `,
    }
  }

  if (t === 'cookie') {
    return {
      kicker: 'PENGATURAN TEKNOLOGI',
      title: 'Kebijakan Cookie (Cookie Policy)',
      subtitle: 'Informasi penggunaan cookie dan teknologi penyimpanan lokal peramban untuk kenyamanan akses portal ISF.',
      body: `
        <h2>1. Apa itu Cookie?</h2>
        <p>Cookie adalah berkas teks kecil yang disimpan pada peramban (browser) perangkat Anda saat mengunjungi portal Indonesia Strategy Forum untuk menyimpan preferensi dan sesi otentikasi.</p>
        <h2>2. Penggunaan Cookie di ISF</h2>
        <p>ISF menggunakan cookie esensial yang diperlukan untuk memastikan fungsi keamanan login anggota, mengingat preferensi tampilan visual, dan menganalisis performa muat halaman secara anonim.</p>
        <h2>3. Mengelola Cookie</h2>
        <p>Anda dapat mengontrol atau menonaktifkan penggunaan cookie melalui pengaturan peramban masing-masing. Namun, menonaktifkan cookie tertentu dapat mempengaruhi kelancaran navigasi pada portal anggota.</p>
      `,
    }
  }

  if (t === 'tentang') {
    return {
      kicker: 'PROFIL LEMBAGA',
      title: 'Tentang Indonesia Strategy Forum (ISF)',
      subtitle: 'Pilar referensi strategis independen bagi para pengambil kebijakan dan eksekutif nasional.',
      body: `
        <h2>Visi & Misi ISF</h2>
        <p>${data.about_us || 'Indonesia Strategy Forum (ISF) adalah kanal referensi independen untuk para pembuat kebijakan, eksekutif puncak korporasi, akademisi, dan profesional hukum yang membentuk arah pembangunan Indonesia.'}</p>
        <h2>Fokus Kajian</h2>
        <p>Kajian ISF berfokus pada empat rubrik utama: Dinamika Politik & Parlemen, Hukum & Regulasi, Kebijakan Publik & Fiskal, serta Survei & Opini Pakar Terkurasi.</p>
      `,
    }
  }

  if (t === 'kontak') {
    return {
      kicker: 'HUBUNGI REDAKSI',
      title: 'Kontak Redaksi & Sekretariat ISF',
      subtitle: 'Saluran resmi komunikasi redaksi, pengajuan hak jawab, kemitraan strategis, dan audiensi.',
      isContactPage: true,
      body: '',
    }
  }

  // Default: Syarat & Ketentuan
  return {
    kicker: 'LEGALITAS PENGGUNAAN',
    title: 'Syarat & Ketentuan (Terms & Conditions)',
    subtitle: 'Aturan dan pedoman resmi penggunaan layanan informasi, laporan riset, dan portal digital Indonesia Strategy Forum.',
    body: data.terms_conditions || `
      <h2>1. Ketentuan Umum</h2>
      <p>Selamat datang di <strong>Indonesia Strategy Forum (ISF)</strong>. Dengan mengakses dan menggunakan portal ini, Anda menyatakan setuju untuk terikat pada Syarat dan Ketentuan penggunaan yang berlaku.</p>
      <h2>2. Hak Kekayaan Intelektual</h2>
      <p>Seluruh materi publikasi, analisis kebijakan, infografik, basis data peraturan, dan dokumentasi di situs ini dilindungi hak cipta. Pengutipan materi untuk kepentingan akademis dan non-komersial wajib menyertakan atribusi sumber resmi ISF.</p>
      <h2>3. Penafian Tanggung Jawab</h2>
      <p>Informasi dan analisis yang disajikan bertujuan sebagai referensi strategis dan edukasi publik, serta bukan merupakan nasihat hukum atau investasi resmi.</p>
    `,
  }
})
</script>

<template>
  <div class="min-h-screen bg-[#FBF9F4] text-neutral-900">
    <!-- Hero / Header Section -->
    <header class="bg-[#0B1220] border-b border-neutral-800 text-white">
      <div class="isf-mobile-gutter mx-auto max-w-[1100px] px-4 py-10 lg:px-6">
        <!-- Breadcrumbs -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-neutral-400 mb-4">
          <IsfLink to="/" class="hover:text-white transition-colors">Beranda</IsfLink>
          <IconChevronRight class="h-3 w-3 text-neutral-600" />
          <span class="text-neutral-200 font-medium">{{ pageInfo.title }}</span>
        </nav>

        <!-- Kicker -->
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-red-950/60 border border-red-800/60 text-isf-red text-[10px] font-bold tracking-wider uppercase mb-3">
          <IconShieldCheck class="h-3.5 w-3.5" />
          <span>{{ pageInfo.kicker }}</span>
        </div>

        <!-- Headline -->
        <h1 class="text-2xl lg:text-4xl font-bold tracking-tight font-headline text-white mb-3">
          {{ pageInfo.title }}
        </h1>

        <!-- Subtitle -->
        <p class="text-xs lg:text-sm text-neutral-300 max-w-3xl leading-relaxed">
          {{ pageInfo.subtitle }}
        </p>

        <div class="flex items-center gap-3 text-[11px] text-neutral-400 mt-4 pt-3 border-t border-neutral-800/80">
          <span class="flex items-center gap-1">
            <IconClock class="h-3.5 w-3.5 text-neutral-500" />
            Diperbarui: Edisi Tahun {{ currentYear }}
          </span>
          <span class="text-neutral-600">•</span>
          <span class="text-neutral-400">Sekretariat &amp; Dewan Redaksi ISF</span>
        </div>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="isf-mobile-gutter mx-auto max-w-[1100px] px-4 py-8 lg:px-6">
      <div class="bg-white rounded-xl border border-neutral-200/80 p-6 lg:p-10 shadow-xs space-y-6">
        <!-- If Kontak Page: render contact cards -->
        <div v-if="pageInfo.isContactPage" class="space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-5 rounded-lg border border-neutral-200 bg-neutral-50/50 space-y-2">
              <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center text-isf-red">
                <IconMail class="h-5 w-5" />
              </div>
              <h2 class="text-sm font-bold text-neutral-900">Email Redaksi</h2>
              <p class="text-xs text-neutral-600">Untuk korespondensi naskah, opini, dan ralat pemberitaan.</p>
              <a
                :href="'mailto:' + (footerData?.contact_email || 'kontak@indonesiastrategyforum.com')"
                class="inline-block text-xs font-semibold text-isf-red hover:underline pt-1 break-all"
              >
                {{ footerData?.contact_email || 'kontak@indonesiastrategyforum.com' }}
              </a>
            </div>

            <div class="p-5 rounded-lg border border-neutral-200 bg-neutral-50/50 space-y-2">
              <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center text-isf-red">
                <IconPhone class="h-5 w-5" />
              </div>
              <h2 class="text-sm font-bold text-neutral-900">Telepon Kantor</h2>
              <p class="text-xs text-neutral-600">Hotline sekretariat dan konfirmasi audiensi.</p>
              <p class="text-xs font-semibold text-neutral-900 pt-1">
                {{ footerData?.contact_phone || '+62 21 555-1234' }}
              </p>
            </div>

            <div class="p-5 rounded-lg border border-neutral-200 bg-neutral-50/50 space-y-2">
              <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center text-isf-red">
                <IconMapPin class="h-5 w-5" />
              </div>
              <h2 class="text-sm font-bold text-neutral-900">Alamat Kantor</h2>
              <p class="text-xs text-neutral-600">Pusat operasional dan dewan pakar ISF.</p>
              <p class="text-xs font-semibold text-neutral-900 pt-1">
                {{ footerData?.contact_address || 'Jakarta, Indonesia' }}
              </p>
            </div>
          </div>

          <div class="pt-6 border-t border-neutral-200 text-xs text-neutral-600 leading-relaxed">
            <h2 class="text-sm font-bold text-neutral-900 mb-2">Pedoman Korespondensi Redaksi</h2>
            <p>
              Seluruh surat pembaca atau permohonan hak jawab wajib menyertakan identitas resmi (KTP/kartu identitas institusi) serta nomor kontak yang dapat diverifikasi oleh tim redaksi kami.
            </p>
          </div>
        </div>

        <!-- Default Rich Content Body -->
        <article
          v-else
          class="cms-content prose max-w-none text-neutral-800 text-xs lg:text-sm leading-relaxed"
          v-html="pageInfo.body"
        />

        <!-- Back Link -->
        <div class="pt-6 border-t border-neutral-200 flex items-center justify-between">
          <IsfLink
            to="/"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-isf-red transition-colors"
          >
            <IconArrowLeft class="h-4 w-4" />
            <span>Kembali ke Beranda</span>
          </IsfLink>
          <span class="text-[11px] text-neutral-400">
            © {{ currentYear }} Indonesia Strategy Forum
          </span>
        </div>
      </div>
    </main>
  </div>
</template>
