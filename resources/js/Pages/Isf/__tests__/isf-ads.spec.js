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
import { useIsfPopupQueue, POPUP_QUEUE_STORAGE_KEY } from '@/Composables/useIsfPopupQueue';

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
        // Tab Kelola Iklan disimpan di URL (?tab=); mulai tiap uji dari URL bersih.
        window.history.replaceState(null, '', '/');
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

    describe('CMS Kelola Iklan — tabel isf_ads: 2 tab, status, statistik & toggle Aktif/Dijeda', () => {
        const baseAd = {
            kicker: 'SPONSOR RESMI',
            target_url: '/register',
            video_url: '',
            video_duration: null,
            start_at: null,
            end_at: null,
            active_hours_start: null,
            active_hours_end: null,
            status: 'active',
            display_state: 'live',
            impressions: 0,
            clicks: 0,
            ctr: 0,
        };

        it('merender 2 tab (Iklan Body & Iklan Popup) dengan kolom jadwal, statistik, dan antrean popup', async () => {
            const mockAds = [
                {
                    ...baseAd,
                    id: 1,
                    placement: 'spanduk',
                    type: 'banner',
                    title: 'Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026',
                    image_url: 'https://example.com/banner.jpg',
                    start_at: '2026-11-01 08:00',
                    end_at: '2026-11-30 23:59',
                    active_hours_start: '08:00',
                    active_hours_end: '17:00',
                    impressions: 1200,
                    clicks: 30,
                    ctr: 2.5,
                    order: 1,
                },
                {
                    ...baseAd,
                    id: 2,
                    placement: 'popup',
                    type: 'banner',
                    title: 'Indonesia Strategy Forum Annual Summit 2026',
                    image_url: 'https://example.com/popup.jpg',
                    first_delay_seconds: null,
                    interval_minutes: 0,
                    order: 2,
                },
                {
                    ...baseAd,
                    id: 3,
                    placement: 'popup',
                    type: 'video',
                    title: 'Teaser Video Kebijakan ISF 2026',
                    image_url: '',
                    video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    video_duration: 30,
                    interval_minutes: 10,
                    order: 3,
                },
            ];

            vi.spyOn(iklanService, 'get').mockResolvedValue(mockAds);

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));

            expect(root.textContent).toContain('Kelola Iklan & Monetisasi');
            expect(root.textContent).toContain('Tambah Iklan');
            expect(root.textContent).toContain('Iklan Body (Spanduk Beranda)');
            expect(root.textContent).toContain('Iklan Popup (Modal Popup)');

            // Default tab: 'body'
            const thBodyList = Array.from(root.querySelectorAll('th')).map((th) => th.textContent.trim());
            expect(thBodyList).toEqual(expect.arrayContaining([
                'No', 'Materi Media', 'Judul Kampanye & Tautan', 'Jadwal & Status Tayang', 'Statistik', 'Status Aktif', 'Aksi',
            ]));
            expect(thBodyList).not.toContain('Format & Penayangan');
            // Spanduk dirotasi acak: nomor biasa tanpa tombol naik/turun.
            expect(thBodyList).not.toContain('Urutan');
            expect(root.querySelector('[data-testid="iklan-no"]').textContent.trim()).toBe('1');
            expect(root.querySelector('button[title="Pindah Urutan Naik"]')).toBeNull();

            const bodyRows = root.querySelectorAll('tbody tr');
            expect(bodyRows.length).toBe(1);
            expect(bodyRows[0].textContent).toContain('Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026');
            expect(bodyRows[0].textContent).toContain('Jam 08:00–17:00');
            expect(bodyRows[0].textContent).toContain('Sedang Tayang');
            const stats = bodyRows[0].querySelector('[data-testid="iklan-stats"]').textContent;
            expect(stats).toContain('Tayangan');
            expect(stats).toContain('1.200');
            expect(stats).toContain('Klik');
            expect(stats).toContain('30');
            expect(stats).toContain('2,5%');
            expect(stats).not.toContain('Bobot');

            // Pindah ke tab 'popup'
            const popupTabBtn = Array.from(root.querySelectorAll('button')).find((b) => b.textContent.includes('Iklan Popup'));
            popupTabBtn.click();
            await nextTick();

            const thPopupList = Array.from(root.querySelectorAll('th')).map((th) => th.textContent.trim());
            expect(thPopupList).toContain('Format & Penayangan');
            expect(thPopupList).toContain('Urutan');
            // Urutan popup dihitung per tab (order 2 & 3 di data → tampil 1 & 2).
            expect(Array.from(root.querySelectorAll('[data-testid="iklan-urutan"]')).map((el) => el.textContent.trim())).toEqual(['1', '2']);
            expect(root.querySelectorAll('button[title="Pindah Urutan Naik"]').length).toBe(2);

            const popupRows = root.querySelectorAll('tbody tr');
            expect(popupRows.length).toBe(2);
            expect(popupRows[0].textContent).toContain('Indonesia Strategy Forum Annual Summit 2026');
            expect(popupRows[0].textContent).toContain('Pertama · 5 detik setelah halaman dibuka');
            expect(popupRows[1].textContent).toContain('Teaser Video Kebijakan ISF 2026');
            expect(popupRows[1].textContent).toContain('10 menit setelah popup sebelumnya ditutup');
            expect(popupRows[1].textContent).toContain('Video (30s)');
        });

        it('menampilkan status tayang dari server (Terjadwal, Di Luar Jam Tayang, Dijeda, Kedaluwarsa)', async () => {
            const mockAds = [
                { ...baseAd, id: 1, placement: 'spanduk', type: 'banner', title: 'A', display_state: 'scheduled', order: 1 },
                { ...baseAd, id: 2, placement: 'spanduk', type: 'banner', title: 'B', display_state: 'off_hours', order: 2 },
                { ...baseAd, id: 3, placement: 'spanduk', type: 'banner', title: 'C', status: 'paused', display_state: 'paused', order: 3 },
                { ...baseAd, id: 4, placement: 'spanduk', type: 'banner', title: 'D', status: 'expired', display_state: 'expired', order: 4 },
            ];
            vi.spyOn(iklanService, 'get').mockResolvedValue(mockAds);

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));

            const states = Array.from(root.querySelectorAll('[data-testid="iklan-display-state"]')).map((el) => el.textContent.trim());
            expect(states).toEqual(['Terjadwal', 'Di Luar Jam Tayang', 'Dijeda', 'Kedaluwarsa']);

            // Filter status "Dijeda"
            Array.from(root.querySelectorAll('button')).find((b) => b.textContent.trim() === 'Dijeda').click();
            await nextTick();
            const rows = root.querySelectorAll('[data-testid="iklan-row"]');
            expect(rows.length).toBe(1);
            expect(rows[0].textContent).toContain('C');
        });

        it('menyimpan tab aktif di URL (?tab=popup) agar tetap saat refresh', async () => {
            vi.spyOn(iklanService, 'get').mockResolvedValue([
                { ...baseAd, id: 1, placement: 'spanduk', type: 'banner', title: 'Spanduk', order: 1 },
                { ...baseAd, id: 2, placement: 'popup', type: 'banner', title: 'Popup', order: 2 },
            ]);

            let view = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));
            Array.from(view.root.querySelectorAll('button')).find((b) => b.textContent.includes('Iklan Popup')).click();
            await nextTick();
            expect(window.location.search).toBe('?tab=popup');

            // "Refresh": mount ulang dengan URL yang sama → tab popup tetap aktif.
            view.unmount();
            view = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));
            expect(view.root.querySelector('tbody').textContent).toContain('Popup');
            expect(Array.from(view.root.querySelectorAll('th')).map((th) => th.textContent.trim())).toContain('Format & Penayangan');

            // Kembali ke tab body → query dihapus.
            Array.from(view.root.querySelectorAll('button')).find((b) => b.textContent.includes('Iklan Body')).click();
            await nextTick();
            expect(window.location.search).toBe('');
            view.unmount();

            // Dibuka dari menu lain (URL tanpa ?tab) → tab body.
            window.history.replaceState(null, '', '/cms/iklan');
            view = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));
            expect(Array.from(view.root.querySelectorAll('th')).map((th) => th.textContent.trim())).not.toContain('Format & Penayangan');
        });

        it('pindah urutan popup hanya menomori ulang popup (1..n), tidak ikut spanduk', async () => {
            vi.spyOn(iklanService, 'get').mockResolvedValue([
                { ...baseAd, id: 1, placement: 'spanduk', type: 'banner', title: 'Spanduk', order: 1 },
                { ...baseAd, id: 2, placement: 'popup', type: 'banner', title: 'Popup A', order: 2 },
                { ...baseAd, id: 3, placement: 'popup', type: 'banner', title: 'Popup B', order: 3 },
            ]);
            const reorderSpy = vi.spyOn(iklanService, 'reorder').mockResolvedValue({});

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));
            Array.from(root.querySelectorAll('button')).find((b) => b.textContent.includes('Iklan Popup')).click();
            await nextTick();

            root.querySelectorAll('button[title="Pindah Urutan Naik"]')[1].click();
            await new Promise((r) => setTimeout(r, 50));
            expect(reorderSpy).toHaveBeenCalledWith([{ id: 3, order: 1 }, { id: 2, order: 2 }]);
        });

        it('memunculkan konfirmasi Jeda / Aktifkan saat toggle diklik', async () => {
            const mockAds = [
                { ...baseAd, id: 1, placement: 'spanduk', type: 'banner', title: 'Kemitraan Strategis ISF', order: 1 },
                { ...baseAd, id: 2, placement: 'spanduk', type: 'banner', title: 'Spanduk Dijeda', status: 'paused', display_state: 'paused', order: 2 },
            ];

            vi.spyOn(iklanService, 'get').mockResolvedValue(mockAds);
            const toggleSpy = vi.spyOn(iklanService, 'toggleActive').mockResolvedValue({ id: 1, status: 'paused' });

            const { root } = await renderComponent(CmsIklanIndex);
            await new Promise((r) => setTimeout(r, 50));

            const toggles = root.querySelectorAll('button[role="switch"]');
            expect(toggles.length).toBe(2);
            expect(toggles[0].getAttribute('aria-checked')).toBe('true');
            expect(toggles[1].getAttribute('aria-checked')).toBe('false');

            toggles[0].click();
            await new Promise((r) => setTimeout(r, 50));
            expect(confirmAction).toHaveBeenCalledWith(
                expect.objectContaining({
                    title: 'Jeda Iklan?',
                    text: expect.stringContaining('Kemitraan Strategis ISF'),
                    confirmText: 'Ya, Jeda',
                })
            );
            expect(toggleSpy).toHaveBeenCalledWith(1);

            // Mengaktifkan spanduk lain tidak lagi menonaktifkan spanduk aktif (rotasi acak).
            root.querySelectorAll('button[role="switch"]')[1].click();
            await new Promise((r) => setTimeout(r, 50));
            expect(confirmAction).toHaveBeenLastCalledWith(
                expect.objectContaining({ title: 'Aktifkan Iklan?', confirmText: 'Ya, Aktifkan' })
            );
            expect(toggleSpy).toHaveBeenCalledWith(2);
        });

        it('merender CmsIklanForm dengan kategori penempatan, tab Gambar vs Video, status, jadwal jam, jam tayang harian, tanpa bobot', async () => {
            vi.spyOn(cmsResources.iklan, 'list').mockResolvedValue([]);

            const { root } = await renderComponent(CmsIklanForm, { id: null });
            await new Promise((r) => setTimeout(r, 50));

            expect(root.textContent).toContain('Tambah Iklan Baru');
            expect(root.textContent).toContain('Kategori Penempatan Iklan');

            const select = root.querySelector('select#placement');
            expect(select.value).toBe('');
            const options = Array.from(select.querySelectorAll('option')).map((o) => o.value);
            expect(options).toEqual(['', 'spanduk', 'popup']);

            // Status: Aktif / Dijeda / Kedaluwarsa (default Aktif)
            const status = root.querySelector('select#status');
            expect(status.value).toBe('active');
            expect(Array.from(status.querySelectorAll('option')).map((o) => o.textContent.trim())).toEqual(['Aktif', 'Dijeda', 'Kedaluwarsa']);

            // Jadwal tanggal + jam & jam tayang harian
            expect(root.textContent).toContain('Rentang Penayangan');
            expect(root.querySelector('input#start_time')).not.toBeNull();
            expect(root.querySelector('input#end_time')).not.toBeNull();
            expect(root.textContent).toContain('Jam Tayang Harian');
            expect(root.querySelector('input#active_hours_start')).not.toBeNull();
            expect(root.querySelector('input#active_hours_end')).not.toBeNull();

            // Popup → tab media & waktu tampil popup
            select.value = 'popup';
            select.dispatchEvent(new Event('change'));
            await nextTick();
            expect(root.textContent).toContain('Gambar / Banner');
            expect(root.textContent).toContain('Video (Durasi)');
            expect(root.textContent).toContain('Waktu Tampil Popup');
            expect(root.textContent).toContain('Tampil pertama setelah');
            expect(root.textContent).toContain('Tampil lagi setelah');

            // Spanduk → tanpa waktu tampil popup & tanpa bobot rotasi
            select.value = 'spanduk';
            select.dispatchEvent(new Event('change'));
            await nextTick();
            expect(root.textContent).not.toContain('Bobot Rotasi');
            expect(root.querySelector('input#weight')).toBeNull();
            expect(root.textContent).not.toContain('Waktu Tampil Popup');
            expect(root.textContent).not.toContain('bertabrakan');
        });

        it('menggabungkan tanggal + jam menjadi start_at / end_at saat menyimpan', async () => {
            vi.spyOn(cmsResources.iklan, 'list').mockResolvedValue([]);
            const createSpy = vi.spyOn(cmsResources.iklan, 'create').mockResolvedValue({ id: 9 });

            const { root } = await renderComponent(CmsIklanForm, { id: null });
            await new Promise((r) => setTimeout(r, 50));

            const select = root.querySelector('select#placement');
            select.value = 'spanduk';
            select.dispatchEvent(new Event('change'));
            await nextTick();

            const setInput = (sel, value) => {
                const el = root.querySelector(sel);
                el.value = value;
                el.dispatchEvent(new Event('input'));
            };
            setInput('input#title', 'Spanduk Uji');
            // Tanggal diisi lewat input di popover date picker.
            root.querySelector('[data-testid="date-range-trigger"]').click();
            await nextTick();
            const startInput = root.querySelector('input#pop_start_date');
            startInput.value = '2099-01-01';
            startInput.dispatchEvent(new Event('change'));
            await nextTick();
            const endInput = root.querySelector('input#pop_end_date');
            endInput.value = '2099-01-31';
            endInput.dispatchEvent(new Event('change'));
            await nextTick();
            setInput('input#start_time', '08:30');
            setInput('input#active_hours_start', '22:00');
            setInput('input#active_hours_end', '02:00');
            await nextTick();

            const saveBtn = Array.from(root.querySelectorAll('button')).find((b) => b.textContent.includes('Simpan Iklan'));
            saveBtn.click();
            await new Promise((r) => setTimeout(r, 50));

            expect(createSpy).toHaveBeenCalledWith(expect.objectContaining({
                placement: 'spanduk',
                title: 'Spanduk Uji',
                start_at: '2099-01-01 08:30',
                end_at: '2099-01-31 23:59',
                active_hours_start: '22:00',
                active_hours_end: '02:00',
                status: 'active',
            }));
        });
    });

    describe('useIsfPopupQueue — frequency capping (localStorage)', () => {
        const Harness = {
            props: ['list'],
            setup(props) {
                const queue = useIsfPopupQueue(() => props.list);
                return () => h('div', { 'data-current': queue.current.value?.id ?? '' }, [
                    h('button', { 'data-testid': 'close', onClick: queue.onClose }),
                ]);
            },
        };
        const popups = [
            { id: 11, firstDelaySeconds: 5, intervalMinutes: 30 },
            { id: 12, firstDelaySeconds: null, intervalMinutes: 10 },
        ];
        const current = (root) => root.querySelector('[data-current]').getAttribute('data-current');

        beforeEach(() => {
            localStorage.clear();
            vi.useFakeTimers();
        });
        afterEach(() => {
            vi.useRealTimers();
        });

        it('popup pertama muncul setelah jeda pertama, popup berikutnya setelah interval, dan tidak muncul lagi saat refresh', async () => {
            let view = await renderComponent(Harness, { list: popups });
            expect(current(view.root)).toBe('');
            vi.advanceTimersByTime(5000);
            await nextTick();
            expect(current(view.root)).toBe('11');

            view.root.querySelector('[data-testid="close"]').click();
            await nextTick();
            expect(current(view.root)).toBe('');
            const saved = JSON.parse(localStorage.getItem(POPUP_QUEUE_STORAGE_KEY));
            expect(saved.nextId).toBe(12);

            // "Refresh": mount ulang → popup 11 tidak muncul lagi; popup 12 menunggu 10 menit sejak ditutup.
            view.unmount();
            view = await renderComponent(Harness, { list: popups });
            vi.advanceTimersByTime(60 * 1000);
            await nextTick();
            expect(current(view.root)).toBe('');
            vi.advanceTimersByTime(9 * 60 * 1000);
            await nextTick();
            expect(current(view.root)).toBe('12');

            // Setelah popup terakhir ditutup → kembali ke popup pertama setelah 30 menit.
            view.root.querySelector('[data-testid="close"]').click();
            await nextTick();
            vi.advanceTimersByTime(29 * 60 * 1000);
            await nextTick();
            expect(current(view.root)).toBe('');
            vi.advanceTimersByTime(60 * 1000);
            await nextTick();
            expect(current(view.root)).toBe('11');
            view.unmount();
        });

        it('refresh saat popup terbuka tidak memunculkannya lagi sebelum jedanya habis', async () => {
            let view = await renderComponent(Harness, { list: [popups[0]] });
            vi.advanceTimersByTime(5000);
            await nextTick();
            expect(current(view.root)).toBe('11');

            view.unmount();
            view = await renderComponent(Harness, { list: [popups[0]] });
            vi.advanceTimersByTime(10 * 60 * 1000);
            await nextTick();
            expect(current(view.root)).toBe('');
            vi.advanceTimersByTime(20 * 60 * 1000);
            await nextTick();
            expect(current(view.root)).toBe('11');
            view.unmount();
        });
    });
});
