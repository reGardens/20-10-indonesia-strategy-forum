import { afterEach, describe, expect, it } from 'vitest'
import { createApp, h, nextTick } from 'vue'
import RepeatableRows from '../RepeatableRows.vue'

let app
let root

function mountComponent(props, onUpdate) {
  root = document.createElement('div')
  document.body.appendChild(root)
  app = createApp({
    render() {
      return h(RepeatableRows, {
        ...props,
        'onUpdate:modelValue': onUpdate
      })
    }
  })
  app.mount(root)
  return root
}

afterEach(() => {
  app?.unmount()
  app = undefined
  root?.remove()
  root = undefined
  document.body.innerHTML = ''
})

describe('RepeatableRows.vue', () => {
  const fields = [
    { key: 'label', label: 'Label', type: 'text' },
    { key: 'value', label: 'Value', type: 'number' }
  ]

  it('renders existing rows properly', async () => {
    const el = mountComponent({
      modelValue: [
        { label: 'Alpha', value: 10 },
        { label: 'Beta', value: 20 }
      ],
      fields
    })
    await nextTick()

    const inputs = el.querySelectorAll('input[type="text"]')
    expect(inputs.length).toBe(2)
    expect(inputs[0].value).toBe('Alpha')
    expect(inputs[1].value).toBe('Beta')
  })

  it('emits updated array when adding a row', async () => {
    let emitted = null
    const el = mountComponent(
      {
        modelValue: [{ label: 'Alpha', value: 10 }],
        fields
      },
      (val) => {
        emitted = val
      }
    )
    await nextTick()

    const addBtn = el.querySelector('button[type="button"]')
    addBtn.click()
    await nextTick()

    expect(emitted).toBeTruthy()
    expect(emitted.length).toBe(2)
    expect(emitted[1]).toEqual({ label: '', value: 0 })
  })

  it('emits updated array when removing a row', async () => {
    let emitted = null
    const el = mountComponent(
      {
        modelValue: [
          { label: 'Alpha', value: 10 },
          { label: 'Beta', value: 20 }
        ],
        fields
      },
      (val) => {
        emitted = val
      }
    )
    await nextTick()

    const removeButtons = el.querySelectorAll('button[title="Hapus baris"]')
    expect(removeButtons.length).toBe(2)
    removeButtons[0].click()
    await nextTick()

    expect(emitted).toBeTruthy()
    expect(emitted.length).toBe(1)
    expect(emitted[0].label).toBe('Beta')
  })
})
