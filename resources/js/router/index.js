import { createRouter, createWebHistory } from 'vue-router'
import { getBeranda, getAnggota, getInfografik, getIsu, getTokoh } from '@/api/services/isfPublicService'

const CmsLayout = () => import('../Pages/Cms/layouts/CmsLayout.vue')
const InvitationLayout = () => import('../Pages/Landing/invitation/layouts/InvitationLayout.vue')
const IsfPublicLayout = () => import('../Layouts/IsfPublicLayout.vue')
const IsfMemberLayout = () => import('../Layouts/IsfMemberLayout.vue')
const BerandaView = () => import('../Pages/Isf/views/BerandaView.vue')

const routes = [
  {
    path: '/',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-home',
        component: BerandaView,
        meta: { title: 'Beranda · Indonesia Strategy Forum' }
      }
    ]
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
    path: '/beranda',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-beranda',
        component: BerandaView,
        meta: { title: 'Beranda · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/infografik',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-infografik',
        component: () => import('../Pages/Isf/views/InfografikView.vue'),
        meta: { title: 'Infografik Kebijakan & Regulasi · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/tokoh/:slug',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-tokoh',
        component: () => import('../Pages/Isf/views/TokohProfileView.vue'),
        meta: { title: 'Profil & Tokoh Pekan Ini · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/isu/:slug',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-isu',
        component: () => import('../Pages/Isf/views/IsuDetailView.vue'),
        meta: { title: 'Anatomi Isu Mingguan · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/anggota',
    component: IsfMemberLayout,
    children: [
      {
        path: '',
        name: 'isf-anggota',
        component: () => import('../Pages/Isf/views/AnggotaDashboardView.vue'),
        meta: { title: 'Dasbor Peringatan Dini · Portal Anggota ISF' }
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
    const [{ weddingRegistryService }, { default: store }] = await Promise.all([
      import('@/api/services/weddingRegistryService'),
      import('@/store/index.js')
    ])
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

// Prefetch ISF public content in parallel with chunk downloads
router.beforeResolve((to) => {
  try {
    if (to.name === 'isf-home' || to.path === '/' || to.path === '/beranda') {
      getBeranda()
    } else if (to.name === 'isf-anggota' || to.path.startsWith('/anggota')) {
      getAnggota()
    } else if (to.name === 'isf-infografik' || to.path === '/infografik') {
      getInfografik()
    } else if (to.name === 'isf-isu' && to.params.slug) {
      getIsu(to.params.slug)
    } else if (to.name === 'isf-tokoh' && to.params.slug) {
      getTokoh(to.params.slug)
    }
  } catch {}
})

export default router
