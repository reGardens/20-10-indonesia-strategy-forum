import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import LoadingIndicator from '../../../src/cms/components/shared/LoadingIndicator.vue'
import ErrorMessage from '../../../src/cms/components/shared/ErrorMessage.vue'
import ConfirmDialog from '../../../src/cms/components/shared/ConfirmDialog.vue'
import ColorPicker from '../../../src/cms/components/shared/ColorPicker.vue'
import AudioPlayer from '../../../src/cms/components/shared/AudioPlayer.vue'
import FileUploader from '../../../src/cms/components/shared/FileUploader.vue'

// ─── LoadingIndicator ────────────────────────────────────────────────────────

describe('LoadingIndicator', () => {
  it('renders with default medium size', () => {
    const wrapper = mount(LoadingIndicator)
    const svg = wrapper.find('svg')
    expect(svg.classes()).toContain('h-8')
    expect(svg.classes()).toContain('w-8')
  })

  it('renders small spinner when size is sm', () => {
    const wrapper = mount(LoadingIndicator, { props: { size: 'sm' } })
    const svg = wrapper.find('svg')
    expect(svg.classes()).toContain('h-5')
    expect(svg.classes()).toContain('w-5')
  })

  it('renders large spinner when size is lg', () => {
    const wrapper = mount(LoadingIndicator, { props: { size: 'lg' } })
    const svg = wrapper.find('svg')
    expect(svg.classes()).toContain('h-12')
    expect(svg.classes()).toContain('w-12')
  })

  it('shows loading text when provided', () => {
    const wrapper = mount(LoadingIndicator, { props: { text: 'Memuat data...' } })
    expect(wrapper.text()).toContain('Memuat data...')
  })

  it('shows sr-only fallback text when no text prop', () => {
    const wrapper = mount(LoadingIndicator)
    const srOnly = wrapper.find('.sr-only')
    expect(srOnly.exists()).toBe(true)
    expect(srOnly.text()).toBe('Memuat...')
  })

  it('has role="status" for accessibility', () => {
    const wrapper = mount(LoadingIndicator)
    expect(wrapper.find('[role="status"]').exists()).toBe(true)
  })
})

// ─── ErrorMessage ────────────────────────────────────────────────────────────

describe('ErrorMessage', () => {
  it('displays the error message', () => {
    const wrapper = mount(ErrorMessage, { props: { message: 'Gagal menyimpan data' } })
    expect(wrapper.text()).toContain('Gagal menyimpan data')
  })

  it('does not show retry button when retryable is false', () => {
    const wrapper = mount(ErrorMessage, { props: { message: 'Error', retryable: false } })
    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('shows "Coba Lagi" button when retryable is true', () => {
    const wrapper = mount(ErrorMessage, { props: { message: 'Error', retryable: true } })
    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    expect(button.text()).toBe('Coba Lagi')
  })

  it('emits retry event when button is clicked', async () => {
    const wrapper = mount(ErrorMessage, { props: { message: 'Error', retryable: true } })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('retry')).toHaveLength(1)
  })

  it('has role="alert" for accessibility', () => {
    const wrapper = mount(ErrorMessage, { props: { message: 'Error' } })
    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
  })
})

// ─── ConfirmDialog ───────────────────────────────────────────────────────────

describe('ConfirmDialog', () => {
  const baseProps = {
    visible: true,
    title: 'Hapus Tamu',
    message: 'Apakah Anda yakin ingin menghapus tamu ini?'
  }

  // ConfirmDialog uses <Teleport to="body">, so content renders in document.body
  // We need to query document.body for the teleported content
  function findInBody(selector) {
    return document.body.querySelector(selector)
  }

  afterEach(() => {
    // Clean up any teleported content
    const dialogs = document.body.querySelectorAll('[role="dialog"]')
    dialogs.forEach(d => d.parentElement?.remove?.())
  })

  it('renders dialog when visible is true', () => {
    const wrapper = mount(ConfirmDialog, {
      props: baseProps,
      attachTo: document.body
    })
    expect(findInBody('[role="dialog"]')).not.toBeNull()
    wrapper.unmount()
  })

  it('does not render dialog when visible is false', () => {
    const wrapper = mount(ConfirmDialog, {
      props: { ...baseProps, visible: false },
      attachTo: document.body
    })
    expect(findInBody('[role="dialog"]')).toBeNull()
    wrapper.unmount()
  })

  it('displays title and message', () => {
    const wrapper = mount(ConfirmDialog, {
      props: baseProps,
      attachTo: document.body
    })
    const dialog = findInBody('[role="dialog"]')
    expect(dialog.textContent).toContain('Hapus Tamu')
    expect(dialog.textContent).toContain('Apakah Anda yakin ingin menghapus tamu ini?')
    wrapper.unmount()
  })

  it('uses default button texts "Hapus" and "Batal"', () => {
    const wrapper = mount(ConfirmDialog, {
      props: baseProps,
      attachTo: document.body
    })
    const dialog = findInBody('[role="dialog"]')
    const buttons = Array.from(dialog.querySelectorAll('button'))
    const texts = buttons.map(b => b.textContent.trim())
    expect(texts).toContain('Hapus')
    expect(texts).toContain('Batal')
    wrapper.unmount()
  })

  it('uses custom button texts when provided', () => {
    const wrapper = mount(ConfirmDialog, {
      props: { ...baseProps, confirmText: 'Ya, Hapus', cancelText: 'Tidak' },
      attachTo: document.body
    })
    const dialog = findInBody('[role="dialog"]')
    const buttons = Array.from(dialog.querySelectorAll('button'))
    const texts = buttons.map(b => b.textContent.trim())
    expect(texts).toContain('Ya, Hapus')
    expect(texts).toContain('Tidak')
    wrapper.unmount()
  })

  it('emits confirm when confirm button is clicked', async () => {
    const wrapper = mount(ConfirmDialog, {
      props: baseProps,
      attachTo: document.body
    })
    const dialog = findInBody('[role="dialog"]')
    const confirmBtn = Array.from(dialog.querySelectorAll('button')).find(b => b.textContent.trim() === 'Hapus')
    confirmBtn.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('confirm')).toHaveLength(1)
    wrapper.unmount()
  })

  it('emits cancel when cancel button is clicked', async () => {
    const wrapper = mount(ConfirmDialog, {
      props: baseProps,
      attachTo: document.body
    })
    const dialog = findInBody('[role="dialog"]')
    const cancelBtn = Array.from(dialog.querySelectorAll('button')).find(b => b.textContent.trim() === 'Batal')
    cancelBtn.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.emitted('cancel')).toHaveLength(1)
    wrapper.unmount()
  })

  it('has aria-modal attribute', () => {
    const wrapper = mount(ConfirmDialog, {
      props: baseProps,
      attachTo: document.body
    })
    expect(findInBody('[aria-modal="true"]')).not.toBeNull()
    wrapper.unmount()
  })
})

// ─── ColorPicker ─────────────────────────────────────────────────────────────

describe('ColorPicker', () => {
  it('renders label text', () => {
    const wrapper = mount(ColorPicker, {
      props: { modelValue: '#8B4513', label: 'Warna Utama' }
    })
    expect(wrapper.text()).toContain('Warna Utama')
  })

  it('displays the hex value', () => {
    const wrapper = mount(ColorPicker, {
      props: { modelValue: '#8B4513', label: 'Warna' }
    })
    expect(wrapper.text()).toContain('#8B4513')
  })

  it('renders a color input with the correct value', () => {
    const wrapper = mount(ColorPicker, {
      props: { modelValue: '#FF0000', label: 'Warna' }
    })
    const input = wrapper.find('input[type="color"]')
    expect(input.exists()).toBe(true)
    expect(input.element.value.toLowerCase()).toBe('#ff0000')
  })

  it('emits update:modelValue on input change', async () => {
    const wrapper = mount(ColorPicker, {
      props: { modelValue: '#000000', label: 'Warna' }
    })
    const input = wrapper.find('input[type="color"]')
    await input.setValue('#FF5733')
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
  })

  it('associates label with input via for/id', () => {
    const wrapper = mount(ColorPicker, {
      props: { modelValue: '#000000', label: 'Warna' }
    })
    const label = wrapper.find('label')
    const input = wrapper.find('input[type="color"]')
    expect(label.attributes('for')).toBe(input.attributes('id'))
  })
})

// ─── AudioPlayer ─────────────────────────────────────────────────────────────

describe('AudioPlayer', () => {
  it('renders play button', () => {
    const wrapper = mount(AudioPlayer, { props: { src: 'test.mp3' } })
    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    expect(button.attributes('aria-label')).toBe('Putar musik')
  })

  it('renders audio element with correct src', () => {
    const wrapper = mount(AudioPlayer, { props: { src: 'music/song.mp3' } })
    const audio = wrapper.find('audio')
    expect(audio.exists()).toBe(true)
    expect(audio.attributes('src')).toBe('music/song.mp3')
  })

  it('displays file name from src', () => {
    const wrapper = mount(AudioPlayer, { props: { src: 'media/background-music.mp3' } })
    expect(wrapper.text()).toContain('background-music.mp3')
  })

  it('renders range input for seeking', () => {
    const wrapper = mount(AudioPlayer, { props: { src: 'test.mp3' } })
    const range = wrapper.find('input[type="range"]')
    expect(range.exists()).toBe(true)
    expect(range.attributes('aria-label')).toBe('Posisi audio')
  })

  it('displays time as 0:00 / 0:00 initially', () => {
    const wrapper = mount(AudioPlayer, { props: { src: 'test.mp3' } })
    expect(wrapper.text()).toContain('0:00 / 0:00')
  })
})

// ─── FileUploader ────────────────────────────────────────────────────────────

describe('FileUploader', () => {
  it('renders upload area with instructions', () => {
    const wrapper = mount(FileUploader)
    expect(wrapper.text()).toContain('Seret file ke sini')
    expect(wrapper.text()).toContain('pilih file')
  })

  it('displays accepted formats when accept prop is set', () => {
    const wrapper = mount(FileUploader, { props: { accept: 'image/*,.mp3' } })
    expect(wrapper.text()).toContain('image/*,.mp3')
  })

  it('displays max size when maxSizeMB prop is set', () => {
    const wrapper = mount(FileUploader, { props: { maxSizeMB: 10 } })
    expect(wrapper.text()).toContain('10MB')
  })

  it('renders a hidden file input', () => {
    const wrapper = mount(FileUploader, { props: { accept: 'image/*' } })
    const input = wrapper.find('input[type="file"]')
    expect(input.exists()).toBe(true)
    expect(input.attributes('accept')).toBe('image/*')
    expect(input.classes()).toContain('sr-only')
  })

  it('sets multiple attribute when multiple prop is true', () => {
    const wrapper = mount(FileUploader, { props: { multiple: true } })
    const input = wrapper.find('input[type="file"]')
    expect(input.attributes('multiple')).toBeDefined()
  })

  it('emits files-selected when files are selected via input', async () => {
    const wrapper = mount(FileUploader, { props: { maxSizeMB: 10 } })
    const input = wrapper.find('input[type="file"]')

    const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' })
    Object.defineProperty(input.element, 'files', { value: [file] })
    await input.trigger('change')

    expect(wrapper.emitted('files-selected')).toHaveLength(1)
    expect(wrapper.emitted('files-selected')[0][0]).toHaveLength(1)
    expect(wrapper.emitted('files-selected')[0][0][0].name).toBe('photo.jpg')
  })

  it('shows error when file exceeds maxSizeMB', async () => {
    const wrapper = mount(FileUploader, { props: { maxSizeMB: 1 } })
    const input = wrapper.find('input[type="file"]')

    // Create a file larger than 1MB
    const bigContent = new Uint8Array(2 * 1024 * 1024)
    const file = new File([bigContent], 'big.jpg', { type: 'image/jpeg' })
    Object.defineProperty(input.element, 'files', { value: [file] })
    await input.trigger('change')

    expect(wrapper.emitted('files-selected')).toBeUndefined()
    expect(wrapper.text()).toContain('terlalu besar')
  })

  it('applies drag-over visual feedback', async () => {
    const wrapper = mount(FileUploader)
    const dropZone = wrapper.find('div')

    await dropZone.trigger('dragenter')
    expect(dropZone.classes()).toContain('border-emerald-500')
    expect(dropZone.classes()).toContain('bg-emerald-50')

    await dropZone.trigger('dragleave')
    expect(dropZone.classes()).not.toContain('border-emerald-500')
  })

  it('emits files-selected on drop', async () => {
    const wrapper = mount(FileUploader)
    const dropZone = wrapper.find('div')

    const file = new File(['content'], 'dropped.jpg', { type: 'image/jpeg' })
    await dropZone.trigger('drop', {
      dataTransfer: { files: [file] }
    })

    expect(wrapper.emitted('files-selected')).toHaveLength(1)
  })
})
