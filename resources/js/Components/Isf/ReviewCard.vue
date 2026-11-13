<script setup>
/**
 * ReviewCard — satu kartu ulasan publik (avatar inisial / anonim, bintang, tanggal, isi).
 * Data anonim sudah disamarkan oleh server (nama "Anonim", tanpa inisial); komponen hanya menampilkan.
 *
 * <ReviewCard :review="{ rating, body, isAnonymous, isMine, author: { name, initials }, updatedAt }">
 *     <template #action><button>Edit</button></template>   (opsional, tampil di samping header)
 * </ReviewCard>
 */
import { IconUserCircle } from "@tabler/icons-vue";
import { formatDate } from "@/Utils/formatters";
import StarRating from "./StarRating.vue";

defineProps({
    review: { type: Object, required: true },
});
</script>

<template>
    <article class="space-y-2 py-4">
        <header class="flex items-center gap-3">
            <span
                v-if="!review.isAnonymous && review.author?.initials"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-isf-navy text-xs font-bold text-white"
                aria-hidden="true"
                >{{ review.author.initials }}</span
            >
            <IconUserCircle
                v-else
                class="h-9 w-9 shrink-0 text-neutral-400"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-neutral-900">
                    {{ review.author?.name }}
                    <span
                        v-if="review.isMine"
                        class="ml-1 text-xs font-normal text-neutral-500"
                        >(ulasan Anda)</span
                    >
                </p>
                <div class="flex items-center gap-2">
                    <StarRating :value="review.rating" size="sm" />
                    <time
                        v-if="review.updatedAt"
                        :datetime="review.updatedAt"
                        class="text-xs text-neutral-500"
                    >
                        {{ formatDate(review.updatedAt) }}
                    </time>
                </div>
            </div>
            <!-- Aksi di samping ulasan (mis. tombol Edit untuk ulasan milik user) -->
            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" />
            </div>
        </header>
        <p
            class="whitespace-pre-line font-serif text-[15px] leading-relaxed text-neutral-800"
        >
            {{ review.body }}
        </p>
    </article>
</template>
