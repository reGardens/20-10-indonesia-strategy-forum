# Cetak Biru Platform ISF (Indonesia Strategy Forum)

Dokumen ini adalah turunan tertulis dari `docs/Cetak_Biru_Platform_ISF.pdf` (15 slide, "Draf Visualisasi & Grafis Arsitektur untuk Tim Eksekusi", ditujukan kepada Bapak Khoirul Fajar, Chief Developer). Semua bagian disatukan dalam satu file ini, tetapi tetap dipecah per bagian pengerjaan supaya tiap modul bisa dikerjakan, di-review, dan diestimasi secara terpisah.

## Konvensi penulisan

- **[Slide N]** = poin yang tertulis eksplisit di slide ke-N pada PDF.
- **[Usulan]** = rincian teknis turunan (tabel, route, acceptance criteria) yang tidak ada di slide, disusun agar modul siap dikerjakan. Wajib dikonfirmasi sebelum dikunci.
- **[Perlu keputusan]** = hal yang ambigu atau bertentangan di slide; dikumpulkan juga di [Bagian 12](#modul-12).

## Daftar modul

| No  | Bagian                 | Modul                                                               | Zona sitemap | Target rilis         |
| --- | ---------------------- | ------------------------------------------------------------------- | ------------ | -------------------- |
| 00  | [Bagian 00](#modul-00) | Visi, misi, matriks pembeda, prinsip desain                         | -            | -                    |
| 01  | [Bagian 01](#modul-01) | Fondasi: graf akuntabilitas kebijakan, ID permanen, taksonomi       | Semua        | R1 (fondasi)         |
| 02  | [Bagian 02](#modul-02) | Akun, peran, akun korporasi & kursi                                 | Semua        | R1 (dasar), R3 (B2B) |
| 03  | [Bagian 03](#modul-03) | Beranda, video, artikel bersumber primer, infografik                | Zona 1       | R1                   |
| 04  | [Bagian 04](#modul-04) | Profil & Tokoh Pekan Ini, Jejak Kebijakan, Skor Sorotan             | Zona 1 + 3   | R1 (kurasi manual)   |
| 05  | [Bagian 05](#modul-05) | Kartu isu 7 kerangka, Lensa GRC, transparansi metodologi            | Zona 1 + 3   | R1 (kurasi manual)   |
| 06  | [Bagian 06](#modul-06) | Daftar 20 RUU prioritas, detail & linimasa RUU, Ikuti RUU           | Zona 1       | R2                   |
| 07  | [Bagian 07](#modul-07) | Katalog data statistik (embed), Insight Center                      | Zona 1       | Belum ditentukan     |
| 08  | [Bagian 08](#modul-08) | Dasbor Peringatan Dini, penjejak regulasi sektoral, unduhan dataset | Zona 2       | R3 (Beta)            |
| 09  | [Bagian 09](#modul-09) | Ringkasan pagi via surel / WhatsApp                                 | Zona 2       | R1                   |
| 10  | [Bagian 10](#modul-10) | Editor blok, cek sumber AI, verifikasi ganda, log koreksi           | Zona 3       | R1                   |
| 11  | [Bagian 11](#modul-11) | Tarik data JDIH/DPR, taksonomi, tinjauan klasifikasi AI             | Zona 4       | R1 (dasar), R2 (DPR) |
| 12  | [Bagian 12](#modul-12) | Konflik stack, celah jadwal, hal yang harus diputuskan              | -            | Sebelum mulai        |
| 13  | [Bagian 13](#modul-13) | Peta jalan MVP R1–R3, urutan kerja, tabel estimasi                  | -            | -                    |

## Urutan pengerjaan yang disarankan [Usulan]

```
01 Model data ─┬─> 02 Auth & peran ──> 10 Redaksi ──> 03 Portal publik
               │                                      ├─> 04 Profil tokoh
               │                                      └─> 05 Anatomi isu
               ├─> 11 Admin & ingestion ──> 06 Pelacak legislasi (R2)
               └─> 09 Email briefing (R1) ──> 08 Portal B2B (R3)
07 Pusat data: menunggu keputusan jadwal (lihat 12)
```

Alasannya: semua halaman publik membaca entitas dari graf (01), dan tidak ada konten yang boleh terbit tanpa alur redaksi verifikasi ganda (10). Jadi 01, 02, dan 10 adalah jalur kritis R1.

## Peta sitemap [Slide 5]

Domain: `indonesiastrategyforum.co.id`

| Zona | Nama                  | Isi                                                                                            |
| ---- | --------------------- | ---------------------------------------------------------------------------------------------- |
| 1    | Kelompok Publik       | Beranda & Video; Artikel Bersumber Primer; Halaman Tokoh & Isu; Pelacak Legislasi (Detail RUU) |
| 2    | Anggota B2B (Premium) | Dasbor Peringatan Dini; Penjejak Regulasi Sektoral; Unduhan Dataset Mentah                     |
| 3    | Kelompok Redaksi      | Panel Editor Blok & Verifikasi Ganda; Panel Kurasi Tokoh & Isu                                 |
| 4    | Admin & Data          | Panel Tarik Data (JDIH, DPR); Pengelolaan Taksonomi Entitas; Tinjauan Klasifikasi AI           |

---

<a id="modul-00"></a>

## 00 · Visi, Misi & Prinsip Desain

Sumber: Slide 1, 2, 4.

### Misi [Slide 2]

"Lebih dari sekadar portal berita." ISF bukan portal berita biasa yang mengejar clickbait, melainkan **Platform Intelijen Kebijakan**. Tiga pilar:

1. Data kebijakan publik, politik, dan hukum yang **terstruktur**.
2. **Nyaman dibaca analis manusia** dan **ramah perayap mesin AI**.
3. **Relasi informasi dinamis**, bukan sekadar volume publikasi.

### Matriks pembeda: CMS lama vs Platform Cerdas ISF [Slide 4]

| Aspek                   | Pendekatan CMS lama                     | Platform Cerdas ISF                                               | Modul terkait |
| ----------------------- | --------------------------------------- | ----------------------------------------------------------------- | ------------- |
| Unit dasar data         | Halaman lepas & postingan tunggal       | Entitas berstruktur dengan taksonomi terkontrol                   | 01, 11        |
| Riwayat tokoh & jabatan | Teks biografi manual (cepat usang)      | Riwayat jabatan terstruktur; alias disatukan via ID tetap         | 01, 04        |
| Tata kelola redaksi     | Draf sederhana & publikasi langsung     | Verifikasi dua lapis editor & log koreksi transparan              | 10            |
| Keanggotaan akses B2B   | Plugin langganan pembaca retail standar | Akun korporasi dengan manajemen kursi (seats) & penjejak sektoral | 02, 08        |

### Prinsip desain yang menjadi acuan semua modul [Usulan, diturunkan dari slide]

| Prinsip                                      | Asal           | Konsekuensi teknis                                                                                                                        |
| -------------------------------------------- | -------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Satu model data tunggal, ID permanen         | Slide 3        | Setiap entitas punya ID/slug stabil yang tidak berubah saat nama/judul diganti. Halaman dirender dari entitas, bukan dari teks bebas.     |
| Fakta selalu bersumber primer                | Slide 6        | Setiap klaim fakta wajib tertaut ke dokumen primer dengan URL jelas. Klaim tanpa rujukan ditandai AI (Slide 12).                          |
| Teks murni, ramah AI                         | Slide 6        | Konten utama dirender di server sebagai HTML semantik; tidak bergantung pada JavaScript untuk tampil. Sertakan structured data (JSON-LD). |
| AI menyaring, manusia memutuskan             | Slide 12       | AI hanya memberi tanda/saran. Tidak ada akun yang bisa menerbitkan konten sendirian.                                                      |
| Transparansi                                 | Slide 7, 9, 12 | Metodologi seleksi isu, metodologi data, dan log koreksi dapat dilihat publik.                                                            |
| Penilaian berbasis kebijakan, bukan sentimen | Slide 8        | Skor Sorotan dihitung dari keterlibatan RUU & dampak kebijakan, bukan dari media sosial.                                                  |

---

<a id="modul-01"></a>

## 01 · Model Data: Graf Akuntabilitas Kebijakan

Sumber: Slide 3, 4, 5 (Zona 4).
Target rilis: **R1** (fondasi, dikerjakan paling awal).

### Tujuan

Menyediakan satu model data tunggal tempat semua halaman (artikel, tokoh, isu, RUU, data, dasbor B2B) mengambil informasi. Ini fondasi yang membedakan ISF dari CMS biasa.

> **Catatan Arsitektur [Slide 3]:** Satu model data tunggal dengan ID permanen. Perubahan pada satu entitas akan otomatis memperbarui entitas terkait lainnya di seluruh sistem.

### Enam entitas inti [Slide 3]

| Entitas              | Keterangan                                    | Contoh                             |
| -------------------- | --------------------------------------------- | ---------------------------------- |
| **Tokoh**            | Individu (pejabat, legislator, hakim, dll.)   | Anggota DPR, menteri               |
| **RUU**              | Rancangan Undang-Undang                       | RUU Perampasan Aset                |
| **Pasal / Regulasi** | Produk hukum yang berlaku, sampai level pasal | KUHP baru, PP, Permen              |
| **Isu**              | Topik kebijakan yang dikurasi                 | Dampak KUHP baru bagi pelaku usaha |
| **Putusan**          | Putusan pengadilan (MK, MA, dll.)             | Putusan MK uji materi              |
| **Anggaran**         | Pos anggaran negara/program                   | Pos APBN sektor tertentu           |

### Relasi antar-entitas [Slide 3]

Label di slide memiliki beberapa salah ketik ("Menwsahas", "Memololala"). Tabel di bawah adalah interpretasi yang paling masuk akal; **arah dan label final perlu dikonfirmasi** [Perlu keputusan].

| Dari        | Relasi                            | Ke               | Catatan                        |
| ----------- | --------------------------------- | ---------------- | ------------------------------ |
| Tokoh       | Mengusulkan                       | RUU              | Jelas di slide                 |
| RUU         | Menghasilkan                      | Pasal / Regulasi | Jelas di slide                 |
| Isu         | Memperjuangkan / Membahas         | Tokoh            | Jelas di slide                 |
| Isu         | Membahas _(slide: "Menwsahas")_   | RUU, Anggaran    | Diduga salah ketik "Membahas"  |
| Putusan     | Membatalkan / Mengubah            | Pasal / Regulasi | Panah dua arah di slide        |
| Tokoh / RUU | Mengelola                         | Anggaran         | Garis vertikal tengah          |
| Isu         | Mengelola _(slide: "Memololala")_ | Putusan          | Diduga salah ketik "Mengelola" |
| Isu         | Mengelola                         | Anggaran         | Jelas di slide                 |
| Anggaran    | Membiayai / Mengatasi             | RUU, Pasal, Isu  | Dari simpul tengah kanan       |
| Anggaran    | Melaksanakan                      | Putusan          | Jelas di slide                 |
| Tokoh       | (terkait)                         | Putusan          | Garis diagonal tanpa label     |

### Rincian teknis [Usulan]

#### Pola penyimpanan

Disarankan pola **tabel entitas + tabel relasi generik**, bukan graph database, agar tetap sederhana di stack relasional:

```
entities            (opsional, jika memakai super-table)
tokoh               id, uid, slug, nama, foto, ringkasan, ...
tokoh_aliases       id, tokoh_id, alias              -- penyatuan alias via ID tetap [Slide 4]
tokoh_jabatan       id, tokoh_id, jabatan, lembaga, mulai, selesai, sumber_url  -- riwayat jabatan [Slide 4]
ruu                 id, uid, slug, judul, sektor_id, status_tahap, prioritas, ...
ruu_tahapan         id, ruu_id, tahap, tanggal, ringkasan, sumber_url
regulasi            id, uid, slug, jenis, nomor, tahun, judul, sumber_url (JDIH)
pasal               id, regulasi_id, nomor, isi
isu                 id, uid, slug, judul, minggu, ...
putusan             id, uid, slug, lembaga, nomor, tanggal, amar, sumber_url
anggaran            id, uid, slug, tahun, pos, nilai, sumber_url

entity_relations    id, from_type, from_id, relation, to_type, to_id,
                    peran (opsional), tanggal, sumber_url, created_by, verified_by
taxonomies          id, jenis (sektor|topik|lembaga|...), nama, slug, parent_id
entity_taxonomy     entity_type, entity_id, taxonomy_id
```

Poin penting:

- `uid` adalah **ID permanen** (mis. ULID/UUID) yang tidak pernah berubah. `slug` boleh berubah, dengan redirect 301 dari slug lama.
- `entity_relations.relation` diisi dari daftar enum terkontrol (tabel di atas), bukan teks bebas.
- Setiap relasi dan fakta menyimpan `sumber_url` (prinsip sumber primer).
- "Perubahan otomatis memperbarui entitas terkait" dicapai dengan **membaca relasi saat render** (bukan menyalin data), ditambah invalidasi cache entitas yang terkait ketika satu entitas diubah.

#### Tahapan RUU (dipakai modul 06) [Slide 9]

Enum `tahap`: `usulan`, `pembahasan_1`, `pembahasan_2`, `harmonisasi`, `diundangkan`.

#### Sektor [Slide 9, 10]

Nilai yang muncul di mock: `Ekonomi`, `Hukum`, `Sosial`. Dikelola sebagai taksonomi (modul 11) agar bisa ditambah.

### Acceptance criteria [Usulan]

- [ ] Keenam entitas bisa dibuat, diubah, dan dihubungkan lewat relasi terkontrol.
- [ ] Mengubah nama tokoh langsung tercermin di semua artikel/isu/RUU yang menautnya tanpa edit manual.
- [ ] Alias tokoh (mis. nama panggilan, gelar) mengarah ke satu `uid` yang sama.
- [ ] Riwayat jabatan tokoh tersimpan sebagai baris berperiode, bukan paragraf teks.
- [ ] Setiap relasi menyimpan sumber dan siapa yang memverifikasi.
- [ ] Ganti slug membuat redirect 301 dari slug lama.

### Dependensi

- Dipakai oleh: 03, 04, 05, 06, 07, 08, 09, 11.
- Bergantung pada: keputusan stack (lihat 12).

---

<a id="modul-02"></a>

## 02 · Autentikasi, Peran & Akses

Sumber: Slide 4, 5, 8, 11, 12.
Target rilis: **R1** (peran internal & publik), **R3** (akun korporasi B2B & kursi).

### Tujuan

Memisahkan empat zona sitemap (Publik, B2B, Redaksi, Admin) dengan kontrol akses berbasis peran, dan menegakkan prinsip "tidak ada satu akun pun yang bisa menerbitkan konten sendirian".

### Kebutuhan dari slide

- **Zona akses** [Slide 5]: Publik (tanpa login), Anggota B2B (premium), Redaksi, Admin & Data.
- **Akun korporasi dengan manajemen kursi (seats)** [Slide 4]: satu organisasi berlangganan, punya sejumlah kursi pengguna.
- **Personalisasi notifikasi pengguna B2B** lewat tombol "Ikuti Tokoh" [Slide 8] dan "Ikuti RUU" [Slide 9].
- **Verifikasi ganda berbasis peran** [Slide 12]: publikasi butuh minimal dua orang berbeda.
- **Uji penetrasi keamanan** sebelum R3 [Slide 14].

### Daftar peran [Usulan]

| Peran                      | Zona | Hak utama                                                                   |
| -------------------------- | ---- | --------------------------------------------------------------------------- |
| `pengunjung` (tanpa login) | 1    | Baca konten publik                                                          |
| `anggota_b2b`              | 1, 2 | Dasbor peringatan, ikuti tokoh/RUU, unduh dataset, terima briefing          |
| `admin_korporasi`          | 1, 2 | Semua hak `anggota_b2b` + kelola kursi organisasinya (undang/cabut anggota) |
| `penulis`                  | 3    | Buat & ubah draf miliknya                                                   |
| `editor_1`                 | 3    | Verifikasi fakta primer (tahap Editor 1)                                    |
| `editor_2_legal`           | 3    | Persetujuan akhir untuk konten berisiko hukum                               |
| `kurator`                  | 3    | Kurasi tokoh & isu (Panel Kurasi)                                           |
| `admin_data`               | 4    | Tarik data JDIH/DPR, kelola taksonomi, tinjau klasifikasi AI                |
| `super_admin`              | 4    | Kelola pengguna, peran, organisasi                                          |

Aturan tambahan:

- Seseorang boleh memegang beberapa peran, tetapi **sistem menolak** jika orang yang sama menjadi penulis dan verifikator pada konten yang sama, atau menjadi Editor 1 dan Editor 2 pada konten yang sama.
- `super_admin` juga tunduk pada aturan verifikasi ganda (tidak ada jalan pintas publikasi).

### Model organisasi & kursi [Usulan]

```
organizations        id, nama, sektor_minat, paket, kursi_maks, aktif_hingga
organization_users   organization_id, user_id, peran (admin_korporasi|anggota), status
follows              user_id, followable_type (tokoh|ruu|isu|sektor), followable_id
```

### Acceptance criteria [Usulan]

- [ ] Halaman Zona 2/3/4 tidak bisa diakses tanpa peran yang sesuai (diuji per route).
- [ ] Undangan anggota ditolak jika kursi organisasi sudah penuh.
- [ ] Langganan kedaluwarsa mencabut akses Zona 2 tanpa menghapus data organisasi.
- [ ] Upaya publikasi oleh satu akun saja selalu gagal, termasuk untuk super admin.
- [ ] Login memiliki rate limiting; sesi internal (Redaksi/Admin) mendukung 2FA.
- [ ] Hasil pen-test R3 tidak menyisakan temuan tingkat tinggi/kritis yang terbuka.

### Dependensi

- Bergantung pada: 01 (untuk `follows`).
- Dipakai oleh: 08, 09, 10, 11.

---

<a id="modul-03"></a>

## 03 · Portal Publik & Artikel Inti

Sumber: Slide 2, 5 (Zona 1), 6.
Target rilis: **R1** (12 Februari 2027).

### Tujuan

Menyajikan konten kebijakan yang nyaman dibaca analis dan mudah dirayapi mesin AI, dengan setiap fakta tertaut ke dokumen primer.

### Halaman dalam lingkup [Slide 5]

- Beranda & Video
- Artikel Bersumber Primer
- Halaman Tokoh & Isu (detail di modul 04 dan 05)
- Pelacak Legislasi / Detail RUU (detail di modul 06, rilis R2)

### Tata letak beranda / artikel (dari mock) [Slide 6]

Mock berjudul "Draft Artikel Hukum ISF" memperlihatkan:

| Area                  | Isi                                                                                                                                                   |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bar atas              | Label sorotan berwarna (badge) + teks pengumuman singkat, info di kanan                                                                               |
| Hero kiri             | Infografik besar, kategori/tanggal, judul utama (contoh: "Apa yang berubah bagi pelaku usaha setelah KUHP baru berlaku"), ringkasan, metadata penulis |
| Kolom kanan "Terkini" | Daftar berita terbaru dengan label kategori berwarna (politik, ekonomi, kebijakan, hukum, RUU)                                                        |
| "Infografik Terbaru"  | Grid 4 kartu infografik + tautan "Lihat semua"                                                                                                        |
| "Pekan Ini"           | Bagian rangkuman mingguan dengan rentang tanggal (contoh "15-10 Okt 2026")                                                                            |

### Tiga ketentuan kunci [Slide 6]

1. **Blok Fakta Kunci**: selalu tertaut langsung ke dokumen primer yang beralamat jelas.
2. **Format teks murni**: mudah dirender dan dirayapi mesin AI tanpa gangguan skrip.
3. **Grafik interaktif** bersumber data langsung.

### Rincian teknis [Usulan]

#### Blok Fakta Kunci

- Tipe blok khusus di editor (modul 10): `{ klaim, sumber_url, sumber_judul, sumber_tanggal, entitas_terkait[] }`.
- Validasi: `sumber_url` wajib dan harus URL valid; peringatan bila domain bukan sumber primer (mis. bukan `jdih.*`, `dpr.go.id`, `mkri.id`, `go.id`). Daftar domain primer dikelola admin.
- Ditampilkan sebagai kotak terpisah dengan tautan sumber yang terlihat jelas.

#### Ramah perayap AI

- Konten artikel dirender di server (SSR) sebagai HTML semantik (`<article>`, `<h1>`–`<h3>`, `<time>`, `<cite>`). Halaman harus terbaca utuh dengan JavaScript dimatikan.
- JSON-LD: `NewsArticle`/`Article`, `Person` (tokoh), `Legislation` (RUU/regulasi), `Dataset` (modul 07).
- Sitemap XML otomatis dan `robots.txt` yang eksplisit mengizinkan perayap. Pertimbangkan `llms.txt`.
- Tautan entitas di badan artikel (nama tokoh, RUU, regulasi) mengarah ke halaman entitasnya.

#### Grafik interaktif

- Grafik mengambil data dari dataset di modul 07, bukan gambar statis. Sediakan fallback tabel HTML untuk perayap dan pembaca layar.

#### Route [Usulan]

| Route              | Halaman                            |
| ------------------ | ---------------------------------- |
| `/`                | Beranda                            |
| `/video`           | Daftar video                       |
| `/artikel/{slug}`  | Detail artikel                     |
| `/kategori/{slug}` | Daftar artikel per kategori/sektor |
| `/infografik`      | Daftar infografik                  |
| `/koreksi`         | Log koreksi publik (modul 10)      |

### Acceptance criteria [Usulan]

- [ ] Artikel tidak bisa disimpan untuk verifikasi jika ada Blok Fakta Kunci tanpa sumber.
- [ ] `curl` ke halaman artikel mengembalikan judul, isi, fakta kunci, dan sumber lengkap dalam HTML (tanpa eksekusi JS).
- [ ] JSON-LD lolos Google Rich Results Test / validator schema.org.
- [ ] Skor Lighthouse Performance, Accessibility, dan SEO ≥ 90 di mobile.
- [ ] Artikel yang direvisi setelah terbit menampilkan catatan koreksi dan tautan ke log koreksi.
- [ ] Gambar & infografik punya teks alternatif; grafik punya tabel data alternatif.

### Dependensi

- Bergantung pada: 01, 10.
- Terkait: 04, 05, 06, 07.

---

<a id="modul-04"></a>

## 04 · Fitur Unggulan 1: Profil & Tokoh Pekan Ini

Sumber: Slide 4, 5, 8, 14.
Target rilis: **R1** dengan kurasi manual tokoh [Slide 14]. Tombol "Ikuti Tokoh" bergantung pada akun B2B, lihat [Perlu keputusan] di bawah.

### Tujuan

Halaman profil tokoh yang datanya ditarik dari graf entitas, menampilkan jejak kebijakan seseorang secara terstruktur, bukan biografi teks yang cepat usang.

### Komponen di mock [Slide 8]

| Komponen                            | Isi di mock                                                                                                                                                   | Keterangan slide                                                                                         |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Header situs                        | Logo, kolom "Search profile", ikon notifikasi, avatar                                                                                                         | -                                                                                                        |
| Judul halaman                       | "Profil & Tokoh Pekan Ini"                                                                                                                                    | -                                                                                                        |
| Kartu identitas                     | Foto, nama, jabatan, 4 angka statistik (contoh: 244, 15:in 1k, 263, 34)                                                                                       | Label statistik di mock tidak terbaca jelas                                                              |
| **Skor Sorotan**                    | Persentase (contoh 50%)                                                                                                                                       | Penilaian berbasis keterlibatan RUU & dampak kebijakan, **bukan** sentimen media sosial                  |
| **Tombol "Ikuti Tokoh"**            | Tombol hitam kanan atas                                                                                                                                       | Untuk personalisasi notifikasi pengguna B2B                                                              |
| **Policy Matrix / Jejak Kebijakan** | Tabel: baris = kebijakan/RUU; kolom = keterlibatan per tahap (contoh header: "Aktif/Pengusul", "Anggota", "Menolak/Mempertanyakan"), sel berisi tanda centang | Tabel terstruktur yang menarik data relasional otomatis dari partisipasi tokoh di setiap tahap legislasi |
| Related articles                    | Daftar artikel terkait + waktu ("3 months ago")                                                                                                               | -                                                                                                        |
| Calendar                            | Kalender bulanan + tombol "Subscribe"                                                                                                                         | Diduga agenda tokoh (sidang, rapat)                                                                      |

### Rincian teknis [Usulan]

#### Data

- Profil: tabel `tokoh`, `tokoh_aliases`, `tokoh_jabatan` (modul 01).
- Jejak Kebijakan: query `entity_relations` dengan `from=tokoh` ke `ruu`, dikelompokkan per `ruu_tahapan`. Kolom matriks = jenis peran (`pengusul`, `pembahas`, `pendukung`, `penolak`), **label kolom final perlu dikonfirmasi** karena tidak terbaca di mock.
- Statistik kartu: diusulkan jumlah RUU diusulkan, RUU dibahas, artikel yang menyebut, putusan terkait. Perlu konfirmasi.
- Agenda/kalender: tabel `agenda (tokoh_id, judul, tanggal, sumber_url)`; tombol Subscribe mengekspor `.ics`.

#### Skor Sorotan Kebijakan

Rumus belum ditentukan di slide. Usulan awal untuk didiskusikan:

```
skor = w1·(jumlah RUU diusulkan, bobot tahap)
     + w2·(partisipasi pembahasan)
     + w3·(dampak: jumlah regulasi/anggaran/putusan terkait)
     dinormalisasi 0–100 dalam jendela waktu tertentu (mis. 30 hari)
```

Syarat: rumus dan bobot dipublikasikan di halaman metodologi (prinsip transparansi), dan tidak memakai data sentimen media sosial.

#### Tokoh Pekan Ini

- R1: dipilih manual oleh `kurator` di Panel Kurasi (Zona 3).
- Kemudian: bisa disarankan otomatis dari Skor Sorotan tertinggi, tetap disetujui manusia.

#### Route [Usulan]

| Route                      | Halaman                                           |
| -------------------------- | ------------------------------------------------- |
| `/tokoh`                   | Daftar & pencarian tokoh, sorotan Tokoh Pekan Ini |
| `/tokoh/{slug}`            | Profil tokoh                                      |
| `/tokoh/{slug}/agenda.ics` | Ekspor kalender                                   |

### Acceptance criteria [Usulan]

- [ ] Menambah relasi tokoh–RUU di admin langsung muncul di Policy Matrix tanpa edit halaman.
- [ ] Pencarian dengan alias tokoh menemukan profil yang sama.
- [ ] Riwayat jabatan tampil berurutan waktu dengan sumber.
- [ ] Skor Sorotan menampilkan tautan "cara menghitung".
- [ ] Tombol "Ikuti Tokoh" untuk pengunjung non-B2B mengarah ke halaman info langganan/login.

### [Perlu keputusan]

- "Ikuti Tokoh" ditujukan untuk pengguna B2B, sedangkan portal B2B baru Beta di R3. Apakah tombol aktif di R1 (hanya menyimpan follow + mengisi Email Briefing) atau disembunyikan sampai R3?
- Definisi 4 angka statistik dan label kolom Policy Matrix.
- Rumus Skor Sorotan.

### Dependensi

- Bergantung pada: 01, 10 (kurasi), 02 (follow).
- Dipakai oleh: 08, 09.

---

<a id="modul-05"></a>

## 05 · Fitur Unggulan 2: Anatomi Isu Mingguan

Sumber: Slide 5, 7, 14.
Target rilis: **R1** dengan kurasi manual isu [Slide 14].

### Tujuan

Membedah isu kebijakan yang kompleks ke dalam **7 kerangka analitis yang terstandarisasi** pada satu kartu isu, sehingga mudah dipindai pembaca dan konsisten antar-isu.

### Ketentuan dari slide [Slide 7]

1. **Desain kartu isu tunggal**: membedah isu kompleks ke dalam 7 kerangka analitis terstandarisasi agar mudah dipindai.
2. **Modul Lensa GRC** (Governance, Risk, Compliance): komponen krusial yang dicari analis pengguna korporasi. Di mock berada di bagian **05**.
3. **Transparansi metodologi**: panel penjelas "Cara isu ini dipilih" yang mengedukasi pembaca tentang kurasi AI & Redaksi. Di mock berupa panel gelap di sidebar kanan.

### Struktur halaman di mock [Slide 7]

| Bagian  | Isi yang terlihat di mock                                                                                                                           |
| ------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Header  | Navigasi kategori, pencarian, tombol berlangganan                                                                                                   |
| 01      | Judul isu (contoh "Apa yang berubah bagi pelaku usaha setelah KUHP baru berlaku"), ringkasan, metadata, tombol aksi (bagikan, simpan, unduh, kutip) |
| 02      | Visual utama / infografik                                                                                                                           |
| 03      | Daftar poin bernomor (diduga fakta kunci / linimasa)                                                                                                |
| 04      | Kutipan atau pernyataan tokoh                                                                                                                       |
| 05      | **Lensa GRC**                                                                                                                                       |
| 06      | Paragraf analisis (diduga dampak / reaksi pemangku kepentingan)                                                                                     |
| 07      | Penutup dengan tag/tautan lanjutan (diduga "apa selanjutnya")                                                                                       |
| Bawah   | Pertanyaan yang sering diajukan, artikel terkait                                                                                                    |
| Sidebar | Isu terpopuler, panel "Cara isu ini dipilih", blok iklan/promosi                                                                                    |

Teks pada kerangka 03, 04, 06, 07 di mock tidak terbaca, jadi nama ketujuh kerangka **belum pasti**.

### Usulan 7 kerangka analitis [Usulan, perlu konfirmasi]

| No  | Kerangka               | Isi minimum                                                                                                     |
| --- | ---------------------- | --------------------------------------------------------------------------------------------------------------- |
| 01  | Ringkasan Isu          | Apa yang terjadi, dalam 2–3 kalimat                                                                             |
| 02  | Visual Kunci           | Infografik/grafik dari data terstruktur                                                                         |
| 03  | Fakta Kunci & Linimasa | Blok Fakta Kunci bersumber primer (modul 03)                                                                    |
| 04  | Aktor & Posisi         | Tokoh/lembaga terkait (relasi graf) dan posisinya                                                               |
| 05  | Lensa GRC              | Governance: siapa berwenang; Risk: risiko bagi pelaku usaha/publik; Compliance: kewajiban baru, tenggat, sanksi |
| 06  | Dampak & Reaksi        | Dampak sektoral dan reaksi pemangku kepentingan                                                                 |
| 07  | Apa Selanjutnya        | Tahap berikutnya, tanggal penting, entitas yang perlu dipantau                                                  |

### Rincian teknis [Usulan]

- Simpan sebagai entitas `isu` dengan field terstruktur per kerangka (bukan satu kolom HTML), agar bisa dipakai ulang di Email Briefing (09) dan dasbor B2B (08).
- Lensa GRC sebaiknya punya sub-field terstruktur (`governance`, `risk_level`, `risk_desc`, `compliance_items[]` berisi kewajiban + tenggat + regulasi terkait) supaya bisa difilter per sektor oleh pengguna korporasi.
- Panel "Cara isu ini dipilih": konten statis yang dikelola redaksi + metadata per isu (sinyal yang dipakai: jumlah dokumen primer baru, tahapan RUU, skor AI, keputusan redaksi).
- "Isu Mingguan": setiap isu punya field `minggu` (tanggal awal pekan); beranda menampilkan isu pekan berjalan.

#### Route [Usulan]

| Route                       | Halaman                       |
| --------------------------- | ----------------------------- |
| `/isu`                      | Arsip isu per pekan           |
| `/isu/{slug}`               | Kartu anatomi isu             |
| `/metodologi/pemilihan-isu` | Penjelasan lengkap metodologi |

### Acceptance criteria [Usulan]

- [ ] Isu tidak bisa diajukan ke verifikasi bila ada kerangka wajib yang kosong.
- [ ] Ketujuh kerangka tampil dalam urutan dan penomoran tetap di semua isu.
- [ ] Lensa GRC tampil konsisten dan bisa difilter per sektor di Zona 2.
- [ ] Panel "Cara isu ini dipilih" tampil di setiap halaman isu.
- [ ] Aktor di kerangka 04 tertaut ke profil tokoh (modul 04).

### Dependensi

- Bergantung pada: 01, 03 (Blok Fakta Kunci), 10.
- Dipakai oleh: 08, 09.

---

<a id="modul-06"></a>

## 06 · Dasbor Pelacak Legislasi

Sumber: Slide 5 (Zona 1), 9, 14.
Target rilis: **R2** (26 Maret 2027): modul Pelacak Legislasi penuh & linimasa progress RUU.

### Tujuan

Memantau perjalanan RUU prioritas dari usulan hingga diundangkan, dengan tampilan ringkas di daftar dan linimasa detail per RUU.

### Tampilan utama: Daftar 20 RUU Prioritas [Slide 9]

Tabel ringkas 20 RUU prioritas lengkap dengan label sektor dan progress bar horizontal.

| Kolom     | Isi                                                                                                     |
| --------- | ------------------------------------------------------------------------------------------------------- |
| Judul RUU | Judul lengkap, tertaut ke detail                                                                        |
| Sektor    | Contoh: Ekonomi, Hukum, Sosial                                                                          |
| Progress  | Bar horizontal dengan titik merah pada tahap saat ini + label tahap (Usulan / Pembahasan / Diundangkan) |

Header halaman memiliki ikon notifikasi.

### Detail Linimasa RUU [Slide 9]

Simpul progress bar **vertikal**, titik merah dari tahap Usulan hingga Diundangkan, plus tombol **"Ikuti RUU"** (merah, kanan atas).

Tahapan di mock, masing-masing dengan tanggal dan ringkasan:

1. Usulan
2. Pembahasan I
3. Pembahasan II
4. Harmonisasi
5. Diundangkan

Ada sidebar kiri (navigasi/daftar) dan area linimasa dapat di-scroll.

### Rincian teknis [Usulan]

#### Data

- `ruu` + `ruu_tahapan` (modul 01). Satu baris per tahap yang sudah terjadi: `tahap`, `tanggal`, `ringkasan`, `sumber_url`.
- Progress bar dihitung dari tahap terakhir yang tercatat (5 tahap → 0/25/50/75/100%).
- Label ringkas di daftar: `usulan` → "Usulan"; `pembahasan_1`, `pembahasan_2`, `harmonisasi` → "Pembahasan"; `diundangkan` → "Diundangkan". Perlu konfirmasi apakah "Harmonisasi" ditampilkan terpisah.
- Field `prioritas` (boolean + urutan) untuk menentukan 20 RUU prioritas. Sumber daftar prioritas (Prolegnas Prioritas DPR) ditarik lewat modul 11.
- Relasi ke tokoh pengusul/pembahas, isu, dan regulasi yang dihasilkan ditampilkan di detail.

#### Ikuti RUU

- Menyimpan baris `follows` (modul 02). Perubahan tahap RUU memicu notifikasi ke pengikut (masuk ke Dasbor Peringatan Dini 08 dan Email Briefing 09).

#### Fitur pendukung [Usulan]

- Filter per sektor dan per tahap, pencarian judul.
- Halaman tetap SSR dan ramah perayap (JSON-LD `Legislation`).

#### Route [Usulan]

| Route               | Halaman                 |
| ------------------- | ----------------------- |
| `/legislasi`        | Daftar 20 RUU prioritas |
| `/legislasi/semua`  | Semua RUU yang dilacak  |
| `/legislasi/{slug}` | Detail & linimasa RUU   |

### Acceptance criteria [Usulan]

- [ ] Daftar menampilkan tepat RUU yang ditandai prioritas, maksimal 20, urut sesuai prioritas.
- [ ] Menambah tahap baru di admin langsung menggeser progress di daftar dan detail.
- [ ] Setiap tahap di linimasa punya tanggal dan tautan sumber primer.
- [ ] "Ikuti RUU" membuat notifikasi ketika tahap berubah, diuji end-to-end.
- [ ] Tampilan linimasa responsif di mobile.

### Dependensi

- Bergantung pada: 01, 11 (data DPR), 02 (follow).
- Dipakai oleh: 04 (Jejak Kebijakan), 08, 09.

---

<a id="modul-07"></a>

## 07 · Pusat Data Visual & Layanan Riset Terpadu

Sumber: Slide 6 (grafik interaktif), 10.
Target rilis: **belum ditentukan**. Modul ini tidak disebut di peta jalan R1–R3 (Slide 14), lihat [Bagian 12](#modul-12).

### Tujuan

Menyediakan katalog data statistik yang bisa disematkan (embed) dengan metodologi terbuka, dan gerbang layanan riset tingkat tinggi untuk organisasi korporasi.

### A. Katalog Data (Statistical Data Catalog) [Slide 10]

Grafik visual siap semat (embed) yang disertai catatan metodologi terbuka.

Komponen di mock:

| Komponen     | Isi                                                                         |
| ------------ | --------------------------------------------------------------------------- |
| Sidebar kiri | Navigasi kategori data                                                      |
| Filter       | Dropdown "All Data metric"                                                  |
| Pencarian    | Kolom "Search"                                                              |
| Tabel        | Kolom Metric, Sektor (Ekonomi/Hukum/Sosial), Value (angka + bar horizontal) |

### B. Pusat Wawasan (Insight Center) [Slide 10]

Gerbang utama layanan tingkat tinggi seperti **Indeks, Survei, dan Advisory** untuk organisasi korporasi.

Komponen di mock:

| Komponen                 | Isi                                                                                 |
| ------------------------ | ----------------------------------------------------------------------------------- |
| 4 kartu layanan bernomor | 01–04. Tiga disebut di slide (Indeks, Survei, Advisory); layanan ke-4 belum disebut |
| Latest Publications      | Grid kartu publikasi (gambar, label, judul, ringkasan)                              |

### Rincian teknis [Usulan]

#### Dataset

```
datasets          id, uid, slug, judul, sektor_id, deskripsi, metodologi, sumber, lisensi,
                  frekuensi_update, terakhir_update, akses (publik|b2b)
dataset_series    id, dataset_id, metric, satuan
dataset_points    id, series_id, periode, nilai, catatan
```

- Setiap dataset wajib punya `metodologi` dan `sumber` (prinsip transparansi).
- **Embed**: route `/data/{slug}/embed` menghasilkan halaman ringan (iframe) + snippet kode yang bisa disalin. Sertakan atribusi & tautan balik ke ISF.
- Grafik di artikel (modul 03) memakai dataset yang sama.
- Unduhan mentah (CSV/XLSX) untuk dataset ber-akses `b2b` hanya untuk anggota Zona 2 (modul 08, "Unduhan Dataset Mentah").
- JSON-LD `Dataset` agar terindeks Google Dataset Search.

#### Insight Center

- Halaman layanan statis yang dikelola redaksi (Indeks, Survei, Advisory, + 1).
- Publikasi riset: entitas `publikasi (judul, jenis, ringkasan, file_pdf, akses)`, ditampilkan di "Latest Publications".
- Formulir permintaan layanan / kontak untuk Advisory (masuk ke email tim + tersimpan di database, dengan proteksi spam).

#### Route [Usulan]

| Route                | Halaman                              |
| -------------------- | ------------------------------------ |
| `/data`              | Katalog data                         |
| `/data/{slug}`       | Detail dataset + grafik + metodologi |
| `/data/{slug}/embed` | Versi embed                          |
| `/insight`           | Insight Center                       |
| `/insight/{layanan}` | Detail layanan                       |
| `/publikasi/{slug}`  | Detail publikasi                     |

### Acceptance criteria [Usulan]

- [ ] Setiap dataset menampilkan metodologi, sumber, dan tanggal pembaruan.
- [ ] Snippet embed bisa ditempel di halaman HTML eksternal dan tampil benar.
- [ ] Dataset ber-akses B2B tidak bisa diunduh tanpa login anggota.
- [ ] Filter metric, sektor, dan pencarian berfungsi di katalog.
- [ ] Formulir Advisory tersimpan dan mengirim notifikasi ke tim.

### [Perlu keputusan]

- Rilis mana modul ini masuk (R1 bersama grafik artikel? setelah R3?).
- Nama layanan ke-4 di Insight Center.
- Dataset mana yang publik dan mana yang khusus B2B.

### Dependensi

- Bergantung pada: 01, 02 (akses B2B), 10 (publikasi lewat redaksi).
- Dipakai oleh: 03 (grafik), 08 (unduhan dataset).

---

<a id="modul-08"></a>

## 08 · Portal Khusus Anggota B2B (Premium)

Sumber: Slide 4, 5 (Zona 2), 8, 9, 11, 14, 15.
Target rilis: **R3** (23 April 2027): peluncuran versi **Beta** Dasbor Peringatan Dini B2B & uji penetrasi keamanan.

### Tujuan

Memberi organisasi korporasi peringatan dini atas perubahan regulasi yang relevan bagi sektornya, dengan prioritas berdasarkan urgensi dampak.

### Lingkup dari sitemap [Slide 5]

1. Dasbor Peringatan Dini
2. Penjejak Regulasi Sektoral
3. Unduhan Dataset Mentah

Plus dari Slide 4: akun korporasi dengan manajemen kursi & penjejak sektoral (lihat modul 02).

### Dasbor Umpan Peringatan / Feed Peringatan Dini [Slide 11]

**Anotasi 1:** Feed Peringatan Dini menggunakan **label prioritas warna (Merah, Kuning)** berdasarkan urgensi dampak regulasi.

Komponen di mock:

| Area              | Isi                                                                                                                |
| ----------------- | ------------------------------------------------------------------------------------------------------------------ |
| Header            | Ikon notifikasi, avatar & nama pengguna                                                                            |
| Sidebar           | Dashboard, (menu kedua, diduga Pemantauan), Anggota, Alerts, **Prioritas Tinggi** (disorot)                        |
| Tabel alert       | Kolom: Alert (badge "Prioritas Tinggi" merah / "Merah, Kuning" kuning), Konten (judul + ringkasan), Data (tanggal) |
| Filter            | Dropdown kanan atas tabel (diduga filter prioritas/sektor)                                                         |
| Panel kanan atas  | Kartu profil pengguna/organisasi (nama, info, tombol)                                                              |
| Panel kanan bawah | "Tautan Langsung"                                                                                                  |

### Rincian teknis [Usulan]

#### Model alert

```
alerts             id, organization_id (null = global), sumber_type, sumber_id
                   (ruu_tahapan|regulasi|putusan|isu|anggaran),
                   judul, ringkasan, sektor_id, prioritas (merah|kuning|info),
                   alasan_prioritas, dibuat_pada, diverifikasi_oleh
alert_reads        alert_id, user_id, dibaca_pada
```

- **Pemicu alert**: tahap RUU berubah, regulasi baru ditarik dari JDIH, putusan baru, isu baru diterbitkan, yang cocok dengan `follows` pengguna atau `sektor_minat` organisasi.
- **Prioritas**: AI boleh mengusulkan level (merah/kuning) beserta alasan, tetapi level final ditetapkan/disetujui manusia (prinsip Slide 12). Kriteria merah vs kuning perlu didefinisikan redaksi [Perlu keputusan].
- **Penjejak Regulasi Sektoral**: tampilan alert + regulasi yang difilter per sektor organisasi, dengan Lensa GRC dari isu terkait (modul 05).
- **Unduhan Dataset Mentah**: daftar dataset ber-akses B2B dari modul 07, unduh CSV/XLSX, dicatat di log unduhan.
- **Anggota**: halaman untuk `admin_korporasi` mengelola kursi (modul 02).

#### Route [Usulan]

| Route                             | Halaman                        |
| --------------------------------- | ------------------------------ |
| `/anggota`                        | Dashboard                      |
| `/anggota/alerts`                 | Semua alert                    |
| `/anggota/alerts?prioritas=merah` | Prioritas Tinggi               |
| `/anggota/sektor/{slug}`          | Penjejak regulasi sektoral     |
| `/anggota/dataset`                | Unduhan dataset mentah         |
| `/anggota/diikuti`                | Tokoh/RUU/isu yang diikuti     |
| `/anggota/organisasi`             | Kelola kursi (admin korporasi) |

### Acceptance criteria [Usulan]

- [ ] Alert hanya muncul untuk organisasi/pengguna yang relevan (sektor atau follow).
- [ ] Badge warna konsisten: merah = prioritas tinggi, kuning = perlu dipantau.
- [ ] Menu "Prioritas Tinggi" hanya menampilkan alert merah.
- [ ] Setiap alert tertaut ke entitas sumber dan dokumen primer.
- [ ] Data antar-organisasi terisolasi (diuji: user organisasi A tidak bisa melihat alert khusus organisasi B).
- [ ] Pen-test selesai dan temuan tinggi/kritis diperbaiki sebelum Beta dibuka.

### [Perlu keputusan]

- Kriteria prioritas merah vs kuning.
- Apakah ada notifikasi real-time (push/email instan) untuk alert merah, atau cukup di dasbor + briefing pagi.
- Model harga/paket dan jumlah kursi per paket.

### Dependensi

- Bergantung pada: 01, 02, 05, 06, 07, 11.
- Terkait: 09 (briefing memakai data alert yang sama).

---

<a id="modul-09"></a>

## 09 · Email Briefing (Ekstensi Platform)

Sumber: Slide 11, 14.
Target rilis: **R1** (12 Februari 2027), disebut eksplisit sebagai fokus R1 [Slide 14].

### Tujuan

**Anotasi 2 [Slide 11]:** Ringkasan data anggota dikirimkan secara **terotomatisasi** ke **surel atau WhatsApp pagi** dengan struktur rapi.

### Kerangka surel di mock [Slide 11]

Urutan blok dari atas ke bawah:

| Blok                   | Isi                                                                                                                 |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Header                 | Logo/banner, teks kecil "Ringkasan data dikirimkan Anggota"                                                         |
| Ringkasan Data Anggota | Gambar/grafik ringkas + catatan                                                                                     |
| Poin-Poin Utama        | Gambar, paragraf pembuka, 3 butir poin utama (judul + deskripsi), paragraf penutup, tombol "Ringkasan Data Anggota" |
| Tautan Langsung        | Gambar + tautan ke platform                                                                                         |

### Rincian teknis [Usulan]

#### Isi briefing

- Dibangun otomatis dari data 24 jam terakhir: alert (modul 08), perubahan tahap RUU yang diikuti (06), isu baru (05), tokoh yang diikuti (04).
- Di R1 (sebelum portal B2B ada), isi briefing berasal dari konten terbit + kurasi redaksi, dan penerima dikelola manual/daftar langganan. Personalisasi per follow menyusul setelah modul 02/08 siap.
- Poin-poin utama: diambil dari ringkasan/kerangka isu, bukan ditulis ulang AI tanpa review.

#### Pengiriman

- Penjadwal harian (cron/queue), default pukul 06.00 WIB, bisa diatur per penerima.
- **Surel**: template HTML responsif (tabel, inline CSS), versi teks polos, tautan berhenti langganan, SPF/DKIM/DMARC pada domain pengirim.
- **WhatsApp**: lewat WhatsApp Business Platform (Cloud API) dengan template pesan yang disetujui Meta; berisi ringkasan singkat + tautan. Butuh opt-in eksplisit penerima.
- Log pengiriman per penerima (terkirim/gagal/dibuka) dan retry untuk kegagalan sementara.
- Mode pratinjau & kirim-uji untuk redaksi sebelum jadwal.

#### Data [Usulan]

```
briefing_subscribers   id, user_id/email, phone_wa, kanal (email|wa|both), jam_kirim, aktif, opt_in_at
briefings              id, tanggal, konten_json, status (draf|disetujui|terkirim), disetujui_oleh
briefing_deliveries    id, briefing_id, subscriber_id, kanal, status, error, dikirim_pada
```

### Acceptance criteria [Usulan]

- [ ] Briefing terkirim otomatis setiap pagi tanpa intervensi, dengan log per penerima.
- [ ] Tampilan surel benar di Gmail (web & mobile) dan Outlook.
- [ ] Setiap poin memiliki tautan ke halaman sumber di platform.
- [ ] Berhenti langganan berfungsi satu klik dan langsung berlaku.
- [ ] Pesan WhatsApp hanya terkirim ke nomor yang opt-in.
- [ ] Briefing hari itu bisa ditinjau redaksi sebelum dikirim [Perlu keputusan: wajib atau opsional].

### [Perlu keputusan]

- Briefing di R1 untuk siapa: semua pelanggan gratis, atau calon anggota B2B saja?
- WhatsApp masuk R1 atau menyusul (butuh verifikasi bisnis Meta yang memakan waktu)?
- Perlu persetujuan redaksi tiap hari, atau terkirim otomatis dari konten yang sudah terverifikasi?

### Dependensi

- Bergantung pada: 03, 05, 10 (konten terverifikasi). Personalisasi: 02, 06, 08.

---

<a id="modul-10"></a>

## 10 · Panel Redaksi & Tata Kelola Konten

Sumber: Slide 4, 5 (Zona 3), 12, 14.
Target rilis: **R1** (12 Februari 2027): Panel Redaksi & kurasi manual tokoh dan isu.

### Tujuan

**"AI menyaring, manusia memutuskan."** [Slide 12] Menjamin setiap konten terbit melalui verifikasi dua lapis editor, dengan jejak audit terbuka.

> **TIDAK ADA SATU PUN AKUN YANG BISA MENERBITKAN KONTEN SENDIRIAN** (Prinsip Verifikasi Ganda Berbasis Peran). [Slide 12]

### Alur kerja enam tahap [Slide 12]

| No  | Tahap                   | Keterangan slide                               | Pelaku [Usulan]                                  |
| --- | ----------------------- | ---------------------------------------------- | ------------------------------------------------ |
| 1   | **Draf**                | Disusun menggunakan editor blok                | `penulis`                                        |
| 2   | **Cek Sumber AI**       | Otomatis menandai klaim tanpa rujukan          | Sistem                                           |
| 3   | **Verifikasi Editor 1** | Pengecekan fakta primer                        | `editor_1` (bukan penulis)                       |
| 4   | **Editor 2 / Legal**    | Persetujuan untuk isu berisiko hukum           | `editor_2_legal` (bukan penulis, bukan Editor 1) |
| 5   | **Terbit**              | Publikasi peluncuran data                      | Sistem, setelah syarat terpenuhi                 |
| 6   | **Log Koreksi**         | Jejak audit terbuka untuk publik jika direvisi | Publik dapat melihat                             |

### Komponen panel [Slide 5]

- **Panel Editor Blok & Verifikasi Ganda**
- **Panel Kurasi Tokoh & Isu** (pilih Tokoh Pekan Ini, isu mingguan, validasi relasi entitas)

### Rincian teknis [Usulan]

#### Editor blok

Tipe blok minimum: paragraf, judul, kutipan, daftar, gambar, video embed, **Fakta Kunci** (modul 03), **Tautan Entitas** (sisip tokoh/RUU/regulasi dari graf), grafik dataset (modul 07), kerangka isu (modul 05). Disimpan sebagai JSON terstruktur, dirender ke HTML semantik di server.

#### Mesin status

```
draf ──> cek_ai ──> menunggu_editor_1 ──> [berisiko_hukum?] ──> menunggu_editor_2 ──> siap_terbit ──> terbit
  ^         |              |                                          |
  └─────────┴── dikembalikan (dengan catatan) ────────────────────────┘
terbit ──> revisi (draf turunan) ──> ... ──> terbit + entri log_koreksi
```

Aturan:

- Cek Sumber AI berjalan otomatis saat penulis mengajukan. Hasilnya berupa **tanda** pada klaim tanpa rujukan; penulis harus menyelesaikan atau memberi alasan. AI tidak bisa menyetujui/menolak.
- Konten "berisiko hukum" ditandai oleh Editor 1 (atau saran AI); bila ya, wajib Editor 2/Legal. [Perlu keputusan: apakah Editor 2 wajib untuk semua konten, karena slide menyebut "verifikasi dua lapis editor" di Slide 4.]
- Penegakan di backend (bukan hanya UI): transisi ditolak bila pelaku sama dengan penulis atau verifikator sebelumnya.
- Konten terbit yang direvisi: versi baru melalui alur yang sama; setelah terbit, entri `log_koreksi` publik dibuat otomatis (tanggal, apa yang diubah, alasan).

#### Data

```
contents            id, uid, jenis (artikel|isu|video|infografik), status, penulis_id, berisiko_hukum, ...
content_versions    id, content_id, versi, blocks_json, dibuat_oleh, dibuat_pada
content_reviews     id, content_id, versi, tahap, reviewer_id, keputusan, catatan, waktu
ai_source_flags     id, content_id, versi, blok_id, kutipan_klaim, alasan, status (terbuka|selesai|diabaikan), ditangani_oleh
correction_logs     id, content_id, versi_lama, versi_baru, ringkasan_perubahan, alasan, waktu   -- publik
audit_logs          id, actor_id, aksi, target, data, waktu                                   -- internal
```

### Acceptance criteria [Usulan]

- [ ] Uji otomatis: penulis yang juga editor tidak bisa memverifikasi kontennya sendiri.
- [ ] Uji otomatis: Editor 1 dan Editor 2 harus orang berbeda.
- [ ] Klaim tanpa rujukan ditandai sebelum konten masuk antrean Editor 1.
- [ ] Semua transisi status tercatat di audit log (siapa, kapan, catatan).
- [ ] Revisi pasca-terbit otomatis muncul di halaman log koreksi publik dan di artikelnya.
- [ ] Kurator bisa menetapkan Tokoh Pekan Ini dan Isu Mingguan dari Panel Kurasi.

### Dependensi

- Bergantung pada: 01, 02.
- Dipakai oleh: 03, 04, 05, 07, 09.

---

<a id="modul-11"></a>

## 11 · Admin & Data: Penarikan Data, Taksonomi, Klasifikasi AI

Sumber: Slide 3, 4, 5 (Zona 4), 12.
Target rilis: **R1** (taksonomi & input manual, karena R1 memakai kurasi manual), **R2** (penarikan data DPR untuk Pelacak Legislasi). [Usulan, karena slide tidak menempatkan Zona 4 di rilis tertentu]

### Lingkup dari sitemap [Slide 5]

1. **Panel Tarik Data (JDIH, DPR)**
2. **Pengelolaan Taksonomi Entitas**
3. **Tinjauan Klasifikasi AI**

### A. Panel Tarik Data [Usulan]

| Sumber                                                                                   | Data                                                  | Dipakai di              |
| ---------------------------------------------------------------------------------------- | ----------------------------------------------------- | ----------------------- |
| JDIH (Jaringan Dokumentasi dan Informasi Hukum, mis. `jdihn.go.id` dan JDIH kementerian) | Regulasi: UU, PP, Perpres, Permen, beserta dokumen    | 01 (Pasal/Regulasi), 08 |
| DPR (`dpr.go.id`)                                                                        | RUU, Prolegnas prioritas, tahapan pembahasan, anggota | 01 (RUU, Tokoh), 06     |

Cara kerja:

- Konektor per sumber dijalankan terjadwal (mis. harian) dan bisa dipicu manual dari panel.
- Hasil tarikan masuk ke **area staging**, tidak langsung ke data publik. Admin meninjau, mencocokkan ke entitas yang sudah ada (deduplikasi berdasarkan nomor/tahun regulasi, judul RUU, nama + alias tokoh), lalu menyetujui.
- Setiap record menyimpan `sumber_url`, waktu tarik, dan hash konten untuk mendeteksi perubahan.
- Log jalan konektor: jumlah baru/berubah/gagal, pesan error.

[Perlu keputusan] Cek dulu apakah sumber menyediakan API/open data resmi; jika harus scraping, pastikan sesuai ketentuan penggunaan situs, batasi laju request, dan siapkan penanganan perubahan struktur HTML.

### B. Pengelolaan Taksonomi Entitas [Slide 4: "taksonomi terkontrol"]

- CRUD taksonomi: sektor, topik, lembaga, jenis regulasi, jenis relasi (enum relasi graf di modul 01).
- Struktur hierarkis (`parent_id`), slug stabil, gabung (merge) dua istilah dengan redirect.
- Penyatuan alias tokoh: gabungkan dua record tokoh duplikat menjadi satu `uid`, semua relasi ikut pindah.

### C. Tinjauan Klasifikasi AI [Slide 5, 12]

AI dipakai untuk **mengusulkan**, manusia memutuskan:

- Usulan sektor/topik untuk regulasi dan artikel baru.
- Usulan relasi entitas (mis. "artikel ini menyebut Tokoh X dan RUU Y").
- Usulan level prioritas alert (merah/kuning) untuk modul 08.
- Tanda klaim tanpa rujukan (modul 10).

Panel antrean: setiap usulan tampil dengan tingkat keyakinan dan alasan; admin bisa **terima / ubah / tolak**. Keputusan disimpan untuk evaluasi akurasi model.

```
ai_suggestions   id, target_type, target_id, jenis, usulan_json, keyakinan, alasan,
                 status (menunggu|diterima|diubah|ditolak), ditinjau_oleh, waktu
```

[Perlu keputusan] Penyedia/model AI yang dipakai dan kebijakan data (dokumen yang dikirim ke API pihak ketiga).

### Acceptance criteria [Usulan]

- [ ] Data hasil tarikan tidak tampil publik sebelum disetujui admin.
- [ ] Menjalankan konektor dua kali tidak menghasilkan duplikat.
- [ ] Perubahan pada dokumen sumber terdeteksi dan memunculkan record "berubah" untuk ditinjau.
- [ ] Merge dua tokoh memindahkan semua relasi dan alias tanpa kehilangan data.
- [ ] Tidak ada usulan AI yang berlaku tanpa persetujuan manusia.

### Dependensi

- Bergantung pada: 01, 02.
- Dipakai oleh: 06, 08, 10.

---

<a id="modul-12"></a>

## 12 · Keputusan Terbuka (Harus Diputuskan Sebelum Mulai)

Daftar hal yang ambigu, bertentangan, atau tidak dijelaskan di slide. Diurutkan dari yang paling memblokir.

### 1. Stack teknologi: slide vs repositori ini (MEMBLOKIR)

**Slide 13 "Keselarasan Metode Eksekusi Kita"** menggambarkan siklus:

| Langkah               | Isi slide                                                                        |
| --------------------- | -------------------------------------------------------------------------------- |
| PRD via Claude        | Penyelarasan logika dasar dokumen spesifikasi produk                             |
| UI/UX via Stitch      | Mengubah draf via AI generator untuk konversi antarmuka secara instan            |
| Modular Code          | Kode file **Google Apps Script** yang terisolasi (Code.gs terpisah dari HTML/JS) |
| Automated Environment | Skrip pengaturan instan untuk membuat **basis data Sheet** dalam hitungan detik  |
| Deployment via Vercel | Mendorong kode ke repositori dan menautkannya ke indonesiastrategyforum.co.id    |

Sementara repositori ini berisi **Laravel 11/12 + Inertia + Vue 3 + Tailwind 3 + spatie/laravel-permission** (dan model sisa dari aplikasi undangan pernikahan: Couple, Wedding, Rsvp, dll.).

Pertimbangan:

- Google Apps Script + Google Sheets cocok untuk prototipe cepat, tetapi berat untuk kebutuhan di modul 01, 02, 08, 10: relasi graf, ID permanen, verifikasi ganda yang ditegakkan di backend, isolasi data antar-organisasi B2B, audit log, dan lolos pen-test (R3).
- Vercel tidak menjalankan Google Apps Script; Vercel juga bukan target alami untuk Laravel (bisa, tapi dengan batasan serverless).
- Laravel + database relasional (MySQL/PostgreSQL) sudah tersedia di repo dan cocok untuk seluruh modul.

Opsi:

- **A.** Laravel (repo ini) + MySQL/PostgreSQL, deploy ke VPS/managed PHP hosting. Bersihkan model sisa aplikasi pernikahan.
- **B.** GAS + Sheets sesuai slide untuk R1 sebagai prototipe, lalu migrasi sebelum R3.
- **C.** Kombinasi: Stitch untuk desain UI, hasilnya diimplementasikan di Vue/Inertia (Laravel).

Rekomendasi penyusun: **A** atau **C**. Butuh keputusan Chief Developer.

### 2. Modul tanpa jadwal

- **Pusat Data Visual & Insight Center (07)** tidak masuk R1–R3. Padahal grafik interaktif di artikel (Slide 6) membutuhkan dataset.
- **Zona 4 Admin & Data (11)** tidak disebut di peta jalan, padahal Pelacak Legislasi R2 butuh data DPR.

### 3. Ketergantungan yang tidak sejajar dengan jadwal

- **Email Briefing** di R1, tetapi "Ringkasan Data Anggota" bergantung pada data anggota B2B yang baru Beta di R3.
- **Tombol "Ikuti Tokoh" / "Ikuti RUU"** ditujukan ke pengguna B2B (R3), sementara halaman tokoh (R1) dan RUU (R2) lebih dulu.

### 4. Detail produk yang belum terdefinisi

| Topik                                       | Modul  | Pertanyaan                                                                          |
| ------------------------------------------- | ------ | ----------------------------------------------------------------------------------- |
| Label relasi graf                           | 01     | Slide memuat salah ketik ("Menwsahas", "Memololala") dan beberapa garis tanpa label |
| Rumus Skor Sorotan                          | 04     | Komponen dan bobot                                                                  |
| Statistik kartu tokoh & kolom Policy Matrix | 04     | Label di mock tidak terbaca                                                         |
| Nama 7 kerangka analitis                    | 05     | Hanya 05 (Lensa GRC) yang pasti                                                     |
| Tahap Harmonisasi                           | 06     | Ditampilkan terpisah di progress daftar atau digabung ke "Pembahasan"               |
| Layanan ke-4 Insight Center                 | 07     | Baru Indeks, Survei, Advisory yang disebut                                          |
| Kriteria prioritas Merah vs Kuning          | 08     | Siapa menetapkan, dengan ukuran apa                                                 |
| Paket & kursi B2B                           | 02, 08 | Harga, jumlah kursi per paket                                                       |
| WhatsApp di R1                              | 09     | Butuh verifikasi Meta Business                                                      |
| Editor 2 wajib untuk semua konten?          | 10     | Slide 4 "dua lapis" vs Slide 12 "untuk isu berisiko hukum"                          |
| Penyedia AI & kebijakan data                | 10, 11 | Model, biaya, data apa yang boleh dikirim                                           |
| Sumber data JDIH/DPR                        | 11     | API resmi atau scraping                                                             |

### 5. Estimasi waktu

Kolom "Estimasi Waktu Penyelesaian" di Slide 15 masih kosong. Lihat [Bagian 13](#modul-13).

---

<a id="modul-13"></a>

## 13 · Peta Jalan MVP & Estimasi

Sumber: Slide 14, 15.

### Peta jalan MVP [Slide 14]

| Rilis  | Target           | Fokus (sesuai slide)                                                                 |
| ------ | ---------------- | ------------------------------------------------------------------------------------ |
| **R1** | 12 Februari 2027 | Portal Publik, Panel Redaksi, Kurasi manual Tokoh & Isu, Email Briefing              |
| **R2** | 26 Maret 2027    | Modul Pelacak Legislasi penuh & Linimasa progress RUU                                |
| **R3** | 23 April 2027    | Peluncuran versi Beta Dasbor Peringatan Dini B2B & Uji Penetrasi Keamanan (Pen-Test) |

Per hari ini (20 Oktober 2026), sisa waktu: sekitar **16 minggu** ke R1, lalu **6 minggu** R1→R2, dan **4 minggu** R2→R3.

### Pemetaan modul ke rilis [Usulan]

#### R1 · 12 Februari 2027

| Modul | Lingkup R1                                                                 |
| ----- | -------------------------------------------------------------------------- |
| 12    | Kunci keputusan stack & detail produk (minggu pertama)                     |
| 01    | Seluruh skema entitas & relasi, ID permanen, redirect slug                 |
| 02    | Peran internal (penulis, editor 1/2, kurator, admin), login + 2FA internal |
| 10    | Editor blok, Cek Sumber AI, verifikasi ganda, log koreksi                  |
| 11    | Taksonomi, input/kurasi manual entitas (tanpa konektor otomatis dulu)      |
| 03    | Beranda, video, artikel, infografik, SEO/AI-crawler                        |
| 04    | Profil tokoh, Policy Matrix, Tokoh Pekan Ini (manual)                      |
| 05    | Kartu isu 7 kerangka, Lensa GRC, panel metodologi                          |
| 09    | Email briefing harian (non-personal)                                       |

#### R2 · 26 Maret 2027

| Modul | Lingkup R2                                                               |
| ----- | ------------------------------------------------------------------------ |
| 11    | Konektor DPR (RUU, Prolegnas, tahapan) + staging & review; konektor JDIH |
| 06    | Daftar 20 RUU prioritas, detail & linimasa, Ikuti RUU (simpan follow)    |
| 04    | Jejak Kebijakan otomatis dari data DPR                                   |

#### R3 · 23 April 2027 (Beta)

| Modul | Lingkup R3                                                                   |
| ----- | ---------------------------------------------------------------------------- |
| 02    | Organisasi, kursi, admin korporasi, langganan                                |
| 08    | Dasbor Peringatan Dini, Prioritas Tinggi, penjejak sektoral, unduhan dataset |
| 09    | Briefing personal per follow/sektor, kanal WhatsApp                          |
| 11    | Tinjauan klasifikasi AI (prioritas alert, saran relasi)                      |
| -     | Pen-test eksternal + perbaikan temuan                                        |

#### Belum terjadwal

| Modul | Catatan                                                                                          |
| ----- | ------------------------------------------------------------------------------------------------ |
| 07    | Katalog data & Insight Center. Minimal dataset untuk grafik artikel kemungkinan dibutuhkan di R1 |

### Tabel estimasi [Slide 15]

Slide 15 ("Langkah Selanjutnya: Diskusi Estimasi Waktu") menyediakan tabel kosong untuk diisi bersama tim:

| Tahap Eksekusi      | Komponen yang Dikerjakan        | Estimasi Waktu Penyelesaian |
| ------------------- | ------------------------------- | --------------------------- |
| Tahap R1 (Feb 2027) | Publik & Panel Redaksi          | [ ...... ] Hari             |
| Tahap R2 (Mar 2027) | Pelacak & Dasbor RUU            | [ ...... ] Hari             |
| Tahap R3 (Apr 2027) | Portal Eksekutif B2B & Keamanan | [ ...... ] Hari             |

> "Mari kita bedah draf antarmuka ini dan kunci jadwal kerjanya. Siap mengeksekusi bersama?"

### Lembar estimasi per modul (untuk diisi tim) [Usulan]

| Modul                       | Rilis | Backend (hari) | Frontend (hari) | QA (hari) | Total | PIC |
| --------------------------- | ----- | -------------- | --------------- | --------- | ----- | --- |
| 01 Model data               | R1    |                |                 |           |       |     |
| 02 Auth & peran (internal)  | R1    |                |                 |           |       |     |
| 10 Redaksi & tata kelola    | R1    |                |                 |           |       |     |
| 11 Taksonomi & input manual | R1    |                |                 |           |       |     |
| 03 Portal publik            | R1    |                |                 |           |       |     |
| 04 Profil tokoh             | R1    |                |                 |           |       |     |
| 05 Anatomi isu              | R1    |                |                 |           |       |     |
| 09 Email briefing (dasar)   | R1    |                |                 |           |       |     |
| 11 Konektor DPR/JDIH        | R2    |                |                 |           |       |     |
| 06 Pelacak legislasi        | R2    |                |                 |           |       |     |
| 02 Organisasi & kursi       | R3    |                |                 |           |       |     |
| 08 Portal B2B               | R3    |                |                 |           |       |     |
| 09 Briefing personal + WA   | R3    |                |                 |           |       |     |
| Pen-test & perbaikan        | R3    |                |                 |           |       |     |
| 07 Pusat data               | ?     |                |                 |           |       |     |
