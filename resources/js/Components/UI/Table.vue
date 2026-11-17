<script setup>
import { computed, ref, watch } from "vue";
import { cn } from "@/lib/utils";
import TablePagination from "./TablePagination.vue";

const props = defineProps({
    class: {
        type: String,
        default: "",
    },
    pagination: {
        type: Boolean,
        default: true,
    },
    currentPage: {
        type: Number,
        default: 1,
    },
    totalPages: {
        type: Number,
        default: null,
    },
    perPage: {
        type: Number,
        default: 10,
    },
    total: {
        type: Number,
        default: null,
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 75, 100],
    },
    items: {
        type: Array,
        default: null,
    },
});

const emit = defineEmits([
    "update:currentPage",
    "update:perPage",
    "page-change",
    "per-page-change",
]);

const activePage = ref(props.currentPage || 1);
const activePerPage = ref(props.perPage || 10);

watch(
    () => props.currentPage,
    (val) => {
        if (val !== undefined && val !== null && val !== activePage.value) {
            activePage.value = val;
        }
    }
);

watch(
    () => props.perPage,
    (val) => {
        if (val !== undefined && val !== null && val !== activePerPage.value) {
            activePerPage.value = val;
        }
    }
);

const computedTotal = computed(() => {
    if (typeof props.total === 'number') return props.total;
    if (Array.isArray(props.items)) return props.items.length;
    return 0;
});

const computedTotalPages = computed(() => {
    if (typeof props.totalPages === 'number' && props.totalPages > 0) {
        return props.totalPages;
    }
    if (computedTotal.value > 0) {
        return Math.max(1, Math.ceil(computedTotal.value / activePerPage.value));
    }
    return 1;
});

const isPaginationActive = computed(() => {
    if (!props.pagination) return false;
    return typeof props.total === 'number' ||
           typeof props.totalPages === 'number' ||
           Array.isArray(props.items);
});

const paginatedItems = computed(() => {
    if (!Array.isArray(props.items)) return [];
    const start = (activePage.value - 1) * activePerPage.value;
    return props.items.slice(start, start + activePerPage.value);
});

function handlePageChange(page) {
    activePage.value = page;
    emit("update:currentPage", page);
    emit("page-change", page);
}

function handlePerPageChange(size) {
    activePerPage.value = size;
    activePage.value = 1;
    emit("update:perPage", size);
    emit("update:currentPage", 1);
    emit("per-page-change", size);
    emit("page-change", 1);
}

const classes = computed(() =>
    cn("w-full caption-bottom text-sm", props.class),
);
</script>

<template>
    <div class="relative w-full">
        <div class="overflow-x-auto w-full">
            <table :class="classes">
                <slot
                    :paginated-items="paginatedItems"
                    :current-page="activePage"
                    :per-page="activePerPage"
                    :total="computedTotal"
                />
            </table>
        </div>

        <TablePagination
            v-if="isPaginationActive"
            :current-page="activePage"
            :total-pages="computedTotalPages"
            :per-page="activePerPage"
            :total="computedTotal"
            :per-page-options="perPageOptions"
            @update:current-page="handlePageChange"
            @update:per-page="handlePerPageChange"
        />
    </div>
</template>
