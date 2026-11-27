<script setup>
import { IconInfoCircle } from '@tabler/icons-vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      age: '',
      domicile: '',
      profession: '',
    }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue'])

const AGE_OPTIONS = [
  { value: '17-27', label: '17 - 27 Tahun (Gen Z)' },
  { value: '28-43', label: '28 - 43 Tahun (Milenial)' },
  { value: '44-59', label: '44 - 59 Tahun (Gen X)' },
  { value: '60+', label: '60+ Tahun (Baby Boomer)' },
]

const PROFESSION_OPTIONS = [
  'Akademisi / Peneliti / Dosen',
  'Profesional Swasta / Korporasi',
  'Aparatur Sipil Negara (ASN) / Pejabat Publik',
  'Pelaku Usaha / Pengusaha / Founder',
  'Praktisi Hukum / Advokat / Notaris',
  'Analis Kebijakan / Lembaga Riset / LSM',
  'Jurnalis / Praktisi Media',
  'Mahasiswa / Pascasarjana',
  'Lainnya',
]

const REGION_OPTIONS = [
  'DKI Jakarta',
  'Jawa Barat',
  'Jawa Tengah & DI Yogyakarta',
  'Jawa Timur',
  'Banten',
  'Sumatera Bagian Utara (Aceh, Sumut, Sumbar, Riau, Kepri)',
  'Sumatera Bagian Selatan (Sumsel, Jambi, Bengkulu, Lampung, Babel)',
  'Kalimantan',
  'Sulawesi',
  'Bali & Nusa Tenggara',
  'Maluku & Papua',
  'Luar Negeri (Diaspora)',
]

function updateField(key, val) {
  emit('update:modelValue', {
    ...props.modelValue,
    [key]: val,
  })
}
</script>

<template>
  <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 sm:p-7 shadow-xs">
    <div class="flex items-start gap-3 pb-4 border-b border-neutral-100 dark:border-neutral-800">
      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-isf-red/10 text-isf-red">
        <IconInfoCircle class="h-5 w-5" />
      </div>
      <div>
        <h3 class="font-heading text-lg font-bold text-neutral-900 dark:text-neutral-100">
          Data Diri Singkat
        </h3>
        <p class="font-ui text-xs text-neutral-500 dark:text-neutral-400 mt-1">
          Data ini bersifat rahasia dan digunakan untuk mengelompokkan hasil survei.
        </p>
      </div>
    </div>

    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-5">
      <!-- 1. Usia -->
      <div>
        <label class="block font-ui text-xs font-semibold text-neutral-800 dark:text-neutral-200 mb-1.5">
          Kelompok Usia <span class="text-isf-red">*</span>
        </label>
        <select
          :value="modelValue.age"
          @change="updateField('age', $event.target.value)"
          class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/80 p-3 text-sm text-neutral-900 dark:text-neutral-100 focus:border-isf-red focus:bg-white dark:focus:bg-neutral-900 focus:outline-none focus:ring-1 focus:ring-isf-red"
          :class="errors['demographics.age'] ? 'border-red-500' : ''"
        >
          <option value="" disabled>Pilih Kelompok Usia</option>
          <option v-for="opt in AGE_OPTIONS" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
        <p v-if="errors['demographics.age']" class="mt-1 text-xs text-red-600 dark:text-red-400">
          {{ errors['demographics.age'] }}
        </p>
      </div>

      <!-- 2. Domisili Wilayah -->
      <div>
        <label class="block font-ui text-xs font-semibold text-neutral-800 dark:text-neutral-200 mb-1.5">
          Domisili Wilayah <span class="text-isf-red">*</span>
        </label>
        <select
          :value="modelValue.domicile"
          @change="updateField('domicile', $event.target.value)"
          class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/80 p-3 text-sm text-neutral-900 dark:text-neutral-100 focus:border-isf-red focus:bg-white dark:focus:bg-neutral-900 focus:outline-none focus:ring-1 focus:ring-isf-red"
          :class="errors['demographics.domicile'] ? 'border-red-500' : ''"
        >
          <option value="" disabled>Pilih Wilayah Domisili</option>
          <option v-for="reg in REGION_OPTIONS" :key="reg" :value="reg">
            {{ reg }}
          </option>
        </select>
        <p v-if="errors['demographics.domicile']" class="mt-1 text-xs text-red-600 dark:text-red-400">
          {{ errors['demographics.domicile'] }}
        </p>
      </div>

      <!-- 3. Profesi -->
      <div>
        <label class="block font-ui text-xs font-semibold text-neutral-800 dark:text-neutral-200 mb-1.5">
          Latar Belakang Profesi <span class="text-isf-red">*</span>
        </label>
        <select
          :value="modelValue.profession"
          @change="updateField('profession', $event.target.value)"
          class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/80 p-3 text-sm text-neutral-900 dark:text-neutral-100 focus:border-isf-red focus:bg-white dark:focus:bg-neutral-900 focus:outline-none focus:ring-1 focus:ring-isf-red"
          :class="errors['demographics.profession'] ? 'border-red-500' : ''"
        >
          <option value="" disabled>Pilih Latar Belakang Profesi</option>
          <option v-for="prof in PROFESSION_OPTIONS" :key="prof" :value="prof">
            {{ prof }}
          </option>
        </select>
        <p v-if="errors['demographics.profession']" class="mt-1 text-xs text-red-600 dark:text-red-400">
          {{ errors['demographics.profession'] }}
        </p>
      </div>
    </div>
  </div>
</template>
