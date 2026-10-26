<script setup>
import { ref } from 'vue'
import { Button, Input, Label } from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: 'Daftar Nilai' },
  placeholder: { type: String, default: 'Ketik nilai lalu tekan Tambah...' },
  addLabel: { type: String, default: 'Tambah' },
  description: { type: String, default: '' },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 30 }
})

const emit = defineEmits(['update:modelValue'])

const newItem = ref('')

function addItem() {
  const val = newItem.value.trim()
  if (!val) return
  if (props.modelValue.length >= props.max) return
  emit('update:modelValue', [...props.modelValue, val])
  newItem.value = ''
}

function removeItem(index) {
  if (props.modelValue.length <= props.min) return
  const next = [...props.modelValue]
  next.splice(index, 1)
  emit('update:modelValue', next)
}

function updateItem(index, value) {
  const next = [...props.modelValue]
  next[index] = value
  emit('update:modelValue', next)
}
</script>

<template>
  <div class="space-y-2 border border-border/60 bg-card/40 rounded-xl p-4">
    <div class="flex items-center justify-between">
      <Label class="text-xs font-semibold text-foreground">
        {{ label }}
      </Label>
      <span class="text-[11px] text-muted-foreground">{{ modelValue.length }} item</span>
    </div>
    <p v-if="description" class="text-xs text-muted-foreground -mt-1">
      {{ description }}
    </p>

    <!-- Add Input -->
    <div class="flex items-center gap-2">
      <Input
        v-model="newItem"
        :placeholder="placeholder"
        @keydown.enter.prevent="addItem"
        class="h-8 text-xs"
        :disabled="modelValue.length >= max"
      />
      <Button
        type="button"
        size="sm"
        @click="addItem"
        :disabled="!newItem.trim() || modelValue.length >= max"
        class="h-8 bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3"
      >
        <IconComponent name="Plus" :size="14" class="mr-1" />
        {{ addLabel }}
      </Button>
    </div>

    <!-- Items pills / list -->
    <div v-if="modelValue.length > 0" class="flex flex-wrap gap-1.5 pt-1">
      <span
        v-for="(item, idx) in modelValue"
        :key="idx"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs bg-card border border-border shadow-xs text-foreground"
      >
        <span>{{ item }}</span>
        <button
          type="button"
          @click="removeItem(idx)"
          class="text-muted-foreground hover:text-red-500 rounded p-0.5"
          title="Hapus"
        >
          <IconComponent name="X" :size="12" />
        </button>
      </span>
    </div>
    <div v-else class="text-xs text-muted-foreground py-2 text-center border border-dashed border-border/60 rounded-md">
      Belum ada item ditambahkan.
    </div>
  </div>
</template>
