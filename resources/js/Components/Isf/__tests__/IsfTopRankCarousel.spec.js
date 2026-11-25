import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { createApp, h, nextTick } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import IsfTopRankCarousel from '@/Components/Isf/IsfTopRankCarousel.vue'

let app
let root

const mockItems = [
    {
        rank: 1,
        title: 'Artikel Peringkat Pertama',
        summary: 'Ringkasan artikel nomor satu of the week.',
        author: 'Penulis Satu',
        imageUrl: 'https://example.com/thumb1.jpg',
        to: '/artikel/politik/artikel-1',
    },
    {
        rank: 2,
        title: 'Artikel Peringkat Kedua',
        summary: 'Ringkasan artikel nomor dua of the week.',
        author: 'Penulis Dua',
        imageUrl: 'https://example.com/thumb2.jpg',
        to: '/artikel/hukum/artikel-2',
    },
    {
        rank: 3,
        title: 'Artikel Peringkat Ketiga',
        summary: 'Ringkasan artikel nomor tiga of the week.',
        author: 'Penulis Tiga',
        imageUrl: 'https://example.com/thumb3.jpg',
        to: '/artikel/kebijakan/artikel-3',
    },
]

async function renderCarousel(props = {}) {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', component: { template: '<div>Home</div>' } },
            { path: '/artikel/:slug*', component: { template: '<div>Article</div>' } },
        ],
    })
    await router.push('/')
    await router.isReady()

    root = document.createElement('div')
    document.body.appendChild(root)

    app = createApp({
        render: () => h(IsfTopRankCarousel, { items: mockItems, ...props }),
    })
    app.use(router)
    app.mount(root)
    await nextTick()
    return { root, router }
}

beforeEach(() => {
    document.body.innerHTML = ''
})

afterEach(() => {
    app?.unmount()
    app = undefined
    document.body.innerHTML = ''
})

describe('IsfTopRankCarousel.vue', () => {
    it('merender judul section TOP ARTIKEL dan kartu awal peringkat 1 dengan badge gold', async () => {
        const { root } = await renderCarousel()

        expect(root.textContent).toContain('TOP')
        expect(root.textContent).toContain('ARTIKEL')
        expect(root.textContent).toContain('TOP 1')
        expect(root.textContent).toContain('Artikel Peringkat Pertama')
        expect(root.textContent).toContain('Penulis Satu')
        expect(root.textContent).toContain('Ringkasan artikel nomor satu of the week.')
    })

    it('dapat berpindah slide ke peringkat 2 dan 3 melalui navigasi next dan indicator', async () => {
        const { root } = await renderCarousel()

        // Klik tombol next
        const nextBtn = root.querySelector('button[aria-label="Peringkat berikutnya"]')
        expect(nextBtn).not.toBeNull()
        nextBtn.click()
        await nextTick()

        expect(root.textContent).toContain('TOP 2')
        expect(root.textContent).toContain('Artikel Peringkat Kedua')
        expect(root.textContent).toContain('Penulis Dua')

        // Klik tombol indicator berikutnya
        nextBtn.click()
        await nextTick()

        expect(root.textContent).toContain('TOP 3')
        expect(root.textContent).toContain('Artikel Peringkat Ketiga')
        expect(root.textContent).toContain('Penulis Tiga')
    })
})
