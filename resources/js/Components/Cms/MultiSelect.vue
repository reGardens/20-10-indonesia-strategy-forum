<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import IconComponent from "@/Components/IconComponent/IconComponent.vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: "Pilih...",
    },
    labelKey: {
        type: String,
        default: "name",
    },
    valueKey: {
        type: String,
        default: "id",
    },
});

const emit = defineEmits(["update:modelValue"]);

const toId = (value) => Number(value);

const normalizedValue = computed(() =>
    props.modelValue.map(toId).filter((id) => !Number.isNaN(id)),
);

const isOpen = ref(false);
const searchQuery = ref("");
const containerRef = ref(null);

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    return props.options.filter((opt) =>
        opt[props.labelKey]
            .toLowerCase()
            .includes(searchQuery.value.toLowerCase())
    );
});

const selectedItems = computed(() => {
    return props.options.filter((opt) =>
        normalizedValue.value.includes(toId(opt[props.valueKey])),
    );
});

const isSelected = (id) => normalizedValue.value.includes(toId(id));

const toggleOption = (id) => {
    const normalizedId = toId(id);
    const newValue = [...normalizedValue.value];
    const index = newValue.indexOf(normalizedId);
    if (index === -1) {
        newValue.push(normalizedId);
    } else {
        newValue.splice(index, 1);
    }
    emit("update:modelValue", newValue);
};

const removeTag = (id) => {
    const normalizedId = toId(id);
    const newValue = normalizedValue.value.filter((val) => val !== normalizedId);
    emit("update:modelValue", newValue);
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("mousedown", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("mousedown", handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <div
            @click="isOpen = !isOpen"
            class="min-h-[42px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-within:ring-1 focus-within:ring-emerald-500 cursor-pointer flex flex-wrap gap-2 items-center"
        >
            <div v-if="selectedItems.length === 0" class="text-muted-foreground">
                {{ placeholder }}
            </div>
            
            <div
                v-for="item in selectedItems"
                :key="item[valueKey]"
                class="flex items-center gap-1 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-xs font-medium border border-emerald-200"
            >
                {{ item[labelKey] }}
                <button
                    type="button"
                    @click.stop="removeTag(item[valueKey])"
                    class="hover:text-emerald-600"
                >
                    <IconComponent name="X" class="w-3 h-3" />
                </button>
            </div>

            <div class="ml-auto">
                <IconComponent
                    name="ChevronDown"
                    :class="['w-4 h-4 transition-transform', isOpen ? 'rotate-180' : '']"
                />
            </div>
        </div>

        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full rounded-md border bg-popover p-1 text-popover-foreground shadow-md animate-in fade-in zoom-in-95"
        >
            <div class="p-2">
                <input
                    v-model="searchQuery"
                    type="text"
                    class="w-full bg-muted/50 rounded border-none text-xs p-2 focus:ring-1 focus:ring-emerald-500"
                    placeholder="Cari..."
                    @click.stop
                />
            </div>
            
            <div class="max-h-60 overflow-y-auto">
                <div
                    v-for="opt in filteredOptions"
                    :key="opt[valueKey]"
                    @click="toggleOption(opt[valueKey])"
                    class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 cursor-pointer"
                >
                    <span
                        v-if="isSelected(opt[valueKey])"
                        class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center text-emerald-600"
                    >
                        <IconComponent name="Check" class="h-4 w-4" />
                    </span>
                    {{ opt[labelKey] }}
                </div>
                
                <div
                    v-if="filteredOptions.length === 0"
                    class="p-4 text-center text-xs text-muted-foreground"
                >
                    Tidak ada data ditemukan.
                </div>
            </div>
        </div>
    </div>
</template>
