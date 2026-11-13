<script setup>
/**
 * RatingSummary — ringkasan rating gaya Play Store yang bisa dipakai ulang:
 * skor rata-rata besar + bintang + jumlah ulasan, dan sebaran bintang 5→1 (bar).
 *
 * <RatingSummary :average="4.3" :total="120" :distribution="{ 5: 80, 4: 20, 3: 10, 2: 5, 1: 5 }" />
 */
import { computed } from "vue";
import StarRating from "./StarRating.vue";

const props = defineProps({
    average: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    /** { "5": n, "4": n, "3": n, "2": n, "1": n } */
    distribution: { type: Object, default: () => ({}) },
});

const rows = computed(() =>
    [5, 4, 3, 2, 1].map((star) => {
        const count = Number(props.distribution?.[star] ?? props.distribution?.[String(star)] ?? 0);
        return { star, count, percent: props.total ? Math.round((count / props.total) * 100) : 0 };
    }),
);

const averageLabel = computed(() => (props.total ? props.average.toFixed(1).replace(".", ",") : "–"));
const formatCount = (n) => new Intl.NumberFormat("id-ID").format(n);
</script>

<template>
    <div class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-6">
        <div class="text-center">
            <p class="font-headline text-5xl font-bold leading-none text-neutral-950" data-testid="rating-average">
                {{ averageLabel }}
            </p>
            <StarRating class="mt-2" :value="total ? average : 0" size="sm" />
            <p class="mt-1 text-xs text-neutral-600">{{ formatCount(total) }} ulasan</p>
        </div>

        <ul class="space-y-1.5" aria-label="Sebaran rating bintang">
            <li v-for="row in rows" :key="row.star" class="flex items-center gap-2 text-xs text-neutral-700">
                <span class="w-3 shrink-0 text-right font-semibold">{{ row.star }}</span>
                <span class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-neutral-200" aria-hidden="true">
                    <span class="block h-full rounded-full bg-amber-500" :style="{ width: `${row.percent}%` }"></span>
                </span>
                <span class="sr-only">bintang: {{ row.count }} ulasan</span>
                <span class="w-8 shrink-0 text-right tabular-nums text-neutral-500" aria-hidden="true">{{ formatCount(row.count) }}</span>
            </li>
        </ul>
    </div>
</template>
