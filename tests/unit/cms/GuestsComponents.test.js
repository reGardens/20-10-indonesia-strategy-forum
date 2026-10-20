import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import GuestTable from '@/cms/components/guests/GuestTable.vue'
import GuestForm from '@/cms/components/guests/GuestForm.vue'
import ImportExport from '@/cms/components/guests/ImportExport.vue'
import GuestsView from '@/cms/views/GuestsView.vue'

// --- GuestTable ---
describe('GuestTable', () => {
  const sampleGuests = [
    { id: '1', name: 'Ahmad Fauzi', phone: '628123456789', maxPax: 2 },
    { id: '2', name: 'Siti Aminah', phone: '', maxPax: 1 }
  ]

  it('renders table headers correctly', () => {
    const wrapper = mount(GuestTable, {
      props: { guests: sampleGuests }
    })
    const headers = wrapper.findAll('th')
    expect(headers.length).toBe(4)
    expect(headers[0].text()).toBe('Nama')
    expect(headers[1].text()).toBe('No. Telepon')
    expect(headers[2].text()).toBe('Maks. Tamu')
    expect(headers[3].text()).toBe('Aksi')
  })

  it('renders guest rows', () => {
    const wrapper = mount(GuestTable, {
      props: { guests: sampleGuests }
    })
    const rows = wrapper.findAll('tbody tr')
    expect(rows.length).toBe(2)
    expect(rows[0].text()).toContain('Ahmad Fauzi')
    expect(rows[0].text()).toContain('628123456789')
    expect(rows[0].text()).toContain('2')
  })

  it('shows dash for empty phone', () => {
    const wrapper = mount(GuestTable, {
      props: { guests: [{ id: '1', name: 'Test', phone: '', maxPax: 1 }] }
    })
    const cells = wrapper.findAll('td')
    expect(cells[1].text()).toBe('-')
  })

  it('shows empty message when no guests', () => {
    const wrapper = mount(GuestTable, {
      props: { guests: [] }
    })
    expect(wrapper.text()).toContain('Tidak ada data tamu')
  })

  it('emits edit event when edit button clicked', async () => {
    const wrapper = mount(GuestTable, {
      props: { guests: sampleGuests }
    })
    const editBtn = wrapper.findAll('button').find(b => b.text() === 'Edit')
    await editBtn.trigger('click')
    expect(wrapper.emitted('edit')).toBeTruthy()
    expect(wrapper.emitted('edit')[0][0]).toEqual(sampleGuests[0])
  })

  it('emits delete event when delete button clicked', async () => {
    const wrapper = mount(GuestTable, {
      props: { guests: sampleGuests }
    })
    const deleteBtn = wrapper.findAll('button').find(b => b.text() === 'Hapus')
    await deleteBtn.trigger('click')
    expect(wrapper.emitted('delete')).toBeTruthy()
    expect(wrapper.emitted('delete')[0][0]).toEqual(sampleGuests[0])
  })
})

// --- GuestForm ---
describe('GuestForm', () => {
  it('renders add form title by default', () => {
    const wrapper = mount(GuestForm)
    expect(wrapper.text()).toContain('Tambah Tamu')
  })

  it('renders edit form title when isEditing', () => {
    const wrapper = mount(GuestForm, {
      props: { isEditing: true }
    })
    expect(wrapper.text()).toContain('Edit Tamu')
  })

  it('validates that name is required', async () => {
    const wrapper = mount(GuestForm)
    await wrapper.find('form').trigger('submit')
    expect(wrapper.text()).toContain('Nama tamu wajib diisi')
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('validates maxPax range', async () => {
    const wrapper = mount(GuestForm, {
      props: {
        modelValue: { name: 'Test', phone: '', maxPax: 15 }
      }
    })
    await wrapper.find('form').trigger('submit')
    expect(wrapper.text()).toContain('Jumlah tamu maksimal 10')
  })

  it('emits submit with valid data', async () => {
    const wrapper = mount(GuestForm, {
      props: {
        modelValue: { name: 'Ahmad', phone: '08123', maxPax: 2 }
      }
    })
    await wrapper.find('form').trigger('submit')
    expect(wrapper.emitted('submit')).toBeTruthy()
    const emitted = wrapper.emitted('submit')[0][0]
    expect(emitted.name).toBe('Ahmad')
    expect(emitted.phone).toBe('08123')
    expect(emitted.maxPax).toBe(2)
  })

  it('emits cancel when cancel button clicked', async () => {
    const wrapper = mount(GuestForm)
    const cancelBtn = wrapper.findAll('button').find(b => b.text() === 'Batal')
    await cancelBtn.trigger('click')
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('shows saving state on button', () => {
    const wrapper = mount(GuestForm, {
      props: { saving: true }
    })
    expect(wrapper.text()).toContain('Menyimpan...')
  })
})

// --- ImportExport ---
describe('ImportExport', () => {
  it('renders import and export buttons', () => {
    const wrapper = mount(ImportExport, {
      global: {
        stubs: { Teleport: true }
      }
    })
    expect(wrapper.text()).toContain('Impor Excel')
    expect(wrapper.text()).toContain('Ekspor Excel')
  })

  it('emits export event when export button clicked', async () => {
    const wrapper = mount(ImportExport, {
      global: {
        stubs: { Teleport: true }
      }
    })
    const exportBtn = wrapper.findAll('button').find(b => b.text().includes('Ekspor'))
    await exportBtn.trigger('click')
    expect(wrapper.emitted('export')).toBeTruthy()
  })
})

// --- GuestsView ---
describe('GuestsView', () => {
  function createMockStore(overrides = {}) {
    return createStore({
      modules: {
        guests: {
          namespaced: true,
          state: () => ({
            items: overrides.items || [],
            loading: overrides.loading || false,
            error: overrides.error || null
          }),
          getters: {
            guests: (state) => state.items,
            guestCount: (state) => state.items.length,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          },
          actions: {
            fetchGuests: vi.fn(),
            createGuest: vi.fn(),
            updateGuest: vi.fn(),
            deleteGuest: vi.fn(),
            searchGuests: vi.fn(),
            importGuests: vi.fn(),
            exportGuests: vi.fn()
          }
        }
      }
    })
  }

  it('renders page title', () => {
    const store = createMockStore()
    const wrapper = mount(GuestsView, {
      global: {
        plugins: [store],
        stubs: { Teleport: true }
      }
    })
    expect(wrapper.text()).toContain('Daftar Tamu')
  })

  it('shows loading indicator when loading', () => {
    const store = createMockStore({ loading: true })
    const wrapper = mount(GuestsView, {
      global: {
        plugins: [store],
        stubs: { Teleport: true }
      }
    })
    expect(wrapper.text()).toContain('Memuat daftar tamu...')
  })

  it('shows error message when error exists', () => {
    const store = createMockStore({ error: 'Gagal memuat data' })
    const wrapper = mount(GuestsView, {
      global: {
        plugins: [store],
        stubs: { Teleport: true }
      }
    })
    expect(wrapper.text()).toContain('Gagal memuat data')
  })

  it('renders guest table with data', () => {
    const store = createMockStore({
      items: [
        { id: '1', name: 'Ahmad', phone: '08123', maxPax: 2 }
      ]
    })
    const wrapper = mount(GuestsView, {
      global: {
        plugins: [store],
        stubs: { Teleport: true }
      }
    })
    expect(wrapper.text()).toContain('Ahmad')
    expect(wrapper.text()).toContain('Total: 1 tamu')
  })

  it('shows search bar', () => {
    const store = createMockStore()
    const wrapper = mount(GuestsView, {
      global: {
        plugins: [store],
        stubs: { Teleport: true }
      }
    })
    const searchInput = wrapper.find('#guest-search')
    expect(searchInput.exists()).toBe(true)
  })

  it('dispatches fetchGuests on mount', () => {
    const fetchGuests = vi.fn()
    const store = createStore({
      modules: {
        guests: {
          namespaced: true,
          state: () => ({ items: [], loading: false, error: null }),
          getters: {
            guests: (state) => state.items,
            guestCount: (state) => state.items.length,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          },
          actions: {
            fetchGuests,
            createGuest: vi.fn(),
            updateGuest: vi.fn(),
            deleteGuest: vi.fn(),
            searchGuests: vi.fn(),
            importGuests: vi.fn(),
            exportGuests: vi.fn()
          }
        }
      }
    })
    mount(GuestsView, {
      global: {
        plugins: [store],
        stubs: { Teleport: true }
      }
    })
    expect(fetchGuests).toHaveBeenCalled()
  })
})

import WhatsAppLinkGenerator from '@/cms/components/guests/WhatsAppLinkGenerator.vue'
import QrCodeGenerator from '@/cms/components/guests/QrCodeGenerator.vue'

// --- WhatsAppLinkGenerator ---
describe('WhatsAppLinkGenerator', () => {
  const sampleGuests = [
    { id: 'g1', name: 'Ahmad Fauzi', phone: '628123456789', maxPax: 2 },
    { id: 'g2', name: 'Siti Aminah', phone: '628987654321', maxPax: 1 },
    { id: 'g3', name: 'Budi Santoso', phone: '', maxPax: 1 }
  ]

  const defaultProps = {
    guests: sampleGuests,
    domain: 'https://example.com',
    weddingSlug: 'budi-ani'
  }

  it('renders component title', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    expect(wrapper.text()).toContain('Generator Link WhatsApp')
  })

  it('renders Generate Semua button', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    const btn = wrapper.findAll('button').find(b => b.text().includes('Generate Semua'))
    expect(btn).toBeTruthy()
  })

  it('shows empty message when no guests', () => {
    const wrapper = mount(WhatsAppLinkGenerator, {
      props: { ...defaultProps, guests: [] }
    })
    expect(wrapper.text()).toContain('Tidak ada tamu untuk di-generate link-nya')
  })

  it('renders guest names in the list', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    expect(wrapper.text()).toContain('Ahmad Fauzi')
    expect(wrapper.text()).toContain('Siti Aminah')
    expect(wrapper.text()).toContain('Budi Santoso')
  })

  it('shows WhatsApp button for guests with phone numbers', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    const waLinks = wrapper.findAll('a').filter(a => a.text().includes('WhatsApp'))
    // Only guests with phone numbers get WhatsApp links (g1 and g2)
    expect(waLinks.length).toBe(2)
  })

  it('shows "No HP kosong" for guests without phone', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    expect(wrapper.text()).toContain('No HP kosong')
  })

  it('generates bulk links when Generate Semua is clicked', async () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    const btn = wrapper.findAll('button').find(b => b.text().includes('Generate Semua'))
    await btn.trigger('click')
    // After bulk generation, invitation URLs should appear
    expect(wrapper.text()).toContain('https://example.com/wedding/budi-ani')
  })

  it('WhatsApp link has correct href format', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    const waLink = wrapper.findAll('a').find(a => a.text().includes('WhatsApp'))
    const href = waLink.attributes('href')
    expect(href).toContain('https://wa.me/628123456789')
    expect(href).toContain('text=')
  })

  it('WhatsApp links open in new tab', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    const waLink = wrapper.findAll('a').find(a => a.text().includes('WhatsApp'))
    expect(waLink.attributes('target')).toBe('_blank')
    expect(waLink.attributes('rel')).toContain('noopener')
  })

  it('disables Generate Semua button when no guests', () => {
    const wrapper = mount(WhatsAppLinkGenerator, {
      props: { ...defaultProps, guests: [] }
    })
    const btn = wrapper.findAll('button').find(b => b.text().includes('Generate Semua'))
    expect(btn.attributes('disabled')).toBeDefined()
  })

  it('has accessible aria-labels on action buttons', () => {
    const wrapper = mount(WhatsAppLinkGenerator, { props: defaultProps })
    const buttons = wrapper.findAll('button[aria-label], a[aria-label]')
    expect(buttons.length).toBeGreaterThan(0)
  })
})

// --- QrCodeGenerator ---
describe('QrCodeGenerator', () => {
  const defaultProps = {
    guest: { id: 'guest-123', name: 'Ahmad Fauzi' },
    weddingSlug: 'budi-ani'
  }

  it('renders component title', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: defaultProps,
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    expect(wrapper.text()).toContain('QR Code Tamu')
  })

  it('renders guest name', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: defaultProps,
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    expect(wrapper.text()).toContain('Ahmad Fauzi')
  })

  it('renders QR code component when guest data is valid', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: defaultProps,
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    expect(wrapper.find('.qr-stub').exists()).toBe(true)
  })

  it('shows fallback message when guest data is missing', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: { guest: { id: '', name: '' }, weddingSlug: '' },
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    expect(wrapper.text()).toContain('Data tamu tidak tersedia')
  })

  it('renders download button', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: defaultProps,
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    const btn = wrapper.findAll('button').find(b => b.text().includes('Download QR'))
    expect(btn).toBeTruthy()
  })

  it('has accessible aria-label on download button', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: defaultProps,
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    const btn = wrapper.find('button[aria-label]')
    expect(btn.exists()).toBe(true)
    expect(btn.attributes('aria-label')).toContain('Ahmad Fauzi')
  })

  it('shows empty state when guest prop has no id', () => {
    const wrapper = mount(QrCodeGenerator, {
      props: { guest: { id: '', name: 'Test' }, weddingSlug: 'test' },
      global: {
        stubs: { QrcodeVue: { template: '<div class="qr-stub"></div>', props: ['value', 'size', 'level', 'renderAs'] } }
      }
    })
    expect(wrapper.text()).toContain('Data tamu tidak tersedia')
  })
})
