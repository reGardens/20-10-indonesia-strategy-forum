<script setup>
/**
 * Accordion Component
 * Reusable FAQ/accordion with smooth open/close animation.
 * Clickable on entire header row (not just arrow).
 */
import { ref, watch } from "vue";

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
        // Each item: { question: string, answer: string }
    },
});

const localItems = ref(props.items.map((item) => ({ ...item, isOpen: false })));

watch(
    () => props.items,
    (newItems) => {
        localItems.value = newItems.map((item) => ({ ...item, isOpen: false }));
    },
);

function toggle(index) {
    localItems.value[index].isOpen = !localItems.value[index].isOpen;
}
</script>

<template>
    <div>
        <div
            v-for="(item, index) in localItems"
            :key="index"
            class="border-b border-blue-200"
        >
            <div
                @click="toggle(index)"
                class="flex justify-between items-center w-full text-left font-semibold text-gray-800 cursor-pointer py-6 select-none"
            >
                <span>{{ item.question }}</span>
                <svg
                    :class="{ 'rotate-180': item.isOpen }"
                    class="w-7 h-7 flex-shrink-0 ml-4 transition-transform duration-300 ease-in-out text-black"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M19 9l-7 7-7-7"
                    ></path>
                </svg>
            </div>
            <div
                class="overflow-hidden transition-all duration-300 ease-in-out"
                :style="{
                    maxHeight: item.isOpen ? '500px' : '0px',
                    opacity: item.isOpen ? 1 : 0,
                    paddingBottom: item.isOpen ? '24px' : '0px',
                }"
            >
                <p class="text-gray-600">
                    {{ item.answer }}
                </p>
            </div>
        </div>
    </div>
</template>
