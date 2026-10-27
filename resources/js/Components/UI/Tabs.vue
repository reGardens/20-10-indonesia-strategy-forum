<script setup>
/**
 * Tabs — reusable tab container.
 *
 * Usage:
 *   <Tabs v-model="activeTab" :tabs="[{ key:'url', label:'URL' }, { key:'upload', label:'Upload' }]">
 *     <TabPanel key="url" :activeTab="activeTab"> ... </TabPanel>
 *   </Tabs>
 *
 * Or use the simplified slot approach with just the tab bar:
 *   <Tabs v-model="tab" :tabs="[...]" />
 *   <div v-if="tab === 'url'"> ... </div>
 */
const props = defineProps({
  /** Currently active tab key */
  modelValue: { type: String, required: true },
  /**
   * Array of tab definitions: [{ key: string, label: string, icon?: string }]
   */
  tabs: { type: Array, required: true },
  /** Visual variant: 'pills' (default) | 'underline' */
  variant: { type: String, default: 'pills' }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <!-- Pills variant -->
  <div v-if="variant === 'pills'" class="flex items-center border border-border rounded-lg overflow-hidden w-fit">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      type="button"
      @click="emit('update:modelValue', tab.key)"
      :class="[
        'flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium transition-colors select-none',
        modelValue === tab.key
          ? 'bg-red-600 text-white'
          : 'bg-card text-muted-foreground hover:bg-accent'
      ]"
    >
      <slot :name="`icon-${tab.key}`" />
      {{ tab.label }}
    </button>
  </div>

  <!-- Underline variant -->
  <div v-else class="flex items-center border-b border-border gap-0">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      type="button"
      @click="emit('update:modelValue', tab.key)"
      :class="[
        'flex items-center gap-1.5 px-4 py-2 text-xs font-medium transition-colors select-none border-b-2 -mb-px',
        modelValue === tab.key
          ? 'border-red-600 text-red-600 dark:text-red-400'
          : 'border-transparent text-muted-foreground hover:text-foreground'
      ]"
    >
      <slot :name="`icon-${tab.key}`" />
      {{ tab.label }}
    </button>
  </div>

  <!-- Default slot: tab content panels rendered by parent -->
  <slot />
</template>
