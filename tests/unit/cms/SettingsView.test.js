import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import SettingsView from '../../../src/cms/views/SettingsView.vue'

function createMockStore(overrides = {}) {
  return createStore({
    modules: {
      settings: {
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
          fetchSettings: overrides.fetchSettings || vi.fn(({ commit }) => {
            commit('SET_DATA', overrides.data || {
              customDomain: '',
              showWatermark: true,
              passwordProtected: false,
              password: '',
              moderationEnabled: true,
              healthProtocol: '',
              liveStreamUrl: '',
              shippingAddress: '',
              seoMeta: { title: '', description: '', image: '' }
            })
          }),
          saveSettings: overrides.saveSettings || vi.fn(({ commit }, data) => {
            commit('SET_DATA', data)
            return data
          })
        },
        getters: {
          settings: (state) => state.data,
          isLoading: (state) => state.loading,
          error: (state) => state.error
        }
      }
    }
  })
}

describe('SettingsView', () => {
  it('renders heading', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Pengaturan')
  })

  it('dispatches fetchSettings on mount', () => {
    const fetchSettings = vi.fn()
    const store = createMockStore({ fetchSettings })
    mount(SettingsView, { global: { plugins: [store] } })
    expect(fetchSettings).toHaveBeenCalled()
  })

  it('shows loading indicator when loading', () => {
    const store = createMockStore({ loading: true })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Memuat pengaturan...')
  })

  it('shows error message when there is an error', () => {
    const store = createMockStore({ error: 'Gagal memuat pengaturan' })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Gagal memuat pengaturan')
  })

  // ─── Section rendering ──────────────────────────────────────────────────

  it('renders Domain & Branding section', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Domain & Branding')
    expect(wrapper.text()).toContain('Custom Domain')
    expect(wrapper.text()).toContain('Tampilkan "Powered by" watermark')
  })

  it('renders Keamanan section', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Keamanan')
    expect(wrapper.text()).toContain('Proteksi Password')
  })

  it('renders Moderasi section', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Moderasi')
    expect(wrapper.text()).toContain('Aktifkan moderasi ucapan')
  })

  it('renders Informasi Tambahan section', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Informasi Tambahan')
    expect(wrapper.text()).toContain('Protokol Kesehatan')
    expect(wrapper.text()).toContain('Live Streaming URL')
    expect(wrapper.text()).toContain('Alamat Pengiriman Hadiah Fisik')
  })

  it('renders SEO section', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('SEO')
    expect(wrapper.text()).toContain('Meta Title')
    expect(wrapper.text()).toContain('Meta Description')
    expect(wrapper.text()).toContain('Meta Image URL')
  })

  it('renders save button', () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    const saveBtn = wrapper.find('button:not([role="switch"])')
    expect(saveBtn.exists()).toBe(true)
    expect(saveBtn.text()).toContain('Simpan')
  })

  // ─── Data population ───────────────────────────────────────────────────

  it('populates form with store data on mount', async () => {
    const store = createMockStore({
      data: {
        customDomain: 'undangan.test.com',
        showWatermark: false,
        passwordProtected: true,
        password: 'secret123',
        moderationEnabled: false,
        healthProtocol: 'Wajib masker',
        liveStreamUrl: 'https://youtube.com/live/abc',
        shippingAddress: 'Jl. Merdeka No. 1',
        seoMeta: {
          title: 'Pernikahan Budi & Ani',
          description: 'Undangan pernikahan',
          image: 'media/og.webp'
        }
      }
    })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    expect(wrapper.find('#customDomain').element.value).toBe('undangan.test.com')
    expect(wrapper.find('#password').element.value).toBe('secret123')
    expect(wrapper.find('#healthProtocol').element.value).toBe('Wajib masker')
    expect(wrapper.find('#liveStreamUrl').element.value).toBe('https://youtube.com/live/abc')
    expect(wrapper.find('#shippingAddress').element.value).toBe('Jl. Merdeka No. 1')
    expect(wrapper.find('#seoTitle').element.value).toBe('Pernikahan Budi & Ani')
    expect(wrapper.find('#seoDescription').element.value).toBe('Undangan pernikahan')
    expect(wrapper.find('#seoImage').element.value).toBe('media/og.webp')
  })

  // ─── Password field visibility ─────────────────────────────────────────

  it('hides password input when password protection is off', async () => {
    const store = createMockStore({
      data: {
        customDomain: '',
        showWatermark: true,
        passwordProtected: false,
        password: '',
        moderationEnabled: true,
        healthProtocol: '',
        liveStreamUrl: '',
        shippingAddress: '',
        seoMeta: { title: '', description: '', image: '' }
      }
    })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    expect(wrapper.find('#password').exists()).toBe(false)
  })

  it('shows password input when password protection is on', async () => {
    const store = createMockStore({
      data: {
        customDomain: '',
        showWatermark: true,
        passwordProtected: true,
        password: 'test',
        moderationEnabled: true,
        healthProtocol: '',
        liveStreamUrl: '',
        shippingAddress: '',
        seoMeta: { title: '', description: '', image: '' }
      }
    })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    expect(wrapper.find('#password').exists()).toBe(true)
  })

  it('toggles password input visibility when toggle is clicked', async () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    // Initially off
    expect(wrapper.find('#password').exists()).toBe(false)

    // Click the password protection toggle
    const toggle = wrapper.find('#passwordProtected')
    await toggle.trigger('click')

    expect(wrapper.find('#password').exists()).toBe(true)
  })

  // ─── Toggle interactions ───────────────────────────────────────────────

  it('toggles watermark switch', async () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const toggle = wrapper.find('#showWatermark')
    expect(toggle.attributes('aria-checked')).toBe('true')

    await toggle.trigger('click')
    expect(toggle.attributes('aria-checked')).toBe('false')
  })

  it('toggles moderation switch', async () => {
    const store = createMockStore()
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const toggle = wrapper.find('#moderationEnabled')
    expect(toggle.attributes('aria-checked')).toBe('true')

    await toggle.trigger('click')
    expect(toggle.attributes('aria-checked')).toBe('false')
  })

  // ─── Save functionality ────────────────────────────────────────────────

  it('dispatches saveSettings on save button click', async () => {
    const saveSettings = vi.fn(({ commit }, data) => {
      commit('SET_DATA', data)
      return data
    })
    const store = createMockStore({ saveSettings })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button:not([role="switch"])')
    await saveBtn.trigger('click')
    await flushPromises()

    expect(saveSettings).toHaveBeenCalled()
  })

  it('shows success message after saving', async () => {
    const saveSettings = vi.fn(({ commit }, data) => {
      commit('SET_DATA', data)
      return data
    })
    const store = createMockStore({ saveSettings })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button:not([role="switch"])')
    await saveBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Pengaturan berhasil disimpan')
  })

  it('shows error message when save fails', async () => {
    const saveSettings = vi.fn(() => {
      throw new Error('Gagal menyimpan pengaturan')
    })
    const store = createMockStore({ saveSettings })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button:not([role="switch"])')
    await saveBtn.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Gagal menyimpan pengaturan')
  })

  it('disables save button while saving', async () => {
    let resolvePromise
    const saveSettings = vi.fn(() => {
      return new Promise((resolve) => { resolvePromise = resolve })
    })
    const store = createMockStore({ saveSettings })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button:not([role="switch"])')
    await saveBtn.trigger('click')

    expect(saveBtn.attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('Menyimpan...')

    resolvePromise({ })
    await flushPromises()
  })

  it('sends correct payload when saving', async () => {
    const saveSettings = vi.fn(({ commit }, data) => {
      commit('SET_DATA', data)
      return data
    })
    const store = createMockStore({
      data: {
        customDomain: 'test.com',
        showWatermark: false,
        passwordProtected: true,
        password: 'pw123',
        moderationEnabled: false,
        healthProtocol: 'Wajib masker',
        liveStreamUrl: 'https://yt.com/live',
        shippingAddress: 'Jl. Test',
        seoMeta: { title: 'Test', description: 'Desc', image: 'img.webp' }
      },
      saveSettings
    })
    const wrapper = mount(SettingsView, { global: { plugins: [store] } })
    await flushPromises()

    const saveBtn = wrapper.find('button:not([role="switch"])')
    await saveBtn.trigger('click')
    await flushPromises()

    const payload = saveSettings.mock.calls[0][1]
    expect(payload.customDomain).toBe('test.com')
    expect(payload.showWatermark).toBe(false)
    expect(payload.passwordProtected).toBe(true)
    expect(payload.password).toBe('pw123')
    expect(payload.moderationEnabled).toBe(false)
    expect(payload.healthProtocol).toBe('Wajib masker')
    expect(payload.liveStreamUrl).toBe('https://yt.com/live')
    expect(payload.shippingAddress).toBe('Jl. Test')
    expect(payload.seoMeta).toEqual({ title: 'Test', description: 'Desc', image: 'img.webp' })
  })
})
