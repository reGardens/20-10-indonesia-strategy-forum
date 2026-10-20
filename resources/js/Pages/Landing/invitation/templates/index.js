/**
 * Template Registry
 *
 * Maps template IDs to their definitions (name, description, thumbnail, and
 * lazy-loaded Vue component). Templates are loaded dynamically via
 * `<component :is>` in InvitationView.
 *
 * Each entry follows the TemplateDefinition interface from the design doc.
 */

const templates = [
  {
    id: 'jawa-klasik',
    name: 'Jawa Klasik',
    description: 'Motif batik Jawa dengan nuansa coklat emas klasik',
    thumbnail: '/templates/jawa-klasik/thumbnail.webp',
    component: () => import('./jawa-klasik/JawaKlasikTemplate.vue'),
    defaultConfig: {
      primaryColor: '#8B4513',
      secondaryColor: '#D2691E',
      accentColor: '#FFD700',
      fontFamily: 'Playfair Display',
      galleryLayout: 'masonry',
      animationStyle: 'fade-up',
    },
  },
  {
    id: 'sunda-pasundan',
    name: 'Sunda Pasundan',
    description: 'Nuansa hijau dan emas khas Sunda',
    thumbnail: '/templates/sunda-pasundan/thumbnail.webp',
    component: () => import('./sunda-pasundan/SundaPasundanTemplate.vue'),
    defaultConfig: {
      primaryColor: '#2D5016',
      secondaryColor: '#4A7C2E',
      accentColor: '#C5A028',
      fontFamily: 'Lora',
      galleryLayout: 'grid',
      animationStyle: 'fade-up',
    },
  },
  {
    id: 'bali-dewata',
    name: 'Bali Dewata',
    description: 'Ornamen Bali dengan warna merah dan emas',
    thumbnail: '/templates/bali-dewata/thumbnail.webp',
    component: () => import('./bali-dewata/BaliDewataTemplate.vue'),
    defaultConfig: {
      primaryColor: '#8B1A1A',
      secondaryColor: '#C41E3A',
      accentColor: '#FFD700',
      fontFamily: 'Cinzel',
      galleryLayout: 'masonry',
      animationStyle: 'fade-up',
    },
  },
  {
    id: 'betawi-jakarta',
    name: 'Betawi Jakarta',
    description: 'Warna cerah khas Betawi (oranye, hijau)',
    thumbnail: '/templates/betawi-jakarta/thumbnail.webp',
    component: () => import('./betawi-jakarta/BetawiJakartaTemplate.vue'),
    defaultConfig: {
      primaryColor: '#D4652F',
      secondaryColor: '#2E7D32',
      accentColor: '#FFC107',
      fontFamily: 'Poppins',
      galleryLayout: 'slider',
      animationStyle: 'fade-right',
    },
  },
  {
    id: 'minang-rantau',
    name: 'Minang Rantau',
    description: 'Motif songket Minangkabau merah maroon dan emas',
    thumbnail: '/templates/minang-rantau/thumbnail.webp',
    component: () => import('./minang-rantau/MinangRantauTemplate.vue'),
    defaultConfig: {
      primaryColor: '#7B2D26',
      secondaryColor: '#C9A84C',
      accentColor: '#F5E6CA',
      fontFamily: 'Cinzel',
      galleryLayout: 'grid',
      animationStyle: 'zoom-in',
    },
  },
  {
    id: 'bugis-makassar',
    name: 'Bugis Makassar',
    description: 'Nuansa biru laut dan emas Sulawesi',
    thumbnail: '/templates/bugis-makassar/thumbnail.webp',
    component: () => import('./bugis-makassar/BugisMakassarTemplate.vue'),
    defaultConfig: {
      primaryColor: '#1B3A5C',
      secondaryColor: '#2E6B9E',
      accentColor: '#C9A84C',
      fontFamily: 'Cormorant Garamond',
      galleryLayout: 'masonry',
      animationStyle: 'fade-up',
    },
  },
  {
    id: 'dayak-borneo',
    name: 'Dayak Borneo',
    description: 'Motif etnik Dayak dengan merah dan hitam',
    thumbnail: '/templates/dayak-borneo/thumbnail.webp',
    component: () => import('./dayak-borneo/DayakBorneoTemplate.vue'),
    defaultConfig: {
      primaryColor: '#8B0000',
      secondaryColor: '#2C1810',
      accentColor: '#DAA520',
      fontFamily: 'Montserrat',
      galleryLayout: 'grid',
      animationStyle: 'fade-right',
    },
  },
  {
    id: 'toraja-sulawesi',
    name: 'Toraja Sulawesi',
    description: 'Motif ukiran Toraja merah dan hitam',
    thumbnail: '/templates/toraja-sulawesi/thumbnail.webp',
    component: () => import('./toraja-sulawesi/TorajaSulawesiTemplate.vue'),
    defaultConfig: {
      primaryColor: '#4A1C1C',
      secondaryColor: '#8B4513',
      accentColor: '#CD853F',
      fontFamily: 'Playfair Display',
      galleryLayout: 'masonry',
      animationStyle: 'fade-up',
    },
  },
  {
    id: 'melayu-riau',
    name: 'Melayu Riau',
    description: 'Nuansa kuning kerajaan Melayu',
    thumbnail: '/templates/melayu-riau/thumbnail.webp',
    component: () => import('./melayu-riau/MelayuRiauTemplate.vue'),
    defaultConfig: {
      primaryColor: '#B8860B',
      secondaryColor: '#8B6914',
      accentColor: '#FFFACD',
      fontFamily: 'Lora',
      galleryLayout: 'slider',
      animationStyle: 'fade-up',
    },
  },
  {
    id: 'papua-cendrawasih',
    name: 'Papua Cendrawasih',
    description: 'Motif tifa dan cendrawasih, warna tanah dan merah',
    thumbnail: '/templates/papua-cendrawasih/thumbnail.webp',
    component: () => import('./papua-cendrawasih/PapuaCendrawasihTemplate.vue'),
    defaultConfig: {
      primaryColor: '#5C3317',
      secondaryColor: '#8B4513',
      accentColor: '#CD5C5C',
      fontFamily: 'Montserrat',
      galleryLayout: 'grid',
      animationStyle: 'zoom-in',
    },
  },
  {
    id: 'misal',
    name: 'misal',
    description: 'misal',
    thumbnail: '/templates/misal/thumbnail.webp',
    component: () => import('./misal/MisalTemplate.vue'),
    defaultConfig: {
      primaryColor: '#1B3A5C',
      secondaryColor: '#2E6B9E',
      accentColor: '#C9A84C',
      fontFamily: 'Playfair Display',
      galleryLayout: 'grid',
      animationStyle: 'fade-up',
    },
  }
  // [REGISTER_TEMPLATES]
]

/**
 * Get a template definition by its ID.
 * @param {string} templateId
 * @returns {object|undefined} The template definition, or undefined if not found.
 */
export function getTemplate(templateId) {
  return templates.find((t) => t.id === templateId)
}

/**
 * Get all registered templates.
 * @returns {object[]} Array of template definitions.
 */
export function getAllTemplates() {
  return templates
}

export default templates
