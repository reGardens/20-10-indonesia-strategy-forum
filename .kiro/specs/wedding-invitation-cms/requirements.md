# Dokumen Persyaratan (Requirements Document)

## Pendahuluan

Dokumen ini mendefinisikan persyaratan untuk **Wedding Invitation CMS** — sebuah sistem manajemen konten (CMS) komprehensif untuk undangan pernikahan online. Sistem ini terdiri dari dua bagian utama: (1) Panel CMS untuk pengelolaan data pernikahan, tamu, media, dan konfigurasi, serta (2) Aplikasi Undangan (guest-facing) yang dapat dikustomisasi dengan berbagai template elegan. Seluruh data disimpan secara lokal menggunakan JSON/local storage dengan arsitektur folder `api` yang siap migrasi ke database. Proyek menggunakan stack Vue 3 + Vite + Tailwind CSS.

## Glosarium

- **CMS**: Content Management System — panel administrasi untuk mengelola seluruh data undangan pernikahan
- **Aplikasi_Undangan**: Halaman undangan yang dilihat oleh tamu undangan (guest-facing application)
- **Dashboard**: Halaman utama CMS yang menampilkan ringkasan statistik dan aktivitas
- **Pengelola_Data_Pernikahan**: Modul CMS untuk menginput dan mengelola data mempelai, acara, musik, dan tema
- **Pembangun_Undangan**: Modul CMS untuk memilih template dan menyesuaikan tampilan undangan
- **Pengelola_Tamu**: Modul CMS untuk mengelola daftar tamu, impor/ekspor, dan generate link undangan
- **Perpustakaan_Media**: Modul CMS untuk mengelola foto, video, dan aset media lainnya
- **Pengelola_Pembayaran**: Modul CMS untuk mengatur rekening bank, QRIS, dan catatan hadiah digital
- **Moderasi_RSVP**: Modul CMS untuk memoderasi ucapan dan mengelola data kehadiran tamu
- **Pengelola_Domain**: Modul opsional CMS untuk pengaturan custom domain dan whitelabel
- **Template_Engine**: Komponen yang merender undangan berdasarkan template yang dipilih tanpa kehilangan data
- **Pengoptimal_Gambar**: Komponen yang mengompresi dan mengonversi gambar ke format WebP secara otomatis
- **Generator_Link_WhatsApp**: Komponen yang menghasilkan link undangan unik per tamu untuk dibagikan via WhatsApp
- **Sistem_Check_In**: Komponen untuk memindai QR code tamu di lokasi acara
- **Penghitung_Mundur**: Widget countdown timer di Aplikasi_Undangan menuju hari pernikahan
- **Formulir_RSVP**: Formulir di Aplikasi_Undangan untuk konfirmasi kehadiran tamu
- **Buku_Tamu**: Fitur ucapan/komentar publik di Aplikasi_Undangan
- **Amplop_Digital**: Fitur pemberian hadiah digital melalui transfer bank atau e-wallet di Aplikasi_Undangan
- **Penyimpanan_Lokal**: Mekanisme penyimpanan data menggunakan JSON file dan local storage browser
- **Mempelai**: Data pasangan pengantin (pria dan wanita) termasuk nama, foto, dan informasi orang tua
- **Acara**: Data event pernikahan termasuk tanggal, waktu, lokasi, dan koordinat peta
- **Tamu**: Individu yang diundang ke acara pernikahan
- **RSVP**: Répondez s'il vous plaît — konfirmasi kehadiran tamu (Hadir, Tidak Hadir, Mungkin)
- **QRIS**: Quick Response Code Indonesian Standard — standar pembayaran QR code di Indonesia
- **Serializer_Data**: Komponen yang mengubah objek data ke format JSON dan sebaliknya untuk penyimpanan

---

## Persyaratan

### Persyaratan 1: Dashboard dan Statistik Utama

**User Story:** Sebagai admin pernikahan, saya ingin melihat ringkasan statistik undangan di dashboard, sehingga saya dapat memantau jumlah pengunjung, status RSVP, dan aktivitas terbaru dalam satu tampilan.

#### Kriteria Penerimaan

1. WHEN admin membuka halaman Dashboard, THE Dashboard SHALL menampilkan grafik total pengunjung yang membuka link undangan dalam rentang waktu harian, mingguan, dan bulanan.
2. WHEN admin membuka halaman Dashboard, THE Dashboard SHALL menampilkan ringkasan RSVP berupa jumlah tamu dengan status "Hadir", "Tidak Hadir", dan "Mungkin" dalam format angka dan diagram.
3. WHEN tamu baru mengirimkan ucapan atau komentar, THE Dashboard SHALL menampilkan ucapan tersebut di bagian log aktivitas terbaru dengan urutan kronologis terbalik (terbaru di atas).
4. WHEN data RSVP atau ucapan berubah, THE Dashboard SHALL memperbarui statistik secara otomatis tanpa memerlukan refresh halaman manual.

---

### Persyaratan 2: Pengelolaan Data Mempelai

**User Story:** Sebagai admin pernikahan, saya ingin menginput dan mengelola data mempelai pria dan wanita secara lengkap, sehingga informasi pasangan ditampilkan dengan benar di undangan.

#### Kriteria Penerimaan

1. THE Pengelola_Data_Pernikahan SHALL menyediakan formulir input untuk data mempelai pria dan wanita yang mencakup: nama lengkap, nama panggilan, foto, nama orang tua (ayah dan ibu), dan link Instagram.
2. WHEN admin mengunggah foto mempelai, THE Pengoptimal_Gambar SHALL mengompresi foto tersebut dan mengonversinya ke format WebP dengan ukuran maksimal 500KB.
3. WHEN admin menyimpan data mempelai, THE Serializer_Data SHALL menyimpan data ke Penyimpanan_Lokal dalam format JSON di folder `api`.
4. IF admin menyimpan formulir dengan field nama lengkap kosong, THEN THE Pengelola_Data_Pernikahan SHALL menampilkan pesan validasi yang menunjukkan field wajib yang belum diisi.
5. FOR ALL objek data Mempelai yang valid, serialisasi ke JSON kemudian deserialisasi kembali SHALL menghasilkan objek yang ekuivalen dengan objek asli (round-trip property).

---

### Persyaratan 3: Pengelolaan Data Acara

**User Story:** Sebagai admin pernikahan, saya ingin mengelola informasi acara pernikahan termasuk tanggal, waktu, lokasi, dan peta, sehingga tamu mendapatkan informasi yang akurat.

#### Kriteria Penerimaan

1. THE Pengelola_Data_Pernikahan SHALL menyediakan formulir input untuk satu atau lebih acara yang mencakup: nama acara (misal: Akad Nikah, Resepsi), tanggal, waktu mulai, waktu selesai, nama tempat, alamat lengkap, dan koordinat Google Maps (latitude/longitude).
2. WHEN admin memasukkan koordinat latitude dan longitude, THE Pengelola_Data_Pernikahan SHALL menampilkan preview peta Google Maps embed sesuai koordinat tersebut.
3. WHEN admin menyimpan data acara, THE Serializer_Data SHALL menyimpan data ke Penyimpanan_Lokal dalam format JSON di folder `api`.
4. IF admin memasukkan tanggal acara yang sudah lewat, THEN THE Pengelola_Data_Pernikahan SHALL menampilkan peringatan bahwa tanggal acara berada di masa lalu.
5. IF admin memasukkan koordinat latitude di luar rentang -90 sampai 90 atau longitude di luar rentang -180 sampai 180, THEN THE Pengelola_Data_Pernikahan SHALL menampilkan pesan error validasi koordinat.

---

### Persyaratan 4: Pengelolaan Musik dan Tema

**User Story:** Sebagai admin pernikahan, saya ingin mengunggah musik latar dan memilih warna tema, sehingga undangan memiliki suasana yang sesuai keinginan.

#### Kriteria Penerimaan

1. THE Pengelola_Data_Pernikahan SHALL menyediakan fitur unggah file musik dalam format MP3 dengan ukuran maksimal 10MB.
2. WHEN admin mengunggah file musik, THE Pengelola_Data_Pernikahan SHALL menampilkan preview audio player untuk mendengarkan musik yang diunggah.
3. THE Pengelola_Data_Pernikahan SHALL menyediakan color picker untuk memilih warna tema utama (primary), warna sekunder (secondary), dan warna aksen (accent).
4. WHEN admin memilih warna tema, THE Pembangun_Undangan SHALL menerapkan warna tersebut ke live preview undangan secara real-time.
5. IF admin mengunggah file dengan format selain MP3, THEN THE Pengelola_Data_Pernikahan SHALL menolak file dan menampilkan pesan bahwa hanya format MP3 yang didukung.

---

### Persyaratan 5: Pemilihan dan Pergantian Template

**User Story:** Sebagai admin pernikahan, saya ingin memilih dan mengganti template desain undangan dengan satu klik tanpa kehilangan data, sehingga saya dapat mencoba berbagai tampilan.

#### Kriteria Penerimaan

1. THE Pembangun_Undangan SHALL menampilkan galeri template yang tersedia dengan thumbnail preview untuk setiap template.
2. WHEN admin memilih template baru, THE Template_Engine SHALL menerapkan template tersebut ke undangan tanpa mengubah atau menghapus data mempelai, acara, tamu, dan media yang sudah diinput.
3. WHEN admin memilih template, THE Pembangun_Undangan SHALL menampilkan live preview hasil undangan di iframe atau tab baru di samping formulir pengaturan.
4. THE Template_Engine SHALL mendukung minimal 3 template dengan tema Nusantara Indonesia yang berbeda.
5. WHEN admin mengganti template dari Template A ke Template B kemudian kembali ke Template A, THE Template_Engine SHALL menampilkan hasil yang identik dengan kondisi sebelum pergantian (idempotence property).

---

### Persyaratan 6: Pengelolaan Daftar Tamu

**User Story:** Sebagai admin pernikahan, saya ingin mengelola daftar tamu undangan secara efisien termasuk impor massal, sehingga saya dapat menangani ratusan tamu dengan mudah.

#### Kriteria Penerimaan

1. THE Pengelola_Tamu SHALL menyediakan tabel daftar tamu dengan kolom: nama tamu, nomor telepon, status RSVP, jumlah tamu yang dibawa, dan status check-in.
2. THE Pengelola_Tamu SHALL mendukung operasi tambah, edit, dan hapus data tamu secara individual.
3. WHEN admin mengunggah file Excel (.xlsx), THE Pengelola_Tamu SHALL mengimpor seluruh data tamu dari file tersebut ke dalam daftar tamu.
4. WHEN admin mengklik tombol ekspor, THE Pengelola_Tamu SHALL menghasilkan file Excel (.xlsx) yang berisi seluruh data tamu beserta status RSVP dan check-in.
5. IF admin mengunggah file Excel dengan format kolom yang tidak sesuai, THEN THE Pengelola_Tamu SHALL menampilkan pesan error yang menjelaskan format kolom yang diharapkan.
6. FOR ALL daftar tamu yang diekspor ke Excel kemudian diimpor kembali, THE Pengelola_Tamu SHALL menghasilkan daftar tamu yang ekuivalen dengan daftar asli (round-trip property).

---

### Persyaratan 7: Generator Link WhatsApp per Tamu

**User Story:** Sebagai admin pernikahan, saya ingin menghasilkan link undangan unik untuk setiap tamu yang bisa dibagikan via WhatsApp, sehingga setiap tamu mendapat undangan personal.

#### Kriteria Penerimaan

1. WHEN admin mengklik tombol generate link untuk seorang tamu, THE Generator_Link_WhatsApp SHALL menghasilkan URL unik dengan format `{domain}/wedding/{slug-pernikahan}?to={Nama+Tamu}`.
2. THE Generator_Link_WhatsApp SHALL menyediakan tombol salin link dan tombol buka WhatsApp untuk setiap tamu di daftar tamu.
3. WHEN admin mengklik tombol buka WhatsApp, THE Generator_Link_WhatsApp SHALL membuka link `https://wa.me/{nomor_tamu}?text={pesan_undangan_dengan_link}` di tab baru.
4. THE Generator_Link_WhatsApp SHALL mendukung generate link massal untuk seluruh tamu sekaligus.
5. FOR ALL nama tamu yang mengandung karakter spesial atau spasi, THE Generator_Link_WhatsApp SHALL melakukan URL encoding yang benar sehingga link tetap valid dan nama tamu ditampilkan dengan benar di Aplikasi_Undangan.

---

### Persyaratan 8: Sistem Check-In dengan QR Code

**User Story:** Sebagai admin pernikahan, saya ingin memindai QR code tamu di lokasi acara untuk mencatat kehadiran, sehingga proses check-in berjalan cepat dan terdata.

#### Kriteria Penerimaan

1. THE Pengelola_Tamu SHALL menghasilkan QR code unik untuk setiap tamu yang berisi identifikasi tamu.
2. THE Sistem_Check_In SHALL menyediakan antarmuka pemindai QR code menggunakan kamera perangkat.
3. WHEN QR code tamu dipindai, THE Sistem_Check_In SHALL memperbarui status check-in tamu menjadi "Sudah Hadir" beserta timestamp kedatangan.
4. IF QR code yang dipindai tidak terdaftar dalam daftar tamu, THEN THE Sistem_Check_In SHALL menampilkan pesan "Tamu tidak ditemukan".
5. IF QR code tamu yang sudah check-in dipindai ulang, THEN THE Sistem_Check_In SHALL menampilkan pesan bahwa tamu sudah tercatat hadir sebelumnya dan tidak mengubah data check-in yang ada (idempotence property).

---

### Persyaratan 9: Perpustakaan Media dan Optimasi Gambar

**User Story:** Sebagai admin pernikahan, saya ingin mengunggah dan mengelola foto pre-wedding dengan optimasi otomatis, sehingga galeri tampil cepat tanpa mengorbankan kualitas visual.

#### Kriteria Penerimaan

1. THE Perpustakaan_Media SHALL menyediakan antarmuka unggah foto dengan dukungan drag-and-drop dan pemilihan multiple file.
2. WHEN admin mengunggah foto, THE Pengoptimal_Gambar SHALL mengompresi foto secara otomatis dan mengonversinya ke format WebP.
3. THE Perpustakaan_Media SHALL menyediakan 3 pilihan tata letak galeri: masonry, slider, dan standard grid.
4. WHEN admin memilih tata letak galeri, THE Pembangun_Undangan SHALL menerapkan tata letak tersebut ke preview galeri secara real-time.
5. THE Perpustakaan_Media SHALL menampilkan daftar semua media yang diunggah dengan thumbnail, nama file, ukuran asli, dan ukuran setelah kompresi.
6. THE Perpustakaan_Media SHALL mendukung operasi hapus media secara individual dan massal.

---

### Persyaratan 10: Pengelolaan Pembayaran dan Hadiah Digital

**User Story:** Sebagai admin pernikahan, saya ingin mengatur informasi rekening bank dan QRIS untuk menerima hadiah digital, sehingga tamu dapat memberikan hadiah secara cashless.

#### Kriteria Penerimaan

1. THE Pengelola_Pembayaran SHALL menyediakan formulir input untuk satu atau lebih rekening bank yang mencakup: nama bank, nomor rekening, dan nama pemilik rekening.
2. THE Pengelola_Pembayaran SHALL mendukung unggah gambar QRIS untuk pembayaran via e-wallet.
3. WHEN tamu mengirimkan konfirmasi hadiah digital, THE Pengelola_Pembayaran SHALL mencatat data hadiah di log hadiah yang mencakup: nama pengirim, jumlah, metode pembayaran, dan timestamp.
4. THE Pengelola_Pembayaran SHALL menampilkan daftar log hadiah digital yang diterima dengan total akumulasi.
5. IF admin menyimpan data rekening dengan nomor rekening kosong, THEN THE Pengelola_Pembayaran SHALL menampilkan pesan validasi bahwa nomor rekening wajib diisi.

---

### Persyaratan 11: Moderasi Ucapan dan Ekspor RSVP

**User Story:** Sebagai admin pernikahan, saya ingin memoderasi ucapan tamu dan mengekspor data RSVP, sehingga hanya ucapan yang pantas ditampilkan dan data kehadiran terdokumentasi.

#### Kriteria Penerimaan

1. THE Moderasi_RSVP SHALL menampilkan daftar seluruh ucapan tamu dengan opsi sembunyikan dan hapus untuk setiap ucapan.
2. WHEN admin menyembunyikan sebuah ucapan, THE Aplikasi_Undangan SHALL berhenti menampilkan ucapan tersebut di halaman buku tamu.
3. WHEN admin mengklik tombol ekspor RSVP, THE Moderasi_RSVP SHALL menghasilkan file dalam format Excel (.xlsx) atau PDF yang berisi daftar tamu beserta status RSVP, jumlah tamu yang dibawa, dan ucapan.
4. THE Moderasi_RSVP SHALL menyediakan filter untuk menampilkan ucapan berdasarkan status: semua, ditampilkan, dan disembunyikan.

---

### Persyaratan 12: Custom Domain dan Whitelabel (Opsional)

**User Story:** Sebagai admin pernikahan, saya ingin menggunakan domain kustom dan mengontrol branding, sehingga undangan terlihat profesional dan personal.

#### Kriteria Penerimaan

1. WHERE fitur custom domain diaktifkan, THE Pengelola_Domain SHALL menyediakan formulir input untuk memasukkan nama domain kustom.
2. WHERE fitur whitelabel diaktifkan, THE Pengelola_Domain SHALL menyediakan toggle untuk menampilkan atau menyembunyikan logo "Powered by" di Aplikasi_Undangan.
3. WHEN admin menonaktifkan toggle watermark, THE Aplikasi_Undangan SHALL menyembunyikan seluruh elemen branding "Powered by" dari tampilan tamu.

---

### Persyaratan 13: Countdown Timer dan Informasi Utama Undangan

**User Story:** Sebagai tamu undangan, saya ingin melihat countdown timer dan informasi acara yang jelas, sehingga saya mengetahui kapan dan di mana acara berlangsung.

#### Kriteria Penerimaan

1. WHEN tamu membuka Aplikasi_Undangan, THE Aplikasi_Undangan SHALL menampilkan halaman pembuka dengan nama tamu yang dipersonalisasi (contoh: "Kepada Yth: Bpk. Budi") berdasarkan parameter `to` di URL.
2. THE Aplikasi_Undangan SHALL menampilkan countdown timer yang menghitung mundur menuju tanggal dan waktu acara pernikahan secara real-time (hari, jam, menit, detik).
3. THE Aplikasi_Undangan SHALL menampilkan peta Google Maps interaktif yang menunjukkan lokasi acara berdasarkan koordinat yang diinput admin.
4. WHEN tamu mengklik tombol "Add to Calendar", THE Aplikasi_Undangan SHALL menghasilkan file .ics (iCal) atau membuka link Google Calendar dengan data acara yang sudah terisi otomatis.
5. IF parameter `to` tidak ada di URL, THEN THE Aplikasi_Undangan SHALL menampilkan halaman pembuka dengan teks umum tanpa nama tamu spesifik.

---

### Persyaratan 14: Formulir RSVP dan Buku Tamu

**User Story:** Sebagai tamu undangan, saya ingin mengkonfirmasi kehadiran dan mengirimkan ucapan, sehingga pasangan pengantin mengetahui rencana kehadiran saya.

#### Kriteria Penerimaan

1. THE Formulir_RSVP SHALL menyediakan input untuk: nama tamu, status kehadiran (Hadir/Tidak Hadir/Mungkin), dan jumlah tamu yang akan hadir.
2. WHEN tamu mengirimkan formulir RSVP, THE Aplikasi_Undangan SHALL menyimpan data RSVP ke Penyimpanan_Lokal dan menampilkan pesan konfirmasi.
3. THE Buku_Tamu SHALL menampilkan daftar ucapan dari tamu lain yang sudah disetujui oleh admin secara kronologis.
4. WHEN tamu mengirimkan ucapan melalui Buku_Tamu, THE Aplikasi_Undangan SHALL menyimpan ucapan tersebut dengan status default "menunggu moderasi" jika moderasi aktif, atau langsung ditampilkan jika moderasi nonaktif.
5. WHEN tamu mengirimkan RSVP dengan status "Hadir", THE Aplikasi_Undangan SHALL mengirimkan notifikasi ke admin melalui integrasi WhatsApp.
6. IF tamu mengirimkan formulir RSVP dengan nama kosong, THEN THE Formulir_RSVP SHALL menampilkan pesan validasi bahwa nama wajib diisi.

---

### Persyaratan 15: Galeri Multimedia dan Musik Latar

**User Story:** Sebagai tamu undangan, saya ingin melihat galeri foto/video dan mendengarkan musik latar, sehingga pengalaman melihat undangan menjadi lebih berkesan.

#### Kriteria Penerimaan

1. THE Aplikasi_Undangan SHALL menampilkan galeri foto pre-wedding sesuai tata letak yang dipilih admin (masonry, slider, atau standard grid).
2. WHEN tamu membuka Aplikasi_Undangan, THE Aplikasi_Undangan SHALL memutar musik latar secara otomatis setelah interaksi pertama pengguna (klik/tap) sesuai kebijakan autoplay browser.
3. THE Aplikasi_Undangan SHALL menyediakan tombol toggle untuk mematikan dan menyalakan musik latar yang terlihat jelas di seluruh halaman.
4. THE Aplikasi_Undangan SHALL mendukung tampilan video (embed YouTube atau video langsung) di dalam galeri.
5. THE Aplikasi_Undangan SHALL menampilkan seluruh animasi transisi antar-seksi dengan smooth animation menggunakan library AOS (Animate on Scroll).

---

### Persyaratan 16: Amplop Digital dan Logistik Hadiah

**User Story:** Sebagai tamu undangan, saya ingin memberikan hadiah digital atau mengetahui alamat pengiriman hadiah fisik, sehingga saya dapat memberikan hadiah kepada pasangan pengantin.

#### Kriteria Penerimaan

1. THE Amplop_Digital SHALL menampilkan informasi rekening bank (nama bank, nomor rekening, nama pemilik) dan gambar QRIS yang diinput admin.
2. THE Amplop_Digital SHALL menyediakan tombol salin nomor rekening ke clipboard untuk setiap rekening yang ditampilkan.
3. WHERE admin mengaktifkan fitur alamat pengiriman hadiah fisik, THE Aplikasi_Undangan SHALL menampilkan alamat pengiriman lengkap.
4. WHERE admin mengaktifkan fitur live streaming, THE Aplikasi_Undangan SHALL menampilkan link live streaming (Zoom/YouTube/Instagram) yang dapat diklik tamu.

---

### Persyaratan 17: Keamanan dan Privasi Undangan

**User Story:** Sebagai admin pernikahan, saya ingin mengontrol akses ke undangan dan menampilkan informasi protokol, sehingga undangan tetap eksklusif dan informatif.

#### Kriteria Penerimaan

1. WHERE fitur proteksi password diaktifkan, THE Aplikasi_Undangan SHALL menampilkan halaman input password sebelum menampilkan konten undangan.
2. WHEN tamu memasukkan password yang benar, THE Aplikasi_Undangan SHALL menampilkan konten undangan secara lengkap.
3. IF tamu memasukkan password yang salah, THEN THE Aplikasi_Undangan SHALL menampilkan pesan "Password salah" dan tetap menampilkan halaman input password.
4. THE Aplikasi_Undangan SHALL menghasilkan e-ticket berupa QR code unik untuk setiap tamu yang dapat digunakan untuk check-in di lokasi acara.
5. WHERE admin mengaktifkan informasi protokol kesehatan, THE Aplikasi_Undangan SHALL menampilkan panduan protokol kesehatan di seksi yang terlihat jelas.

---

### Persyaratan 18: Penyimpanan Data Lokal dengan Arsitektur Siap Migrasi

**User Story:** Sebagai developer, saya ingin data disimpan secara lokal dengan struktur folder `api` yang terorganisir, sehingga migrasi ke database di masa depan dapat dilakukan dengan mudah.

#### Kriteria Penerimaan

1. THE Penyimpanan_Lokal SHALL menyimpan seluruh data (mempelai, acara, tamu, RSVP, ucapan, media, pembayaran) dalam file JSON terpisah di folder `api/data/`.
2. THE Penyimpanan_Lokal SHALL menyediakan fungsi CRUD (Create, Read, Update, Delete) melalui modul service di folder `api/services/` yang mengabstraksi akses data.
3. WHEN data dimodifikasi melalui CMS, THE Penyimpanan_Lokal SHALL memperbarui file JSON yang sesuai secara atomik (seluruh perubahan tersimpan atau tidak ada yang tersimpan).
4. THE Serializer_Data SHALL menggunakan skema JSON yang terdefinisi dengan jelas untuk setiap entitas data (mempelai, acara, tamu, ucapan, hadiah).
5. FOR ALL entitas data yang valid, serialisasi ke JSON kemudian deserialisasi kembali SHALL menghasilkan objek yang ekuivalen dengan objek asli (round-trip property).
6. FOR ALL operasi simpan data yang dijalankan dua kali berturut-turut dengan data yang sama, THE Penyimpanan_Lokal SHALL menghasilkan state yang identik dengan menjalankan operasi tersebut satu kali (idempotence property).

---

### Persyaratan 19: Animasi dan Performa Aplikasi Undangan

**User Story:** Sebagai tamu undangan, saya ingin pengalaman melihat undangan yang halus dan responsif, sehingga undangan terasa premium dan profesional.

#### Kriteria Penerimaan

1. THE Aplikasi_Undangan SHALL menerapkan animasi scroll-triggered pada setiap seksi menggunakan library AOS dengan transisi yang halus (smooth).
2. THE Aplikasi_Undangan SHALL memuat halaman pertama (First Contentful Paint) dalam waktu kurang dari 3 detik pada koneksi 3G.
3. THE Aplikasi_Undangan SHALL responsif dan menampilkan tata letak yang optimal pada perangkat mobile (lebar 320px hingga 480px), tablet (481px hingga 1024px), dan desktop (di atas 1024px).
4. THE Aplikasi_Undangan SHALL menghasilkan markup HTML yang SEO-friendly dengan meta tag Open Graph yang terisi otomatis berdasarkan data pernikahan (judul, deskripsi, gambar).

---

### Persyaratan 20: Navigasi dan Struktur CMS

**User Story:** Sebagai admin pernikahan, saya ingin CMS yang mudah digunakan dengan navigasi yang jelas, sehingga saya dapat mengelola undangan tanpa kesulitan teknis.

#### Kriteria Penerimaan

1. THE CMS SHALL menyediakan sidebar navigasi dengan menu: Dashboard, Data Pernikahan, Template & Preview, Daftar Tamu, Media, Pembayaran & Hadiah, RSVP & Ucapan, dan Pengaturan.
2. WHEN admin mengklik menu navigasi, THE CMS SHALL menampilkan halaman yang sesuai tanpa reload halaman penuh (single-page application behavior).
3. THE CMS SHALL menampilkan indikator loading yang jelas saat memproses operasi yang membutuhkan waktu (unggah file, impor data, generate link).
4. THE CMS SHALL mendukung bahasa Indonesia sebagai bahasa utama antarmuka dengan dukungan i18n untuk penambahan bahasa lain di masa depan.
5. IF terjadi error saat menyimpan data, THEN THE CMS SHALL menampilkan pesan error yang deskriptif dan menyediakan opsi untuk mencoba kembali.
