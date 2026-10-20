import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import TemplateCard from '../../../src/cms/components/template/TemplateCard.vue'
import TemplateGallery from '../../../src/cms/components/template/TemplateGallery.vue'
import ThemeCustomizer from '../../../src/cms/components/template/ThemeCustomizer.vue'
import LivePreview from '../../../src/cms/components/template/LivePreview.vue'
import TemplateView from '../../../src/cms/views/TemplateView.vue'

// ─── TemplateCard ────────────────────────────────────────────────────────────

describe('TemplateCard', () => {
  const baseProps = {
    id: 'batik-elegance',
    name: 'Batik Elegance',
    description: 'Desain elegan dengan motif batik Jawa'
  }

  it('renders template name and description', () => {
    const wrapper = mount(TemplateCard, { props: baseProps })
    expect(wrapper.text()).toContain('Batik Elegance')
    expect(wrapper.text()).toContain('Desain elegan dengan motif batik Jawa')
  })

  it('emits select event with template id when clicked', async () => {
    const wrapper = mount(TemplateCard, { props: baseProps })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('select')).toHaveLength(1)
    expect(wrapper.emitted('select')[0][0]).toBe('batik-elegance')
  })

  it('shows selected indicator when selected is true', () => {
    const wrapper = mount(TemplateCard, { props: { ...baseProps, selected: true } })
    expect(wrapper.text()).toContain('Dipilih')
  })

  it('does not show selected indicator when selected is false', () => {
    const wrapper = mount(TemplateCard, { props: { ...baseProps, selected: false } })
    expect(wrapper.text()).not.toContain('Dipilih')
  })

  it('applies indigo border when selected', () => {
    const wrapper = mount(TemplateCard, { props: { ...baseProps, selected: true } })
    expect(wrapper.find('button').classes()).toContain('border-emerald-600')
  })

  it('applies gray border when not selected', () => {
    const wrapper = mount(TemplateCard, { props: { ...baseProps, selected: false } })
    expect(wrapper.find('button').classes()).toContain('border-gray-200')
  })

  it('renders checkmark icon when selected', () => {
    const wrapper = mount(TemplateCard, { props: { ...baseProps, selected: true } })
    const checkmark = wrapper.find('.bg-emerald-600.rounded-full')
    expect(checkmark.exists()).toBe(true)
  })

  it('uses correct gradient for each template id', () => {
    const batik = mount(TemplateCard, { props: { ...baseProps, id: 'batik-elegance' } })
    expect(batik.find('.from-amber-100').exists()).toBe(true)

    const wayang = mount(TemplateCard, { props: { ...baseProps, id: 'wayang-romance' } })
    expect(wayang.find('.from-rose-100').exists()).toBe(true)

    const songket = mount(TemplateCard, { props: { ...baseProps, id: 'songket-royal' } })
    expect(songket.find('.from-purple-100').exists()).toBe(true)
  })
})

// ─── TemplateGallery ─────────────────────────────────────────────────────────

describe('TemplateGallery', () => {
  const templates = [
    { id: 'batik-elegance', name: 'Batik Elegance', description: 'Desc 1' },
    { id: 'wayang-romance', name: 'Wayang Romance', description: 'Desc 2' },
    { id: 'songket-royal', name: 'Songket Royal', description: 'Desc 3' }
  ]

  it('renders all template cards', () => {
    const wrapper = mount(TemplateGallery, {
      props: { templates, selectedId: 'batik-elegance' }
    })
    const cards = wrapper.findAllComponents(TemplateCard)
    expect(cards).toHaveLength(3)
  })

  it('passes selected prop correctly to the selected card', () => {
    const wrapper = mount(TemplateGallery, {
      props: { templates, selectedId: 'wayang-romance' }
    })
    const cards = wrapper.findAllComponents(TemplateCard)
    expect(cards[0].props('selected')).toBe(false)
    expect(cards[1].props('selected')).toBe(true)
    expect(cards[2].props('selected')).toBe(false)
  })

  it('emits select event when a card is clicked', async () => {
    const wrapper = mount(TemplateGallery, {
      props: { templates, selectedId: 'batik-elegance' }
    })
    const cards = wrapper.findAllComponents(TemplateCard)
    await cards[2].find('button').trigger('click')
    expect(wrapper.emitted('select')).toHaveLength(1)
    expect(wrapper.emitted('select')[0][0]).toBe('songket-royal')
  })

  it('renders section heading', () => {
    const wrapper = mount(TemplateGallery, {
      props: { templates, selectedId: '' }
    })
    expect(wrapper.text()).toContain('Pilih Template')
  })
})

// ─── ThemeCustomizer ─────────────────────────────────────────────────────────

describe('ThemeCustomizer', () => {
  const baseProps = {
    themeColors: { primary: '#8B4513', secondary: '#D2691E', accent: '#FFD700' },
    fontFamily: 'Playfair Display'
  }

  it('renders section heading', () => {
    const wrapper = mount(ThemeCustomizer, { props: baseProps })
    expect(wrapper.text()).toContain('Kustomisasi Tema')
  })

  it('renders three color pickers', () => {
    const wrapper = mount(ThemeCustomizer, { props: baseProps })
    const colorInputs = wrapper.findAll('input[type="color"]')
    expect(colorInputs).toHaveLength(3)
  })

  it('renders font family selector with options', () => {
    const wrapper = mount(ThemeCustomizer, { props: baseProps })
    const select = wrapper.find('select')
    expect(select.exists()).toBe(true)
    const options = select.findAll('option')
    expect(options.length).toBe(5)
    expect(options.map(o => o.text())).toContain('Playfair Display')
    expect(options.map(o => o.text())).toContain('Dancing Script')
  })

  it('emits update:themeColors when a color changes', async () => {
    const wrapper = mount(ThemeCustomizer, { props: baseProps })
    const colorInputs = wrapper.findAll('input[type="color"]')
    await colorInputs[0].setValue('#FF0000')
    expect(wrapper.emitted('update:themeColors')).toBeTruthy()
    const emitted = wrapper.emitted('update:themeColors')[0][0]
    expect(emitted.primary.toLowerCase()).toBe('#ff0000')
    expect(emitted.secondary.toLowerCase()).toBe('#d2691e')
    expect(emitted.accent.toLowerCase()).toBe('#ffd700')
  })

  it('emits update:fontFamily when font changes', async () => {
    const wrapper = mount(ThemeCustomizer, { props: baseProps })
    const select = wrapper.find('select')
    await select.setValue('Lora')
    expect(wrapper.emitted('update:fontFamily')).toBeTruthy()
    expect(wrapper.emitted('update:fontFamily')[0][0]).toBe('Lora')
  })

  it('shows current font family as selected', () => {
    const wrapper = mount(ThemeCustomizer, {
      props: { ...baseProps, fontFamily: 'Poppins' }
    })
    const select = wrapper.find('select')
    expect(select.element.value).toBe('Poppins')
  })
})

// ─── LivePreview ─────────────────────────────────────────────────────────────

describe('LivePreview', () => {
  const baseProps = {
    templateId: 'batik-elegance',
    templateName: 'Batik Elegance',
    themeColors: { primary: '#8B4513', secondary: '#D2691E', accent: '#FFD700' },
    fontFamily: 'Playfair Display'
  }

  it('renders template name in header', () => {
    const wrapper = mount(LivePreview, { props: baseProps })
    expect(wrapper.text()).toContain('Batik Elegance')
  })

  it('renders Live Preview heading', () => {
    const wrapper = mount(LivePreview, { props: baseProps })
    expect(wrapper.text()).toContain('Live Preview')
  })

  it('applies primary color to couple names', () => {
    const wrapper = mount(LivePreview, { props: baseProps })
    const nameEl = wrapper.find('.text-2xl')
    expect(nameEl.attributes('style')).toContain('color: rgb(139, 69, 19)')
  })

  it('applies font family to preview area', () => {
    const wrapper = mount(LivePreview, { props: baseProps })
    const previewArea = wrapper.find('.min-h-\\[400px\\]')
    expect(previewArea.attributes('style')).toContain('Playfair Display')
  })

  it('renders placeholder content', () => {
    const wrapper = mount(LivePreview, { props: baseProps })
    expect(wrapper.text()).toContain('Budi & Ani')
    expect(wrapper.text()).toContain('25 Desember 2024')
    expect(wrapper.text()).toContain('Akad Nikah')
    expect(wrapper.text()).toContain('Konfirmasi Kehadiran')
  })

  it('updates colors when theme changes', () => {
    const wrapper = mount(LivePreview, {
      props: {
        ...baseProps,
        themeColors: { primary: '#FF0000', secondary: '#00FF00', accent: '#0000FF' }
      }
    })
    const nameEl = wrapper.find('.text-2xl')
    expect(nameEl.attributes('style')).toContain('color: rgb(255, 0, 0)')
  })
})

// ─── TemplateView (integration) ──────────────────────────────────────────────

describe('TemplateView', () => {
  function createMockStore(overrides = {}) {
    return createStore({
      modules: {
        settings: {
          namespaced: true,
          state: () => ({
            data: {
              templateId: 'batik-elegance',
              themeColors: { primary: '#8B4513', secondary: '#D2691E', accent: '#FFD700' },
              fontFamily: 'Playfair Display',
              musicUrl: ''
            },
            loading: false,
            error: null,
            ...overrides.settingsState
          }),
          mutations: {
            SET_DATA(state, data) { state.data = data },
            SET_LOADING(state, v) { state.loading = v },
            SET_ERROR(state, v) { state.error = v }
          },
          actions: {
            fetchSettings: overrides.fetchSettings || vi.fn(),
            saveSettings: overrides.saveSettings || vi.fn()
          },
          getters: {
            settings: (state) => state.data,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        },
        template: {
          namespaced: true,
          state: () => ({
            selectedId: 'batik-elegance',
            templates: [
              { id: 'batik-elegance', name: 'Batik Elegance', description: 'Desc 1' },
              { id: 'wayang-romance', name: 'Wayang Romance', description: 'Desc 2' },
              { id: 'songket-royal', name: 'Songket Royal', description: 'Desc 3' }
            ],
            loading: false,
            ...overrides.templateState
          }),
          mutations: {
            SET_SELECTED_ID(state, id) { state.selectedId = id },
            SET_TEMPLATES(state, t) { state.templates = t },
            SET_LOADING(state, v) { state.loading = v }
          },
          actions: {
            fetchTemplates: overrides.fetchTemplates || vi.fn(),
            selectTemplate: overrides.selectTemplate || vi.fn()
          },
          getters: {
            selectedId: (state) => state.selectedId,
            templates: (state) => state.templates,
            selectedTemplate: (state) => state.templates.find(t => t.id === state.selectedId) || null,
            isLoading: (state) => state.loading
          }
        }
      }
    })
  }

  it('renders page heading', () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Template & Preview')
  })

  it('shows loading indicator when loading', () => {
    const store = createMockStore({
      settingsState: { loading: true }
    })
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Memuat pengaturan template...')
  })

  it('shows error message when there is an error', () => {
    const store = createMockStore({
      settingsState: { loading: false, error: 'Gagal memuat' }
    })
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Gagal memuat')
  })

  it('renders template gallery with templates from store', () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Pilih Template')
    expect(wrapper.text()).toContain('Batik Elegance')
    expect(wrapper.text()).toContain('Wayang Romance')
    expect(wrapper.text()).toContain('Songket Royal')
  })

  it('renders theme customizer section', () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Kustomisasi Tema')
  })

  it('renders music upload section', () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Musik Latar')
  })

  it('renders live preview section', () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Live Preview')
  })

  it('renders save button', () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })
    const saveBtn = wrapper.findAll('button').find(b => b.text().includes('Simpan Pengaturan'))
    expect(saveBtn).toBeTruthy()
  })

  it('dispatches fetchSettings and fetchTemplates on mount', () => {
    const fetchSettings = vi.fn()
    const fetchTemplates = vi.fn()
    const store = createMockStore({ fetchSettings, fetchTemplates })
    mount(TemplateView, { global: { plugins: [store] } })
    expect(fetchSettings).toHaveBeenCalled()
    expect(fetchTemplates).toHaveBeenCalled()
  })

  it('rejects non-MP3 files with error message', async () => {
    const store = createMockStore()
    const wrapper = mount(TemplateView, { global: { plugins: [store] } })

    // Simulate file selection with a non-MP3 file
    const fileInput = wrapper.find('input[type="file"]')
    const invalidFile = new File(['content'], 'song.wav', { type: 'audio/wav' })
    Object.defineProperty(fileInput.element, 'files', { value: [invalidFile] })
    await fileInput.trigger('change')

    // The FileUploader emits files-selected, then TemplateView validates
    // We need to check if the error message appears
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('Hanya file format MP3 yang didukung')
  })
})
