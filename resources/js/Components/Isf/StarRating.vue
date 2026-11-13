<script setup>
/**
 * StarRating — rating bintang 1–5 yang bisa dipakai ulang.
 *
 * Mode tampilan (default):  <StarRating :value="4.3" />          → bintang terisi sebagian, role="img"
 * Mode input:               <StarRating v-model="rating" interactive label="Rating Anda" />
 *                           → radiogroup (klik / tombol panah kiri-kanan), sesuai pola ARIA radio.
 */
import { computed } from "vue";
import { IconStarFilled } from "@tabler/icons-vue";

const props = defineProps({
    /** Nilai tampilan (boleh desimal, mis. rata-rata 4.3). Diabaikan saat interactive. */
    value: { type: Number, default: 0 },
    /** Nilai input (v-model) saat interactive: 0 = belum dipilih, 1–5. */
    modelValue: { type: Number, default: 0 },
    interactive: { type: Boolean, default: false },
    /** Ukuran bintang: 'sm' | 'md' | 'lg' */
    size: {
        type: String,
        default: "md",
        validator: (v) => ["sm", "md", "lg"].includes(v),
    },
    /** Label aksesibel untuk radiogroup (mode input). */
    label: { type: String, default: "Rating bintang" },
    /** id elemen pesan error/hint (aria-describedby) untuk mode input. */
    describedby: { type: String, default: undefined },
});

const emit = defineEmits(["update:modelValue"]);

const STARS = [1, 2, 3, 4, 5];
const sizeClass = computed(
    () => ({ sm: "h-3.5 w-3.5", md: "h-5 w-5", lg: "h-8 w-8" })[props.size],
);

const shown = computed(() =>
    Math.max(
        0,
        Math.min(5, props.interactive ? props.modelValue : props.value),
    ),
);

/** Persentase isi bintang ke-n (0–100), untuk nilai desimal. */
function fill(n) {
    return Math.round(Math.max(0, Math.min(1, shown.value - (n - 1))) * 100);
}

function select(n) {
    emit("update:modelValue", n);
}

function onKeydown(event, n) {
    const next = {
        ArrowRight: n + 1,
        ArrowUp: n + 1,
        ArrowLeft: n - 1,
        ArrowDown: n - 1,
        Home: 1,
        End: 5,
    }[event.key];
    if (next === undefined) return;
    event.preventDefault();
    const clamped = Math.max(1, Math.min(5, next));
    select(clamped);
    event.currentTarget.parentElement
        ?.querySelectorAll('[role="radio"]')
        [clamped - 1]?.focus();
}

const displayLabel = computed(
    () => `Rating ${String(shown.value).replace(".", ",")} dari 5 bintang`,
);
</script>

<template>
    <div
        v-if="interactive"
        role="radiogroup"
        :aria-label="label"
        :aria-describedby="describedby"
        class="inline-flex items-center gap-1"
    >
        <button
            v-for="n in STARS"
            :key="n"
            type="button"
            role="radio"
            :aria-checked="modelValue === n ? 'true' : 'false'"
            :aria-label="`${n} bintang`"
            :tabindex="modelValue === n || (!modelValue && n === 1) ? 0 : -1"
            class="rounded-sm p-0.5 transition-transform hover:scale-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
            @click="select(n)"
            @keydown="onKeydown($event, n)"
        >
            <IconStarFilled
                :class="[
                    sizeClass,
                    n <= modelValue ? 'text-amber-500' : 'text-neutral-300',
                ]"
                aria-hidden="true"
            />
        </button>
    </div>

    <span
        v-else
        role="img"
        :aria-label="displayLabel"
        class="inline-flex items-center gap-0.5"
    >
        <span
            v-for="n in STARS"
            :key="n"
            class="relative inline-block"
            :class="sizeClass"
            aria-hidden="true"
        >
            <IconStarFilled
                :class="[sizeClass, 'absolute inset-0 text-neutral-300']"
            />
            <span
                class="absolute inset-0 overflow-hidden"
                :style="{ width: `${fill(n)}%` }"
            >
                <!-- max-w-none: isf-theme membatasi svg max-width:100%; di sini bintang harus terpotong, bukan mengecil -->
                <IconStarFilled
                    :class="[sizeClass, 'max-w-none text-amber-500']"
                />
            </span>
        </span>
    </span>
</template>
