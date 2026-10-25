<script setup>
/**
 * SimpleBarChart — "Grafik interaktif" from a static data array, drawn as
 * Politico-style poll bars (label above, value printed inside the filled bar,
 * light-gray track). The numbers stay available to assistive tech through an
 * sr-only table (the bars themselves are aria-hidden).
 * Interaction: hovering a bar darkens it.
 */
import { computed, ref } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    // Each item: { label: string, value: number }
    data: {
        type: Array,
        required: true,
    },
    unit: {
        type: String,
        default: "",
    },
    sourceTitle: {
        type: String,
        default: "",
    },
    sourceUrl: {
        type: String,
        default: "",
    },
    compact: {
        type: Boolean,
        default: false,
    },
    class: {
        type: String,
        default: "",
    },
});

const activeIndex = ref(null);

const maxValue = computed(() =>
    props.data.reduce((max, item) => Math.max(max, Number(item.value) || 0), 0),
);

const bars = computed(() =>
    props.data.map((item) => {
        const value = Number(item.value) || 0;
        return {
            label: item.label,
            value,
            display: `${value}${props.unit}`,
            pct: maxValue.value > 0 ? (value / maxValue.value) * 100 : 0,
        };
    }),
);

function fillClass(index) {
    if (activeIndex.value === index) return "bg-isf-red-dark";
    return index === 0 ? "bg-isf-red" : "bg-neutral-700";
}

const classes = computed(() =>
    cn(
        props.compact
            ? "isf-chart-compact rounded-[4px] border border-neutral-300 bg-neutral-100 p-4"
            : "border-y border-neutral-300 py-5",
        props.class,
    ),
);
</script>

<template>
    <figure :class="classes">
        <figcaption>
            <p
                :class="[
                    'isf-headline text-neutral-950',
                    compact
                        ? 'text-[17px] leading-[1.2]'
                        : 'text-[20px] leading-[1.2]',
                ]"
            >
                {{ title }}
            </p>
            <p
                v-if="description"
                class="mt-2 font-body text-[13px] leading-snug text-neutral-700"
            >
                {{ description }}
            </p>
        </figcaption>

        <ul class="mt-4 space-y-3" aria-hidden="true">
            <li
                v-for="(bar, index) in bars"
                :key="bar.label"
                class="cursor-default"
                @mouseenter="activeIndex = index"
                @mouseleave="activeIndex = null"
            >
                <p
                    class="font-ui text-[12px] font-semibold leading-tight text-neutral-800"
                >
                    {{ bar.label }}
                </p>
                <div class="mt-1 h-6 w-full bg-neutral-200">
                    <div
                        :class="[
                            'flex h-full min-w-[2.25rem] items-center justify-end px-2 font-ui text-[12px] font-bold text-white transition-colors',
                            fillClass(index),
                        ]"
                        :style="{ width: `${bar.pct}%` }"
                    >
                        {{ bar.display }}
                    </div>
                </div>
            </li>
        </ul>

        <!-- A <table> cannot shrink to sr-only's 1px width, so clip it in a wrapper. -->
        <div class="sr-only">
            <table>
                <caption>
                    {{
                        title
                    }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Kategori</th>
                        <th scope="col">
                            Nilai{{ unit ? ` (${unit.trim()})` : "" }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="bar in bars" :key="bar.label">
                        <td>{{ bar.label }}</td>
                        <td>{{ bar.display }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p
            v-if="sourceTitle"
            class="mt-3 font-body text-[11px] leading-snug text-neutral-600"
        >
            Sumber data:
            <cite class="not-italic">
                <a
                    v-if="sourceUrl"
                    :href="sourceUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-semibold text-isf-red-dark underline underline-offset-2 hover:text-isf-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-isf-red"
                >
                    {{ sourceTitle }}
                    <span class="sr-only">(membuka tab baru)</span>
                </a>
                <span v-else>{{ sourceTitle }}</span>
            </cite>
        </p>
    </figure>
</template>
