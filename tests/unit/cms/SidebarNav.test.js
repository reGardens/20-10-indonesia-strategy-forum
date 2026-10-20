import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createStore } from 'vuex'
import SidebarNav from '../../../src/cms/components/shared/SidebarNav.vue'

function createMockStore(overrides = {}) {
  return createStore({
    modules: {
      wedding: {
        namespaced: true,
        state: () => ({
          activeSlug: overrides.activeSlug || null,
          registry: overrides.registry || []
        }),
        getters: {
          activeSlug: (state) => state.activeSlug,
          activeWedding: (state) => state.registry.find(w => w.slug === state.activeSlug) || null,
          registry: (state) => state.registry
        }
      },
      couple: {
        namespaced: true,
        state: () => ({
          data: overrides.coupleData || null,
          loading: false,
          error: null
        }),
        actions: {
          fetchCouple: () => {}
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

function createTestRouter(initialRoute = '/cms/test-wedding/dashboard') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/cms', name: 'cms-selector', component: { template: '<div>Selector</div>' } },
      {
        path: '/cms/:slug',
        children: [
          { path: 'dashboard', name: 'cms-dashboard', component: { template: '<div>Dashboard</div>' } },
          { path: 'couple', name: 'cms-couple', component: { template: '<div>Couple</div>' } },
          { path: 'events', name: 'cms-events', component: { template: '<div>Events</div>' } },
          { path: 'template', name: 'cms-template', component: { template: '<div>Template</div>' } },
          { path: 'guests', name: 'cms-guests', component: { template: '<div>Guests</div>' } },
          { path: 'checkin', name: 'cms-checkin', component: { template: '<div>CheckIn</div>' } },
          { path: 'media', name: 'cms-media', component: { template: '<div>Media</div>' } },
          { path: 'payment', name: 'cms-payment', component: { template: '<div>Payment</div>' } },
          { path: 'rsvp', name: 'cms-rsvp', component: { template: '<div>RSVP</div>' } },
          { path: 'settings', name: 'cms-settings', component: { template: '<div>Settings</div>' } }
        ]
      }
    ]
  })
  router.push(initialRoute)
  return router
}

async function mountSidebarNav(initialRoute = '/cms/test-wedding/dashboard', storeOverrides = {}) {
  const router = createTestRouter(initialRoute)
  const store = createMockStore(storeOverrides)
  await router.isReady()
  return mount(SidebarNav, {
    global: {
      plugins: [router, store]
    }
  })
}

describe('SidebarNav', () => {
  it('renders all 8 menu items plus Ganti Pernikahan link', async () => {
    const wrapper = await mountSidebarNav()
    const links = wrapper.findAll('a')
    // 8 menu items + 1 "Ganti Pernikahan" link = 9
    expect(links.length).toBe(9)
  })

  it('displays correct menu labels', async () => {
    const wrapper = await mountSidebarNav()
    const expectedLabels = [
      'Dashboard',
      'Data Pernikahan',
      'Template & Preview',
      'Daftar Tamu',
      'Media',
      'Pembayaran & Hadiah',
      'RSVP & Ucapan',
      'Pengaturan'
    ]
    for (const label of expectedLabels) {
      expect(wrapper.text()).toContain(label)
    }
  })

  it('highlights Dashboard when on /cms/:slug/dashboard', async () => {
    const wrapper = await mountSidebarNav('/cms/test-wedding/dashboard')
    const links = wrapper.findAll('a')
    const dashboardLink = links.find(l => l.text().includes('Dashboard') && !l.text().includes('Ganti'))
    expect(dashboardLink.classes()).toContain('bg-emerald-600')
    expect(dashboardLink.classes()).toContain('text-white')
  })

  it('displays "Ganti Pernikahan" link', async () => {
    const wrapper = await mountSidebarNav()
    expect(wrapper.text()).toContain('Ganti Pernikahan')
  })

  it('displays couple names from data when available', async () => {
    const wrapper = await mountSidebarNav('/cms/budi-ani/dashboard', {
      activeSlug: 'budi-ani',
      registry: [{ id: '1', slug: 'budi-ani', label: 'Pernikahan Budi & Ani', createdAt: '2024-01-01' }],
      coupleData: {
        groom: { fullName: 'Budi Santoso', nickname: 'Budi' },
        bride: { fullName: 'Ani Rahayu', nickname: 'Ani' }
      }
    })
    expect(wrapper.text()).toContain('Budi & Ani')
  })

  it('falls back to registry label when no couple data', async () => {
    const wrapper = await mountSidebarNav('/cms/budi-ani/dashboard', {
      activeSlug: 'budi-ani',
      registry: [{ id: '1', slug: 'budi-ani', label: 'Pernikahan Budi & Ani', createdAt: '2024-01-01' }],
      coupleData: null
    })
    expect(wrapper.text()).toContain('Pernikahan Budi & Ani')
  })

  it('displays "Wedding CMS" when no active wedding', async () => {
    const wrapper = await mountSidebarNav('/cms/test-wedding/dashboard', {
      activeSlug: null,
      registry: []
    })
    expect(wrapper.text()).toContain('Wedding CMS')
  })
})
