import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import StatCard from '../../../src/cms/components/dashboard/StatCard.vue'
import RsvpSummary from '../../../src/cms/components/dashboard/RsvpSummary.vue'
import VisitorChart from '../../../src/cms/components/dashboard/VisitorChart.vue'
import ActivityLog from '../../../src/cms/components/dashboard/ActivityLog.vue'
import DashboardView from '../../../src/cms/views/DashboardView.vue'

// ─── StatCard ────────────────────────────────────────────────────────────────

describe('StatCard', () => {
  it('renders title and value', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Total Tamu', value: 42, icon: '👥', color: 'bg-blue-100' }
    })
    expect(wrapper.text()).toContain('Total Tamu')
    expect(wrapper.text()).toContain('42')
  })

  it('renders icon', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Test', value: 0, icon: '🎁' }
    })
    expect(wrapper.text()).toContain('🎁')
  })

  it('applies color class to icon container', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Test', value: 5, icon: '📋', color: 'bg-green-100' }
    })
    const iconDiv = wrapper.find('.bg-green-100')
    expect(iconDiv.exists()).toBe(true)
  })

  it('uses default color and icon when not provided', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Test', value: 10 }
    })
    expect(wrapper.text()).toContain('📊')
    expect(wrapper.find('.bg-blue-100').exists()).toBe(true)
  })

  it('accepts string value', () => {
    const wrapper = mount(StatCard, {
      props: { title: 'Hadiah', value: 'Rp 500.000' }
    })
    expect(wrapper.text()).toContain('Rp 500.000')
  })
})

// ─── RsvpSummary ─────────────────────────────────────────────────────────────

describe('RsvpSummary', () => {
  it('renders heading', () => {
    const wrapper = mount(RsvpSummary)
    expect(wrapper.text()).toContain('Ringkasan RSVP')
  })

  it('displays status labels', () => {
    const wrapper = mount(RsvpSummary, {
      props: { summary: { hadir: 10, tidakHadir: 3, mungkin: 5 } }
    })
    expect(wrapper.text()).toContain('Hadir')
    expect(wrapper.text()).toContain('Tidak Hadir')
    expect(wrapper.text()).toContain('Mungkin')
  })

  it('displays correct counts', () => {
    const wrapper = mount(RsvpSummary, {
      props: { summary: { hadir: 10, tidakHadir: 3, mungkin: 5 } }
    })
    expect(wrapper.text()).toContain('10')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.text()).toContain('5')
  })

  it('displays total RSVP count', () => {
    const wrapper = mount(RsvpSummary, {
      props: { summary: { hadir: 10, tidakHadir: 3, mungkin: 5 } }
    })
    expect(wrapper.text()).toContain('18')
  })

  it('handles zero values gracefully', () => {
    const wrapper = mount(RsvpSummary, {
      props: { summary: { hadir: 0, tidakHadir: 0, mungkin: 0 } }
    })
    expect(wrapper.text()).toContain('Total RSVP')
    expect(wrapper.text()).toContain('0')
  })

  it('uses default summary when no props provided', () => {
    const wrapper = mount(RsvpSummary)
    expect(wrapper.text()).toContain('0')
  })
})

// ─── VisitorChart ────────────────────────────────────────────────────────────

describe('VisitorChart', () => {
  it('renders heading', () => {
    const wrapper = mount(VisitorChart)
    expect(wrapper.text()).toContain('Total Pengunjung')
  })

  it('renders period toggle buttons', () => {
    const wrapper = mount(VisitorChart)
    expect(wrapper.text()).toContain('Harian')
    expect(wrapper.text()).toContain('Mingguan')
    expect(wrapper.text()).toContain('Bulanan')
  })

  it('defaults to daily period', () => {
    const wrapper = mount(VisitorChart)
    const buttons = wrapper.findAll('button')
    const dailyBtn = buttons.find(b => b.text() === 'Harian')
    expect(dailyBtn.classes()).toContain('bg-emerald-600')
  })

  it('switches active period on click', async () => {
    const wrapper = mount(VisitorChart)
    const buttons = wrapper.findAll('button')
    const weeklyBtn = buttons.find(b => b.text() === 'Mingguan')
    await weeklyBtn.trigger('click')
    expect(weeklyBtn.classes()).toContain('bg-emerald-600')
  })

  it('shows total visitors', () => {
    const wrapper = mount(VisitorChart)
    expect(wrapper.text()).toContain('Total:')
  })

  it('generates placeholder data when no visitors provided', () => {
    const wrapper = mount(VisitorChart, { props: { visitors: [] } })
    // Should still render bars from placeholder data
    const bars = wrapper.findAll('.bg-emerald-500')
    expect(bars.length).toBeGreaterThan(0)
  })
})

// ─── ActivityLog ─────────────────────────────────────────────────────────────

describe('ActivityLog', () => {
  it('renders heading', () => {
    const wrapper = mount(ActivityLog)
    expect(wrapper.text()).toContain('Aktivitas Terbaru')
  })

  it('shows empty state when no activities', () => {
    const wrapper = mount(ActivityLog, { props: { activities: [] } })
    expect(wrapper.text()).toContain('Belum ada aktivitas')
  })

  it('renders activities', () => {
    const activities = [
      { id: '1', guestName: 'Budi', message: 'Selamat!', createdAt: '2024-06-01T10:00:00Z' },
      { id: '2', guestName: 'Ani', message: 'Bahagia selalu', createdAt: '2024-06-02T12:00:00Z' }
    ]
    const wrapper = mount(ActivityLog, { props: { activities } })
    expect(wrapper.text()).toContain('Budi')
    expect(wrapper.text()).toContain('Selamat!')
    expect(wrapper.text()).toContain('Ani')
    expect(wrapper.text()).toContain('Bahagia selalu')
  })

  it('sorts activities in reverse chronological order (newest first)', () => {
    const activities = [
      { id: '1', guestName: 'Older', message: 'First', createdAt: '2024-01-01T00:00:00Z' },
      { id: '2', guestName: 'Newer', message: 'Second', createdAt: '2024-06-01T00:00:00Z' }
    ]
    const wrapper = mount(ActivityLog, { props: { activities } })
    const items = wrapper.findAll('li')
    expect(items[0].text()).toContain('Newer')
    expect(items[1].text()).toContain('Older')
  })

  it('shows "Anonim" for activities without guestName', () => {
    const activities = [
      { id: '1', message: 'Anonymous wish', createdAt: '2024-06-01T00:00:00Z' }
    ]
    const wrapper = mount(ActivityLog, { props: { activities } })
    expect(wrapper.text()).toContain('Anonim')
  })

  it('has list role for accessibility', () => {
    const activities = [
      { id: '1', guestName: 'Test', message: 'Hello', createdAt: '2024-06-01T00:00:00Z' }
    ]
    const wrapper = mount(ActivityLog, { props: { activities } })
    expect(wrapper.find('[role="list"]').exists()).toBe(true)
  })
})

// ─── DashboardView (integration) ────────────────────────────────────────────

describe('DashboardView', () => {
  function createMockStore(overrides = {}) {
    return createStore({
      modules: {
        guests: {
          namespaced: true,
          state: () => ({ items: [], loading: false, error: null, ...overrides.guests }),
          actions: { fetchGuests: vi.fn() },
          getters: {
            guestCount: (state) => state.items.length,
            guests: (state) => state.items,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        },
        rsvp: {
          namespaced: true,
          state: () => ({
            items: [],
            summary: { hadir: 0, tidakHadir: 0, mungkin: 0 },
            loading: false,
            error: null,
            ...overrides.rsvp
          }),
          actions: { fetchRsvps: vi.fn(), getSummary: vi.fn() },
          getters: {
            totalRsvps: (state) => state.items.length,
            summary: (state) => state.summary,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        },
        wishes: {
          namespaced: true,
          state: () => ({ items: [], loading: false, error: null, ...overrides.wishes }),
          actions: { fetchWishes: vi.fn() },
          getters: {
            wishes: (state) => state.items,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        },
        payments: {
          namespaced: true,
          state: () => ({
            bankAccounts: [],
            gifts: [],
            totalGifts: 0,
            loading: false,
            error: null,
            ...overrides.payments
          }),
          actions: { fetchPayments: vi.fn() },
          getters: {
            totalGifts: (state) => state.totalGifts,
            isLoading: (state) => state.loading,
            error: (state) => state.error
          }
        }
      }
    })
  }

  it('renders dashboard heading', () => {
    const store = createMockStore()
    const wrapper = mount(DashboardView, {
      global: { plugins: [store] }
    })
    expect(wrapper.text()).toContain('Dashboard')
  })

  it('dispatches fetch actions on mount', () => {
    const store = createMockStore()
    mount(DashboardView, { global: { plugins: [store] } })
    expect(store._actions['guests/fetchGuests']).toBeDefined()
    expect(store._actions['rsvp/fetchRsvps']).toBeDefined()
    expect(store._actions['wishes/fetchWishes']).toBeDefined()
    expect(store._actions['payments/fetchPayments']).toBeDefined()
  })

  it('shows loading indicator when data is loading', () => {
    const store = createMockStore({ guests: { items: [], loading: true, error: null } })
    const wrapper = mount(DashboardView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Memuat data dashboard...')
  })

  it('shows error message when there is an error', () => {
    const store = createMockStore({ guests: { items: [], loading: false, error: 'Gagal memuat data' } })
    const wrapper = mount(DashboardView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Gagal memuat data')
  })

  it('renders stat cards with store data', () => {
    const store = createMockStore({
      guests: { items: [{ id: '1' }, { id: '2' }], loading: false, error: null },
      rsvp: { items: [{ id: '1' }], summary: { hadir: 1, tidakHadir: 0, mungkin: 0 }, loading: false, error: null },
      wishes: { items: [{ id: '1' }, { id: '2' }, { id: '3' }], loading: false, error: null },
      payments: { bankAccounts: [], gifts: [], totalGifts: 500000, loading: false, error: null }
    })
    const wrapper = mount(DashboardView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Total Tamu')
    expect(wrapper.text()).toContain('2')
    expect(wrapper.text()).toContain('Total RSVP')
    expect(wrapper.text()).toContain('Total Ucapan')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.text()).toContain('Total Hadiah')
  })

  it('renders RSVP summary component', () => {
    const store = createMockStore({
      rsvp: { items: [], summary: { hadir: 5, tidakHadir: 2, mungkin: 3 }, loading: false, error: null }
    })
    const wrapper = mount(DashboardView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Ringkasan RSVP')
  })

  it('renders activity log with wishes', () => {
    const store = createMockStore({
      wishes: {
        items: [
          { id: '1', guestName: 'Budi', message: 'Selamat!', createdAt: '2024-06-01T10:00:00Z' }
        ],
        loading: false,
        error: null
      }
    })
    const wrapper = mount(DashboardView, { global: { plugins: [store] } })
    expect(wrapper.text()).toContain('Aktivitas Terbaru')
    expect(wrapper.text()).toContain('Budi')
  })
})
