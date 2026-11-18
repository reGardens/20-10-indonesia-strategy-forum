<script setup>
/**
 * MediaKitView.vue — Halaman Iklan & Kemitraan Strategis ISF (/media-kit/).
 *
 * Mengimplementasikan:
 * - Header Hero bernuansa editorial Navy (#0B1220) konsisten dengan standar ISF:
 *   Breadcrumb responsif, kicker badge ISF Red (#E10600), headline serif berwibawa, dan consultative tagline.
 * - STRICTLY NO RATE CARD: Pendekatan kemitraan konsultatif tanpa publikasi harga standar.
 * - Pitch Audiens & Demografi: C-Level 34%, ASN/Pemerintah 28%, Akademisi 22%, Hukum & Kepatuhan 16%.
 * - Saluran Kemitraan Strategis: Display Banner, Advertorial Terkurasi, Executive Roundtable, dan Riset & Policy Brief.
 * - Alur Kemitraan 4-Tahap: Pengajuan RFP -> Analisis & Kurasi Editorial -> Proposal Kustom -> Eksekusi & Laporan Dampak.
 * - Form RFP (Request for Proposal) (#ajukan-kemitraan):
 *   Validasi field lengkap, SVG visual captcha terintegrasi (/api/isf/captcha), status loading, penanganan error, dan konfirmasi sukses dengan SLA 1x24 jam kerja.
 * - FAQ Interaktif (Accordion) menjawab seputar ketiadaan rate card terbuka, independensi redaksi, dan waktu respon.
 * - Identitas Visual: Navy #0B1220, Crimson Red #E10600, Cream #FBF9F4, serif font-headline, Space Grotesk UI.
 */
import { computed, ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  IconChevronRight,
  IconChevronDown,
  IconChevronUp,
  IconBuildingSkyscraper,
  IconUserCheck,
  IconMail,
  IconPhone,
  IconSend,
  IconRefresh,
  IconCheck,
  IconAlertCircle,
  IconShieldCheck,
  IconArrowRight,
  IconFileText,
  IconChartBar,
  IconUsers,
  IconAward,
  IconTarget,
  IconSpeakerphone,
  IconCalendarEvent,
  IconHelpCircle,
  IconBrandWhatsapp,
  IconLockCheck,
  IconInfoCircle,
} from '@tabler/icons-vue'
import http from '@/api/http'
import { getMediaKit, submitPartnershipRfp } from '@/api/services/isfPublicService'
import { useIsfContent } from '@/Composables/useIsfContent'
import SectionHeading from '@/Components/Isf/SectionHeading.vue'

// Fetch dynamic data from API with fallback defaults
const { data, status, load } = useIsfContent(() => getMediaKit())

onMounted(() => {
  load()
  fetchCaptcha()
})

// Data helpers with complete editorial fallbacks
const pitch = computed(() => data.value?.pitch || {
  title: 'Pintu Masuk Terpercaya Menjangkau Pengambil Kebijakan Puncak & Eksekutif Indonesia',
  subtitle: 'Indonesia Strategy Forum (ISF) menyediakan saluran kemitraan berbobot tinggi untuk diseminasi gagasan strategis, dialog kebijakan, advertorial kurasi mendalam, dan riset terarah bagi para pemimpin industri dan regulator.',
  metrics: [
    { label: 'Pembaca Aktif Bulanan', value: '150K+', desc: 'Pengambil keputusan & analis kebijakan' },
    { label: 'Eksekutif & C-Level', value: '45K+', desc: 'Direksi, komisaris, pejabat publik & regulator' },
    { label: 'Retensi Audiens Senior', value: '85%', desc: 'Keterlibatan mendalam pada isu strategis' },
    { label: 'Independensi Editorial', value: '100%', desc: 'Data berbasis riset empiris tanpa sensasi' },
  ],
})

const audiens = computed(() => {
  const raw = data.value?.audiens
  const rawList = raw?.demographics || raw?.segments
  if (rawList && rawList.length > 0) {
    return {
      ...raw,
      demographics: rawList.map(item => ({
        label: item.label || item.name,
        percentage: item.percentage,
        description: item.description || item.desc || '',
      }))
    }
  }
  return {
    demographics: [
      { label: 'C-Level & Pemimpin Korporasi', percentage: 34, description: 'CEO, CFO, Direksi BUMN/Swasta, Managing Partner, Investor Utama' },
      { label: 'ASN, Regulator & Lembaga Negara', percentage: 28, description: 'Kementerian, Lembaga Tinggi, Staf Khusus, Fraksi Parlemen' },
      { label: 'Peneliti, Akademisi & Think Tank', percentage: 22, description: 'Guru Besar, Peneliti Kebijakan Publik, Pusat Studi Strategis' },
      { label: 'Praktisi Hukum, Kepatuhan & Relasi Publik', percentage: 16, description: 'General Counsel, Corporate Secretary, Compliance, External Affairs' },
    ],
  }
})

const channels = computed(() => {
  const raw = data.value?.channels
  if (raw && raw.length > 0) {
    return raw.map(ch => ({
      key: ch.key || ch.id,
      title: ch.title,
      kicker: ch.kicker || 'Saluran Strategis',
      desc: ch.desc,
      features: ch.features || ch.highlights || [],
    }))
  }
  return [
    {
      key: 'banner',
      title: 'Display Banner Strategis',
      kicker: 'Penempatan Eksekutif',
      desc: 'Penempatan banner visual high-impact pada posisi strategis beranda dan halaman rubrik inti tanpa mengorbankan kenyamanan pembaca profesional.',
      features: [
        'Posisi Leaderboard & Desktop Half-Page beranda',
        'Penargetan per rubrik (Politik, Hukum, Kebijakan Publik)',
        'Tanpa rotasi berlebihan untuk eksklusivitas brand',
        'Desain elegan selaras dengan palet identitas ISF',
      ],
    },
    {
      key: 'advertorial',
      title: 'Advertorial & Thought Leadership',
      kicker: 'Opini & Analisis Mendalam',
      desc: 'Artikel naratif mendalam yang menyajikan inisiatif, studi kasus, atau pandangan strategis institusi Anda, diselaraskan dengan standar redaksi ISF.',
      features: [
        'Kurasi langsung oleh Dewan Redaksi ISF',
        'Distribusi di rubrik terkait dan email briefing eksekutif',
        'Penanda transparansi editorial yang menjaga reputasi kredibel',
        'Masa tayang permanen dengan optimasi referensi kebijakan',
      ],
    },
    {
      key: 'event',
      title: 'Executive Roundtable & Policy Dialogue',
      kicker: 'Forum Dialog Tingkat Tinggi',
      desc: 'Kolaborasi penyelenggaraan diskusi meja bundar tertutup atau seminar strategis yang mempertemukan pembuat kebijakan, asosiasi, dan pelaku industri.',
      features: [
        'Akses tatap muka dengan narasumber otoritatif',
        'Penyusunan Executive Summary / Whitepaper hasil forum',
        'Liputan eksklusif dan publikasi pasca-event di media ISF',
        'Fasilitasi moderasi profesional dari tim peneliti senior',
      ],
    },
    {
      key: 'riset',
      title: 'Sponsorship Riset & Policy Brief',
      kicker: 'Kajian Berbasis Data',
      desc: 'Kemitraan riset tematik dan visualisasi data infografik yang mengangkat isu-isu krusial regulasi atau lanskap ekonomi nasional.',
      features: [
        'Ko-branding pada dokumen Policy Brief resmi',
        'Infografik interaktif yang dapat diakses publik luas',
        'Metodologi independen berbasis kaidah ilmiah terpercaya',
        'Diseminasi ke jejaring pemangku kepentingan kunci',
      ],
    },
  ]
})

const workflow = computed(() => data.value?.workflow || [
  {
    step: '01',
    title: 'Pengajuan Kebutuhan (RFP)',
    desc: 'Mitra mengisi formulir Request for Proposal di bawah ini dengan mencantumkan sasaran strategis, latar belakang institusi, dan preferensi saluran kemitraan.',
  },
  {
    step: '02',
    title: 'Konsultasi & Penyelarasan (1x24 Jam)',
    desc: 'Tim Kemitraan ISF akan menghubungi PIC Anda dalam kurun waktu 1x24 jam kerja untuk mendiskusikan angle editorial, target audiens, dan keselarasan nilai.',
  },
  {
    step: '03',
    title: 'Penyusunan Proposal Khusus',
    desc: 'Kami menyusun proposal kemitraan kustom (tailor-made) yang mencakup rancangan materi, jadwal publikasi, dan indikator capaian terukur.',
  },
  {
    step: '04',
    title: 'Eksekusi & Laporan Dampak',
    desc: 'Materi ditayangkan sesuai standar editorial tertinggi ISF, disusul penyampaian laporan performa komprehensif bagi institusi Anda.',
  },
])

const faq = computed(() => data.value?.faq || [
  {
    q: 'Mengapa ISF tidak menampilkan daftar harga (Rate Card) di website?',
    a: 'ISF mengutamakan pendekatan konsultatif tailor-made. Setiap inisiatif mitra memerlukan kedalaman riset, format penayangan, dan segmentasi audiens yang unik. Pendekatan ini memastikan efektivitas dampak investasi Anda dibandingkan model tarif baku generik.',
  },
  {
    q: 'Berapa lama waktu respon setelah formulir RFP dikirimkan?',
    a: 'Tim Kemitraan ISF berkomitmen menghubungi narahubung resmi (PIC) Anda dalam kurun waktu maksimal 1x24 jam pada hari kerja untuk memulai tahapan diskusi.',
  },
  {
    q: 'Apakah instansi pemerintah, BUMN, dan lembaga riset dapat bermitra?',
    a: 'Tentu. ISF secara berkala berkolaborasi dengan kementerian, lembaga negara, BUMN, organisasi non-pemerintah, lembaga riset, serta sektor swasta untuk diseminasi analisis kebijakan dan laporan strategis.',
  },
  {
    q: 'Bagaimana ISF menjaga independensi redaksi pada program berbayar?',
    a: 'ISF memisahkan secara tegas kepentingan komersial dan independensi ruang redaksi. Seluruh konten berbayar diberi label transparansi ("Kemitraan Strategis" atau "Advertorial") dan wajib memenuhi standar verifikasi fakta tanpa muatan disinformasi.',
  },
  {
    q: 'Apakah mitra dapat mengajukan format kemitraan di luar opsi yang tertera?',
    a: 'Sangat memungkinkan. Kami terbuka untuk format kolaborasi khusus seperti riset longitudinal, forum survei opini khusus, maupun pengembangan konten interaktif visual.',
  },
])

// FAQ accordion toggles
const activeFaq = ref(0)
function toggleFaq(index) {
  activeFaq.value = activeFaq.value === index ? -1 : index
}

// ═══ FORM RFP STATE ═══
const form = reactive({
  institution_name: '',
  pic_name: '',
  email: '',
  phone: '',
  partnership_type: 'banner',
  message: '',
  captcha_key: '',
  captcha_answer: '',
})

const partnershipTypes = [
  { value: 'banner', label: 'Display Banner Strategis' },
  { value: 'advertorial', label: 'Advertorial & Thought Leadership' },
  { value: 'event', label: 'Executive Roundtable / Event' },
  { value: 'riset', label: 'Sponsorship Riset & Policy Brief' },
  { value: 'lainnya', label: 'Format Kolaborasi Khusus / Lainnya' },
]

const formErrors = ref({})
const submitting = ref(false)
const submittedSuccess = ref(false)
const submissionResult = ref(null)

// Captcha state
const captchaKey = ref('')
const captchaSvg = ref('')
const captchaLoading = ref(false)

async function fetchCaptcha() {
  captchaLoading.value = true
  form.captcha_answer = ''
  try {
    const res = await http.get('/isf/captcha')
    captchaKey.value = res.data?.key || ''
    captchaSvg.value = res.data?.image || ''
    form.captcha_key = captchaKey.value
  } catch (err) {
    console.error('Gagal mengambil captcha ISF:', err)
  } finally {
    captchaLoading.value = false
  }
}

function clearError(field) {
  if (formErrors.value[field]) {
    delete formErrors.value[field]
  }
}

function resetForm() {
  form.institution_name = ''
  form.pic_name = ''
  form.email = ''
  form.phone = ''
  form.partnership_type = 'banner'
  form.message = ''
  form.captcha_answer = ''
  formErrors.value = {}
  submittedSuccess.value = false
  submissionResult.value = null
  fetchCaptcha()
}

async function handleSubmitRfp() {
  formErrors.value = {}
  form.captcha_key = captchaKey.value

  // Client-side quick checks
  const errs = {}
  if (!form.institution_name.trim()) errs.institution_name = ['Nama institusi atau perusahaan wajib diisi.']
  if (!form.pic_name.trim()) errs.pic_name = ['Nama lengkap PIC / narahubung wajib diisi.']
  if (!form.email.trim()) {
    errs.email = ['Alamat email resmi wajib diisi.']
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errs.email = ['Format alamat email tidak valid.']
  }
  if (!form.phone.trim()) {
    errs.phone = ['Nomor telepon / WhatsApp aktif wajib diisi.']
  }
  if (!form.message.trim()) {
    errs.message = ['Uraian kebutuhan kemitraan wajib diisi.']
  } else if (form.message.trim().length < 10) {
    errs.message = ['Uraian kebutuhan minimal 10 karakter agar kebutuhan dapat dipahami dengan baik.']
  }
  if (!form.captcha_answer.trim()) {
    errs.captcha_answer = ['Kode verifikasi keamanan wajib diisi.']
  }

  if (Object.keys(errs).length > 0) {
    formErrors.value = errs
    return
  }

  submitting.value = true

  try {
    const res = await submitPartnershipRfp({
      institution_name: form.institution_name.trim(),
      pic_name: form.pic_name.trim(),
      email: form.email.trim(),
      phone: form.phone.trim(),
      partnership_type: form.partnership_type,
      message: form.message.trim(),
      captcha_key: form.captcha_key,
      captcha_answer: form.captcha_answer.trim(),
    })

    submissionResult.value = res.data
    submittedSuccess.value = true
  } catch (err) {
    if (err.response && err.response.status === 422) {
      formErrors.value = err.response.data?.errors || { general: [err.response.data?.message] }
    } else {
      formErrors.value = {
        general: [err.response?.data?.message || 'Terjadi kesalahan sistem saat mengirim proposal kemitraan. Silakan coba kembali.'],
      }
    }
    fetchCaptcha()
  } finally {
    submitting.value = false
  }
}

function scrollToForm() {
  const el = document.getElementById('ajukan-kemitraan')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <div class="isf-theme font-space-grotesk min-h-screen bg-white text-neutral-900 antialiased pb-24">
    <!-- ═══ 1. HERO HEADER (Editorial Navy #0B1220) ═══ -->
    <header class="bg-isf-navy text-white border-b border-neutral-800 pt-8 sm:pt-12 pb-12 lg:pb-16">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12">
        <!-- Breadcrumb Nav -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-400 mb-6 select-none min-w-0">
          <RouterLink to="/beranda" class="whitespace-nowrap shrink-0 hover:text-white transition-colors">Beranda</RouterLink>
          <IconChevronRight class="w-3.5 h-3.5 opacity-60 shrink-0" />
          <span class="text-white font-bold whitespace-nowrap shrink-0">Iklan &amp; Kemitraan</span>
        </nav>

        <!-- Kicker & Headline -->
        <div class="max-w-4xl">
          <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-3">
            <span class="inline-block w-2.5 h-2.5 bg-isf-red rounded-xs"></span>
            <span>PANDUAN KEMITRAAN &amp; MEDIA KIT ISF</span>
          </div>
          <h1 class="font-headline text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-[1.08] text-white">
            {{ pitch.title }}
          </h1>
          <p class="text-sm sm:text-base md:text-lg text-neutral-300 mt-5 max-w-3xl leading-relaxed">
            {{ pitch.subtitle }}
          </p>

          <!-- Consultative Note Badge (No rate card statement) -->
          <div class="mt-6 inline-flex items-start sm:items-center gap-2.5 px-3.5 py-2 rounded bg-neutral-900/90 border border-neutral-700/80 text-neutral-300 text-xs sm:text-sm break-words leading-relaxed max-w-full">
            <IconShieldCheck class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5 sm:mt-0" />
            <span class="break-words">
              <strong class="text-white font-medium">Prinsip Kemitraan Konsultatif:</strong>
              Seluruh program dirancang kustom (tailor-made) tanpa daftar harga publik baku demi menjaga integritas editorial dan dampak maksimal.
            </span>
          </div>

          <!-- CTAs -->
          <div class="mt-8 flex flex-wrap items-center gap-3">
            <button
              type="button"
              @click="scrollToForm"
              class="inline-flex items-center gap-2 px-6 py-3 bg-isf-red hover:bg-red-700 text-white font-bold text-sm tracking-wide transition-all shadow-md active:scale-95"
            >
              <span>Ajukan Proposal Kemitraan</span>
              <IconArrowRight class="w-4 h-4" />
            </button>
            <a
              href="#saluran-kemitraan"
              class="inline-flex items-center gap-2 px-5 py-3 bg-neutral-800/80 hover:bg-neutral-800 text-neutral-200 hover:text-white font-medium text-sm border border-neutral-700 transition-colors"
            >
              <span>Eksplorasi Saluran</span>
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══ 2. EXECUTIVE METRICS BAR ═══ -->
    <section class="border-b border-neutral-200 bg-[#FBF9F4]">
      <div class="isf-mobile-gutter w-full px-4 sm:px-6 md:px-8 xl:px-12 py-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 lg:gap-8">
          <div
            v-for="(metric, idx) in pitch.metrics"
            :key="idx"
            class="border-l-2 border-isf-red pl-4"
          >
            <div class="font-headline text-3xl sm:text-4xl lg:text-5xl font-black text-neutral-900 tracking-tight">
              {{ metric.value }}
            </div>
            <div class="text-xs sm:text-sm font-bold text-neutral-800 uppercase tracking-wide mt-1">
              {{ metric.label }}
            </div>
            <div class="text-xs text-neutral-500 mt-0.5 leading-snug">
              {{ metric.desc }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ 3. AUDIENCE DEMOGRAPHICS & PROFILE ═══ -->
    <section class="w-full px-4 sm:px-6 md:px-8 xl:px-12 py-12 lg:py-16 border-b border-neutral-200">
      <SectionHeading
        title="PROFIL &amp; STRUKTUR AUDIENS ISF"
        eyebrow="KOMPOSISI PEMBACA &amp; PENGAMBIL KEPUTUSAN"
        size="lg"
        class="mb-8"
      />

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <!-- Left: Breakdown text -->
        <div class="lg:col-span-5 space-y-4">
          <h3 class="font-headline text-2xl sm:text-3xl font-black text-neutral-900 leading-tight">
            Jaringan Pengaruh Tertinggi di Lingkaran Kebijakan Nasional
          </h3>
          <p class="text-sm text-neutral-600 leading-relaxed">
            Pembaca Indonesia Strategy Forum bukanlah audiens publik awam. Mereka adalah aktor penentu arah regulasi, eksekutif penggerak industri strategis, akademisi pengkaji kebijakan, serta konsultan hukum korporasi terkemuka.
          </p>
          <div class="p-4 bg-neutral-50 border border-neutral-200 rounded-xs space-y-3">
            <div class="flex items-start gap-3">
              <IconTarget class="w-5 h-5 text-isf-red shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-900">Nol Sensasionalisme (Zero Clickbait)</h4>
                <p class="text-xs text-neutral-600 mt-0.5">Seluruh artikel, data grafik, dan infografik disusun dengan kedalaman riset dan metodologi ilmiah terverifikasi.</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <IconBuildingSkyscraper class="w-5 h-5 text-isf-red shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-900">Jangkauan Langsung ke Ruang Rapat</h4>
                <p class="text-xs text-neutral-600 mt-0.5">Diakses langsung oleh staf kementerian, pimpinan BUMN, direksi swasta, dan komisioner lembaga negara.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Demographic progress bars -->
        <div class="lg:col-span-7 bg-white border border-neutral-200 p-6 sm:p-8 rounded-xs shadow-xs">
          <div class="flex items-center justify-between border-b border-neutral-200 pb-4 mb-6">
            <span class="text-xs font-mono font-bold tracking-wider uppercase text-neutral-500">SEGMEN PROFESIONAL</span>
            <span class="text-xs font-mono font-bold tracking-wider uppercase text-neutral-500">PANSA AUDIENS</span>
          </div>

          <div class="space-y-6">
            <div
              v-for="(item, idx) in audiens.demographics"
              :key="idx"
              class="space-y-1.5"
            >
              <div class="flex items-baseline justify-between">
                <span class="text-sm font-bold text-neutral-900">{{ item.label }}</span>
                <span class="font-headline text-lg sm:text-xl font-black text-isf-red">{{ item.percentage }}%</span>
              </div>
              <p class="text-xs text-neutral-500">{{ item.description }}</p>
              <!-- Bar -->
              <div class="w-full h-2.5 bg-neutral-100 rounded-full overflow-hidden">
                <div
                  class="h-full bg-isf-red transition-all duration-700 ease-out"
                  :style="{ width: `${item.percentage}%` }"
                ></div>
              </div>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-neutral-200 flex items-center justify-between text-xs text-neutral-500">
            <span>Sumber: Analisis Sirkulasi Pembaca &amp; Audit Pembaca ISF</span>
            <span class="font-mono">Pembaruan Rutin 2026</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ 4. PARTNERSHIP CHANNELS (STRICTLY NO RATE CARD) ═══ -->
    <section id="saluran-kemitraan" class="w-full px-4 sm:px-6 md:px-8 xl:px-12 py-12 lg:py-16 bg-[#FBF9F4] border-b border-neutral-200">
      <SectionHeading
        title="SALURAN KEMITRAAN STRATEGIS"
        eyebrow="FORMAT DISPARITAS &amp; KOLABORASI"
        size="lg"
        class="mb-8"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
        <div
          v-for="(channel, idx) in channels"
          :key="idx"
          class="bg-white border border-neutral-200 border-t-4 border-t-isf-red p-6 sm:p-8 flex flex-col justify-between shadow-xs"
        >
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-mono font-bold tracking-widest text-isf-red uppercase">
                {{ channel.kicker }}
              </span>
              <span class="text-xs font-mono text-neutral-400">#0{{ idx + 1 }}</span>
            </div>
            <h3 class="font-headline text-xl sm:text-2xl font-black text-neutral-900 mb-3">
              {{ channel.title }}
            </h3>
            <p class="text-xs sm:text-sm text-neutral-600 leading-relaxed mb-6">
              {{ channel.desc }}
            </p>

            <!-- Features checklist -->
            <ul class="space-y-2.5 border-t border-neutral-100 pt-4 mb-6">
              <li
                v-for="(f, fIdx) in channel.features"
                :key="fIdx"
                class="flex items-start gap-2.5 text-xs text-neutral-700"
              >
                <IconCheck class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>{{ f }}</span>
              </li>
            </ul>
          </div>

          <div class="pt-4 border-t border-neutral-100 flex items-center justify-between">
            <span class="text-xs text-neutral-500 italic">Solusi kustom &amp; kurasi editorial</span>
            <button
              type="button"
              @click="form.partnership_type = channel.key; scrollToForm()"
              class="inline-flex items-center gap-1.5 text-xs font-bold text-isf-red hover:text-red-700 transition-colors"
            >
              <span>Pilih Saluran Ini</span>
              <IconArrowRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ 5. 4-STEP COLLABORATIVE WORKFLOW ═══ -->
    <section class="w-full px-4 sm:px-6 md:px-8 xl:px-12 py-12 lg:py-16 border-b border-neutral-200">
      <SectionHeading
        title="ALUR KOLABORASI KEMITRAAN"
        eyebrow="4 TAHAP KONSULTATIF DARI AWAL HINGGA TAYANG"
        size="lg"
        class="mb-8"
      />

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="(step, idx) in workflow"
          :key="idx"
          class="relative bg-white border border-neutral-200 p-6 flex flex-col justify-between"
        >
          <div>
            <div class="font-headline text-4xl font-black text-neutral-300 mb-3">
              {{ step.step }}
            </div>
            <h4 class="text-base font-bold text-neutral-900 mb-2 leading-snug">
              {{ step.title }}
            </h4>
            <p class="text-xs text-neutral-600 leading-relaxed">
              {{ step.desc }}
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-neutral-100 text-[11px] font-mono text-neutral-400">
            Tahap {{ idx + 1 }} dari 4
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ 6. FORM REQUEST FOR PROPOSAL (RFP) ═══ -->
    <section id="ajukan-kemitraan" class="w-full px-4 sm:px-6 md:px-8 xl:px-12 py-12 lg:py-16 bg-neutral-900 text-white">
      <div class="max-w-4xl mx-auto">
        <!-- Heading -->
        <div class="text-center max-w-2xl mx-auto mb-10">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold font-mono tracking-widest bg-isf-red/20 text-isf-red border border-isf-red/30 mb-3 uppercase">
            <IconSend class="w-3.5 h-3.5" />
            Formulir Permintaan Kemitraan (RFP)
          </div>
          <h2 class="font-headline text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-white">
            Sampaikan Kebutuhan Strategis Institusi Anda
          </h2>
          <p class="text-xs sm:text-sm text-neutral-400 mt-3 leading-relaxed">
            Isi formulir di bawah ini. Tim Kemitraan ISF akan mengkaji profil kebutuhan Anda dan menghubungi narahubung resmi dalam kurun waktu <strong>1x24 jam kerja</strong>.
          </p>
        </div>

        <!-- Success Banner / View -->
        <div
          v-if="submittedSuccess"
          class="bg-neutral-950 border border-emerald-500/40 rounded-xs p-8 text-center space-y-5 shadow-2xl"
        >
          <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center">
            <IconCheck class="w-8 h-8" />
          </div>
          <div class="space-y-2">
            <h3 class="font-headline text-2xl sm:text-3xl font-black text-white">
              Permintaan Kemitraan Berhasil Dikirim
            </h3>
            <p class="text-sm text-neutral-300 max-w-xl mx-auto leading-relaxed">
              Terima kasih atas kepercayaan Anda terhadap Indonesia Strategy Forum. Data proposal Anda telah tercatat dengan aman di basis data kemitraan kami.
            </p>
          </div>

          <!-- Summary card -->
          <div class="max-w-md mx-auto bg-neutral-900 border border-neutral-800 p-4 rounded-xs text-left text-xs space-y-2">
            <div class="flex justify-between py-1 border-b border-neutral-800">
              <span class="text-neutral-400">Institusi:</span>
              <span class="font-bold text-white">{{ submissionResult?.institution_name }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-neutral-800">
              <span class="text-neutral-400">Narahubung (PIC):</span>
              <span class="font-bold text-white">{{ submissionResult?.pic_name }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-neutral-800">
              <span class="text-neutral-400">Jenis Kemitraan:</span>
              <span class="font-bold text-isf-red">{{ submissionResult?.partnership_type_label }}</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-neutral-400">SLA Tanggapan:</span>
              <span class="font-bold text-emerald-400">Maksimal 1x24 Jam Kerja</span>
            </div>
          </div>

          <div class="p-3 bg-neutral-900/60 rounded-xs border border-neutral-800 text-xs text-neutral-400 max-w-lg mx-auto">
            Email konfirmasi penerimaan proposal telah otomatis dikirimkan ke alamat email PIC yang Anda daftarkan.
          </div>

          <button
            type="button"
            @click="resetForm"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-neutral-800 hover:bg-neutral-700 text-white font-medium text-xs rounded-xs transition-colors"
          >
            <span>Kirim Permintaan Lain</span>
          </button>
        </div>

        <!-- Form Box -->
        <form
          v-else
          @submit.prevent="handleSubmitRfp"
          class="bg-neutral-950 border border-neutral-800 p-6 sm:p-10 rounded-xs space-y-6 shadow-xl"
        >
          <!-- General error alert -->
          <div
            v-if="formErrors.general"
            class="p-4 bg-red-950/80 border border-red-800 text-red-200 text-xs rounded-xs flex items-start gap-2.5"
          >
            <IconAlertCircle class="w-5 h-5 text-red-400 shrink-0" />
            <div>
              <strong class="font-bold">Terjadi Kesalahan:</strong>
              <div v-for="(msg, i) in formErrors.general" :key="i">{{ msg }}</div>
            </div>
          </div>

          <!-- Row 1: Institusi & PIC -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label for="rfp-institution" class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-1.5">
                Nama Institusi / Perusahaan <span class="text-isf-red">*</span>
              </label>
              <input
                id="rfp-institution"
                type="text"
                v-model="form.institution_name"
                @input="clearError('institution_name')"
                placeholder="Contoh: PT Solusi Strategis Indonesia / Kementerian X"
                class="w-full bg-neutral-900 border border-neutral-700 focus:border-isf-red focus:ring-1 focus:ring-isf-red px-3.5 py-2.5 text-xs text-white placeholder-neutral-500 rounded-xs outline-none transition-colors"
              />
              <p v-if="formErrors.institution_name" class="mt-1 text-[11px] text-red-400">
                {{ formErrors.institution_name[0] }}
              </p>
            </div>

            <div>
              <label for="rfp-pic" class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-1.5">
                Nama Lengkap PIC / Narahubung <span class="text-isf-red">*</span>
              </label>
              <input
                id="rfp-pic"
                type="text"
                v-model="form.pic_name"
                @input="clearError('pic_name')"
                placeholder="Contoh: Dian Pratama, S.H."
                class="w-full bg-neutral-900 border border-neutral-700 focus:border-isf-red focus:ring-1 focus:ring-isf-red px-3.5 py-2.5 text-xs text-white placeholder-neutral-500 rounded-xs outline-none transition-colors"
              />
              <p v-if="formErrors.pic_name" class="mt-1 text-[11px] text-red-400">
                {{ formErrors.pic_name[0] }}
              </p>
            </div>
          </div>

          <!-- Row 2: Email & Phone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label for="rfp-email" class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-1.5">
                Alamat Email Resmi <span class="text-isf-red">*</span>
              </label>
              <input
                id="rfp-email"
                type="email"
                v-model="form.email"
                @input="clearError('email')"
                placeholder="Contoh: dian.pratama@institusi.go.id"
                class="w-full bg-neutral-900 border border-neutral-700 focus:border-isf-red focus:ring-1 focus:ring-isf-red px-3.5 py-2.5 text-xs text-white placeholder-neutral-500 rounded-xs outline-none transition-colors"
              />
              <p v-if="formErrors.email" class="mt-1 text-[11px] text-red-400">
                {{ formErrors.email[0] }}
              </p>
            </div>

            <div>
              <label for="rfp-phone" class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-1.5">
                Nomor Telepon / WhatsApp <span class="text-isf-red">*</span>
              </label>
              <input
                id="rfp-phone"
                type="tel"
                v-model="form.phone"
                @input="clearError('phone')"
                placeholder="Contoh: 081234567890"
                class="w-full bg-neutral-900 border border-neutral-700 focus:border-isf-red focus:ring-1 focus:ring-isf-red px-3.5 py-2.5 text-xs text-white placeholder-neutral-500 rounded-xs outline-none transition-colors"
              />
              <p v-if="formErrors.phone" class="mt-1 text-[11px] text-red-400">
                {{ formErrors.phone[0] }}
              </p>
            </div>
          </div>

          <!-- Row 3: Jenis Kemitraan Selector -->
          <div>
            <label class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-2">
              Jenis Kemitraan yang Diminati <span class="text-isf-red">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
              <label
                v-for="item in partnershipTypes"
                :key="item.value"
                :class="[
                  'flex items-center gap-2.5 p-3 rounded-xs border cursor-pointer transition-all text-xs select-none break-words min-h-[44px]',
                  form.partnership_type === item.value
                    ? 'bg-isf-red/15 border-isf-red text-white font-medium'
                    : 'bg-neutral-900 border-neutral-800 text-neutral-400 hover:border-neutral-700 hover:text-neutral-200'
                ]"
              >
                <input
                  type="radio"
                  name="partnership_type"
                  :value="item.value"
                  v-model="form.partnership_type"
                  class="sr-only"
                />
                <span
                  :class="[
                    'w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0',
                    form.partnership_type === item.value ? 'border-isf-red bg-isf-red' : 'border-neutral-600'
                  ]"
                >
                  <span v-if="form.partnership_type === item.value" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                </span>
                <span class="leading-snug break-words">{{ item.label }}</span>
              </label>
            </div>
            <p v-if="formErrors.partnership_type" class="mt-1 text-[11px] text-red-400">
              {{ formErrors.partnership_type[0] }}
            </p>
          </div>

          <!-- Row 4: Uraian Kebutuhan & Pesan -->
          <div>
            <label for="rfp-message" class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-1.5">
              Uraian Kebutuhan &amp; Sasaran Strategis <span class="text-isf-red">*</span>
            </label>
            <textarea
              id="rfp-message"
              rows="4"
              v-model="form.message"
              @input="clearError('message')"
              placeholder="Jelaskan secara ringkas tujuan kemitraan, target audiens spesifik yang disasar, estimasi periode pelaksanaan, atau topik kebijakan yang ingin diangkat..."
              class="w-full bg-neutral-900 border border-neutral-700 focus:border-isf-red focus:ring-1 focus:ring-isf-red p-3 text-xs text-white placeholder-neutral-500 rounded-xs outline-none transition-colors"
            ></textarea>
            <p v-if="formErrors.message" class="mt-1 text-[11px] text-red-400">
              {{ formErrors.message[0] }}
            </p>
          </div>

          <!-- Row 5: Visual Captcha -->
          <div class="p-4 sm:p-5 bg-neutral-900 border border-neutral-800 rounded-xs space-y-3">
            <div>
              <label for="rfp-captcha" class="block text-xs font-mono font-bold tracking-wider text-neutral-300 uppercase mb-1">
                Verifikasi Keamanan (Captcha) <span class="text-isf-red">*</span>
              </label>
              <p class="text-xs text-neutral-400">
                Ketik karakter visual yang tampak pada gambar untuk mencegah bot otomatis.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
              <!-- Captcha Image Container -->
              <div class="flex items-center gap-2 bg-neutral-950 border border-neutral-700 rounded-xs px-3 py-1.5 h-11 shrink-0">
                <img
                  v-if="captchaSvg && !captchaLoading"
                  :src="captchaSvg"
                  alt="Kode Captcha"
                  class="h-8 max-w-[130px] object-contain select-none rounded-xs"
                />
                <div v-else class="w-24 h-8 flex items-center justify-center text-xs text-neutral-500">
                  <IconRefresh class="w-4 h-4 animate-spin text-neutral-400" />
                </div>
                <button
                  type="button"
                  @click="fetchCaptcha"
                  :disabled="captchaLoading"
                  class="p-1.5 text-neutral-400 hover:text-white hover:bg-neutral-800 rounded-xs transition-colors shrink-0"
                  title="Segarkan Gambar Captcha"
                  aria-label="Segarkan Captcha"
                >
                  <IconRefresh :class="['w-4 h-4', captchaLoading && 'animate-spin']" />
                </button>
              </div>

              <!-- Input Captcha -->
              <div class="w-full sm:w-64">
                <input
                  id="rfp-captcha"
                  type="text"
                  v-model="form.captcha_answer"
                  @input="clearError('captcha_answer')"
                  maxlength="10"
                  placeholder="Masukkan kode captcha"
                  class="w-full h-11 bg-neutral-950 border border-neutral-700 focus:border-isf-red focus:ring-1 focus:ring-isf-red px-3 text-xs text-white placeholder-neutral-500 rounded-xs outline-none transition-colors uppercase font-mono tracking-widest"
                />
              </div>
            </div>

            <p v-if="formErrors.captcha_answer" class="text-[11px] text-red-400 mt-1">
              {{ formErrors.captcha_answer[0] }}
            </p>
          </div>

          <!-- Submit Button & SLA Statement -->
          <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-neutral-800">
            <div class="flex items-center gap-2 text-xs text-neutral-400">
              <IconLockCheck class="w-4 h-4 text-neutral-500 shrink-0" />
              <span>Data institusi Anda terenkripsi aman dan tidak dipindahtangankan kepada pihak ketiga.</span>
            </div>

            <button
              type="submit"
              :disabled="submitting"
              class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-isf-red hover:bg-red-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm tracking-wide transition-all shadow-md active:scale-95 shrink-0"
            >
              <IconRefresh v-if="submitting" class="w-4 h-4 animate-spin" />
              <IconSend v-else class="w-4 h-4" />
              <span>{{ submitting ? 'Mengirim Proposal...' : 'Kirim Permintaan RFP' }}</span>
            </button>
          </div>
        </form>
      </div>
    </section>

    <!-- ═══ 7. FAQ (TANYA JAWAB KEMITRAAN) ═══ -->
    <section class="w-full px-4 sm:px-6 md:px-8 xl:px-12 py-12 lg:py-16 border-b border-neutral-200">
      <div class="max-w-4xl mx-auto">
        <SectionHeading
          title="TANYA JAWAB SEPUTAR KEMITRAAN"
          eyebrow="TRANSPARANSI KEBIJAKAN &amp; KETENTUAN ISF"
          size="lg"
          class="mb-8"
        />

        <div class="space-y-4">
          <div
            v-for="(item, idx) in faq"
            :key="idx"
            class="border border-neutral-200 bg-white rounded-xs overflow-hidden transition-colors"
          >
            <button
              type="button"
              @click="toggleFaq(idx)"
              class="w-full text-left px-5 py-4 flex items-center justify-between gap-4 hover:bg-neutral-50 transition-colors"
            >
              <span class="font-headline text-base sm:text-lg font-bold text-neutral-900 leading-snug">
                {{ item.q }}
              </span>
              <IconChevronUp v-if="activeFaq === idx" class="w-5 h-5 text-neutral-500 shrink-0" />
              <IconChevronDown v-else class="w-5 h-5 text-neutral-400 shrink-0" />
            </button>
            <div
              v-show="activeFaq === idx"
              class="px-5 pb-5 text-xs sm:text-sm text-neutral-600 leading-relaxed border-t border-neutral-100 pt-3"
            >
              {{ item.a }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ 8. DIRECT CONTACT & KONSULTASI CEPAT ═══ -->
    <section class="w-full px-4 sm:px-6 md:px-8 xl:px-12 py-12 bg-[#FBF9F4]">
      <div class="max-w-4xl mx-auto bg-white border border-neutral-200 p-6 sm:p-8 rounded-xs shadow-xs flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="space-y-1.5 text-center sm:text-left">
          <div class="text-xs font-mono font-bold tracking-wider text-isf-red uppercase">KONSULTASI LANGSUNG</div>
          <h4 class="font-headline text-xl sm:text-2xl font-black text-neutral-900">
            Perlu Diskusi Kemitraan Khusus atau Mendesak?
          </h4>
          <p class="text-xs sm:text-sm text-neutral-600">
            Hubungi Tim Kemitraan Strategis ISF melalui surat elektronik resmi kami.
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <a
            href="mailto:kemitraan@indonesiastrategyforum.com"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white font-medium text-xs rounded-xs transition-colors"
          >
            <IconMail class="w-4 h-4" />
            <span>kemitraan@indonesiastrategyforum.com</span>
          </a>
        </div>
      </div>
    </section>
  </div>
</template>

