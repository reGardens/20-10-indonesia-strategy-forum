import { createRouter, createWebHistory } from 'vue-router'
import { weddingRegistryService } from '@/api/services/weddingRegistryService'
import store from '@/store/index.js'

const CmsLayout = () => import('../Pages/Cms/layouts/CmsLayout.vue')
const InvitationLayout = () => import('../Pages/Landing/invitation/layouts/InvitationLayout.vue')

const routes = [
  {
    path: '/',
    redirect: '/cms/dashboard'
  },
  {
    path: '/cms',
    redirect: '/cms/dashboard'
  },
  // CMS with slug context
  {
    path: '/cms/:slug',
    component: CmsLayout,
    redirect: (to) => `/cms/${to.params.slug}/dashboard`,
    children: [
      {
        path: 'dashboard',
        name: 'cms-dashboard',
        component: () => import('../Pages/Cms/views/DashboardView.vue')
      },
      // ── Undangan Digital: dropdown with 12 sub-sections ──
      {
        path: 'undangan',
        redirect: (to) => `/cms/${to.params.slug}/undangan/tema`,
      },
      {
        path: 'undangan/tema',
        name: 'cms-undangan-tema',
        component: () => import('../Pages/Cms/views/undangan/TemaPilihan.vue'),
        meta: { featureKey: 'undangan_digital', section: 'undangan' }
      },
      {
        path: 'undangan/rsvp',
        name: 'cms-undangan-rsvp',
        component: () => import('../Pages/Cms/views/undangan/RsvpSection.vue'),
        meta: { featureKey: 'rsvp_online', section: 'undangan' }
      },
      {
        path: 'undangan/acara',
        name: 'cms-undangan-acara',
        component: () => import('../Pages/Cms/views/undangan/InfoAcara.vue'),
        meta: { featureKey: 'informasi_acara', section: 'undangan' }
      },
      {
        path: 'undangan/love-story',
        name: 'cms-undangan-love-story',
        component: () => import('../Pages/Cms/views/undangan/LoveStory.vue'),
        meta: { featureKey: 'love_story', section: 'undangan' }
      },
      {
        path: 'undangan/tamu',
        name: 'cms-undangan-tamu',
        component: () => import('../Pages/Cms/views/undangan/ManajemenTamu.vue'),
        meta: { featureKey: 'manajemen_tamu', section: 'undangan' }
      },
      {
        path: 'undangan/amplop',
        name: 'cms-undangan-amplop',
        component: () => import('../Pages/Cms/views/undangan/AmplopDigital.vue'),
        meta: { featureKey: 'amplop_digital', section: 'undangan' }
      },
      {
        path: 'undangan/galeri',
        name: 'cms-undangan-galeri',
        component: () => import('../Pages/Cms/views/undangan/Galeri.vue'),
        meta: { featureKey: 'galeri', section: 'undangan' }
      },
      {
        path: 'undangan/countdown',
        name: 'cms-undangan-countdown',
        component: () => import('../Pages/Cms/views/undangan/Countdown.vue'),
        meta: { featureKey: 'countdown', section: 'undangan' }
      },
      {
        path: 'undangan/qr-checkin',
        name: 'cms-undangan-qr',
        component: () => import('../Pages/Cms/views/undangan/QrCheckin.vue'),
        meta: { featureKey: 'qr_checkin', section: 'undangan' }
      },
      {
        path: 'undangan/streaming',
        name: 'cms-undangan-streaming',
        component: () => import('../Pages/Cms/views/undangan/LiveStreaming.vue'),
        meta: { featureKey: 'live_streaming', section: 'undangan' }
      },
      {
        path: 'undangan/ucapan',
        name: 'cms-undangan-ucapan',
        component: () => import('../Pages/Cms/views/undangan/UcapanDoa.vue'),
        meta: { featureKey: 'ucapan_doa', section: 'undangan' }
      },
      {
        path: 'undangan/wishlist',
        name: 'cms-undangan-wishlist',
        component: () => import('../Pages/Cms/views/undangan/Wishlist.vue'),
        meta: { featureKey: 'wishlist', section: 'undangan' }
      },
      // ── Legacy / other sections (kept for backward-compat) ──
      {
        path: 'couple',
        name: 'cms-couple',
        component: () => import('../Pages/Cms/views/CoupleView.vue')
      },
      {
        path: 'events',
        name: 'cms-events',
        component: () => import('../Pages/Cms/views/EventsView.vue')
      },
      {
        path: 'template',
        name: 'cms-template',
        redirect: (to) => `/cms/${to.params.slug}/undangan/tema`,
      },
      {
        path: 'guests',
        name: 'cms-guests',
        component: () => import('../Pages/Cms/views/GuestsView.vue')
      },

      {
        path: 'media',
        name: 'cms-media',
        component: () => import('../Pages/Cms/views/MediaView.vue')
      },
      {
        path: 'payment',
        name: 'cms-payment',
        component: () => import('../Pages/Cms/views/PaymentView.vue')
      },
      {
        path: 'rsvp',
        name: 'cms-rsvp',
        component: () => import('../Pages/Cms/views/RsvpView.vue')
      },
      {
        path: 'settings',
        name: 'cms-settings',
        component: () => import('../Pages/Cms/views/SettingsView.vue')
      }
    ]
  },
  {
    path: '/wedding/:slug',
    component: InvitationLayout,
    children: [
      {
        path: '',
        name: 'invitation',
        component: () => import('../Pages/Landing/invitation/views/InvitationView.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for CMS slug validation and ACL check
router.beforeEach(async (to, from, next) => {
  if (to.params.slug && to.path.startsWith('/cms/')) {
    const exists = await weddingRegistryService.exists(to.params.slug)
    if (!exists) {
      return next({ path: '/cms/dashboard', query: { error: 'not-found' } })
    }
    // Set active slug in store if changed
    if (store.state.wedding.activeSlug !== to.params.slug) {
      if (store.state.wedding.registry.length === 0) {
        await store.dispatch('wedding/fetchRegistry')
      }
      await store.dispatch('wedding/setActiveWedding', to.params.slug)
    }

    // Ensure user profile is loaded to perform ACL check
    if (!store.state.wedding.userProfile) {
      await store.dispatch('wedding/fetchUserProfile')
    }

    // Check meta-based ACL (undangan sub-sections use meta.featureKey)
    const requiredFeature = to.meta?.featureKey
    if (requiredFeature) {
      const allowedFeatures = store.getters['wedding/allowedFeatures'] || []
      if (!allowedFeatures.includes(requiredFeature)) {
        return next({ name: 'cms-dashboard', params: { slug: to.params.slug }, query: { error: 'unauthorized-feature' } })
      }
    }
  }
  next()
})

export default router
