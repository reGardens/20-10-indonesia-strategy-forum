# Perbaikan Loading Staging — Part 2: Font, Logo & Lebar Halaman Berubah saat Refresh

Tanggal: 28 Oktober 2026
Status: **Rencana, belum dikerjakan**
Lanjutan dari: [Perbaikan_Loading_Staging.md](Perbaikan_Loading_Staging.md)
Sumber analisis: `.agents/tasks/loading-layout-shift/report.md`

## 1. Masalah

Langkah 1–3 di Part 1 sudah dikerjakan dan layar blank sudah hilang. Tapi setiap refresh atau hard refresh, halaman masih terlihat berganti ±4 tampilan sebelum stabil:

1. Judul **"Indonesia Strategy Forum"** tampil dengan **3 font berbeda**.
2. **Logo ISF** berubah ukuran dan posisinya bergeser.
3. **Ukuran seluruh website** berubah: konten turun dan lebarnya menyempit.

Gejala ini muncul juga di **build produksi**, jadi bukan hanya masalah mode dev.

## 2. Akar Masalah

Ada **dua header yang berbeda**:

- header statis di `resources/views/spa.blade.php`, yang tampil selama loading;
- header Vue di `resources/js/Layouts/IsfPublicLayout.vue`, yang tampil setelah Vue mount.

Saat header blade diganti header Vue, semua perbedaannya terlihat. Selain itu, font judul baru diunduh setelah header Vue tampil.

## 3. Urutan Tampilan (hasil rekaman, prod, 1280×800, throttled)

| t (ms) | Tampilan | Font judul | Logo | Tinggi header | Posisi `main` |
|---|---|---|---|---|---|
| 2328 | 1. Blade, logo kosong | **Georgia** italic 26px | kotak kosong 46px (JPEG belum ada) | 74px | 115,5 |
| ~3130 | 2. Blade, logo terisi | Georgia | 46×46 | 74px | 115,5 |
| 4629 | 3. Vue mount | **Comic Sans MS** 50px (lebar 609,6px) | **56×56**, x=292 | **104px** | **152,5** (+37px) |
| 5204 | 4. Pinyon termuat | **Pinyon Script** 50px (lebar 480,4px) | 56×56, x=357 (geser 65px) | 104px | 152,5 |
| 8057 | Inter + Newsreader termuat | Pinyon Script | – | – | nav bergeser sedikit |

Di layar tinggi (1920×1080), scrollbar baru muncul pada tampilan 3, sehingga lebar halaman menyempit **15px** (1920 → 1905).

## 4. Penyebab per Gejala

| Gejala | Penyebab | Lokasi |
|---|---|---|
| Font 1: **Georgia** | Header blade memakai inline style `font-family: Georgia, serif`. | `spa.blade.php` (ketiga cabang `@if`) |
| Font 2: **Comic Sans** | Header Vue memakai `"Pinyon Script", cursive`. Selama Pinyon belum terunduh, fallback `cursive` di Windows adalah Comic Sans. | `isf-theme.css` → `.isf-script` |
| Font 3: **Pinyon Script** | Font akhir. Request-nya baru dimulai setelah header Vue dirender (t≈4314), padahal fase blade berlangsung 2–4 detik tanpa preload. | `spa.blade.php` (URL Google Fonts) |
| Logo 46 → 56px | Blade mengunci logo 46px, sedangkan Vue memakai `.isf-masthead-icon` (`height:100%`, maks 56px) yang mengikuti tinggi teks. | `spa.blade.php`, `isf-theme.css` |
| Logo bergeser 65px | Grup logo + teks di-center. Teks Comic Sans 129px lebih lebar dari Pinyon, jadi grupnya bergeser saat font berganti. | (efek dari font 2 → 3) |
| Logo kosong di awal | `logo-isf.jpeg` berukuran 165,6 KB untuk ikon 46–56px. | `public/images/isf/` |
| Konten turun 37px | Header blade 74 + nav 41,5 = 115,5px. Header Vue 104 + nav 48,5 = 152,5px. Padding juga beda: blade `14px 20px`, Vue `px-6 py-6`. | `spa.blade.php` vs `IsfPublicLayout.vue` |
| Lebar menyempit 15px | Halaman blade lebih pendek dari layar, jadi belum ada scrollbar. Saat konten Vue tampil, scrollbar muncul. Tidak ada `scrollbar-gutter`. | CSS global |
| Body margin 8px (**hanya dev**) | Di mode dev, CSS diinjeksi lewat JS, jadi sebelum itu `<body>` memakai margin default. | `public/hot` aktif |

Pergantian header blade → Vue adalah penggantian DOM, sehingga **tidak terhitung CLS**. Lompatan 37px dan 46 → 56px nyata secara visual, tapi tidak masuk angka CLS.

| Run | CLS |
|---|---|
| prod 1280 throttled | 0,0037 |
| prod mobile 390 | **0,0344** (header menyusut 30px saat Pinyon termuat) |
| dev 1280 | 0,0144 (termasuk margin body 8px) |

## 5. Perbaikan Utama (wajib)

Langkah 1 sampai 4 menghilangkan semua gejala. Setelah itu urutannya menjadi: blade shell (identik dengan header Vue, font Pinyon) → konten Vue, **tanpa** perubahan font, ukuran logo, tinggi header, maupun lebar halaman.

### Langkah 1: Samakan header blade dengan header Vue

**Tujuan:** Georgia, lompatan logo 46 → 56px, dan lompatan konten 37px hilang.

**File:**
- `resources/views/spa.blade.php`
- partial baru `resources/views/partials/isf-masthead.blade.php`

**Langkah:**

1. Salin markup masthead dan nav dari `IsfPublicLayout.vue` ke partial blade. Gunakan **class Tailwind yang sama persis**:
   - Container: `max-w-[1400px] px-4 lg:px-6 py-3 sm:py-4 lg:py-6`
   - Logo: `<img class="isf-masthead-icon ...">`, dengan ukuran sama seperti di Vue
   - Judul: `<span class="isf-script text-[30px] sm:text-[38px] lg:text-[46px] xl:text-[50px]">`
   - Nav: class `font-ui` dan tinggi yang sama
2. Hapus inline style `font-family: Georgia` dan `system-ui` di header blade.
3. Pakai partial ini di cabang Beranda dan Isu/Tokoh. Cabang `/anggota` disamakan dengan header `IsfMemberLayout.vue` dengan cara yang sama.
4. Bungkus shell blade dengan `class="isf-theme"` supaya aturan tema berlaku.

**Catatan:** class Tailwind di blade ikut terkompilasi karena glob `./resources/views/**/*.blade.php` sudah ada di `tailwind.config.js`.

**Risiko:** rendah. Header blade dan header Vue harus selalu diubah bersamaan; tulis komentar pengingat di kedua file.

### Langkah 2: Pindahkan `isf-theme.css` ke CSS entry

**Tujuan:** class `.isf-script`, `.isf-masthead-icon`, dan tema lain sudah berlaku saat blade tampil. Ini **prasyarat Langkah 1**.

**File:** `resources/js/spa.js`

```js
import './assets/isf-theme.css';
```

- Opsional: hapus `import "@/assets/isf-theme.css"` di `IsfPublicLayout.vue` dan `IsfMemberLayout.vue`, karena sudah dimuat di entry.
- Dengan begitu CSS tema ikut di `spa-*.css`, yang dimuat render-blocking lewat `<link>` di `<head>`.

**Risiko:** rendah. Tema di-scope ke `.isf-theme`; hanya `.isf-marquee-track` yang global. Entry bertambah ±6 KB.

### Langkah 3: Muat Pinyon Script sejak HTML

**Tujuan:** Comic Sans tidak pernah tampil, dan CLS mobile 0,034 hilang.

**File:** `public/fonts/` (baru) + `resources/views/spa.blade.php`

1. Self-host file `pinyon-script-400.woff2` (±38 KB) di `public/fonts/`.
2. Di `<head>` blade:

```html
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="/fonts/pinyon-script-400.woff2">
<style>
  @font-face {
    font-family: "Pinyon Script";
    src: url("/fonts/pinyon-script-400.woff2") format("woff2");
    font-weight: 400;
    font-style: normal;
    font-display: block; /* atau optional */
  }
</style>
```

3. Keluarkan `Pinyon+Script` dari URL Google Fonts di `spa.blade.php`.
4. Font selesai diunduh selama fase blade (±0,8 dtk; fase blade 2–4 dtk), jadi saat header tampil font sudah siap.

**Risiko:** rendah.

**Alternatif minimal** kalau Langkah 3 belum bisa dikerjakan: ganti fallback di `isf-theme.css`.

```css
.isf-theme .isf-script {
    font-family: "Pinyon Script", Georgia, "Times New Roman", serif;
}
```

Comic Sans hilang, tetapi tetap ada satu kali ganti font.

### Langkah 4: Kunci ruang scrollbar

**Tujuan:** lebar halaman tidak melompat 15px di layar tinggi.

**File:** `<style>` inline di `<head>` `spa.blade.php`

```css
html { scrollbar-gutter: stable; }
```

**Risiko:** rendah. Di halaman yang lebih pendek dari layar, tetap ada ruang kosong 15px di kanan.

## 6. Perbaikan Lanjutan (opsional)

| No | Perbaikan | Dampak | Risiko | File |
|---|---|---|---|---|
| 5 | Preload atau self-host Inter 400/500/700, dengan cara yang sama seperti Langkah 3 | Nav tidak bergeser (CLS 0,0013) | Rendah | `spa.blade.php`, `public/fonts/` |
| 6 | Perkecil logo menjadi versi 112×112 WebP/PNG (±5–10 KB) untuk masthead. JPEG 165,6 KB tetap dipakai untuk favicon/email. Opsional `<link rel="preload" as="image">`. | Kotak logo kosong di awal hilang | Rendah | `public/images/isf/`, `spa.blade.php`, `IsfPublicLayout.vue`, `IsfMemberLayout.vue` |
| 7 | Rapikan URL Google Fonts: hapus Roboto (hanya dipakai email). Playfair dan Barlow bisa dipindah ke halaman yang memakainya, atau dibiarkan. | Kecil (±2,6 KB CSS) | Sedang untuk Playfair (dipakai CMS/undangan) | `spa.blade.php` |

**Pemakaian font saat ini** (untuk Langkah 7):

| Font | Dipakai di |
|---|---|
| Pinyon Script | Judul/wordmark header ISF |
| Inter | Nav dan UI halaman ISF |
| Newsreader | Headline halaman ISF |
| Barlow | Hanya label tabel mobile (`.isf-stack-table [data-label]::before`) |
| Playfair Display | Hanya CMS/undangan |
| Roboto | Hanya `emails/isf/briefing.blade.php` |
| Source Serif 4 | Ada di stack `.font-serif` tapi tidak dimuat (tidak berdampak) |
| Montserrat | `fontFamily.sans` Tailwind, tidak dimuat di SPA |

## 7. Catatan Pengujian Lokal

- Saat ini `public/hot` ada dan Vite dev berjalan di `[::1]:5174`, jadi `localhost:8000` melayani **mode dev**.
- Mode dev menambah satu gejala palsu: margin body 8px.
- Untuk menguji, hentikan Vite dev, lalu jalankan `npx vite build` dan `rm -f public/hot`.
- Pastikan `public/hot` tidak ikut ter-deploy ke staging.

## 8. Cara Verifikasi

1. Jalankan `npx vite build`, lalu `rm -f public/hot`.
2. Di Chrome DevTools → Network, pilih **Slow 4G** dan CPU **4x slowdown**, lalu centang **Disable cache**.
3. Hard refresh (Ctrl+Shift+R) di `/beranda`, `/anggota`, `/isu/kuhp-baru-pelaku-usaha`, dan `/tokoh/budi-santoso`. Uji di layar 1280px **dan** layar tinggi (≥1080px), serta 390px mobile.
4. Hasil yang diharapkan:
   - judul hanya tampil dengan **satu font** (Pinyon Script) sejak awal, tanpa Georgia dan tanpa Comic Sans;
   - logo ISF **tidak berubah ukuran maupun posisi**;
   - header dan konten **tidak turun** saat Vue tampil;
   - lebar halaman **tidak berubah** saat scrollbar muncul;
   - di DevTools → Performance, CLS < 0,01 di desktop dan mobile.
5. Jalankan `npx vitest run`; semua tes ISF harus lulus.
6. Ulangi pengecekan di staging setelah deploy.

## 9. Ringkasan

| Langkah | Menghilangkan | Risiko | Prioritas |
|---|---|---|---|
| 1. Header blade = header Vue | Georgia, logo 46 → 56px, konten turun 37px | Rendah | Wajib |
| 2. `isf-theme.css` di CSS entry | (prasyarat Langkah 1) | Rendah | Wajib |
| 3. Pinyon self-host + preload | Comic Sans, CLS mobile | Rendah | Wajib |
| 4. `scrollbar-gutter: stable` | Lebar menyempit 15px | Rendah | Wajib |
| 5. Preload Inter | Nav bergeser sedikit | Rendah | Opsional |
| 6. Logo kecil (WebP/PNG) | Kotak logo kosong | Rendah | Opsional |
| 7. Rapikan Google Fonts | Beban CSS kecil | Sedang | Opsional |
