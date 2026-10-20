import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import CoupleForm from '../../../src/cms/components/couple/CoupleForm.vue'
import PhotoUploader from '../../../src/cms/components/couple/PhotoUploader.vue'
import CoupleView from '../../../src/cms/views/CoupleView.vue'

// Mock browser-image-compression used by useImageOptimizer
vi.mock('browser-image-compression', () => ({
  default: vi.fn(async (file) => {
    const blob = new Blob(['compressed'], { type: 'image/webp' })
    const compressed = new File([blob], 'compressed.webp', { type: 'image/webp' })
    Object.defineProperty(compressed, 'size', { value: Math.floor(file.size * 0.5) })
    return compressed
  })
}))

// Mock URL.createObjectURL
globalThis.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
globalThis.URL.revokeObjectURL = vi.fn()

// ─── PhotoUploader ───────────────────────────────────────────────────────────

describe('PhotoUploader', () => {
  it('renders upload area with label', () => {
    const wrapper = mount(PhotoUploader)
    expect(wrapper.text()).toContain('Pilih foto')
  })

  it('shows "Ganti foto" when a preview exists', () => {
    const wrapper = mount(PhotoUploader, {
      props: { modelValue: 'blob:existing-photo' }
    })
    expect(wrapper.text()).toContain('Ganti foto')
  })

  it('shows preview image when modelValue is provided', () => {
    const wrapper = mount(PhotoUploader, {
      props: { modelValue: 'blob:existing-photo' }
    })
    const img = wrapper.find('img')
    expect(img.exists()).toBe(true)
    expect(img.attributes('src')).toBe('blob:existing-photo')
  })

  it('accepts image files via file input', async () => {
    const wrapper = mount(PhotoUploader)
    const file = new File(['image-data'], 'photo.jpg', { type: 'image/jpeg' })
    Object.defineProperty(file, 'size', { value: 1000000 })

    const input = wrapper.find('input[type="file"]')
    expect(input.attributes('accept')).toContain('image/jpeg')
  })

  it('shows format hint text', () => {
    const wrapper = mount(PhotoUploader)
    expect(wrapper.text()).toContain('Format: JPG, PNG, WebP')
  })

  it('has hidden file input for accessibility', () => {
    const wrapper = mount(PhotoUploader)
    const input = wrapper.find('input[type="file"]')
    expect(input.classes()).toContain('sr-only')
  })
})

// ─── CoupleForm ──────────────────────────────────────────────────────────────

describe('CoupleForm', () => {
  const defaultProps = {
    title: 'Mempelai Pria',
    modelValue: {
      fullName: '',
      nickname: '',
      photo: '',
      fatherName: '',
      motherName: '',
      instagramUrl: '',
      childOrder: ''
    }
  }

  it('renders the title', () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    expect(wrapper.text()).toContain('Mempelai Pria')
  })

  it('renders all form fields', () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    expect(wrapper.text()).toContain('Nama Lengkap')
    expect(wrapper.text()).toContain('Nama Panggilan')
    expect(wrapper.text()).toContain('Nama Ayah')
    expect(wrapper.text()).toContain('Nama Ibu')
    expect(wrapper.text()).toContain('Instagram')
    expect(wrapper.text()).toContain('Anak ke-')
    expect(wrapper.text()).toContain('Foto')
  })

  it('marks fullName as required with asterisk', () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    const label = wrapper.find('label[for*="fullName"]')
    expect(label.text()).toContain('*')
  })

  it('populates form fields from modelValue', () => {
    const wrapper = mount(CoupleForm, {
      props: {
        title: 'Mempelai Pria',
        modelValue: {
          fullName: 'Budi Santoso',
          nickname: 'Budi',
          photo: '',
          fatherName: 'H. Santoso',
          motherName: 'Hj. Siti',
          instagramUrl: 'https://instagram.com/budi',
          childOrder: 'Putra pertama'
        }
      }
    })
    const fullNameInput = wrapper.find('input[id*="fullName"]')
    expect(fullNameInput.element.value).toBe('Budi Santoso')
  })

  it('emits update:modelValue when input changes', async () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    const fullNameInput = wrapper.find('input[id*="fullName"]')
    await fullNameInput.setValue('Budi Santoso')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const lastEmit = emitted[emitted.length - 1][0]
    expect(lastEmit.fullName).toBe('Budi Santoso')
  })

  it('validates fullName is required on blur', async () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    const fullNameInput = wrapper.find('input[id*="fullName"]')
    await fullNameInput.setValue('')
    await fullNameInput.trigger('blur')

    expect(wrapper.text()).toContain('Nama lengkap wajib diisi')
  })

  it('clears validation error when fullName is filled', async () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    const fullNameInput = wrapper.find('input[id*="fullName"]')

    // Trigger error
    await fullNameInput.setValue('')
    await fullNameInput.trigger('blur')
    expect(wrapper.text()).toContain('Nama lengkap wajib diisi')

    // Fix it
    await fullNameInput.setValue('Budi')
    await fullNameInput.trigger('blur')
    expect(wrapper.text()).not.toContain('Nama lengkap wajib diisi')
  })

  it('exposes validate method that returns false for empty fullName', () => {
    const wrapper = mount(CoupleForm, { props: defaultProps })
    const isValid = wrapper.vm.validate()
    expect(isValid).toBe(false)
  })

  it('exposes validate method that returns true for filled fullName', () => {
    const wrapper = mount(CoupleForm, {
      props: {
        ...defaultProps,
        modelValue: { ...defaultProps.modelValue, fullName: 'Budi' }
      }
    })
    const isValid = wrapper.vm.validate()
    expect(isValid).toBe(true)
  })
})

// ─── CoupleView (integration) ───────────────────────────────────────────────

describe('CoupleView', () => {
  function createMockStore(overrides = {}) {
    return createStore({
      modules: {
        couple: {
          namespaced: true,
          state: () => ({
            data: overrides.data || null,
            loading: overrides.loading || false,
            error: overrides.error || null
          }),
          mutations: {
            SET_DATA(state, data) { state.data = data },
            SET_LOADING(state, loading) { state.loading = loading },
            SET_ERROR(state, error) { state.error = error }
          },
          actions: {
            fetchCouple: overrides.fetchCouple || vi.fn(({ commit }) => {
              commit('SET_DATA', overrides.data || {
                id: '1',
                groom: { fullName: '', nickname: '', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
                bride: { fullName: '', nickname: '', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
                musicUrl: ''
              })
            }),
            saveCouple: overrides.saveCouple || vi.fn(({ commit }, data) => {
              commit('SET_DATA', data)
              return data
            })
          },
          getters: {
            couple: (state) => state.data,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        }
      }
    })
  }

  it('renders heading', () => {
    const store = createMockStore()
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Data Mempelai')
  })

  it('dispatches fetchCouple on mount', () => {
    const fetchCouple = vi.fn()
    const store = createMockStore({ fetchCouple })
    mount(CoupleView, { global: { plugins: [store] } })
    expect(fetchCouple).toHaveBeenCalled()
  })

  it('shows loading indicator when loading', () => {
    const store = createMockStore({ loading: true })
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Memuat data mempelai...')
  })

  it('shows error message when there is an error', () => {
    const store = createMockStore({ error: 'Gagal memuat data mempelai' })
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Gagal memuat data mempelai')
  })

  it('renders groom and bride form sections', () => {
    const store = createMockStore()
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Mempelai Pria')
    expect(wrapper.text()).toContain('Mempelai Wanita')
  })

  it('renders music URL field', () => {
    const store = createMockStore()
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Musik Latar')
    expect(wrapper.text()).toContain('URL Musik')
  })

  it('renders save button', () => {
    const store = createMockStore()
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    const saveBtn = wrapper.find('button')
    expect(saveBtn.text()).toContain('Simpan')
  })

  it('populates form with store data', async () => {
    const store = createMockStore({
      data: {
        id: '1',
        groom: { fullName: 'Budi', nickname: 'Bud', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
        bride: { fullName: 'Ani', nickname: 'An', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
        musicUrl: 'music.mp3'
      }
    })
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    await flushPromises()
    const musicInput = wrapper.find('#musicUrl')
    expect(musicInput.element.value).toBe('music.mp3')
  })

  it('shows validation error when saving with empty fullName', async () => {
    const store = createMockStore()
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })

    const saveBtn = wrapper.find('button')
    await saveBtn.trigger('click')

    expect(wrapper.text()).toContain('Nama lengkap wajib diisi')
  })

  it('dispatches saveCouple when form is valid', async () => {
    const saveCouple = vi.fn(({ commit }, data) => {
      commit('SET_DATA', data)
      return data
    })
    const store = createMockStore({
      data: {
        id: '1',
        groom: { fullName: 'Budi', nickname: '', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
        bride: { fullName: 'Ani', nickname: '', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
        musicUrl: ''
      },
      saveCouple
    })
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button')
    await saveBtn.trigger('click')
    await flushPromises()

    expect(saveCouple).toHaveBeenCalled()
  })

  it('shows success message after saving', async () => {
    const saveCouple = vi.fn(({ commit }, data) => {
      commit('SET_DATA', data)
      return data
    })
    const store = createMockStore({
      data: {
        id: '1',
        groom: { fullName: 'Budi', nickname: '', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
        bride: { fullName: 'Ani', nickname: '', photo: '', fatherName: '', motherName: '', instagramUrl: '', childOrder: '' },
        musicUrl: ''
      },
      saveCouple
    })
    const wrapper = mount(CoupleView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button')
    await saveBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Data mempelai berhasil disimpan')
  })
})
