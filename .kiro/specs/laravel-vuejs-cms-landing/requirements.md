# Dokumen Requirements

## Pendahuluan

Dokumen ini mendefinisikan requirements untuk template fullstack menggunakan Laravel (terbaru) dan Vue.js (terbaru) dengan Inertia.js. Sistem terdiri dari dua bagian utama: **CMS (Content Management System)** dengan autentikasi dan role-based access control, serta **Landing Page** publik yang menampilkan konten dinamis yang dikelola melalui CMS. Sistem menggunakan tech stack modern termasuk Tailwind CSS, Shadcn UI (shadcn-vue), rich text editor, AOS, GSAP, dan Docker untuk containerization.

## Glossary

- **CMS**: Content Management System — antarmuka admin untuk mengelola konten, pengguna, dan pengaturan sistem
- **Landing_Page**: Halaman publik yang menampilkan konten dinamis dari CMS dengan animasi dan efek visual
- **Auth_System**: Sistem autentikasi yang menangani login, logout, dan session management
- **ACL_System**: Access Control List — sistem yang mengatur hak akses berdasarkan role pengguna
- **Role_Manager**: Komponen yang mengelola role pengguna (Super Admin, Admin, User)
- **User_Manager**: Komponen untuk membuat, mengedit, dan menghapus akun pengguna
- **Content_Manager**: Komponen untuk membuat, mengedit, menghapus, dan mempublikasikan konten (artikel, card, dll)
- **Component_Renderer**: Komponen yang merender dynamic components dari CMS ke Landing_Page
- **Card_Component**: Komponen card yang bisa ditambah/diedit melalui CMS dan ditampilkan di Landing_Page
- **Article_Editor**: Editor artikel dengan rich text, validasi, datetime picker, dan media upload
- **Animation_Engine**: Sistem animasi menggunakan library AOS dan GSAP untuk efek visual di Landing_Page
- **Docker_Environment**: Konfigurasi Docker untuk menjalankan seluruh stack (PHP, Node.js, database, web server)
- **Validation_Utility**: Kumpulan utility untuk validasi input di frontend dan backend
- **Date_Utility**: Kumpulan utility untuk formatting dan manipulasi tanggal
- **Super_Admin**: Role dengan akses penuh — dapat mengelola pengguna, ACL, dan semua konten
- **Admin**: Role dengan akses terbatas — dapat mengelola konten tetapi tidak dapat mengelola pengguna atau ACL
- **User**: Role dengan akses read-only — hanya dapat melihat konten di CMS

---

## Requirements

### Requirement 1: Autentikasi Pengguna

**User Story:** Sebagai pengguna, saya ingin bisa login dan logout dari CMS, sehingga saya dapat mengakses fitur sesuai role saya dengan aman.

#### Acceptance Criteria

1. WHEN pengguna mengakses halaman CMS tanpa session aktif, THE Auth_System SHALL mengarahkan pengguna ke halaman login
2. WHEN pengguna mengirimkan kredensial login yang valid, THE Auth_System SHALL membuat session terautentikasi dan mengarahkan pengguna ke dashboard CMS
3. WHEN pengguna mengirimkan kredensial login yang tidak valid, THE Auth_System SHALL menampilkan pesan error "Email atau password salah" tanpa mengungkapkan field mana yang salah
4. WHEN pengguna menekan tombol logout, THE Auth_System SHALL menghapus session dan mengarahkan pengguna ke halaman login
5. IF session pengguna telah kedaluwarsa, THEN THE Auth_System SHALL mengarahkan pengguna ke halaman login dengan pesan "Session telah berakhir"
6. THE Auth_System SHALL menggunakan CSRF protection pada semua form autentikasi

---

### Requirement 2: Role-Based Access Control (RBAC)

**User Story:** Sebagai Super Admin, saya ingin mengatur hak akses berdasarkan role, sehingga setiap pengguna hanya dapat mengakses fitur yang sesuai dengan perannya.

#### Acceptance Criteria

1. THE ACL_System SHALL mendukung tiga role: Super_Admin, Admin, dan User
2. WHILE pengguna memiliki role Super_Admin, THE ACL_System SHALL memberikan akses penuh ke semua fitur CMS termasuk User_Manager dan ACL_System
3. WHILE pengguna memiliki role Admin, THE ACL_System SHALL memberikan akses ke Content_Manager tetapi memblokir akses ke User_Manager dan pengaturan ACL
4. WHILE pengguna memiliki role User, THE ACL_System SHALL memberikan akses read-only ke konten di CMS
5. WHEN pengguna tanpa izin mencoba mengakses fitur yang dibatasi, THE ACL_System SHALL menampilkan halaman 403 Forbidden
6. THE ACL_System SHALL memeriksa izin pada setiap request ke route yang dilindungi melalui middleware

---

### Requirement 3: Manajemen Pengguna

**User Story:** Sebagai Super Admin, saya ingin bisa membuat dan mengelola akun pengguna, sehingga saya dapat mengontrol siapa yang memiliki akses ke CMS.

#### Acceptance Criteria

1. WHILE pengguna memiliki role Super_Admin, THE User_Manager SHALL menampilkan daftar semua pengguna dengan informasi nama, email, role, dan tanggal pembuatan
2. WHEN Super_Admin mengirimkan form pembuatan pengguna baru dengan data valid, THE User_Manager SHALL membuat akun pengguna baru dengan role yang ditentukan
3. WHEN Super_Admin mengirimkan form pembuatan pengguna dengan email yang sudah terdaftar, THE User_Manager SHALL menampilkan pesan error "Email sudah digunakan"
4. WHEN Super_Admin mengubah role pengguna, THE User_Manager SHALL memperbarui role dan hak akses pengguna secara langsung
5. WHEN Super_Admin menghapus akun pengguna, THE User_Manager SHALL menonaktifkan akun tersebut menggunakan soft delete
6. IF pengguna dengan role Admin atau User mencoba mengakses User_Manager, THEN THE ACL_System SHALL memblokir akses dan menampilkan halaman 403 Forbidden
7. THE User_Manager SHALL memvalidasi input: email harus format valid, nama minimal 2 karakter, password minimal 8 karakter dengan kombinasi huruf dan angka

---

### Requirement 4: Manajemen Konten (Artikel)

**User Story:** Sebagai Admin, saya ingin bisa membuat dan mengelola artikel, sehingga konten dapat dipublikasikan ke Landing_Page.

#### Acceptance Criteria

1. WHEN Admin atau Super_Admin membuka halaman pembuatan artikel, THE Article_Editor SHALL menampilkan form dengan field: judul, slug (auto-generate dari judul), konten (rich text editor), featured image, kategori, status (draft/published), dan tanggal publikasi
2. WHEN pengguna mengirimkan artikel dengan data valid, THE Content_Manager SHALL menyimpan artikel ke database dengan timestamp created_at dan updated_at
3. WHEN pengguna mengirimkan artikel tanpa mengisi field wajib (judul, konten), THE Validation_Utility SHALL menampilkan pesan error spesifik per field
4. THE Article_Editor SHALL menyediakan rich text editor dengan fitur: bold, italic, heading, list, link, image embed, dan code block
5. WHEN pengguna mengubah judul artikel, THE Article_Editor SHALL secara otomatis menghasilkan slug URL-friendly dari judul tersebut
6. THE Content_Manager SHALL menampilkan daftar artikel dengan pagination, pencarian, dan filter berdasarkan status dan kategori
7. WHEN pengguna mengubah status artikel menjadi "published", THE Content_Manager SHALL membuat artikel tersebut tersedia di Landing_Page
8. FOR ALL artikel yang valid, menyimpan lalu memuat artikel tersebut SHALL menghasilkan data yang identik dengan data asli (round-trip property)

---

### Requirement 5: Manajemen Komponen Dinamis (Card)

**User Story:** Sebagai Admin, saya ingin bisa menambah dan mengelola card components melalui CMS, sehingga card tersebut langsung muncul di Landing_Page.

#### Acceptance Criteria

1. WHEN Admin atau Super_Admin membuka halaman manajemen komponen, THE Content_Manager SHALL menampilkan daftar semua card components dengan preview
2. WHEN pengguna membuat card baru dengan data valid (judul, deskripsi, gambar, link, urutan), THE Content_Manager SHALL menyimpan card dan membuatnya tersedia di Landing_Page
3. WHEN pengguna mengubah urutan card, THE Component_Renderer SHALL menampilkan card di Landing_Page sesuai urutan yang baru
4. WHEN pengguna menghapus card, THE Content_Manager SHALL menghapus card menggunakan soft delete dan menghilangkannya dari Landing_Page
5. THE Content_Manager SHALL memvalidasi data card: judul maksimal 100 karakter, deskripsi maksimal 500 karakter, gambar harus format JPG/PNG/WebP dengan ukuran maksimal 2MB
6. WHEN card baru ditambahkan atau diperbarui, THE Component_Renderer SHALL menampilkan perubahan di Landing_Page tanpa memerlukan deployment ulang

---

### Requirement 6: Landing Page Publik

**User Story:** Sebagai pengunjung, saya ingin melihat landing page yang menarik dengan animasi smooth, sehingga saya mendapatkan pengalaman browsing yang menyenangkan.

#### Acceptance Criteria

1. THE Landing_Page SHALL menampilkan section-section berikut: Hero, Features (card components dari CMS), Artikel terbaru, dan Footer
2. THE Animation_Engine SHALL menerapkan scroll-triggered animations menggunakan library AOS pada setiap section saat pertama kali terlihat di viewport
3. THE Animation_Engine SHALL menerapkan smooth scrolling menggunakan GSAP untuk navigasi antar section
4. THE Animation_Engine SHALL menerapkan animasi hover pada tombol dan card dengan efek transisi yang halus (durasi 300ms)
5. THE Landing_Page SHALL menampilkan card components yang dikelola melalui CMS secara dinamis sesuai urutan yang ditentukan
6. THE Landing_Page SHALL menampilkan daftar artikel terbaru yang berstatus "published" dengan judul, ringkasan, gambar, dan tanggal publikasi
7. THE Landing_Page SHALL responsif dan menampilkan layout yang optimal pada perangkat mobile (320px), tablet (768px), dan desktop (1024px ke atas)
8. THE Landing_Page SHALL dapat diakses tanpa autentikasi

---

### Requirement 7: Animasi dan Efek Visual

**User Story:** Sebagai pengunjung, saya ingin melihat animasi yang halus seperti efek Apple, sehingga landing page terasa modern dan profesional.

#### Acceptance Criteria

1. THE Animation_Engine SHALL menggunakan library AOS (Animate On Scroll) untuk animasi elemen saat scroll
2. THE Animation_Engine SHALL menggunakan library GSAP untuk animasi kompleks termasuk smooth scrolling, parallax effect, dan staggered animations
3. WHEN pengunjung melakukan scroll, THE Animation_Engine SHALL menampilkan animasi fade-in, slide-up, dan scale pada elemen-elemen section
4. WHEN pengunjung mengarahkan kursor ke tombol, THE Animation_Engine SHALL menampilkan efek hover dengan transisi scale dan perubahan warna yang halus
5. WHEN pengunjung mengarahkan kursor ke card, THE Animation_Engine SHALL menampilkan efek elevasi (shadow) dan subtle scale transform
6. THE Animation_Engine SHALL memastikan semua animasi berjalan pada 60fps tanpa menyebabkan layout shift
7. WHILE perangkat pengguna mengaktifkan "prefers-reduced-motion", THE Animation_Engine SHALL menonaktifkan semua animasi dan menampilkan konten secara statis

---

### Requirement 8: Tech Stack dan Konfigurasi Project

**User Story:** Sebagai developer, saya ingin project menggunakan tech stack terbaru dan terkonfigurasi dengan baik, sehingga development berjalan efisien.

#### Acceptance Criteria

1. THE CMS SHALL menggunakan Laravel versi terbaru (11.x) sebagai backend framework
2. THE CMS SHALL menggunakan Vue.js versi terbaru (3.x) dengan Composition API sebagai frontend framework
3. THE CMS SHALL menggunakan Inertia.js sebagai bridge antara Laravel dan Vue.js untuk single-page application experience
4. THE CMS SHALL menggunakan Tailwind CSS versi terbaru (3.x atau 4.x) untuk styling
5. THE CMS SHALL menggunakan shadcn-vue sebagai component library untuk UI components
6. THE CMS SHALL menggunakan Vite sebagai build tool dan development server
7. THE Validation_Utility SHALL menyediakan helper functions untuk validasi email, URL, nomor telepon, dan format tanggal
8. THE Date_Utility SHALL menyediakan helper functions untuk formatting tanggal (format Indonesia dan ISO), parsing, dan kalkulasi selisih waktu

---

### Requirement 9: Docker Environment

**User Story:** Sebagai developer, saya ingin menjalankan seluruh stack menggunakan Docker, sehingga setup development environment menjadi konsisten dan mudah.

#### Acceptance Criteria

1. THE Docker_Environment SHALL menyediakan file docker-compose.yml yang mendefinisikan service: PHP (dengan Laravel), Node.js, MySQL/PostgreSQL, dan Nginx
2. THE Docker_Environment SHALL menyediakan Dockerfile untuk PHP dengan ekstensi yang dibutuhkan Laravel (pdo, mbstring, openssl, tokenizer, xml, ctype, json, bcmath)
3. WHEN developer menjalankan perintah `docker-compose up`, THE Docker_Environment SHALL memulai semua service dan membuat aplikasi dapat diakses di localhost
4. THE Docker_Environment SHALL menyediakan volume mapping untuk source code agar perubahan kode langsung terrefleksi tanpa rebuild container
5. THE Docker_Environment SHALL menyediakan file `.env.example` dengan konfigurasi database yang sesuai dengan service Docker
6. IF service database gagal dimulai, THEN THE Docker_Environment SHALL menampilkan log error yang deskriptif melalui `docker-compose logs`

---

### Requirement 10: Struktur Folder dan Clean Code

**User Story:** Sebagai developer, saya ingin kode terstruktur rapi dan mudah dibaca, sehingga maintenance dan kolaborasi menjadi lebih mudah.

#### Acceptance Criteria

1. THE CMS SHALL mengorganisasi Vue components dalam struktur folder: `resources/js/Components/` (reusable), `resources/js/Pages/` (halaman), dan `resources/js/Layouts/` (layout templates)
2. THE CMS SHALL mengorganisasi utility functions dalam folder `resources/js/Utils/` dengan file terpisah untuk setiap kategori (validation.js, date.js, format.js, animation.js)
3. THE CMS SHALL mengorganisasi composables Vue dalam folder `resources/js/Composables/` untuk logic yang reusable
4. THE CMS SHALL menggunakan naming convention yang konsisten: PascalCase untuk components, camelCase untuk functions dan variables, kebab-case untuk file routes
5. THE CMS SHALL menyertakan komentar JSDoc pada setiap utility function yang menjelaskan parameter, return value, dan contoh penggunaan
6. THE CMS SHALL mengorganisasi Laravel backend dengan pattern: Controllers di `app/Http/Controllers/`, Middleware di `app/Http/Middleware/`, Models di `app/Models/`, dan Policies di `app/Policies/`

---

### Requirement 11: Dokumentasi

**User Story:** Sebagai developer, saya ingin dokumentasi README yang simpel dan mudah dipahami, sehingga saya bisa setup project dengan cepat.

#### Acceptance Criteria

1. THE CMS SHALL menyediakan file README.md dengan section: Deskripsi Project, Tech Stack, Prerequisites, Instalasi, Konfigurasi Database (step-by-step), Menjalankan dengan Docker, Struktur Folder, dan Panduan Penggunaan CMS
2. THE CMS SHALL menyediakan panduan database step-by-step yang mencakup: membuat database, konfigurasi .env, menjalankan migration, dan menjalankan seeder untuk data awal (termasuk akun Super Admin default)
3. THE CMS SHALL menyediakan file `.env.example` dengan semua variabel environment yang dibutuhkan dan komentar penjelasan untuk setiap variabel
4. THE CMS SHALL menyediakan seeder yang membuat akun Super Admin default dengan kredensial yang terdokumentasi di README

---

### Requirement 12: Validasi dan Utility Functions

**User Story:** Sebagai developer, saya ingin kumpulan utility functions yang siap pakai, sehingga saya tidak perlu menulis ulang logic umum.

#### Acceptance Criteria

1. THE Validation_Utility SHALL menyediakan fungsi validasi untuk: required field, email format, URL format, minimum/maximum length, numeric, dan phone number format Indonesia
2. THE Date_Utility SHALL menyediakan fungsi untuk: format tanggal ke format Indonesia (contoh: "1 Januari 2025"), format tanggal ke ISO 8601, parsing string tanggal, dan menghitung selisih waktu relatif (contoh: "2 jam yang lalu")
3. THE CMS SHALL menyediakan utility untuk formatting: currency (Rupiah), number dengan separator, dan truncate text dengan ellipsis
4. THE CMS SHALL menyediakan utility untuk string manipulation: slugify, capitalize, dan sanitize HTML
5. FOR ALL string input yang valid, menjalankan slugify lalu memastikan hasilnya hanya mengandung huruf kecil, angka, dan tanda hubung SHALL menghasilkan true (property test)
6. FOR ALL tanggal yang valid, memformat lalu memparse kembali tanggal tersebut SHALL menghasilkan tanggal yang ekuivalen dengan input asli (round-trip property)
