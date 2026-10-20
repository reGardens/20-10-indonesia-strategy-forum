<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    class: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:currentPage"]);

const pages = computed(() => {
    const items = [];
    const total = props.totalPages;
    const current = props.currentPage;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            items.push(i);
        }
    } else {
        items.push(1);
        if (current > 3) {
            items.push("...");
        }
        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);
        for (let i = start; i <= end; i++) {
            items.push(i);
        }
        if (current < total - 2) {
            items.push("...");
        }
        items.push(total);
    }
    return items;
});

function goToPage(page) {
    if (page >= 1 && page <= props.totalPages) {
        emit("update:currentPage", page);
    }
}
</script>

<template>
    <nav
        role="navigation"
        aria-label="pagination"
        :class="cn('mx-auto flex w-full justify-center', props.class)"
    >
        <ul class="flex flex-row items-center gap-1">
            <li>
                <button
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 gap-1 pl-2.5"
                    :disabled="currentPage <= 1"
                    @click="goToPage(currentPage - 1)"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-4 w-4"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    <span>Previous</span>
                </button>
            </li>
            <li v-for="(page, index) in pages" :key="index">
                <span
                    v-if="page === '...'"
                    class="flex h-9 w-9 items-center justify-center"
                >
                    &#8230;
                </span>
                <button
                    v-else
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 w-10"
                    :class="
                        page === currentPage
                            ? 'border border-input bg-background hover:bg-accent hover:text-accent-foreground'
                            : 'hover:bg-accent hover:text-accent-foreground'
                    "
                    :aria-current="page === currentPage ? 'page' : undefined"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>
            </li>
            <li>
                <button
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 gap-1 pr-2.5"
                    :disabled="currentPage >= totalPages"
                    @click="goToPage(currentPage + 1)"
                >
                    <span>Next</span>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-4 w-4"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </li>
        </ul>
    </nav>
</template>
