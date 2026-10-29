# Perbaikan: Skeleton → Blank → Skeleton saat Load Pertama (Staging)

Tanggal: 28 Oktober 2026
Status: **Rencana, belum dikerjakan**
Sumber analisis: `.agents/tasks/staging-loading-flicker/report.md`

## 1. Masalah

Saat halaman landing dibuka pertama kali di staging, urutan tampilannya:

1. skeleton,
2. layar putih kosong (±2 detik),
3. skeleton lagi,
4. baru konten muncul.

Gejala ini terjadi di semua landing route: `/`, `/beranda`, `/isu/{slug}`, `/tokoh/{slug}`, dan `/anggota`. Di localhost hampir tidak terlihat karena tiap fase hanya ±130–240 ms. Di staging, setiap tahap menunggu round-trip jaringan, jadi jedanya terasa.

## 2. Penyebab

| Fase | Penyebab | Lokasi |
|---|---|---|
| Skeleton 1 | Skeleton statis di blade. Bentuknya selalu Beranda, juga di `/anggota`. Tampil lama karena entry JS ikut memuat chunk `vendor-pdf` (jsPDF + html2canvas, 549 KB) yang tidak dipakai halaman publik. | `resources/views/spa.blade.php`, `vite.config.js` |
| Blank | `app.mount('#app')` dipanggil sebelum router siap. Mount menghapus skeleton blade, padahal chunk layout dan view (lazy) belum terunduh, jadi `<router-view>` kosong. | `resources/js/spa.js` |
| Skeleton 2 | Loading milik view. Request API baru dimulai di `onMounted`, setelah semua chunk selesai. | `useIsfContent` + view ISF |

Hal-hal berikut **bukan** penyebabnya:

- **Double fetch:** setiap API hanya dipanggil 1x per load.
- **Remount** karena `:key`.
- **Redirect.**

Hasil rekaman (throttled, 300 ms latency, 1,5 Mbps, CPU 4x) untuk `/anggota`:

| t (ms) | Fase | Kejadian |
|---|---|---|
| 1216 | Skeleton 1 | HTML ter-paint |
| 1164–8796 | Skeleton 1 | unduh `vendor-pdf` 548,8 KB, `aos` 138,7 KB, `vue-i18n` 62,5 KB |
| 9246 | **Blank** | `app.mount()`, router belum siap (`matched=0`) |
| 9245–10930 | Blank | unduh ±25 chunk lazy (layout, view, komponen) |
| 11066 | Skeleton 2 | view mount, `GET /api/isf/anggota` |
| 12660 | Konten | data siap |

## 3. Perbaikan Utama (wajib)

Langkah 1 sampai 3 sudah cukup untuk menghilangkan gejala. Setelah itu yang tersisa hanya **satu skeleton yang lebih singkat**, lalu konten.

### Langkah 1: Tunggu router siap sebelum mount

Tujuannya menghilangkan fase blank.

File: `resources/js/spa.js`

```js
// Sebelum
app.mount('#app');

// Sesudah
router.isReady().finally(() => app.mount('#app'));
```

- Dengan perubahan ini, skeleton blade tetap tampil sampai chunk layout dan view siap, lalu langsung diganti oleh view.
- `.finally` memastikan app tetap mount walaupun navigasi awal gagal.
- Risiko: **rendah**. Guard `beforeEach` hanya async untuk `/cms/*`.

### Langkah 2: Keluarkan helper preload Vite dari chunk `vendor-pdf`

Tujuannya memangkas skeleton 1 sekitar 5–6 detik di jaringan lambat.

File: `vite.config.js` → `build.rollupOptions.output.manualChunks`

**Penyebab.** Helper `vite/preload-helper` ikut masuk ke chunk `vendor-pdf`. Akibatnya `spa.js` mengimpor `vendor-pdf` secara statis, karena helper itu dipakai oleh semua lazy route.

**Perbaikan.** Tambahkan pengecekan paling awal di `manualChunks`:

```js
manualChunks(id) {
    if (id.includes('vite/preload-helper')) return 'vite-preload';
    // ... aturan yang sudah ada (vendor-pdf, dll.)
}
```

Alternatifnya, pastikan `'vendor-pdf'` hanya dikembalikan untuk id yang berasal dari `node_modules/jspdf` atau `node_modules/html2canvas`.

**Verifikasi setelah `npx vite build`.** `public/build/manifest.json` → `resources/js/spa.js` → `imports` **tidak boleh** lagi berisi `_vendor-pdf-*`.

**Risiko: rendah.** Fitur PDF di CMS tetap lazy.

### Langkah 3: Satu skeleton yang konsisten

Tujuannya agar pengguna hanya melihat satu bentuk skeleton.

1. **Blade memilih skeleton sesuai path.** File: `resources/views/spa.blade.php`.
   - `request()->is('anggota*')` → skeleton portal anggota: header member, filter, dan daftar alert.
   - `request()->is('isu/*')` atau `request()->is('tokoh/*')` → skeleton artikel/profil.
   - Selain itu → skeleton Beranda (yang sekarang).
2. **View memakai skeleton yang bentuknya sama.** Ganti `LoadingIndicator` (spinner) dengan skeleton di:
   - `resources/js/Pages/Isf/views/AnggotaDashboardView.vue`
   - `resources/js/Pages/Isf/views/IsuDetailView.vue`
   - `resources/js/Pages/Isf/views/TokohProfileView.vue`

   Ikuti pola `resources/js/Components/Isf/BerandaSkeleton.vue`, misalnya dengan membuat `AnggotaSkeleton.vue` dan `ArtikelSkeleton.vue` di `Components/Isf/`.
3. Risiko: **rendah** (hanya UI). Markup skeleton di blade dan di komponen Vue harus dijaga tetap sama.

## 4. Perbaikan Lanjutan (opsional)

| No | Perbaikan | Dampak | Risiko | File |
|---|---|---|---|---|
| 4 | Mulai fetch API **paralel** dengan unduhan chunk, di `router.beforeResolve` atau di `spa.js` berdasarkan path awal. Simpan promise sampai dipakai view, atau pakai `prefetchQuery` dari `@tanstack/vue-query` yang sudah terpasang. | Skeleton 2 lebih singkat ±0,5–1,5 dtk | Sedang (perlu invalidasi saat slug berubah) | `resources/js/spa.js` atau `resources/js/router/index.js`, `resources/js/api/services/isfPublicService.js`, `resources/js/Composables/useIsfContent.js` |
| 5a | Pindahkan `import AOS` + `aos.css` dari `spa.js` ke layout yang memakainya. Halaman ISF tidak memakai `data-aos`. | Entry lebih kecil ±139 KB | Sedang (cek halaman lain yang memakai AOS) | `resources/js/spa.js` |
| 5b | Import `store` dan service sisa template wedding (`weddingRegistryService`, dll.) di router dibuat **dinamis** di dalam guard `/cms/*`. | Entry lebih kecil | Sedang | `resources/js/router/index.js` |

## 5. Checklist Server Staging

- [ ] File `public/hot` **tidak ikut ter-deploy**. Sudah ada di `.gitignore`, tapi cek juga skrip deploy/rsync.
- [ ] Setelah deploy jalankan `npx vite build`, dan pastikan `public/build/manifest.json` terbaru.
- [ ] `APP_URL` staging sama dengan origin yang diakses (domain + https), supaya tidak ada redirect atau aset dari origin lain.
- [ ] Aset `/build/assets/*` disajikan dengan **gzip/brotli**.
- [ ] Header cache aset: `Cache-Control: public, max-age=31536000, immutable`.
- [ ] Server memakai **HTTP/2**, karena ada ±35 request chunk paralel.
- [ ] Jalankan `php artisan config:cache` dan `php artisan route:cache` setelah deploy.

## 6. Cara Verifikasi

1. Build produksi dengan `npx vite build`, lalu `rm -f public/hot`.
2. Buka Chrome DevTools → tab Network → throttling **Slow 4G** dan CPU **4x slowdown**.
3. Hard refresh (Ctrl+Shift+R) di `/beranda`, `/anggota`, `/isu/kuhp-baru-pelaku-usaha`, dan `/tokoh/budi-santoso`.
4. Hasil yang diharapkan:
   - tidak ada layar putih kosong;
   - hanya satu bentuk skeleton, sesuai halaman;
   - `vendor-pdf-*.js` tidak diunduh di halaman publik;
   - setiap `/api/isf/*` tetap diminta 1x.
5. Jalankan `npx vitest run`; semua tes ISF harus lulus.
6. Ulangi pengecekan di staging setelah deploy.

## 7. Ringkasan

| Langkah | Menghilangkan | Risiko | Prioritas |
|---|---|---|---|
| 1. `router.isReady()` sebelum mount | Layar blank | Rendah | Wajib |
| 2. Keluarkan preload helper dari `vendor-pdf` | Skeleton 1 yang lama | Rendah | Wajib |
| 3. Skeleton blade = skeleton view | Skeleton ganda | Rendah | Wajib |
| 4. Fetch API paralel | Sebagian waktu skeleton 2 | Sedang | Opsional |
| 5. Kecilkan entry + setting server | Waktu load total | Sedang | Opsional |
