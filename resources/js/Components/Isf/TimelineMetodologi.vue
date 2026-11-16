<script setup>
/**
 * TimelineMetodologi.vue — Infografis Interaktif Alur Metodologi & Transparansi Riset Survei ISF
 * Menggunakan konsep timeline modern & minimalis bergaya shadcn UI.
 *
 * Fitur:
 * - Minimalist hairline vertical track dengan node dot indicator.
 * - Pulsing radar indicator pada tahap rilis publik.
 * - Pill badge tahapan berbasis parameter ilmiah (Sampling, CAPI, QC, MoE, Publikasi).
 * - Tata letak 100% responsif di mobile, tablet, dan desktop tanpa clipping.
 */
import { computed } from 'vue'
import {
  IconUsers,
  IconScale,
  IconCalendar,
  IconBuilding,
  IconCheck,
  IconShieldCheck,
  IconDeviceTablet,
  IconFileSpreadsheet,
} from '@tabler/icons-vue'

const props = defineProps({
  survey: {
    type: Object,
    required: true,
  },
  isDark: {
    type: Boolean,
    default: false,
  },
})

const stages = computed(() => {
  const s = props.survey || {}
  return [
    {
      step: '01',
      title: 'Kerangka Sampling & Desain Kuesioner',
      subtitle: 'Formulasi Metodologi Ilmiah',
      status: 'Selesai',
      badgeClass: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
      description:
        'Penyusunan instrumen kuesioner dengan prinsip pertanyaan netral, uji validitas konstruk, serta penetapan kerangka acuan unit sampel acak secara berjenjang dari tingkat provinsi hingga rukun tetangga.',
      metrics: [
        {
          label: 'Metode Penarikan Sampel',
          value: s.sampling_method || 'Multistage Random Sampling',
          icon: IconShieldCheck,
        },
        {
          label: 'Ukuran Sampel Responden',
          value: s.sample_size ? `${s.sample_size.toLocaleString('id-ID')} Responden` : '1.200 Responden',
          icon: IconUsers,
        },
      ],
    },
    {
      step: '02',
      title: 'Pengumpulan Data Lapangan (Fieldwork)',
      subtitle: 'Wawancara Tatap Muka Terstruktur',
      status: 'Selesai',
      badgeClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
      description:
        `Enumerator profesional terlatih dari ${s.institution || 'Indonesia Strategy Forum'} melakukan wawancara tatap muka langsung kepada responden terpilih dengan kontrol geolocation GPS dan rekaman audio acak.`,
      metrics: [
        {
          label: 'Periode Waktu Pengambilan Data',
          value: s.survey_period || 'Periode Lapangan',
          icon: IconCalendar,
        },
        {
          label: 'Teknik Wawancara',
          value: s.interview_method || 'Wawancara Tatap Muka (CAPI)',
          icon: IconDeviceTablet,
        },
      ],
    },
    {
      step: '03',
      title: 'Kontrol Kualitas & Verifikasi (Quality Control)',
      subtitle: 'Audit Integritas Data Independen',
      status: 'Selesai',
      badgeClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      description:
        'Verifikasi ganda terhadap sedikitnya 20% dari total sampel acak melalui spot-check langsung ke alamat responden dan re-interview melalui telepon guna mengeliminasi potensi manipulasi atau bias.',
      metrics: [
        {
          label: 'Margin of Error (Toleransi)',
          value: s.margin_of_error || '± 2.8%',
          icon: IconScale,
        },
        {
          label: 'Tingkat Kepercayaan',
          value: s.confidence_level || '95%',
          icon: IconShieldCheck,
        },
      ],
    },
    {
      step: '04',
      title: 'Pembersihan Data & Tabulasi Silang',
      subtitle: 'Analisis Ekonometrika & Demografi',
      status: 'Selesai',
      badgeClass: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
      description:
        'Pemeriksaan konsistensi logika data (data cleaning), penyesuaian bobot populasi (post-stratification weighting), serta agregasi tabulasi silang lintas segmen usia, gender, pendidikan, dan wilayah.',
      metrics: [
        {
          label: 'Segmentasi Analisis',
          value: 'Usia, Gender, Wilayah, Sosio-Ekonomi',
          icon: IconFileSpreadsheet,
        },
        {
          label: 'Cakupan Wilayah',
          value: (s.region || 'Nasional').toUpperCase(),
          icon: IconBuilding,
        },
      ],
    },
    {
      step: '05',
      title: 'Rilis Publik & Desiminasi Laporan Riset',
      subtitle: 'Publikasi Terbuka & Telaah Kebijakan',
      status: 'Terpublikasi',
      isActive: true,
      badgeClass: 'bg-isf-red text-white border-isf-red',
      description:
        `Publikasi hasil riset lengkap secara independen dan transparan oleh ${s.institution || 'ISF'} untuk menjadi rujukan pengambilan keputusan strategis nasional serta literasi publik yang berimbang.`,
      metrics: [
        {
          label: 'Tanggal Rilis Resmi',
          value: s.release_date || 'Terpublikasi',
          icon: IconCalendar,
        },
        {
          label: 'Lembaga Pelaksana',
          value: s.institution || 'Indonesia Strategy Forum',
          icon: IconBuilding,
        },
      ],
    },
  ]
})
</script>

<template>
  <div class="font-space-grotesk">
    <!-- Hairline Vertical Track (shadcn-style) -->
    <div
      :class="[
        'relative ml-3 sm:ml-4 pl-5 sm:pl-8 space-y-6 sm:space-y-8 border-l transition-colors',
        isDark ? 'border-neutral-800' : 'border-neutral-200'
      ]"
    >
      <div
        v-for="st in stages"
        :key="st.step"
        class="relative group"
      >
        <!-- Node Dot on Line -->
        <div
          :class="[
            'absolute -left-[1.85rem] sm:-left-[2.45rem] top-1.5 w-3.5 h-3.5 rounded-full ring-4 transition-transform group-hover:scale-110 flex items-center justify-center',
            st.isActive
              ? 'bg-isf-red ring-isf-red/25'
              : (isDark ? 'bg-neutral-800 border-2 border-neutral-600 ring-neutral-900' : 'bg-white border-2 border-isf-red ring-isf-red/15')
          ]"
        >
          <!-- Pulsing dot on active stage -->
          <span
            v-if="st.isActive"
            class="absolute inset-0 rounded-full bg-isf-red animate-ping opacity-75"
          ></span>
        </div>

        <!-- Stage Content Card -->
        <div
          :class="[
            'rounded-lg p-4 sm:p-5 border transition-all duration-200',
            isDark
              ? 'bg-isf-panel/80 border-neutral-800 hover:border-neutral-700'
              : 'bg-neutral-50/70 border-neutral-200 hover:border-neutral-300 hover:bg-white hover:shadow-2xs'
          ]"
        >
          <!-- Top Row: Stage Step Badge, Subtitle & Status -->
          <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-isf-red">
                Tahap {{ st.step }}
              </span>
              <span class="text-neutral-400">•</span>
              <span :class="['text-xs font-medium', isDark ? 'text-neutral-400' : 'text-neutral-500']">
                {{ st.subtitle }}
              </span>
            </div>

            <span
              :class="[
                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider border',
                st.badgeClass
              ]"
            >
              <IconCheck v-if="!st.isActive" class="w-3 h-3" />
              <span>{{ st.status }}</span>
            </span>
          </div>

          <!-- Stage Heading -->
          <h4
            :class="[
              'font-headline font-bold text-base sm:text-lg mb-2 leading-snug',
              isDark ? 'text-white' : 'text-neutral-950'
            ]"
          >
            {{ st.title }}
          </h4>

          <!-- Stage Description -->
          <p
            :class="[
              'text-xs sm:text-sm font-serif leading-relaxed mb-4',
              isDark ? 'text-neutral-300' : 'text-neutral-600'
            ]"
          >
            {{ st.description }}
          </p>

          <!-- Parameters / Key Metrics (Grid 1-col on mobile, 2-col on sm+) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 pt-3 border-t" :class="isDark ? 'border-neutral-800' : 'border-neutral-200'">
            <div
              v-for="(m, mIdx) in st.metrics"
              :key="mIdx"
              :class="[
                'p-2.5 rounded-md border flex items-start gap-2.5',
                isDark
                  ? 'bg-isf-navy border-neutral-800'
                  : 'bg-white border-neutral-200'
              ]"
            >
              <component
                :is="m.icon"
                class="w-4 h-4 text-isf-red shrink-0 mt-0.5"
                aria-hidden="true"
              />
              <div class="min-w-0 flex-1">
                <span :class="['text-[10px] uppercase font-mono block truncate', isDark ? 'text-neutral-400' : 'text-neutral-500']">
                  {{ m.label }}
                </span>
                <span
                  :class="[
                    'text-xs font-bold font-space-grotesk block truncate',
                    isDark ? 'text-white' : 'text-neutral-900'
                  ]"
                >
                  {{ m.value }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

