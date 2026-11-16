<script setup>
import { computed, ref } from 'vue'
import {
  IconNetwork,
  IconBuildingBank,
  IconUsers,
  IconUserCheck,
  IconUserX,
  IconTable,
  IconChevronDown,
} from '@tabler/icons-vue'

const props = defineProps({
  affiliations: {
    type: Array,
    default: () => [],
  },
  allies: {
    type: Array,
    default: () => [],
  },
  critics: {
    type: Array,
    default: () => [],
  },
  tokohName: {
    type: String,
    default: 'Tokoh',
  },
})

const showTable = ref(false)

const totalRelations = computed(() => {
  return (props.affiliations?.length || 0) + (props.allies?.length || 0) + (props.critics?.length || 0)
})
</script>

<template>
  <div class="space-y-8">
    <!-- Network Overview Banner -->
    <div class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-[#FBF9F4] dark:bg-neutral-900/60 p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div>
        <div class="flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-isf-red uppercase mb-1">
          <IconNetwork class="w-4 h-4 shrink-0" />
          <span>Pemetaan Jejaring Kekuasaan & Relasi</span>
        </div>
        <h3 class="font-headline text-xl sm:text-2xl font-bold text-neutral-950 dark:text-white">
          Peta Pengaruh & Jejaring {{ tokohName }}
        </h3>
        <p class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk mt-1 max-w-2xl leading-relaxed">
          Pemetaan relasi strategis meliputi kelembagaan formal, sekutu koalisi pembuat kebijakan, serta poros kritis masyarakat sipil.
        </p>
      </div>

      <div class="flex items-center gap-4 border-t md:border-t-0 md:border-l border-neutral-200 dark:border-neutral-800 pt-4 md:pt-0 md:pl-6 shrink-0">
        <div class="text-center">
          <div class="text-2xl font-bold font-mono text-[#0B1220] dark:text-white">
            {{ totalRelations }}
          </div>
          <div class="text-[11px] text-neutral-500 font-space-grotesk">Total Relasi</div>
        </div>
        <div class="h-8 w-px bg-neutral-200 dark:bg-neutral-800"></div>
        <div class="text-center">
          <div class="text-2xl font-bold font-mono text-emerald-600">
            {{ allies.length }}
          </div>
          <div class="text-[11px] text-neutral-500 font-space-grotesk">Sekutu/Mitra</div>
        </div>
        <div class="h-8 w-px bg-neutral-200 dark:bg-neutral-800"></div>
        <div class="text-center">
          <div class="text-2xl font-bold font-mono text-rose-600">
            {{ critics.length }}
          </div>
          <div class="text-[11px] text-neutral-500 font-space-grotesk">Pihak Kritis</div>
        </div>
      </div>
    </div>

    <!-- 3 Core Network Columns -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- 1. Afiliasi Kelembagaan & Partai -->
      <div class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-xs flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-200 dark:border-neutral-800 mb-4">
            <div class="flex items-center gap-2">
              <IconBuildingBank class="w-5 h-5 text-blue-600 shrink-0" />
              <h4 class="font-headline text-base font-bold text-neutral-950 dark:text-white">
                Afiliasi Kelembagaan
              </h4>
            </div>
            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 text-xs font-mono font-bold">
              {{ affiliations.length }}
            </span>
          </div>

          <div v-if="affiliations.length === 0" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
            Belum ada catatan afiliasi formal terdaftar.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(item, idx) in affiliations"
              :key="idx"
              class="p-3.5 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-neutral-50/60 dark:bg-neutral-800/40"
            >
              <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                  {{ item.type || 'Organisasi' }}
                </span>
                <span class="text-[10px] font-mono text-neutral-400">#{{ idx + 1 }}</span>
              </div>
              <div class="font-bold text-sm text-neutral-950 dark:text-white line-clamp-1">
                {{ item.name }}
              </div>
              <div class="text-xs text-neutral-500 font-space-grotesk mt-0.5">
                {{ item.role || 'Anggota / Pengurus' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Sekutu & Koalisi Strategis -->
      <div class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-xs flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-200 dark:border-neutral-800 mb-4">
            <div class="flex items-center gap-2">
              <IconUserCheck class="w-5 h-5 text-emerald-600 shrink-0" />
              <h4 class="font-headline text-base font-bold text-neutral-950 dark:text-white">
                Sekutu & Mitra Koalisi
              </h4>
            </div>
            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 text-xs font-mono font-bold">
              {{ allies.length }}
            </span>
          </div>

          <div v-if="allies.length === 0" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
            Belum ada catatan sekutu strategis tercatat.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(item, idx) in allies"
              :key="idx"
              class="p-3.5 rounded-lg border border-emerald-100 dark:border-emerald-950/60 bg-emerald-50/40 dark:bg-emerald-950/20"
            >
              <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                  {{ item.role || 'Mitra' }}
                </span>
                <span class="text-[10px] font-mono text-neutral-400">Sekutu</span>
              </div>
              <div class="font-bold text-sm text-neutral-950 dark:text-white line-clamp-1">
                {{ item.name }}
              </div>
              <div class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk mt-1 leading-relaxed">
                {{ item.relation || 'Kolaborasi dalam penyusunan kebijakan' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Oposan & Pihak Kritis -->
      <div class="rounded-xl border border-neutral-300 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-xs flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-neutral-200 dark:border-neutral-800 mb-4">
            <div class="flex items-center gap-2">
              <IconUserX class="w-5 h-5 text-rose-600 shrink-0" />
              <h4 class="font-headline text-base font-bold text-neutral-950 dark:text-white">
                Oposan & Penentang Kritis
              </h4>
            </div>
            <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 text-xs font-mono font-bold">
              {{ critics.length }}
            </span>
          </div>

          <div v-if="critics.length === 0" class="py-8 text-center text-xs text-neutral-400 font-space-grotesk">
            Belum ada catatan oposan/penentang kritis tercatat.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(item, idx) in critics"
              :key="idx"
              class="p-3.5 rounded-lg border border-rose-100 dark:border-rose-950/60 bg-rose-50/40 dark:bg-rose-950/20"
            >
              <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                  {{ item.role || 'Pihak Kritis' }}
                </span>
                <span class="text-[10px] font-mono text-neutral-400">Kritik</span>
              </div>
              <div class="font-bold text-sm text-neutral-950 dark:text-white line-clamp-1">
                {{ item.name }}
              </div>
              <div class="text-xs text-neutral-600 dark:text-neutral-400 font-space-grotesk mt-1 leading-relaxed">
                {{ item.note || 'Menyuarakan evaluasi kritis terhadap arah kebijakan' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toggle Accessible Data Table for Screen Readers -->
    <div class="pt-4 border-t border-neutral-200 dark:border-neutral-800">
      <button
        type="button"
        @click="showTable = !showTable"
        class="inline-flex items-center gap-2 text-xs font-mono font-semibold text-neutral-600 dark:text-neutral-400 hover:text-isf-red dark:hover:text-isf-red transition-colors cursor-pointer"
        :aria-expanded="showTable"
      >
        <IconTable class="w-4 h-4 shrink-0" />
        <span>{{ showTable ? 'Sembunyikan' : 'Tampilkan' }} Tabel Alternatif (Untuk Aksesibilitas & Pembaca Layar)</span>
        <IconChevronDown :class="['w-3.5 h-3.5 transition-transform duration-200', showTable ? 'rotate-180' : '']" />
      </button>

      <div v-if="showTable" class="mt-4 overflow-x-auto rounded-lg border border-neutral-300 dark:border-neutral-800">
        <table class="w-full text-left text-xs font-space-grotesk">
          <thead class="bg-neutral-100 dark:bg-neutral-800/80 font-mono font-bold uppercase text-neutral-600 dark:text-neutral-300">
            <tr>
              <th class="p-3">Kategori Relasi</th>
              <th class="p-3">Nama Entitas / Tokoh</th>
              <th class="p-3">Peran / Institusi</th>
              <th class="p-3">Keterangan / Dinamika</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <tr v-for="(it, idx) in affiliations" :key="'aff-'+idx" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
              <td class="p-3 font-semibold text-blue-600">Afiliasi Formal</td>
              <td class="p-3 font-bold">{{ it.name }}</td>
              <td class="p-3">{{ it.role || '-' }}</td>
              <td class="p-3 text-neutral-500">{{ it.type || '-' }}</td>
            </tr>
            <tr v-for="(it, idx) in allies" :key="'all-'+idx" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
              <td class="p-3 font-semibold text-emerald-600">Sekutu / Mitra</td>
              <td class="p-3 font-bold">{{ it.name }}</td>
              <td class="p-3">{{ it.role || '-' }}</td>
              <td class="p-3 text-neutral-500">{{ it.relation || '-' }}</td>
            </tr>
            <tr v-for="(it, idx) in critics" :key="'cri-'+idx" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
              <td class="p-3 font-semibold text-rose-600">Oposan / Kritis</td>
              <td class="p-3 font-bold">{{ it.name }}</td>
              <td class="p-3">{{ it.role || '-' }}</td>
              <td class="p-3 text-neutral-500">{{ it.note || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

