import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import MapPreview from '../../../src/cms/components/events/MapPreview.vue'
import EventForm from '../../../src/cms/components/events/EventForm.vue'
import EventsView from '../../../src/cms/views/EventsView.vue'

// ─── MapPreview ──────────────────────────────────────────────────────────────

describe('MapPreview', () => {
  it('shows placeholder when no coordinates are provided', () => {
    const wrapper = mount(MapPreview)
    expect(wrapper.text()).toContain('Masukkan latitude dan longitude')
    expect(wrapper.find('iframe').exists()).toBe(false)
  })

  it('shows placeholder when latitude is null', () => {
    const wrapper = mount(MapPreview, {
      props: { latitude: null, longitude: 106.831 }
    })
    expect(wrapper.text()).toContain('Masukkan latitude dan longitude')
  })

  it('shows placeholder when longitude is null', () => {
    const wrapper = mount(MapPreview, {
      props: { latitude: -6.17, longitude: null }
    })
    expect(wrapper.text()).toContain('Masukkan latitude dan longitude')
  })

  it('shows Google Maps iframe when valid coordinates are provided', () => {
    const wrapper = mount(MapPreview, {
      props: { latitude: -6.1702, longitude: 106.831 }
    })
    const iframe = wrapper.find('iframe')
    expect(iframe.exists()).toBe(true)
    expect(iframe.attributes('src')).toBe(
      'https://maps.google.com/maps?q=-6.1702,106.831&output=embed'
    )
  })

  it('shows placeholder for invalid latitude (out of range)', () => {
    const wrapper = mount(MapPreview, {
      props: { latitude: 100, longitude: 106.831 }
    })
    expect(wrapper.find('iframe').exists()).toBe(false)
  })

  it('shows placeholder for invalid longitude (out of range)', () => {
    const wrapper = mount(MapPreview, {
      props: { latitude: -6.17, longitude: 200 }
    })
    expect(wrapper.find('iframe').exists()).toBe(false)
  })

  it('sets accessible title on iframe', () => {
    const wrapper = mount(MapPreview, {
      props: { latitude: -6.1702, longitude: 106.831 }
    })
    const iframe = wrapper.find('iframe')
    expect(iframe.attributes('title')).toContain('-6.1702')
    expect(iframe.attributes('title')).toContain('106.831')
  })
})

// ─── EventForm ───────────────────────────────────────────────────────────────

describe('EventForm', () => {
  const defaultProps = {
    modelValue: {
      name: '',
      date: '',
      startTime: '',
      endTime: '',
      venueName: '',
      address: '',
      latitude: '',
      longitude: ''
    }
  }

  it('renders "Tambah Acara" title when not editing', () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    expect(wrapper.text()).toContain('Tambah Acara')
  })

  it('renders "Edit Acara" title when editing', () => {
    const wrapper = mount(EventForm, {
      props: { ...defaultProps, isEditing: true }
    })
    expect(wrapper.text()).toContain('Edit Acara')
  })

  it('renders all form fields', () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    expect(wrapper.text()).toContain('Nama Acara')
    expect(wrapper.text()).toContain('Tanggal')
    expect(wrapper.text()).toContain('Waktu Mulai')
    expect(wrapper.text()).toContain('Waktu Selesai')
    expect(wrapper.text()).toContain('Nama Tempat')
    expect(wrapper.text()).toContain('Alamat')
    expect(wrapper.text()).toContain('Latitude')
    expect(wrapper.text()).toContain('Longitude')
  })

  it('marks name and date as required with asterisk', () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const nameLabel = wrapper.find('label[for*="name"]')
    const dateLabel = wrapper.find('label[for*="date"]')
    expect(nameLabel.text()).toContain('*')
    expect(dateLabel.text()).toContain('*')
  })

  it('populates form fields from modelValue', () => {
    const wrapper = mount(EventForm, {
      props: {
        modelValue: {
          name: 'Akad Nikah',
          date: '2025-12-25',
          startTime: '08:00',
          endTime: '10:00',
          venueName: 'Masjid Istiqlal',
          address: 'Jakarta',
          latitude: -6.1702,
          longitude: 106.831
        }
      }
    })
    const nameInput = wrapper.find('input[id*="name"]')
    expect(nameInput.element.value).toBe('Akad Nikah')
  })

  it('validates name is required on blur', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const nameInput = wrapper.find('input[id*="name"]')
    await nameInput.setValue('')
    await nameInput.trigger('blur')
    expect(wrapper.text()).toContain('Nama acara wajib diisi')
  })

  it('validates date is required on blur', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const dateInput = wrapper.find('input[type="date"]')
    await dateInput.setValue('')
    await dateInput.trigger('blur')
    expect(wrapper.text()).toContain('Tanggal wajib diisi')
  })

  it('shows error for invalid latitude', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const latInput = wrapper.find('input[id*="latitude"]')
    await latInput.setValue(100)
    await latInput.trigger('blur')
    expect(wrapper.text()).toContain('Latitude harus antara -90 dan 90')
  })

  it('shows error for invalid longitude', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const lngInput = wrapper.find('input[id*="longitude"]')
    await lngInput.setValue(200)
    await lngInput.trigger('blur')
    expect(wrapper.text()).toContain('Longitude harus antara -180 dan 180')
  })

  it('does not show coordinate error for empty values', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const latInput = wrapper.find('input[id*="latitude"]')
    await latInput.setValue('')
    await latInput.trigger('blur')
    expect(wrapper.text()).not.toContain('Latitude harus antara')
  })

  it('shows past date warning for past dates', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const dateInput = wrapper.find('input[type="date"]')
    await dateInput.setValue('2020-01-01')
    await dateInput.trigger('change')
    expect(wrapper.text()).toContain('Peringatan: Tanggal acara sudah lewat')
  })

  it('does not show past date warning for future dates', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const dateInput = wrapper.find('input[type="date"]')
    await dateInput.setValue('2099-12-31')
    await dateInput.trigger('change')
    expect(wrapper.text()).not.toContain('Peringatan: Tanggal acara sudah lewat')
  })

  it('emits submit with form data when valid', async () => {
    const wrapper = mount(EventForm, {
      props: {
        modelValue: {
          name: 'Resepsi',
          date: '2025-12-25',
          startTime: '18:00',
          endTime: '21:00',
          venueName: 'Hotel Grand',
          address: 'Jakarta',
          latitude: -6.17,
          longitude: 106.83
        }
      }
    })

    await wrapper.find('form').trigger('submit')
    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted[0][0]).toMatchObject({
      name: 'Resepsi',
      date: '2025-12-25'
    })
  })

  it('does not emit submit when name is empty', async () => {
    const wrapper = mount(EventForm, {
      props: {
        modelValue: {
          name: '',
          date: '2025-12-25',
          startTime: '',
          endTime: '',
          venueName: '',
          address: '',
          latitude: '',
          longitude: ''
        }
      }
    })

    await wrapper.find('form').trigger('submit')
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('emits cancel when cancel button is clicked', async () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    const cancelBtn = wrapper.findAll('button').find(b => b.text() === 'Batal')
    await cancelBtn.trigger('click')
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('renders MapPreview component', () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    expect(wrapper.findComponent(MapPreview).exists()).toBe(true)
  })

  it('shows "Perbarui" button text when editing', () => {
    const wrapper = mount(EventForm, {
      props: { ...defaultProps, isEditing: true }
    })
    expect(wrapper.text()).toContain('Perbarui')
  })

  it('shows "Simpan" button text when adding', () => {
    const wrapper = mount(EventForm, { props: defaultProps })
    expect(wrapper.text()).toContain('Simpan')
  })
})

// ─── EventsView (integration) ───────────────────────────────────────────────

describe('EventsView', () => {
  function createMockStore(overrides = {}) {
    return createStore({
      modules: {
        events: {
          namespaced: true,
          state: () => ({
            items: overrides.items || [],
            loading: overrides.loading || false,
            error: overrides.error || null
          }),
          mutations: {
            SET_ITEMS(state, items) { state.items = items },
            ADD_ITEM(state, item) { state.items.push(item) },
            UPDATE_ITEM(state, updated) {
              const index = state.items.findIndex(i => i.id === updated.id)
              if (index !== -1) state.items.splice(index, 1, updated)
            },
            REMOVE_ITEM(state, id) {
              state.items = state.items.filter(i => i.id !== id)
            },
            SET_LOADING(state, loading) { state.loading = loading },
            SET_ERROR(state, error) { state.error = error }
          },
          actions: {
            fetchEvents: overrides.fetchEvents || vi.fn(({ commit }) => {
              commit('SET_ITEMS', overrides.items || [])
            }),
            createEvent: overrides.createEvent || vi.fn(({ commit }, data) => {
              const item = { id: 'new-1', ...data, createdAt: new Date().toISOString() }
              commit('ADD_ITEM', item)
              return item
            }),
            updateEvent: overrides.updateEvent || vi.fn(({ commit }, { id, data }) => {
              const updated = { id, ...data }
              commit('UPDATE_ITEM', updated)
              return updated
            }),
            deleteEvent: overrides.deleteEvent || vi.fn(({ commit }, id) => {
              commit('REMOVE_ITEM', id)
            })
          },
          getters: {
            events: (state) => state.items,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        }
      }
    })
  }

  it('renders heading', () => {
    const store = createMockStore()
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Acara')
  })

  it('dispatches fetchEvents on mount', () => {
    const fetchEvents = vi.fn()
    const store = createMockStore({ fetchEvents })
    mount(EventsView, { global: { plugins: [store] } })
    expect(fetchEvents).toHaveBeenCalled()
  })

  it('shows loading indicator when loading', () => {
    const store = createMockStore({ loading: true })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Memuat data acara...')
  })

  it('shows error message when there is an error', () => {
    const store = createMockStore({ error: 'Gagal memuat data acara' })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Gagal memuat data acara')
  })

  it('shows empty state when no events exist', () => {
    const store = createMockStore()
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Belum ada acara')
  })

  it('renders "Tambah Acara" button', () => {
    const store = createMockStore()
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Tambah Acara')
  })

  it('shows event list with event details', () => {
    const store = createMockStore({
      items: [
        {
          id: '1',
          name: 'Akad Nikah',
          date: '2025-12-25',
          startTime: '08:00',
          endTime: '10:00',
          venueName: 'Masjid Istiqlal',
          address: 'Jakarta'
        }
      ]
    })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Akad Nikah')
    expect(wrapper.text()).toContain('Masjid Istiqlal')
    expect(wrapper.text()).toContain('Jakarta')
  })

  it('shows edit and delete buttons for each event', () => {
    const store = createMockStore({
      items: [{ id: '1', name: 'Akad Nikah', date: '2025-12-25' }]
    })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Edit')
    expect(wrapper.text()).toContain('Hapus')
  })

  it('opens add form when "Tambah Acara" is clicked', async () => {
    const store = createMockStore()
    const wrapper = mount(EventsView, { global: { plugins: [store] } })

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Tambah Acara'))
    await addBtn.trigger('click')

    expect(wrapper.findComponent(EventForm).exists()).toBe(true)
  })

  it('opens edit form when "Edit" is clicked', async () => {
    const store = createMockStore({
      items: [{ id: '1', name: 'Akad Nikah', date: '2025-12-25' }]
    })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })

    const editBtn = wrapper.findAll('button').find(b => b.text() === 'Edit')
    await editBtn.trigger('click')

    const form = wrapper.findComponent(EventForm)
    expect(form.exists()).toBe(true)
    expect(form.props('isEditing')).toBe(true)
  })

  it('dispatches createEvent when form is submitted in add mode', async () => {
    const createEvent = vi.fn(({ commit }, data) => {
      const item = { id: 'new-1', ...data }
      commit('ADD_ITEM', item)
      return item
    })
    const store = createMockStore({ createEvent })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })

    // Open add form
    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Tambah Acara'))
    await addBtn.trigger('click')

    // Fill and submit
    const form = wrapper.findComponent(EventForm)
    form.vm.$emit('submit', { name: 'Resepsi', date: '2025-12-25' })
    await flushPromises()

    expect(createEvent).toHaveBeenCalled()
  })

  it('dispatches updateEvent when form is submitted in edit mode', async () => {
    const updateEvent = vi.fn(({ commit }, { id, data }) => {
      const updated = { id, ...data }
      commit('UPDATE_ITEM', updated)
      return updated
    })
    const store = createMockStore({
      items: [{ id: '1', name: 'Akad Nikah', date: '2025-12-25' }],
      updateEvent
    })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })

    // Open edit form
    const editBtn = wrapper.findAll('button').find(b => b.text() === 'Edit')
    await editBtn.trigger('click')

    // Submit
    const form = wrapper.findComponent(EventForm)
    form.vm.$emit('submit', { name: 'Akad Nikah Updated', date: '2025-12-26' })
    await flushPromises()

    expect(updateEvent).toHaveBeenCalled()
  })

  it('closes form and dispatches createEvent on submit', async () => {
    const createEvent = vi.fn(({ commit }, data) => {
      const item = { id: 'new-1', ...data }
      commit('ADD_ITEM', item)
      return item
    })
    const store = createMockStore({ createEvent })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })

    // Open add form
    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Tambah Acara'))
    await addBtn.trigger('click')
    expect(wrapper.findComponent(EventForm).exists()).toBe(true)

    // Directly call handleSave to test the async flow
    await wrapper.vm.handleSave({ name: 'Resepsi', date: '2025-12-25' })
    await flushPromises()

    expect(createEvent).toHaveBeenCalled()
    expect(wrapper.vm.showForm).toBe(false)
    expect(wrapper.vm.successMessage).toBe('Acara berhasil ditambahkan.')
  })

  it('sets eventToDelete when delete is clicked', async () => {
    const store = createMockStore({
      items: [{ id: '1', name: 'Akad Nikah', date: '2025-12-25' }]
    })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })

    const deleteBtn = wrapper.findAll('button').find(b => b.text() === 'Hapus')
    await deleteBtn.trigger('click')

    // ConfirmDialog uses Teleport, so we check the component's internal state
    expect(wrapper.vm.showDeleteDialog).toBe(true)
    expect(wrapper.vm.eventToDelete.name).toBe('Akad Nikah')
  })

  it('formats time range correctly', () => {
    const store = createMockStore({
      items: [{ id: '1', name: 'Akad', date: '2025-12-25', startTime: '08:00', endTime: '10:00' }]
    })
    const wrapper = mount(EventsView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('08:00 - 10:00')
  })
})
