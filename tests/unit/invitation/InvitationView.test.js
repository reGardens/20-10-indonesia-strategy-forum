import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { defineComponent, h } from 'vue'
import InvitationView from '@/invitation/views/InvitationView.vue'

// Stub route
const mockRoute = { params: { slug: 'budi-ani' } }
vi.mock('vue-router', () => ({
  useRoute: () => mockRoute
}))

// Mock services
const mockCoupleData = {
  id: '1',
  weddingSlug: 'budi-ani',
  groom: { fullName: 'Budi', nickname: 'Budi' },
  bride: { fullName: 'Ani', nickname: 'Ani' },
  musicUrl: '',
  createdAt: '',
  updatedAt: ''
}

const mockEventsData = [
  { id: 'e1', name: 'Akad Nikah', date: '2025-12-25', startTime: '08:00', endTime: '10:00', venueName: 'Masjid', address: 'Jakarta', latitude: -6.17, longitude: 106.83 }
]

const mockSettingsData = {
  id: 's1',
  templateId: 'batik-elegance',
  themeColors: { primary: '#8B4513', secondary: '#D2691E', accent: '#FFD700' },
  galleryLayout: 'masonry',
  moderationEnabled: true,
  showWatermark: true
}

const mockMediaData = [
  { id: 'm1', fileName: 'photo.webp', type: 'image', sortOrder: 1 }
]

vi.mock('@/api/services/coupleService.js', () => ({
  coupleService: {
    get: vi.fn()
  }
}))

vi.mock('@/api/services/eventService.js', () => ({
  eventService: {
    getAll: vi.fn()
  }
}))

vi.mock('@/api/services/settingsService.js', () => ({
  settingsService: {
    get: vi.fn()
  }
}))

vi.mock('@/api/services/mediaService.js', () => ({
  mediaService: {
    getAll: vi.fn()
  }
}))

vi.mock('@/api/services/weddingRegistryService.js', () => ({
  weddingRegistryService: {
    exists: vi.fn(() => true)
  }
}))

// Fake template component
const FakeTemplate = defineComponent({
  name: 'FakeTemplate',
  props: ['couple', 'events', 'settings', 'media', 'slug'],
  setup(props) {
    return () => h('div', { class: 'fake-template' }, `Template for ${props.slug}`)
  }
})

vi.mock('@/invitation/templates/index.js', () => ({
  getTemplate: vi.fn()
}))

// Import mocked modules
import { coupleService } from '@/api/services/coupleService.js'
import { eventService } from '@/api/services/eventService.js'
import { settingsService } from '@/api/services/settingsService.js'
import { mediaService } from '@/api/services/mediaService.js'
import { weddingRegistryService } from '@/api/services/weddingRegistryService.js'
import { getTemplate } from '@/invitation/templates/index.js'

describe('InvitationView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Default: slug exists in registry
    weddingRegistryService.exists.mockReturnValue(true)
  })

  it('shows loading state initially', () => {
    // Services never resolve so loading stays true
    coupleService.get.mockReturnValue(new Promise(() => {}))
    eventService.getAll.mockReturnValue(new Promise(() => {}))
    settingsService.get.mockReturnValue(new Promise(() => {}))
    mediaService.getAll.mockReturnValue(new Promise(() => {}))

    const wrapper = mount(InvitationView)
    expect(wrapper.text()).toContain('Memuat undangan')
  })

  it('shows not found error when slug is not registered', async () => {
    weddingRegistryService.exists.mockReturnValue(false)

    const wrapper = mount(InvitationView)
    await flushPromises()

    expect(wrapper.text()).toContain('Undangan Tidak Ditemukan')
    expect(wrapper.text()).toContain('Undangan tidak ditemukan. Pastikan URL yang Anda akses sudah benar.')
    expect(coupleService.get).not.toHaveBeenCalled()
    expect(eventService.getAll).not.toHaveBeenCalled()
    expect(settingsService.get).not.toHaveBeenCalled()
    expect(mediaService.getAll).not.toHaveBeenCalled()
  })

  it('shows error state when data loading fails', async () => {
    coupleService.get.mockRejectedValue(new Error('Gagal memuat'))
    eventService.getAll.mockRejectedValue(new Error('Gagal memuat'))
    settingsService.get.mockRejectedValue(new Error('Gagal memuat'))
    mediaService.getAll.mockRejectedValue(new Error('Gagal memuat'))

    const wrapper = mount(InvitationView)
    await flushPromises()

    expect(wrapper.text()).toContain('Undangan Tidak Ditemukan')
    expect(wrapper.text()).toContain('Gagal memuat')
    expect(wrapper.find('button').text()).toContain('Coba Lagi')
  })

  it('shows template not found fallback when template is not in registry', async () => {
    coupleService.get.mockResolvedValue(mockCoupleData)
    eventService.getAll.mockResolvedValue(mockEventsData)
    settingsService.get.mockResolvedValue(mockSettingsData)
    mediaService.getAll.mockResolvedValue(mockMediaData)
    getTemplate.mockReturnValue(undefined)

    const wrapper = mount(InvitationView)
    await flushPromises()

    expect(wrapper.text()).toContain('Template Tidak Tersedia')
  })

  it('renders the resolved template component with correct props', async () => {
    coupleService.get.mockResolvedValue(mockCoupleData)
    eventService.getAll.mockResolvedValue(mockEventsData)
    settingsService.get.mockResolvedValue(mockSettingsData)
    mediaService.getAll.mockResolvedValue(mockMediaData)
    getTemplate.mockReturnValue({
      id: 'batik-elegance',
      component: () => Promise.resolve({ default: FakeTemplate })
    })

    const wrapper = mount(InvitationView)
    await flushPromises()

    expect(wrapper.find('.fake-template').exists()).toBe(true)
    expect(wrapper.text()).toContain('Template for budi-ani')
  })

  it('calls all four services on mount with slug parameter', async () => {
    coupleService.get.mockResolvedValue(mockCoupleData)
    eventService.getAll.mockResolvedValue(mockEventsData)
    settingsService.get.mockResolvedValue(mockSettingsData)
    mediaService.getAll.mockResolvedValue(mockMediaData)
    getTemplate.mockReturnValue(undefined)

    mount(InvitationView)
    await flushPromises()

    expect(coupleService.get).toHaveBeenCalledWith('budi-ani')
    expect(eventService.getAll).toHaveBeenCalledWith('budi-ani')
    expect(settingsService.get).toHaveBeenCalledWith('budi-ani')
    expect(mediaService.getAll).toHaveBeenCalledWith('budi-ani')
  })

  it('retries loading when retry button is clicked', async () => {
    coupleService.get.mockRejectedValue(new Error('Network error'))
    eventService.getAll.mockRejectedValue(new Error('Network error'))
    settingsService.get.mockRejectedValue(new Error('Network error'))
    mediaService.getAll.mockRejectedValue(new Error('Network error'))

    const wrapper = mount(InvitationView)
    await flushPromises()

    expect(wrapper.text()).toContain('Undangan Tidak Ditemukan')

    // Now make services succeed on retry
    coupleService.get.mockResolvedValue(mockCoupleData)
    eventService.getAll.mockResolvedValue(mockEventsData)
    settingsService.get.mockResolvedValue(mockSettingsData)
    mediaService.getAll.mockResolvedValue(mockMediaData)
    getTemplate.mockReturnValue(undefined)

    await wrapper.find('button').trigger('click')
    await flushPromises()

    // Error should be gone, now showing template fallback
    expect(wrapper.text()).toContain('Template Tidak Tersedia')
  })

  it('handles template component load failure gracefully', async () => {
    coupleService.get.mockResolvedValue(mockCoupleData)
    eventService.getAll.mockResolvedValue(mockEventsData)
    settingsService.get.mockResolvedValue(mockSettingsData)
    mediaService.getAll.mockResolvedValue(mockMediaData)
    getTemplate.mockReturnValue({
      id: 'batik-elegance',
      component: () => Promise.reject(new Error('Chunk load failed'))
    })

    const wrapper = mount(InvitationView)
    await flushPromises()

    // Should show template not found fallback, not error
    expect(wrapper.text()).toContain('Template Tidak Tersedia')
  })

  it('defaults to batik-elegance when templateId is missing', async () => {
    coupleService.get.mockResolvedValue(mockCoupleData)
    eventService.getAll.mockResolvedValue(mockEventsData)
    settingsService.get.mockResolvedValue({ ...mockSettingsData, templateId: '' })
    mediaService.getAll.mockResolvedValue(mockMediaData)
    getTemplate.mockReturnValue(undefined)

    mount(InvitationView)
    await flushPromises()

    expect(getTemplate).toHaveBeenCalledWith('batik-elegance')
  })
})
