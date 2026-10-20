# Dokumen Persyaratan: Multi-Tenant Wedding Support

## Pendahuluan

Dokumen ini mendefinisikan persyaratan untuk fitur **Multi-Tenant Wedding Support** pada Wedding Invitation CMS. Saat ini, sistem menyimpan seluruh data di satu namespace localStorage yang sama, sehingga semua slug (`/wedding/:slug`) menampilkan data yang identik. Fitur ini memungkinkan pembuatan dan pengelolaan beberapa pernikahan secara independen, di mana setiap pernikahan memiliki slug unik dan data terisolasi (mempelai, acara, tamu, media, pembayaran, RSVP, ucapan, pengaturan). CMS akan mendukung pembuatan, pemilihan, dan pengelolaan beberapa pernikahan, sementara halaman undangan (`/wedding/:slug`) hanya menampilkan data milik slug yang valid.

## Glosarium

- **Registri_Pernikahan**: Modul yang menyimpan dan mengelola daftar seluruh pernikahan yang telah dibuat, termasuk metadata slug, nama, dan tanggal pembuatan. Disimpan di localStorage dengan key `wedding_registry`.
- **Slug**: Identifier unik untuk setiap pernikahan dalam format kebab-case (huruf kecil, angka, dan tanda hubung), digunakan sebagai bagian dari URL dan namespace penyimpanan data.
- **Namespace_Penyimpanan**: Mekanisme isolasi data per pernikahan menggunakan prefix slug pada key localStorage, dengan format `wedding_{slug}_{entity}` (contoh: `wedding_budi-ani_couple`).
- **CMS**: Content Management System — panel administrasi untuk mengelola data undangan pernikahan.
- **Aplikasi_Undangan**: Halaman undangan yang dilihat oleh tamu undangan di route `/wedding/:slug`.
- **Service_Layer**: Lapisan abstraksi CRUD di folder `api/services/` yang mengelola akses data ke localStorage.
- **Pemilih_Pernikahan**: Komponen UI di CMS yang memungkinkan admin membuat pernikahan baru atau berpindah antar pernikahan yang sudah ada.
- **Halaman_Tidak_Ditemukan**: Halaman error yang ditampilkan ketika slug pernikahan tidak valid atau tidak terdaftar di Registri_Pernikahan.

---

## Persyaratan

### Persyaratan 1: Registri Pernikahan

**User Story:** Sebagai admin, saya ingin memiliki registri pusat yang mencatat semua pernikahan yang telah dibuat, sehingga sistem dapat melacak dan memvalidasi slug pernikahan yang ada.

#### Kriteria Penerimaan

1. THE Registri_Pernikahan SHALL menyimpan daftar pernikahan di localStorage dengan key `wedding_registry` dalam format JSON array.
2. WHEN admin membuat pernikahan baru, THE Registri_Pernikahan SHALL menambahkan entri baru yang mencakup: id (UUID), slug, nama pernikahan (label), dan tanggal pembuatan (createdAt).
3. THE Registri_Pernikahan SHALL menjamin bahwa setiap slug bersifat unik di dalam daftar pernikahan.
4. IF admin mencoba membuat pernikahan dengan slug yang sudah ada, THEN THE Registri_Pernikahan SHALL menolak pembuatan dan menampilkan pesan "Slug sudah digunakan oleh pernikahan lain".
5. WHEN admin menghapus sebuah pernikahan dari registri, THE Registri_Pernikahan SHALL menghapus entri dari daftar dan menghapus seluruh data terkait slug tersebut dari localStorage.
6. FOR ALL daftar pernikahan yang valid, serialisasi ke JSON kemudian deserialisasi kembali SHALL menghasilkan daftar yang ekuivalen dengan daftar asli (round-trip property).

---

### Persyaratan 2: Validasi dan Format Slug

**User Story:** Sebagai admin, saya ingin slug pernikahan divalidasi secara ketat, sehingga URL undangan selalu valid dan konsisten.

#### Kriteria Penerimaan

1. THE Registri_Pernikahan SHALL menerima slug yang hanya terdiri dari huruf kecil (a-z), angka (0-9), dan tanda hubung (-), dengan panjang minimal 3 karakter dan maksimal 64 karakter.
2. THE Registri_Pernikahan SHALL menolak slug yang dimulai atau diakhiri dengan tanda hubung.
3. THE Registri_Pernikahan SHALL menolak slug yang mengandung tanda hubung berturut-turut (contoh: "budi--ani").
4. IF admin memasukkan slug dengan huruf besar, THEN THE Registri_Pernikahan SHALL mengonversi slug ke huruf kecil secara otomatis sebelum validasi.
5. IF admin memasukkan slug yang tidak memenuhi format, THEN THE Registri_Pernikahan SHALL menampilkan pesan validasi yang menjelaskan aturan format slug yang diperbolehkan.

---

### Persyaratan 3: Namespace Penyimpanan Data per Slug

**User Story:** Sebagai developer, saya ingin setiap pernikahan memiliki data yang terisolasi di localStorage, sehingga data antar pernikahan tidak saling tercampur.

#### Kriteria Penerimaan

1. THE Service*Layer SHALL menggunakan key localStorage dengan format `wedding*{slug}\_{entity}` untuk setiap entitas data (couple, events, guests, rsvp, wishes, media, payments, settings).
2. WHEN Service_Layer membaca data untuk slug tertentu, THE Service_Layer SHALL hanya mengembalikan data dari namespace slug tersebut.
3. WHEN Service_Layer menyimpan data untuk slug tertentu, THE Service_Layer SHALL menyimpan data ke namespace slug tersebut tanpa mempengaruhi data slug lain.
4. THE Service_Layer SHALL menerima parameter slug pada setiap method CRUD (get, getAll, create, update, delete, save).
5. IF Service_Layer dipanggil tanpa parameter slug, THEN THE Service_Layer SHALL melempar error dengan pesan "Slug pernikahan wajib disediakan".
6. FOR ALL pasangan slug yang berbeda dan entitas data yang sama, data yang disimpan pada satu slug SHALL tidak mempengaruhi data pada slug lainnya (isolasi property).
7. FOR ALL entitas data yang valid pada suatu slug, serialisasi ke JSON kemudian deserialisasi kembali SHALL menghasilkan objek yang ekuivalen dengan objek asli (round-trip property).

---

### Persyaratan 4: Refaktor Routing CMS dengan Konteks Slug

**User Story:** Sebagai admin, saya ingin CMS menyertakan slug pernikahan di URL, sehingga saya dapat mengelola pernikahan tertentu melalui URL yang jelas.

#### Kriteria Penerimaan

1. THE CMS SHALL mengubah struktur route dari `/cms/{page}` menjadi `/cms/:slug/{page}` (contoh: `/cms/budi-ani/dashboard`).
2. WHEN admin mengakses `/cms` tanpa slug, THE CMS SHALL mengarahkan admin ke halaman Pemilih_Pernikahan untuk memilih atau membuat pernikahan.
3. WHEN admin mengakses `/cms/:slug/{page}`, THE CMS SHALL memvalidasi bahwa slug terdaftar di Registri_Pernikahan sebelum menampilkan halaman.
4. IF admin mengakses `/cms/:slug/{page}` dengan slug yang tidak terdaftar, THEN THE CMS SHALL mengarahkan admin ke halaman Pemilih_Pernikahan dengan pesan "Pernikahan tidak ditemukan".
5. WHEN admin berpindah antar halaman CMS, THE CMS SHALL mempertahankan konteks slug di URL tanpa memerlukan pemilihan ulang.

---

### Persyaratan 5: Pemilih Pernikahan di CMS

**User Story:** Sebagai admin, saya ingin dapat membuat pernikahan baru dan berpindah antar pernikahan yang sudah ada, sehingga saya dapat mengelola beberapa undangan dari satu CMS.

#### Kriteria Penerimaan

1. THE Pemilih_Pernikahan SHALL menampilkan daftar semua pernikahan yang terdaftar di Registri_Pernikahan dengan informasi: nama pernikahan, slug, dan tanggal pembuatan.
2. THE Pemilih_Pernikahan SHALL menyediakan formulir untuk membuat pernikahan baru dengan input: nama pernikahan (label) dan slug.
3. WHEN admin memilih pernikahan dari daftar, THE CMS SHALL mengarahkan admin ke `/cms/{slug}/dashboard`.
4. WHEN admin membuat pernikahan baru, THE Registri_Pernikahan SHALL menambahkan pernikahan ke daftar dan THE CMS SHALL mengarahkan admin ke `/cms/{slug}/dashboard` pernikahan yang baru dibuat.
5. THE Pemilih_Pernikahan SHALL menyediakan opsi hapus pernikahan dengan dialog konfirmasi yang menjelaskan bahwa seluruh data pernikahan akan dihapus secara permanen.
6. THE CMS SHALL menampilkan indikator pernikahan aktif (nama dan slug) di sidebar navigasi agar admin mengetahui konteks pernikahan yang sedang dikelola.
7. THE CMS SHALL menyediakan tombol atau link di sidebar navigasi untuk kembali ke halaman Pemilih_Pernikahan.

---

### Persyaratan 6: Validasi Slug pada Aplikasi Undangan

**User Story:** Sebagai tamu undangan, saya ingin melihat halaman error yang informatif jika saya mengakses URL undangan yang tidak valid, sehingga saya mengetahui bahwa undangan tidak ditemukan.

#### Kriteria Penerimaan

1. WHEN tamu mengakses `/wedding/:slug`, THE Aplikasi_Undangan SHALL memvalidasi bahwa slug terdaftar di Registri_Pernikahan sebelum memuat data undangan.
2. IF slug tidak terdaftar di Registri_Pernikahan, THEN THE Halaman_Tidak_Ditemukan SHALL menampilkan pesan "Undangan tidak ditemukan" dengan ikon yang sesuai dan tanpa menampilkan data undangan apapun.
3. WHEN slug valid, THE Aplikasi_Undangan SHALL memuat seluruh data (mempelai, acara, pengaturan, media) hanya dari namespace slug tersebut.
4. IF slug valid tetapi data undangan belum lengkap (contoh: data mempelai kosong), THEN THE Aplikasi_Undangan SHALL tetap menampilkan undangan dengan bagian yang tersedia dan mengabaikan bagian yang kosong.

---

### Persyaratan 7: Migrasi Data Lama (Legacy)

**User Story:** Sebagai admin yang sudah memiliki data pernikahan sebelum fitur multi-tenant, saya ingin data lama saya tetap dapat diakses, sehingga saya tidak kehilangan data yang sudah diinput.

#### Kriteria Penerimaan

1. WHEN sistem mendeteksi data di localStorage dengan key lama (tanpa prefix slug, contoh: `wedding_couple`), THE Registri_Pernikahan SHALL menawarkan opsi migrasi data ke format multi-tenant.
2. WHEN admin memilih untuk memigrasi data lama, THE Service*Layer SHALL menyalin data dari key lama ke key baru dengan format `wedding*{slug}\_{entity}` menggunakan slug yang ditentukan admin.
3. WHEN migrasi selesai, THE Service_Layer SHALL menghapus key lama dari localStorage untuk menghindari duplikasi.
4. IF migrasi gagal di tengah proses, THEN THE Service_Layer SHALL mempertahankan data lama tanpa perubahan dan menampilkan pesan error.
5. FOR ALL entitas data yang dimigrasikan, data pada key baru SHALL ekuivalen dengan data pada key lama (round-trip property migrasi).

---

### Persyaratan 8: Integrasi Vuex Store dengan Konteks Slug

**User Story:** Sebagai developer, saya ingin Vuex store mengetahui konteks slug aktif, sehingga seluruh komponen CMS mengakses data yang benar.

#### Kriteria Penerimaan

1. THE Vuex Store SHALL menyimpan slug pernikahan aktif di state global yang dapat diakses oleh seluruh modul store.
2. WHEN admin berpindah ke pernikahan lain (slug berubah), THE Vuex Store SHALL mereset seluruh state modul (couple, events, guests, rsvp, wishes, media, payments, settings) dan memuat ulang data dari namespace slug baru.
3. WHEN komponen CMS melakukan dispatch action ke Vuex Store, THE Vuex Store SHALL meneruskan slug aktif ke Service_Layer secara otomatis.
4. IF slug aktif belum diset (null), THEN THE Vuex Store SHALL menolak operasi data dan mengembalikan error "Tidak ada pernikahan aktif".
