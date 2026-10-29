# Perbaikan Loading Staging — Part 3: Ikon SVG Membesar & Skeleton Belum Selaras

Tanggal: 28 Oktober 2026
Status: **Rencana, belum dikerjakan**
Lanjutan dari: [Perbaikan_Loading_Staging.md](Perbaikan_Loading_Staging.md) dan [Perbaikan_Loading_Staging_Part2.md](Perbaikan_Loading_Staging_Part2.md)
Sumber analisis: `.agents/tasks/svg-icon-huge/report.md`

## 1. Masalah

Setelah Part 2 dikerjakan (header blade disamakan dengan header Vue, font Pinyon di-preload, `scrollbar-gutter`), muncul dua hal baru saat load:

1. **Ikon SVG garis tiga (hamburger) tampil sangat besar** sesaat, lalu mengecil ke ukuran normal. Ikon search dan lonceng (bell) terkena masalah yang sama.
2. **Skeleton belum diperbarui** mengikuti komponen/layout baru (gaya Politico: banner carousel, grid 3 kolom). Bentuk skeleton saat loading belum sama dengan tampilan akhir.

---

## Bagian A — Ikon SVG Membesar saat Load

### A.1 Gejala dan cakupan

| SVG            | Lokasi                                                               | Ukuran saat load                            | Ukuran normal |
| -------------- | -------------------------------------------------------------------- | ------------------------------------------- | ------------- |
| Hamburger      | `resources/views/partials/isf-masthead.blade.php` (±baris 10)        | ±1249×1249px (desktop), ±374×374px (mobile) | 20×20         |
| Search         | `resources/views/partials/isf-masthead.blade.php` (±baris 37)        | ±1249×1249 / ±374×374                       | 20×20         |
| Bell (lonceng) | `resources/views/partials/isf-member-masthead.blade.php` (±baris 26) | ±1249×1249                                  | 16×16         |

SVG di skeleton tidak bermasalah (skeleton blade tidak berisi SVG). Panah carousel, pause/play, dan chevron hanya ada di Vue dan baru dirender setelah CSS siap, jadi aman.

### A.2 Penyebab

SVG di partial blade hanya diberi ukuran lewat class Tailwind (`h-5 w-5` / `h-4 w-4`), **tanpa atribut `width`/`height`**. Di mode dev, CSS Tailwind (`main.css`, `isf-theme.css`) baru disuntikkan oleh JS Vite **setelah** halaman pertama kali tampil. Sebelum CSS itu berlaku, SVG tanpa ukuran dirender di ukuran default: lebar 100% wadah, tinggi mengikuti rasio `viewBox` 1:1.

### A.3 Hanya terjadi di mode dev

- **Dev (`public/hot` ada, Vite dev jalan):** CSS disuntikkan JS setelah paint. SVG raksasa benar-benar terlihat ±0,7–7 detik, tergantung kecepatan.
- **Produksi:** `<link rel="stylesheet">` ada di `<head>` dan menahan render. Frame pertama sudah memakai ukuran 20px, jadi tidak pernah terlihat besar.
- **Risiko staging:** masalah ini muncul lagi kalau `public/hot` ikut ter-deploy, atau kalau CSS gagal/terlambat dimuat.

Catatan: class `.h-5 .w-5 .h-4 .w-4` sudah ada di CSS hasil build dan glob Tailwind sudah mencakup `resources/views/**/*.blade.php`, jadi **bukan** masalah class yang tidak terkompilasi.

### A.4 Perbaikan (risiko sangat rendah)

**Langkah A1 (utama): atribut ukuran eksplisit pada SVG blade.** Class Tailwind tetap menang, jadi tampilan akhir tidak berubah.

`resources/views/partials/isf-masthead.blade.php` (hamburger & search):

```html
<svg
    class="h-5 w-5"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    aria-hidden="true"
    focusable="false"
></svg>
```

`resources/views/partials/isf-member-masthead.blade.php` (bell):

```html
<svg
    class="h-4 w-4"
    width="16"
    height="16"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    aria-hidden="true"
    focusable="false"
></svg>
```

**Langkah A2 (pengaman): aturan CSS di `<style>` inline `resources/views/spa.blade.php`.** Melindungi SVG lain yang nanti ditambahkan ke shell tanpa atribut ukuran.

```css
.isf-initial-shell svg:not([width]) {
    width: 1.25rem;
    height: 1.25rem;
    flex: none;
}
```

Jangan memakai `svg { width: 1em }` global, karena bisa menimpa ukuran SVG di Vue.

**Langkah A3 (opsional, konsistensi):** tambahkan juga `width`/`height` pada SVG padanannya di `resources/js/Layouts/IsfPublicLayout.vue` (hamburger & search) dan `resources/js/Layouts/IsfMemberLayout.vue` (bell). Tanpa risiko; hanya jaga-jaga kalau Vue mount sebelum CSS.

---

## Bagian B — Skeleton Belum Selaras dengan Komponen Baru

### B.1 Masalah

Skeleton loading belum diselaraskan dengan **tampilan project ini saat ini**. Acuannya adalah layout/komponen yang sekarang ada di kode (bukan patokan desain lain). Setelah beberapa kali perubahan layout landing, bentuk skeleton tertinggal, lalu "melompat" ke tampilan sekarang saat konten muncul. Tujuan Part 2 — satu bentuk skeleton yang mulus ke konten — baru tercapai kalau skeleton ikut disamakan dengan tampilan terkini.

**Prinsip:** skeleton = cerminan abu-abu dari komponen yang BENAR-BENAR dirender view saat ini. Jadi langkah pertama adalah membaca view dan komponennya sekarang, lalu menyalin struktur blok utamanya ke skeleton — bukan membuat bentuk baru.

### B.2 Inventaris saat ini (hasil cek repo)

Komponen skeleton Vue yang sudah ada di `resources/js/Components/Isf/`:

- `BerandaSkeleton.vue`
- `ArtikelSkeleton.vue` (untuk Isu & Tokoh)
- `AnggotaSkeleton.vue`

Skeleton statis per-path di `resources/views/spa.blade.php` (class `.isf-skel-*`):

- varian Beranda (grid, nav, hero, portal, border kolom)
- varian artikel (`.isf-skel-artikel-grid`)
- varian member (`.isf-skel-member-layout`, `.isf-skel-member-sidebar`)

View yang memakai skeleton / LoadingIndicator:

- `BerandaView.vue`, `IsuDetailView.vue`, `TokohProfileView.vue`, `AnggotaDashboardView.vue`, `InfografikView.vue`

> Catatan: `InfografikView.vue` perlu dicek apakah memakai `LoadingIndicator` (spinner) atau skeleton. Jika masih spinner, selaraskan dengan pola skeleton lain.

### B.3 Cara menyelaraskan (berbasis tampilan sekarang)

Untuk SETIAP varian, kerjakan dengan urutan: **baca view sekarang → daftar blok utamanya → tiru jadi blok abu-abu di skeleton**. Blade dan komponen Vue harus **sama bentuk**. Jangan menambah blok yang tidak ada di view, dan jangan menghapus blok yang ada.

1. **Beranda** — baca `resources/js/Pages/Isf/views/BerandaView.vue` apa adanya, lalu samakan `BerandaSkeleton.vue` dan varian Beranda di blade dengan susunan blok yang sedang dipakai view itu sekarang (urutan section, jumlah kolom, posisi hero/daftar/widget, dan apakah ada banner/carousel). Ukuran dan posisi blok skeleton mengikuti yang aktual, bukan bentuk baru.
2. **Artikel (Isu & Tokoh)** — baca `IsuDetailView.vue` dan `TokohProfileView.vue` sekarang, lalu samakan `ArtikelSkeleton.vue` + `.isf-skel-artikel-grid` dengan struktur aktualnya.
3. **Member/Anggota** — baca `AnggotaDashboardView.vue` sekarang, lalu samakan `AnggotaSkeleton.vue` + `.isf-skel-member-*` (header member, filter, daftar alert, kartu profil) dengan tampilan aktual.
4. **Infografik** — cek `InfografikView.vue`; kalau masih pakai `LoadingIndicator` (spinner), ganti ke skeleton yang menyerupai grid aktualnya.
5. **Header di semua skeleton** — pakai partial masthead yang sama dengan header aktual (hasil Part 2 + perbaikan SVG Bagian A), supaya tidak ada perubahan header saat skeleton → konten.

> Verifikasi kesesuaian: tumpuk screenshot skeleton dan screenshot konten untuk tiap halaman; posisi dan ukuran blok utama harus berdekatan, tanpa lompatan tata letak.

### B.4 Prinsip agar tetap mulus

- Bentuk, lebar container (`max-w-[1400px]`), padding, dan tinggi blok skeleton harus **mendekati** tampilan akhir, supaya tidak ada lompatan tata letak.
- Blade skeleton dan komponen Vue skeleton harus **dijaga sinkron**. Pertimbangkan sumber ukuran yang sama (misalnya variabel CSS atau komentar pengingat di kedua tempat).
- Skeleton tidak perlu identik piksel-demi-piksel; cukup blok abu-abu di posisi dan ukuran yang sama.

### B.5 File yang disentuh

- `resources/views/spa.blade.php` (blok `.isf-skel-*` dan markup `.isf-initial-shell`)
- `resources/js/Components/Isf/BerandaSkeleton.vue`
- `resources/js/Components/Isf/ArtikelSkeleton.vue`
- `resources/js/Components/Isf/AnggotaSkeleton.vue`
- (cek) `resources/js/Pages/Isf/views/InfografikView.vue`
- Komponen carousel yang ditiru bentuknya: `resources/js/Components/Isf/` (komponen banner carousel statis)

---

## 3. Batasan

- Jangan ubah `resources/js/Components/UI/**`.
- Warna tetap `isf-red` #E10600 dan `isf-red-dark` #B30500.
- Tanpa dependency baru.
- Tanpa commit, tanpa menyentuh backend/CMS.

## 4. Cara Verifikasi

1. `npx vite build`, lalu `rm -f public/hot`.
2. Chrome DevTools → Network: **Slow 4G**, CPU **4x**, centang **Disable cache**.
3. Hard refresh di `/beranda`, `/anggota`, `/isu/kuhp-baru-pelaku-usaha`, `/tokoh/budi-santoso`, dan `/infografik`. Uji desktop 1280px, layar tinggi ≥1080px, dan mobile 390px.
4. Hasil yang diharapkan:
    - ikon hamburger, search, dan bell **tidak pernah** tampil besar sejak frame pertama;
    - bentuk skeleton **sama** dengan layout akhir (carousel, 3 kolom, header), tanpa lompatan tata letak;
    - tidak ada perubahan font/logo/lebar (hasil Part 2 tetap terjaga).
5. (Opsional) jalankan ulang `node .agents/tasks/svg-icon-huge/svg.mjs 9333 http://localhost:8000/beranda <out> dev 1 desktop`. Harapannya `maxSvgWidth` ≤ 20 sejak entri pertama dan tidak ada `big=true`.
6. `npx vitest run` — semua tes ISF harus lulus.
7. Ulangi di staging setelah deploy; pastikan `public/hot` tidak ikut ter-deploy.

## 5. Ringkasan

| Bagian | Langkah                                                     | Menghilangkan                         | Risiko            | Prioritas |
| ------ | ----------------------------------------------------------- | ------------------------------------- | ----------------- | --------- |
| A      | A1. `width`/`height` di SVG blade                           | Ikon raksasa saat load                | Sangat rendah     | Wajib     |
| A      | A2. CSS pengaman `.isf-initial-shell svg:not([width])`      | Ikon raksasa untuk SVG baru           | Rendah            | Wajib     |
| A      | A3. `width`/`height` di SVG layout Vue                      | Jaga-jaga                             | Nol               | Opsional  |
| B      | B. Selaraskan skeleton (blade + Vue) dengan layout Politico | Lompatan tata letak skeleton → konten | Rendah (hanya UI) | Wajib     |
