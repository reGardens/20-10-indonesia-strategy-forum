import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import CheckInView from '@/cms/views/CheckInView.vue'
import { useQrCode } from '@/composables/useQrCode.js'

// Mock vue-qrcode-reader — camera not available in jsdom
vi.mock('vue-qrcode-reader', () => ({
  QrcodeStream: {
    name: 'QrcodeStream',
    template: '<div class="qrcode-stream-stub"></div>',
    emits: ['detect', 'error']
  }
}))

function createMockStore(overrides = {}) {
  const actions = {
    fetchGuests: overrides.fetchGuests || vi.fn(),
    updateGuest: overrides.updateGuest || vi.fn(),
  }
  return {
    store: createStore({
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
          actions
        }
      }
    }),
    actions
  }
}

function mountView(overrides = {}) {
  const { store, actions } = createMockStore(overrides)
  const wrapper = mount(CheckInView, {
    global: {
      plugins: [store]
    }
  })
  return { wrapper, store, actions }
}

describe('CheckInView', () => {
  it('renders page title', () => {
    const { wrapper } = mountView()
    expect(wrapper.text()).toContain('Check-In Tamu')
  })

  it('renders scanner section title', () => {
    const { wrapper } = mountView()
    expect(wrapper.text()).toContain('Pemindai QR Code')
  })

  it('renders QrcodeStream component', () => {
    const { wrapper } = mountView()
    expect(wrapper.find('.qrcode-stream-stub').exists()).toBe(true)
  })

  it('renders scanner instruction text', () => {
    const { wrapper } = mountView()
    expect(wrapper.text()).toContain('Arahkan kamera ke QR code tamu')
  })

  it('dispatches fetchGuests on mount', () => {
    const fetchGuests = vi.fn()
    mountView({ fetchGuests })
    expect(fetchGuests).toHaveBeenCalled()
  })

  it('shows loading indicator when loading', () => {
    const { wrapper } = mountView({ loading: true })
    expect(wrapper.text()).toContain('Memuat data tamu...')
  })

  it('shows error message when error exists', () => {
    const { wrapper } = mountView({ error: 'Gagal memuat data' })
    expect(wrapper.text()).toContain('Gagal memuat data')
  })

  it('shows empty check-in message when no guests checked in', () => {
    const { wrapper } = mountView({ items: [{ id: '1', name: 'Test' }] })
    expect(wrapper.text()).toContain('Belum ada tamu yang check-in')
  })

  it('shows recent check-ins table when guests have checked in', () => {
    const { wrapper } = mountView({
      items: [
        { id: '1', name: 'Ahmad Fauzi', checkedInAt: '2024-06-15T10:30:00Z' },
        { id: '2', name: 'Siti Aminah' }
      ]
    })
    expect(wrapper.text()).toContain('Ahmad Fauzi')
    expect(wrapper.text()).not.toContain('Belum ada tamu yang check-in')
  })

  it('sorts recent check-ins by newest first', () => {
    const { wrapper } = mountView({
      items: [
        { id: '1', name: 'First', checkedInAt: '2024-06-15T08:00:00Z' },
        { id: '2', name: 'Second', checkedInAt: '2024-06-15T10:00:00Z' }
      ]
    })
    const rows = wrapper.findAll('tbody tr')
    expect(rows[0].text()).toContain('Second')
    expect(rows[1].text()).toContain('First')
  })
})

describe('CheckInView - QR scan processing', () => {
  const { encode } = useQrCode()

  it('shows invalid QR message for non-decodable QR', async () => {
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad' }]
    })

    // Simulate QR detect with invalid data
    const vm = wrapper.vm
    await vm.processQrCode('not-a-valid-qr')

    expect(wrapper.text()).toContain('QR code tidak valid')
  })

  it('shows guest not found for unknown guestId', async () => {
    const validQr = encode({ guestId: 'unknown-id', weddingSlug: 'test-wedding' })
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad' }]
    })

    await wrapper.vm.processQrCode(validQr)

    expect(wrapper.text()).toContain('Tamu tidak ditemukan')
  })

  it('shows already checked in message for previously checked-in guest', async () => {
    const validQr = encode({ guestId: 'g1', weddingSlug: 'test-wedding' })
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad Fauzi', checkedInAt: '2024-06-15T10:30:00Z' }]
    })

    await wrapper.vm.processQrCode(validQr)

    expect(wrapper.text()).toContain('Tamu sudah tercatat hadir pada')
    expect(wrapper.text()).toContain('Ahmad Fauzi')
  })

  it('does not dispatch updateGuest for already checked-in guest (idempotent)', async () => {
    const validQr = encode({ guestId: 'g1', weddingSlug: 'test-wedding' })
    const updateGuest = vi.fn()
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad', checkedInAt: '2024-06-15T10:30:00Z' }],
      updateGuest
    })

    await wrapper.vm.processQrCode(validQr)

    expect(updateGuest).not.toHaveBeenCalled()
  })

  it('performs check-in for new guest and dispatches updateGuest', async () => {
    const validQr = encode({ guestId: 'g1', weddingSlug: 'test-wedding' })
    const updateGuest = vi.fn()
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad Fauzi' }],
      updateGuest
    })

    await wrapper.vm.processQrCode(validQr)

    expect(updateGuest).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        id: 'g1',
        data: expect.objectContaining({ checkedInAt: expect.any(String) })
      })
    )
    expect(wrapper.text()).toContain('Check-in berhasil: Ahmad Fauzi')
  })

  it('shows error message when updateGuest fails', async () => {
    const validQr = encode({ guestId: 'g1', weddingSlug: 'test-wedding' })
    const updateGuest = vi.fn().mockRejectedValue(new Error('Network error'))
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad' }],
      updateGuest
    })

    await wrapper.vm.processQrCode(validQr)

    expect(wrapper.text()).toContain('Gagal melakukan check-in')
  })

  it('shows Scan Lagi button after a scan result', async () => {
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad' }]
    })

    await wrapper.vm.processQrCode('invalid-qr')

    const scanAgainBtn = wrapper.findAll('button').find(b => b.text().includes('Scan Lagi'))
    expect(scanAgainBtn).toBeTruthy()
  })

  it('resets result when Scan Lagi is clicked', async () => {
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad' }]
    })

    await wrapper.vm.processQrCode('invalid-qr')
    expect(wrapper.text()).toContain('QR code tidak valid')

    const scanAgainBtn = wrapper.findAll('button').find(b => b.text().includes('Scan Lagi'))
    await scanAgainBtn.trigger('click')

    expect(wrapper.text()).not.toContain('QR code tidak valid')
    // Scanner should be visible again
    expect(wrapper.find('.qrcode-stream-stub').exists()).toBe(true)
  })

  it('shows success card with green styling for new check-in', async () => {
    const validQr = encode({ guestId: 'g1', weddingSlug: 'test-wedding' })
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad' }]
    })

    await wrapper.vm.processQrCode(validQr)

    const resultCard = wrapper.find('[role="status"]')
    expect(resultCard.classes()).toContain('bg-green-50')
  })

  it('shows warning card with yellow styling for already checked-in', async () => {
    const validQr = encode({ guestId: 'g1', weddingSlug: 'test-wedding' })
    const { wrapper } = mountView({
      items: [{ id: 'g1', name: 'Ahmad', checkedInAt: '2024-06-15T10:30:00Z' }]
    })

    await wrapper.vm.processQrCode(validQr)

    const resultCard = wrapper.find('[role="status"]')
    expect(resultCard.classes()).toContain('bg-yellow-50')
  })

  it('shows error card with red styling for invalid QR', async () => {
    const { wrapper } = mountView()

    await wrapper.vm.processQrCode('bad-data')

    const resultCard = wrapper.find('[role="status"]')
    expect(resultCard.classes()).toContain('bg-red-50')
  })
})
