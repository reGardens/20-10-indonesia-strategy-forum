import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { createApp, h, nextTick } from 'vue';

vi.mock('@/Layouts/CmsLayout.vue', () => ({
    default: {
        name: 'CmsLayout',
        render() {
            return this.$slots.default ? this.$slots.default() : null;
        },
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        render: () => null,
    },
    Link: {
        name: 'Link',
        props: ['href'],
        render() {
            return h('a', { href: this.href }, this.$slots.default ? this.$slots.default() : null);
        },
    },
}));

vi.mock('@/Composables/useAlert', () => ({
    alertSuccess: vi.fn(),
    alertError: vi.fn(),
    confirmDelete: vi.fn().mockResolvedValue(true),
    confirmAction: vi.fn().mockResolvedValue(true),
}));

import { confirmAction } from '@/Composables/useAlert';
import IsfAdBanner from '@/Components/Isf/IsfAdBanner.vue';
import IsfAdPopupModal from '@/Components/Isf/IsfAdPopupModal.vue';
import CmsIklanIndex from '@/Pages/Cms/Isf/Iklan/Index.vue';
import CmsIklanForm from '@/Pages/Cms/Isf/Iklan/Form.vue';
import { iklanService, cmsResources } from '@/api/services/isfCmsService';

async function renderComponent(component, props = {}) {
    const root = document.createElement('div');
    document.body.appendChild(root);
    const app = createApp({
        render() {
            return h(component, props);
        },
    });
    // Stub Head component from inertia if used
    app.component('Head', { render: () => null });

    const vm = app.mount(root);
    await nextTick();
    return {
        app,
        root,
        vm,
        unmount: () => {
            app.unmount();
            root.remove();
        },
    };
}

describe('ISF Ads Unit Tests (Issue #38)', () => {
    beforeEach(() => {
        sessionStorage.clear();
        document.cookie = 'isf_popup_ad_seen=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.body.innerHTML = '';
        vi.restoreAllMocks();
        confirmAction.mockResolvedValue(true);
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('IsfAdBanner (Slot Spanduk Beranda)', () => {
        it('merender slot iklan banner dengan kicker, banner display murni tanpa border/box artikel', async () => {
            const { root } = await renderComponent(IsfAdBanner, {
                ad: {
                    title: 'Kemitraan Strategis ISF 2026',
                    kicker: 'SPONSOR RESMI',
                    imageUrl: 'https://example.com/ad-banner.jpg',
                    targetUrl: 'https://example.com/sponsor',
                    status: 'terbit',
                    isActive: true,
                },
            });

            expect(root.querySelector('[data-testid="ad-placement-section"]')).not.toBeNull();
            expect(root.textContent).toContain('SPONSOR RESMI');
            expect(root.textContent).toContain('Iklan');

            const bannerLink = root.querySelector('[data-testid="ad-placement-banner"]');
            expect(bannerLink).not.toBeNull();
            expect(bannerLink.getAttribute('href')).toBe('https://example.com/sponsor');
            expect(bannerLink.getAttribute('target')).toBe('_blank');

            const img = bannerLink.querySelector('img');
            expect(img).not.toBeNull();
            expect(img.getAttribute('src')).toBe('https://example.com/ad-banner.jpg');
            expect(img.getAttribute('alt')).toBe('Kemitraan Strategis ISF 2026');
        });

        it('menampilkan default component bertuliskan ADS jika gambar belum disetel', async () => {
            const { root } = await renderComponent(IsfAdBanner, {
                ad: {
                    title: '',
                    kicker: 'IKLAN MITRA',
                    imageUrl: '',
                    status: 'terbit',
                    isActive: true,
                },
            });

            expect(root.querySelector('[data-testid="ad-placement-default-ads"]')).not.toBeNull();
            expect(root.textContent).toContain('ADS');
            expect(root.textContent).toContain('Ruang Iklan Tersedia');
        });

        it('merender default component bertuliskan ADS jika status iklan adalah draf atau isActive false', async () => {
            const { root } = await renderComponent(IsfAdBanner, {
                ad: {
                    title: 'Iklan Draf',
                    imageUrl: 'https://example.com/ad.jpg',
                    status: 'draf',
                    isActive: false,
                },
            });

            expect(root.querySelector('[data-testid="ad-placement-default-ads"]')).not.toBeNull();
            expect(root.textContent).toContain('ADS');
            expect(root.textContent).toContain('Ruang Iklan Tersedia');
        });
    });

    describe('IsfAdPopupModal (Modal Popup Iklan Saat Akses Pertama)', () => {
        it('Tipe A (Banner Statis): merender banner dengan tombol close (X) di bagian bawah tanpa wording Tutup Iklan & tanpa badge Mitra Strategis', async () => {
            await renderComponent(IsfAdPopupModal, {
                ad: {
                    type: 'banner',
                    title: 'Flyer Promo Eksklusif',
                    imageUrl: 'https://example.com/flyer.jpg',
                    targetUrl: 'https://example.com/promo',
                    status: 'terbit',
                    isActive: true,
                },
                forcePreview: true,
            });

            // IsfAdPopupModal uses Teleport to body
            const modal = document.body.querySelector('[data-testid="ad-popup-modal"]');
            expect(modal).not.toBeNull();

            const bannerContent = document.body.querySelector('[data-testid="ad-popup-banner-content"]');
            expect(bannerContent).not.toBeNull();

            // Memastikan badge Iklan Mitra Strategis telah dihapus
            expect(document.body.textContent).not.toContain('IKLAN MITRA STRATEGIS');

            // Tombol penutup (X) di bagian bawah tanpa wording "Tutup Iklan"
            const closeBtn = document.body.querySelector('[data-testid="ad-popup-close-button-bottom"]');
            expect(closeBtn).not.toBeNull();
            expect(closeBtn.textContent).toContain('✕');
            expect(closeBtn.textContent).not.toContain('Tutup Iklan');
        });

        it('Kategori Popup Video dengan Durasi: merender countdown timer, video player, dan mengunci tombol tutup sebelum durasi video selesai', async () => {
            await renderComponent(IsfAdPopupModal, {
                ad: {
                    type: 'video',
                    title: 'Video Teaser ISF Summit',
                    videoUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    videoDuration: 30,
                    status: 'terbit',
                    isActive: true,
                },
                forcePreview: true,
            });

            const modal = document.body.querySelector('[data-testid="ad-popup-modal"]');
            expect(modal).not.toBeNull();

            const videoContent = document.body.querySelector('[data-testid="ad-popup-video-content"]');
            expect(videoContent).not.toBeNull();

            const iframe = document.body.querySelector('[data-testid="ad-popup-youtube-video"]');
            expect(iframe).not.toBeNull();

            // Terdapat countdown text yang menunjukkan durasi tersisa
            const countdown = document.body.querySelector('[data-testid="video-countdown-text"]');
            expect(countdown).not.toBeNull();
            expect(countdown.textContent).toContain('30s');

            // Selama video berjalan (durasi belum habis), tombol tutup manual tidak tersedia (mengunci perhatian pengunjung)
            const manualClose = document.body.querySelector('[data-testid="ad-popup-manual-close"]');
            expect(manualClose).toBeNull();
        });

        it('menyimpan flag sessionStorage setelah popup ditutup sehingga tidak muncul berulang', async () => {
            await renderComponent(IsfAdPopupModal, {
                ad: {
                    type: 'banner',
                    title: 'Kunjungan Pertama',
                    imageUrl: 'https://example.com/banner.jpg',
                    status: 'terbit',
                    isActive: true,
                },
                forcePreview: false,
            });

            // Tunggu 550ms agar setTimeout di onMounted selesai membuka modal
            await new Promise((r) => setTimeout(r, 600));

            const closeBtn = document.body.querySelector('[data-testid="ad-popup-close-button-bottom"]');
            expect(closeBtn).not.toBeNull();

            // Klik tombol X
            closeBtn.click();

            expect(sessionStorage.getItem('isf_popup_ad_seen')).toBe('true');
        });
    });

    describe('CMS Kelola Iklan — 2 Tab Pemisah, Tabel Khusus, Urutan & Toggle Konfirmasi', () => {
        it('merender 2 tab (Iklan Body & Iklan Popup) dengan masing-masing tabel dan kolomnya', async () => {
            const mockAds = [
                {
                    id: 1,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026',
                    kicker: 'SPONSOR RESMI',
                    target_url: '/register',
                    image_url: 'https://example.com/banner.jpg',
                    video_url: '',
                    video_duration: 30,
                    status: 'terbit',
                    is_active: true,
                    order: 1,
                },
                {
                    id: 2,
                    placement: 'popup',
                    type: 'banner',
                    title: 'Indonesia Strategy Forum Annual Summit 2026',
                    kicker: 'SPONSOR RESMI',
                    target_url: '/register',
                    image_url: 'https://example.com/popup.jpg',
                    video_url: '',
                    video_duration: 30,
                    interval_minutes: 0,
                    status: 'terbit',
                    is_active: true,
                    order: 1,
                },
                {
                    id: 3,
                    placement: 'popup',
                    type: 'video',
                    title: 'Teaser Video Kebijakan ISF 2026',
                    kicker: 'SPONSOR RESMI',
                    target_url: '/register',
                    image_url: '',
                    video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    video_duration: 30,
                    interval_minutes: 10,
                    status: 'terbit',
                    is_active: true,
                    order: 2,
                },
            ];

            vi.spyOn(iklanService, 'get').mockResolvedValue(mockAds);

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));

            // 1. Header: Bebas dari kata 'pengganti latest news', tombol Tambah Iklan berupa Link ke /cms/iklan/create
            expect(root.textContent).toContain('Kelola Iklan & Monetisasi');
            expect(root.textContent).not.toContain('pengganti Latest News');
            expect(root.textContent).not.toContain('pengganti latest news');
            expect(root.textContent).toContain('Tambah Iklan');

            // 2. Memiliki 2 tab pemisah: Iklan Body & Iklan Popup
            expect(root.textContent).toContain('Iklan Body (Spanduk Beranda)');
            expect(root.textContent).toContain('Iklan Popup (Modal Popup)');

            // Default tab: 'body'
            const thBodyList = Array.from(root.querySelectorAll('th')).map((th) => th.textContent.trim());
            expect(thBodyList).toContain('Urutan');
            expect(thBodyList).toContain('Materi Media');
            expect(thBodyList).toContain('Judul Kampanye & Tautan');
            expect(thBodyList).toContain('Status Periode & Sisa Waktu');
            expect(thBodyList).not.toContain('Status');
            expect(thBodyList).toContain('Status Aktif');
            expect(thBodyList).toContain('Aksi');

            // Baris tabel body hanya berisi 1 spanduk
            const bodyRows = root.querySelectorAll('tbody tr');
            expect(bodyRows.length).toBe(1);
            expect(bodyRows[0].textContent).toContain('Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026');

            // 3. Pindah ke tab 'popup'
            const popupTabBtn = Array.from(root.querySelectorAll('button')).find((b) => b.textContent.includes('Iklan Popup'));
            expect(popupTabBtn).not.toBeUndefined();
            popupTabBtn.click();
            await nextTick();

            // Tabel popup memiliki kolom Format & Penayangan dan Status Periode & Sisa Waktu
            const thPopupList = Array.from(root.querySelectorAll('th')).map((th) => th.textContent.trim());
            expect(thPopupList).toContain('Format & Penayangan');
            expect(thPopupList).toContain('Status Periode & Sisa Waktu');
            expect(thPopupList).not.toContain('Status');

            // Baris tabel popup berisi 2 item (popup banner & popup video)
            const popupRows = root.querySelectorAll('tbody tr');
            expect(popupRows.length).toBe(2);
            expect(popupRows[0].textContent).toContain('Indonesia Strategy Forum Annual Summit 2026');
            expect(popupRows[0].textContent).toContain('Default #1 (Muncul Pertama)');
            expect(popupRows[1].textContent).toContain('Teaser Video Kebijakan ISF 2026');
            expect(popupRows[1].textContent).toContain('Jeda 10 Menit');
        });

        it('memunculkan SweetAlert konfirmasi dengan wording simpel saat toggle aktif / non-aktif diklik', async () => {
            const mockAds = [
                {
                    id: 1,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Kemitraan Strategis ISF',
                    status: 'terbit',
                    is_active: true,
                    order: 1,
                },
            ];

            vi.spyOn(iklanService, 'get').mockResolvedValue(mockAds);
            const toggleSpy = vi.spyOn(iklanService, 'toggleActive').mockResolvedValue({ id: 1, is_active: false });

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));

            const toggle = root.querySelector('button[role="switch"]');
            expect(toggle).not.toBeNull();

            toggle.click();
            await new Promise((r) => setTimeout(r, 50));

            // Menampilkan SweetAlert konfirmasi dengan wording simpel
            expect(confirmAction).toHaveBeenCalledWith(
                expect.objectContaining({
                    title: 'Nonaktifkan Iklan?',
                    text: expect.stringContaining('Kemitraan Strategis ISF'),
                    confirmText: 'Ya, Nonaktifkan',
                })
            );
            expect(toggleSpy).toHaveBeenCalledWith(1);
        });

        it('merender CmsIklanForm dengan 2 pilihan kategori penempatan dan tab Gambar vs Video pada card materi media saat memilih Modal Popup', async () => {
            const mockExistingAds = [
                {
                    id: 1,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Iklan Spanduk Aktif',
                    status: 'terbit',
                    is_active: true,
                },
                {
                    id: 2,
                    placement: 'popup',
                    type: 'banner',
                    title: 'Iklan Popup Aktif',
                    status: 'terbit',
                    is_active: true,
                },
            ];

            vi.spyOn(cmsResources.iklan, 'list').mockResolvedValue(mockExistingAds);

            const { root } = await renderComponent(CmsIklanForm, { id: null });
            await new Promise((r) => setTimeout(r, 50));

            expect(root.textContent).toContain('Tambah Iklan Baru');
            expect(root.textContent).toContain('Kategori Penempatan Iklan');

            // Select element exists and default is ""
            const select = root.querySelector('select#placement');
            expect(select).not.toBeNull();
            expect(select.value).toBe('');

            const options = Array.from(select.querySelectorAll('option')).map((o) => o.value);
            expect(options).toContain('');
            expect(options).toContain('spanduk');
            expect(options).toContain('popup');
            expect(options).not.toContain('popup_duration');

            // Pilih kategori 'popup'
            select.value = 'popup';
            select.dispatchEvent(new Event('change'));
            await nextTick();

            // Muncul tab Gambar / Banner vs Video (Durasi) di card materi media di atas wording
            expect(root.textContent).toContain('Gambar / Banner');
            expect(root.textContent).toContain('Video (Durasi)');
            // Pengaturan waktu popup (disederhanakan): tampil pertama (detik) & tampil lagi (menit).
            expect(root.textContent).toContain('Waktu Tampil Popup');
            expect(root.textContent).toContain('Tampil pertama setelah');
            expect(root.textContent).toContain('Tampil lagi setelah');

            // Pilih kategori 'spanduk' -> Tidak memunculkan popup konfirmasi lagi (karena spanduk sekarang bisa banyak)
            select.value = 'spanduk';
            select.dispatchEvent(new Event('change'));
            await nextTick();
            expect(confirmAction).not.toHaveBeenCalled();
        });

        it('menonaktifkan tanggal yang sudah terpakai pada date range picker iklan body dan mencegah tanggal bertabrakan (double)', async () => {
            const mockExistingAds = [
                {
                    id: 1,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Iklan Spanduk A',
                    start_date: '2026-11-01',
                    end_date: '2026-11-15',
                    status: 'terbit',
                    is_active: true,
                },
            ];

            vi.spyOn(cmsResources.iklan, 'list').mockResolvedValue(mockExistingAds);

            const { root } = await renderComponent(CmsIklanForm, { id: null });
            await new Promise((r) => setTimeout(r, 50));

            const select = root.querySelector('select#placement');
            select.value = 'spanduk';
            select.dispatchEvent(new Event('change'));
            await nextTick();

            // Memeriksa keberadaan label Rentang Tanggal Penayangan (Date Range)
            expect(root.textContent).toContain('Rentang Tanggal Penayangan (Date Range)');

            // Buka popover date range picker dengan mengklik input bar
            const rangeTrigger = root.querySelector('[data-testid="date-range-trigger"]');
            expect(rangeTrigger).not.toBeNull();
            rangeTrigger.click();
            await nextTick();

            // Popover terbuka dan merender jadwal iklan body lain dengan status terdisable
            expect(root.textContent).toContain('Jadwal iklan body lain terpakai');
            expect(root.textContent).toContain('1 Nov 2026');

            const disabledButtons = root.querySelectorAll('button[disabled]');
            expect(disabledButtons.length).toBeGreaterThan(0);

            // Masukkan tanggal yang bertabrakan (misal 5 Nov 2026) melalui input di popover
            const startInput = root.querySelector('input#pop_start_date');
            expect(startInput).not.toBeNull();
            startInput.value = '2026-11-05';
            startInput.dispatchEvent(new Event('change'));
            await nextTick();

            expect(root.textContent).toContain('Tanggal penayangan bertabrakan dengan jadwal iklan body lain');
        });

        it('menampilkan status Non-Aktif pada tabel jika is_active false, dan mengonfirmasi penonaktifan iklan body lain jika mengaktifkan iklan body baru', async () => {
            const mockAds = [
                {
                    id: 1,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Iklan Spanduk 1',
                    start_date: '2026-11-01',
                    end_date: '2026-11-15',
                    status: 'terbit',
                    is_active: false, // Diset non-aktif
                    order: 1,
                },
                {
                    id: 4,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Iklan Spanduk 2',
                    start_date: '2026-11-16',
                    end_date: '2026-11-30',
                    status: 'terbit',
                    is_active: true,
                    order: 2,
                },
            ];

            vi.spyOn(iklanService, 'get').mockResolvedValue(mockAds);
            const toggleSpy = vi.spyOn(iklanService, 'toggleActive').mockResolvedValue({ id: 1, is_active: true });

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));

            const rows = root.querySelectorAll('tbody tr');
            expect(rows.length).toBe(2);

            // Baris 1 yang is_active = false HARUS menampilkan "Non-Aktif", BUKAN "Aktif Tayang"
            expect(rows[0].textContent).toContain('Non-Aktif');
            expect(rows[0].textContent).not.toContain('Aktif Tayang');

            // Baris 2 yang tanggalnya di masa depan menampilkan "Terjadwal"
            expect(rows[1].textContent).toContain('Terjadwal');

            // Saat admin mencoba mengaktifkan baris 1 (iklan body), muncul peringatan bahwa hanya 1 iklan body yang dapat aktif
            const toggleBtn1 = rows[0].querySelector('button[role="switch"]');
            expect(toggleBtn1).not.toBeNull();
            toggleBtn1.click();
            await new Promise((r) => setTimeout(r, 50));

            expect(confirmAction).toHaveBeenCalledWith(
                expect.objectContaining({
                    title: 'Aktifkan Iklan Body?',
                    text: expect.stringContaining('Hanya 1 iklan body yang dapat aktif'),
                    confirmText: 'Ya, Aktifkan',
                })
            );
            expect(toggleSpy).toHaveBeenCalledWith(1);
        });
    });
});
