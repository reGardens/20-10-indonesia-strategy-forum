<script setup>
import { computed } from 'vue'
import { Button, Input, Label } from '@/Components/UI'
import IconComponent from '@/Components/IconComponent/IconComponent.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  fields: { type: Array, required: true }, // [{ key, label, type, placeholder, required }]
  addLabel: { type: String, default: 'Tambah Baris' },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 50 },
  legend: { type: String, default: '' },
  description: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const rows = computed({
  get: () => (Array.isArray(props.modelValue) ? props.modelValue : []),
  set: (val) => emit('update:modelValue', val)
})

function defaultRow() {
  const row = {}
  for (const f of props.fields) {
    if (f.type === 'checkbox') row[f.key] = false
    else if (f.type === 'number') row[f.key] = 0
    else if (f.type === 'array') row[f.key] = []
    else row[f.key] = ''
  }
  return row
}

function addRow() {
  if (rows.value.length >= props.max) return
  emit('update:modelValue', [...rows.value, defaultRow()])
}

function removeRow(index) {
  if (rows.value.length <= props.min) return
  const next = [...rows.value]
  next.splice(index, 1)
  emit('update:modelValue', next)
}

function moveUp(index) {
  if (index <= 0) return
  const next = [...rows.value]
  const [removed] = next.splice(index, 1)
  next.splice(index - 1, 0, removed)
  emit('update:modelValue', next)
}

function moveDown(index) {
  if (index >= rows.value.length - 1) return
  const next = [...rows.value]
  const [removed] = next.splice(index, 1)
  next.splice(index + 1, 0, removed)
  emit('update:modelValue', next)
}

function updateField(rowIndex, key, value) {
  const next = rows.value.map((row, idx) => {
    if (idx !== rowIndex) return row
    return { ...row, [key]: value }
  })
  emit('update:modelValue', next)
}
</script>

<template>
  <fieldset class="border border-border/60 bg-card/40 rounded-xl p-4 sm:p-5 space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <legend v-if="legend" class="text-sm font-semibold text-foreground px-1">
          {{ legend }}
        </legend>
        <p v-if="description" class="text-xs text-muted-foreground mt-0.5 px-1">
          {{ description }}
        </p>
        <p v-if="error" class="text-xs text-red-500 font-medium mt-1 px-1">
          {{ error }}
        </p>
      </div>
      <Button
        type="button"
        size="sm"
        variant="outline"
        @click="addRow"
        :disabled="rows.length >= max"
        class="border-dashed border-emerald-500/50 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10 text-xs font-medium"
      >
        <IconComponent name="Plus" :size="14" class="mr-1" />
        {{ addLabel }}
      </Button>
    </div>

    <!-- Empty state -->
    <div
      v-if="rows.length === 0"
      class="text-center py-6 border border-dashed border-border rounded-lg text-xs text-muted-foreground"
    >
      Belum ada data. Klik tombol "{{ addLabel }}" di atas untuk menambahkan.
    </div>

    <!-- Rows List -->
    <div v-else class="space-y-3">
      <div
        v-for="(row, idx) in rows"
        :key="idx"
        class="relative border border-border/70 bg-card rounded-lg p-3.5 sm:p-4 space-y-3 shadow-sm transition hover:border-border"
      >
        <!-- Header row actions -->
        <div class="flex items-center justify-between pb-2 border-b border-border/40 text-xs text-muted-foreground">
          <span class="font-semibold text-foreground/80">#{{ idx + 1 }}</span>
          <div class="flex items-center gap-1">
            <button
              type="button"
              :disabled="idx === 0"
              @click="moveUp(idx)"
              class="p-1 rounded hover:bg-accent text-muted-foreground hover:text-foreground disabled:opacity-30"
              title="Pindah ke atas"
            >
              <IconComponent name="ChevronUp" :size="14" />
            </button>
            <button
              type="button"
              :disabled="idx === rows.length - 1"
              @click="moveDown(idx)"
              class="p-1 rounded hover:bg-accent text-muted-foreground hover:text-foreground disabled:opacity-30"
              title="Pindah ke bawah"
            >
              <IconComponent name="ChevronDown" :size="14" />
            </button>
            <button
              type="button"
              :disabled="rows.length <= min"
              @click="removeRow(idx)"
              class="p-1 rounded hover:bg-red-500/10 text-red-500 hover:text-red-600 disabled:opacity-30 ml-1"
              title="Hapus baris"
            >
              <IconComponent name="Trash" :size="14" />
            </button>
          </div>
        </div>

        <!-- Field inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="field in fields"
            :key="field.key"
            :class="[
              field.span ? field.span : (field.type === 'textarea' ? 'sm:col-span-2 lg:col-span-3' : '')
            ]"
            class="space-y-1"
          >
            <Label :for="`row-${idx}-${field.key}`" class="text-xs font-medium text-foreground/90">
              {{ field.label }}
              <span v-if="field.required" class="text-red-500">*</span>
            </Label>

            <!-- Textarea -->
            <textarea
              v-if="field.type === 'textarea'"
              :id="`row-${idx}-${field.key}`"
              :value="row[field.key]"
              @input="updateField(idx, field.key, $event.target.value)"
              :placeholder="field.placeholder || ''"
              rows="2"
              class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-xs text-foreground shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />

            <!-- Checkbox -->
            <label
              v-else-if="field.type === 'checkbox'"
              class="flex items-center gap-2 pt-2 cursor-pointer text-xs"
            >
              <input
                type="checkbox"
                :id="`row-${idx}-${field.key}`"
                :checked="Boolean(row[field.key])"
                @change="updateField(idx, field.key, $event.target.checked)"
                class="rounded border-input text-emerald-600 focus:ring-emerald-500 h-4 w-4"
              />
              <span class="text-xs text-foreground">{{ field.checkboxLabel || field.label }}</span>
            </label>

            <!-- Number / Date / Text / URL -->
            <Input
              v-else
              :id="`row-${idx}-${field.key}`"
              :type="field.type || 'text'"
              :value="row[field.key]"
              @input="updateField(idx, field.key, field.type === 'number' ? Number($event.target.value) : $event.target.value)"
              :placeholder="field.placeholder || ''"
              class="h-8 text-xs"
            />
          </div>
        </div>
      </div>
    </div>
  </fieldset>
</template>
