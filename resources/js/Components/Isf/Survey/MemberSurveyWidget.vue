<script setup>
import { onMounted, ref, computed } from "vue";
import { RouterLink } from "vue-router";
import {
    IconChartBar,
    IconClock,
    IconArrowRight,
    IconCheck,
    IconEdit,
    IconCircleCheck,
} from "@tabler/icons-vue";
import { getSurveiWidget } from "@/api/services/isfMemberSurveyService";

const data = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    try {
        data.value = await getSurveiWidget();
    } catch (e) {
        console.error("Failed to load member survey widget:", e);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

const summary = computed(
    () =>
        data.value?.summary || {
            waiting_count: 0,
            draft_count: 0,
            completed_count: 0,
        },
);
const pendingList = computed(() => data.value?.survei_menunggu || []);
const completedList = computed(() => data.value?.survei_selesai || []);
</script>

<template>
    <div
        v-if="!loading && data"
        class="rounded-lg border border-neutral-300 bg-white shadow-xs overflow-hidden"
    >
        <!-- Header Ringkas -->
        <div
            class="p-4 sm:p-5 border-b border-neutral-200 flex items-center justify-between gap-3"
        >
            <div class="flex items-center gap-2.5">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-md bg-isf-red/10 text-isf-red shrink-0"
                >
                    <IconChartBar class="h-4 w-4" />
                </span>
                <div>
                    <h2
                        class="font-headline text-lg font-bold text-neutral-900"
                    >
                        Survei Menunggu Jawaban Anda
                    </h2>
                    <p class="text-xs text-neutral-500">
                        Pilih survei di bawah ini untuk memberikan pendapat
                        Anda.
                    </p>
                </div>
            </div>
        </div>

        <!-- Ringkasan Status -->
        <div
            class="grid grid-cols-3 divide-x divide-neutral-200 border-b border-neutral-200 bg-neutral-50 text-center py-3"
        >
            <div>
                <span class="block text-xl font-bold text-neutral-900">{{
                    summary.waiting_count
                }}</span>
                <span class="text-xs text-neutral-600">Belum Diisi</span>
            </div>
            <div>
                <span class="block text-xl font-bold text-amber-600">{{
                    summary.draft_count
                }}</span>
                <span class="text-xs text-neutral-600">Draft</span>
            </div>
            <div>
                <span class="block text-xl font-bold text-emerald-600">{{
                    summary.completed_count
                }}</span>
                <span class="text-xs text-neutral-600">Selesai</span>
            </div>
        </div>

        <!-- Daftar Survei -->
        <div class="p-4 sm:p-5">
            <div
                v-if="pendingList.length === 0 && completedList.length === 0"
                class="py-6 text-center"
            >
                <IconCircleCheck
                    class="h-8 w-8 text-emerald-600 mx-auto mb-2"
                />
                <p class="font-headline font-bold text-neutral-800 text-sm">
                    Semua survei sudah Anda isi. Terima kasih!
                </p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="item in pendingList"
                    :key="item.id"
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-neutral-200 p-3.5 hover:border-neutral-300 transition-colors"
                >
                    <div class="min-w-0">
                        <h3
                            class="font-headline text-sm sm:text-base font-bold text-neutral-900 line-clamp-1"
                        >
                            {{ item.title }}
                        </h3>
                        <div
                            class="flex items-center gap-3 mt-1 text-xs text-neutral-500"
                        >
                            <span class="inline-flex items-center gap-1">
                                <IconClock class="h-3.5 w-3.5" />
                                {{ item.estimated_minutes }} Menit
                            </span>
                            <span
                                v-if="item.progress"
                                class="font-semibold text-amber-600"
                            >
                                Draft ({{ item.progress }}%)
                            </span>
                        </div>
                    </div>

                    <RouterLink
                        :to="`/survei/${item.slug || item.id}/isi`"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md px-4 py-2 text-xs font-bold transition-colors shrink-0"
                        :class="[
                            item.progress
                                ? 'bg-amber-500 text-white hover:bg-amber-600'
                                : 'bg-isf-red text-white hover:bg-red-700',
                        ]"
                    >
                        <span>{{
                            item.progress ? "Lanjutkan" : "Isi Survei"
                        }}</span>
                        <IconArrowRight class="h-3.5 w-3.5" />
                    </RouterLink>
                </div>

                <div v-if="completedList.length > 0" class="pt-2 space-y-2">
                    <div
                        v-for="item in completedList"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/50 p-3 text-xs"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <IconCheck
                                class="h-4 w-4 text-emerald-600 shrink-0"
                            />
                            <span
                                class="font-semibold text-neutral-800 truncate"
                                >{{ item.title }}</span
                            >
                        </div>
                        <RouterLink
                            :to="`/survei/${item.slug || item.id}/selesai`"
                            class="shrink-0 font-bold text-emerald-700 hover:underline"
                        >
                            Lihat Hasil →
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
