<script setup>
import { computed } from 'vue';
import * as TablerIcons from '@tabler/icons-vue';

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    size: {
        type: [Number, String],
        default: 24
    },
    color: {
        type: String,
        default: 'currentColor'
    },
    stroke: {
        type: [Number, String],
        default: 2
    }
});

const iconComponent = computed(() => {
    // Automatically prepend 'Icon' if not provided
    const iconName = props.name.startsWith('Icon') ? props.name : `Icon${props.name}`;
    
    // Return the matched icon or a fallback help icon if not found
    return TablerIcons[iconName] || TablerIcons['IconHelp'];
});
</script>

<template>
    <component 
        :is="iconComponent" 
        :size="size" 
        :color="color" 
        :stroke-width="stroke"
    />
</template>
