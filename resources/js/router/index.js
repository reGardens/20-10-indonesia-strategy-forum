import { createRouter, createWebHistory } from 'vue-router'
import { getBeranda, getAnggota, getInfografik, getTokoh, getMediaDetail, getArtikel, getInfografikDetail, getKategori, getPolitik, getSosok, getSosokDetail, getMediaKit } from '@/api/services/isfPublicService'

const IsfPublicLayout = () => import('../Layouts/IsfPublicLayout.vue')
const IsfMemberLayout = () => import('../Layouts/IsfMemberLayout.vue')
const BerandaView = () => import('../Pages/Isf/views/BerandaView.vue')
const RubrikView = () => import('../Pages/Isf/views/RubrikView.vue')
const ArticleDetailView = () => import('../Pages/Isf/views/ArticleDetailView.vue')

const routes = [
  {
    path: '/',
    redirect: '/beranda'
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
    path: '/register',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-register',
        component: () => import('../Pages/Isf/views/RegisterView.vue'),
        meta: { title: 'Pendaftaran Anggota · Indonesia Strategy Forum' }
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
    path: '/infografik/:slug',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-infografik-detail',
        component: () => import('../Pages/Isf/views/InfografikDetailView.vue'),
        meta: { title: 'Infografik · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/sosok',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-sosok',
        component: () => import('../Pages/Isf/views/SosokView.vue'),
        meta: { title: 'Sosok & Tokoh Strategis · Indonesia Strategy Forum' }
      },
      {
        path: ':kategori(eksekutif|legislatif|yudikatif|pakar|militer|pengusaha)',
        name: 'isf-sosok-kategori',
        component: () => import('../Pages/Isf/views/SosokView.vue'),
        meta: { title: 'Sosok & Tokoh Strategis · Indonesia Strategy Forum' }
      },
      {
        path: ':slug',
        name: 'isf-sosok-detail',
        component: () => import('../Pages/Isf/views/SosokDetailView.vue'),
        meta: { title: 'Detail Tokoh · Indonesia Strategy Forum' }
      },
      {
        path: ':slug/:tab(profil|analisis|jejaring|berita|kontroversi)',
        name: 'isf-sosok-detail-tab',
        component: () => import('../Pages/Isf/views/SosokDetailView.vue'),
        meta: { title: 'Detail Tokoh · Indonesia Strategy Forum' }
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
    path: '/media/:id',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-media-detail',
        component: () => import('../Pages/Isf/views/MediaDetailView.vue'),
        meta: { title: 'Media Sorotan & Video Analisis · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/politik',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-politik',
        component: () => import('../Pages/Isf/views/PolitikView.vue'),
        meta: { title: 'Politik · Indonesia Strategy Forum' }
      },
      {
        path: ':subkategori(pemilu|parlemen|pemerintahan|partai|sorotan|eksekutif|legislatif|dinamika-partai|pemilu-pilkada|kegiatan-istana)',
        name: 'isf-politik-subkategori',
        component: () => import('../Pages/Isf/views/PolitikView.vue'),
        meta: { title: 'Politik · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/artikel/politik',
    redirect: '/politik'
  },
  {
    path: '/hukum',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-hukum',
        component: () => import('../Pages/Isf/views/HukumView.vue'),
        meta: { title: 'Hukum · Indonesia Strategy Forum' }
      },
      {
        path: ':subkategori(regulasi|putusan|kasus|glosarium)',
        name: 'isf-hukum-subkategori',
        component: () => import('../Pages/Isf/views/HukumView.vue'),
        meta: { title: 'Hukum · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/artikel/hukum',
    redirect: '/hukum'
  },
  {
    path: '/kebijakan-publik',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-kebijakan-publik',
        component: () => import('../Pages/Isf/views/KebijakanPublikView.vue'),
        meta: { title: 'Kebijakan Publik · Indonesia Strategy Forum' }
      },
      {
        path: ':subkategori(ekonomi|kesehatan|pendidikan|pertahanan|lingkungan|policy-brief|monitoring|data-statistik|isf-insight|database-peraturan)',
        name: 'isf-kebijakan-publik-subkategori',
        component: () => import('../Pages/Isf/views/KebijakanPublikView.vue'),
        meta: { title: 'Kebijakan Publik · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/artikel/kebijakan',
    redirect: '/kebijakan-publik'
  },
  {
    path: '/opini-publik',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-opini-publik',
        component: () => import('../Pages/Isf/views/OpiniPublikView.vue'),
        meta: { title: 'Opini Publik · Indonesia Strategy Forum' }
      },
      {
        path: ':subkategori(op-ed|editorial|surat-pembaca|polling|kutipan)',
        name: 'isf-opini-publik-subkategori',
        component: () => import('../Pages/Isf/views/OpiniPublikView.vue'),
        meta: { title: 'Opini Publik · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/artikel/opini',
    redirect: '/opini-publik'
  },
  {
    path: '/artikel/opini-publik',
    redirect: '/opini-publik'
  },
  {
    path: '/survei',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-survei',
        component: () => import('../Pages/Isf/views/SurveiView.vue'),
        meta: { title: 'Survei Opini Publik · Indonesia Strategy Forum' }
      },
      {
        path: ':slug',
        name: 'isf-survei-detail',
        component: () => import('../Pages/Isf/views/SurveiDetailView.vue'),
        meta: { title: 'Detail Survei · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/media-kit',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-media-kit',
        component: () => import('../Pages/Isf/views/MediaKitView.vue'),
        meta: { title: 'Iklan & Kemitraan Strategis · Media Kit · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/advertise',
    redirect: '/media-kit'
  },
  {
    // Artikel per kategori: /artikel/{kategori} = daftar, /artikel/{kategori}/{slug} = detail.
    // Detail = child route, jadi menu navbar kategori tetap aktif (router-link-active).
    path: '/artikel/:kategori(politik|hukum|kebijakan|opini|opini-publik)',

    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-kategori',
        component: RubrikView,
        meta: { title: 'Artikel · Indonesia Strategy Forum' }
      },
      {
        path: ':slug',
        name: 'isf-artikel',
        component: ArticleDetailView,
        meta: { title: 'Artikel · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    // Tautan lama /artikel/{slug} (tanpa kategori): halaman detail mengganti URL ke URL kanonis.
    path: '/artikel/:slug',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-artikel-plain',
        component: ArticleDetailView,
        meta: { title: 'Artikel · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    // Tautan lama Anatomi Isu (dihapus di #34) diarahkan ke detail artikel.
    path: '/isu/:slug',
    redirect: (to) => ({ name: 'isf-artikel-plain', params: { slug: to.params.slug } })
  },
  {
    path: '/pencarian',
    component: IsfPublicLayout,
    children: [
      {
        path: '',
        name: 'isf-search',
        component: () => import('../Pages/Isf/views/SearchView.vue'),
        meta: { title: 'Pencarian Terpadu · Indonesia Strategy Forum' }
      }
    ]
  },
  {
    path: '/search',
    redirect: '/pencarian'
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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  // Pindah halaman → mulai dari atas (detail tidak lagi terbuka di posisi scroll halaman sebelumnya).
  // Tombol Kembali/Maju memulihkan posisi lama; ganti query/hash di halaman yang sama tidak menggulir.
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash }
    if (to.path === from.path) return false
    // Jangan scroll saat berganti tab di dalam halaman detail tokoh yang sama
    const isSosokDetail = (name) => name === 'isf-sosok-detail' || name === 'isf-sosok-detail-tab'
    if (isSosokDetail(to.name) && isSosokDetail(from.name) && to.params.slug === from.params.slug) {
      return false
    }
    return { top: 0, left: 0 }
  }
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
    } else if (to.name === 'isf-tokoh' && to.params.slug) {
      getTokoh(to.params.slug)
    } else if (to.name === 'isf-media-detail' && to.params.id) {
      getMediaDetail(to.params.id)
    } else if ((to.name === 'isf-artikel' || to.name === 'isf-artikel-plain') && to.params.slug) {
      getArtikel(to.params.slug)
    } else if (to.name === 'isf-infografik-detail' && to.params.slug) {
      getInfografikDetail(to.params.slug)
    } else if (to.name === 'isf-kategori' && to.params.kategori) {
      getKategori(to.params.kategori)
    } else if (to.name === 'isf-politik' || to.name === 'isf-politik-subkategori') {
      getPolitik(to.params.subkategori)
    } else if (to.name === 'isf-sosok' || to.name === 'isf-sosok-kategori') {
      getSosok({ kategori: to.params.kategori })
    } else if ((to.name === 'isf-sosok-detail' || to.name === 'isf-sosok-detail-tab') && to.params.slug) {
      getSosokDetail(to.params.slug)
    } else if (to.name === 'isf-media-kit' || to.path === '/media-kit' || to.path === '/advertise') {
      getMediaKit()
    }
  } catch {}
})

router.afterEach((to) => {
  if (to.meta?.title && typeof document !== 'undefined') {
    document.title = to.meta.title
  }
})

export default router
