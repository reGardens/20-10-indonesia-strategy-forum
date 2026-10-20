# Implementation Plan: Laravel + Vue.js CMS Landing Page

## Overview

Rencana implementasi untuk template fullstack CMS dan Landing Page menggunakan Laravel 11.x, Vue.js 3.x, dan Inertia.js. Implementasi dilakukan secara inkremental — dimulai dari setup project dan infrastruktur Docker, kemudian backend (auth, RBAC, models, controllers), frontend (layouts, pages, components), utility functions dengan property-based tests, dan diakhiri dengan integrasi landing page publik.

Catatan: Build/deployment di-skip sesuai permintaan user (note #6).

## Tasks

- [x]   1. Setup project structure dan Docker environment
    - [x] 1.1 Buat konfigurasi Docker (Dockerfile, docker-compose.yml, Nginx config)
        - Buat `Dockerfile` untuk PHP container dengan ekstensi Laravel (pdo, mbstring, openssl, tokenizer, xml, ctype, json, bcmath)
        - Buat `docker-compose.yml` dengan service: PHP (Laravel), Node.js (Vite), MySQL/PostgreSQL, dan Nginx
        - Buat konfigurasi Nginx untuk routing ke PHP-FPM
        - Sediakan volume mapping untuk source code agar perubahan langsung terrefleksi
        - _Requirements: 9.1, 9.2, 9.3, 9.4_
    - [x] 1.2 Inisialisasi project Laravel 11.x dengan Vue.js 3.x dan Inertia.js
        - Buat project Laravel baru (atau scaffold manual)
        - Install dan konfigurasi Inertia.js (server-side adapter + client-side adapter)
        - Install dan konfigurasi Vue.js 3.x dengan Composition API
        - Konfigurasi Vite untuk Vue.js
        - Buat file `.env.example` dengan semua variabel environment dan komentar penjelasan
        - _Requirements: 8.1, 8.2, 8.3, 8.6, 9.5_
    - [x] 1.3 Install dan konfigurasi Tailwind CSS + shadcn-vue
        - Install Tailwind CSS 3.x/4.x dan konfigurasi `tailwind.config.js`
        - Install dan inisialisasi shadcn-vue
        - Generate base UI components (Button, Input, Card, Dialog, Table, Pagination, Select, Badge)
        - _Requirements: 8.4, 8.5_
    - [x] 1.4 Setup struktur folder frontend
        - Buat direktori: `resources/js/Layouts/`, `resources/js/Pages/`, `resources/js/Components/`, `resources/js/Composables/`, `resources/js/Utils/`
        - Buat sub-direktori: `Pages/Auth/`, `Pages/Cms/Users/`, `Pages/Cms/Articles/`, `Pages/Cms/Cards/`, `Pages/Landing/`, `Pages/Errors/`, `Components/Cms/`, `Components/Landing/`, `Components/UI/`
        - _Requirements: 10.1, 10.2, 10.3_

- [x]   2. Checkpoint — Pastikan project structure dan Docker berjalan
    - Pastikan semua konfigurasi valid, tanyakan ke user jika ada pertanyaan.

- [x]   3. Implementasi database models, migrations, dan seeders
    - [x] 3.1 Install spatie/laravel-permission dan buat migrations
        - Install package `spatie/laravel-permission` v6
        - Publish migration files dari package
        - Buat migration untuk tabel `categories` (id, name, slug, timestamps)
        - Buat migration untuk tabel `articles` (id, user_id FK, category_id FK, title, slug UK, content, featured_image, status enum draft/published, published_at, soft deletes, timestamps)
        - Buat migration untuk tabel `cards` (id, user_id FK, title, description, image, link, sort_order, is_active, soft deletes, timestamps)
        - _Requirements: 2.1, 4.1, 5.1_
    - [x] 3.2 Buat Eloquent Models (User, Article, Category, Card)
        - Update `User.php` — tambahkan `HasRoles` trait, `SoftDeletes`, definisikan relationships dan validasi
        - Buat `Article.php` — `SoftDeletes`, relationships (belongsTo User, belongsTo Category), scope `published()`, accessor untuk slug
        - Buat `Category.php` — relationship hasMany Articles, auto-generate slug
        - Buat `Card.php` — `SoftDeletes`, ordering scope, validasi (judul max 100, deskripsi max 500)
        - _Requirements: 3.7, 4.1, 4.5, 5.5_
    - [x] 3.3 Buat SlugService dan MediaService
        - Buat `app/Services/SlugService.php` — generate unique slug dari judul, handle duplicate dengan angka incremental
        - Buat `app/Services/MediaService.php` — handle upload gambar, validasi format (JPG/PNG/WebP) dan ukuran (max 2MB)
        - _Requirements: 4.5, 5.5_
    - [x] 3.4 Buat database seeders
        - Buat `RoleSeeder` — seed roles: super-admin, admin, user
        - Buat `UserSeeder` — seed Super Admin default dengan kredensial terdokumentasi
        - Buat `CategorySeeder` — seed beberapa kategori contoh
        - Buat `ContentSeeder` — seed beberapa artikel dan card untuk demo
        - Update `DatabaseSeeder.php` untuk menjalankan semua seeders
        - _Requirements: 2.1, 11.2, 11.4_

- [x]   4. Implementasi Auth dan RBAC system
    - [x] 4.1 Buat Auth controllers dan routes
        - Buat `AuthenticatedSessionController.php` — handle login (create, store) dan logout (destroy)
        - Definisikan routes di `web.php`: GET/POST `/login`, POST `/logout`
        - Implementasi validasi kredensial, session management, dan CSRF protection
        - Redirect ke dashboard setelah login sukses, tampilkan error "Email atau password salah" jika gagal
        - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_
    - [x] 4.2 Konfigurasi RBAC middleware dan policies
        - Buat `CheckRole.php` middleware untuk role checking
        - Update `HandleInertiaRequests.php` — share auth data dan role/permission ke frontend
        - Definisikan permission matrix: super-admin (full), admin (content write), user (content read)
        - Register middleware di route groups
        - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_
    - [x] 4.3 Buat error page handling (403, 404, 419, 500)
        - Konfigurasi exception handler di `bootstrap/app.php` untuk render error pages via Inertia
        - Buat Vue error page components: `Pages/Errors/403.vue`, `404.vue`, `419.vue`, `500.vue`
        - _Requirements: 2.5, 1.5_

- [x]   5. Implementasi User Management (Backend + Frontend)
    - [x] 5.1 Buat UserController dengan CRUD operations
        - Buat `UserController.php` — index (daftar + pagination), create, store, edit, update, destroy (soft delete)
        - Buat `UserPolicy.php` untuk authorization
        - Definisikan routes dengan middleware `role:super-admin`
        - Implementasi validasi: email unique, nama min 2 karakter, password min 8 karakter dengan huruf dan angka
        - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_
    - [x] 5.2 Buat Vue pages untuk User Management
        - Buat `Pages/Cms/Users/Index.vue` — tabel pengguna dengan nama, email, role, tanggal pembuatan
        - Buat `Pages/Cms/Users/Create.vue` — form pembuatan pengguna dengan role selection
        - Buat `Pages/Cms/Users/Edit.vue` — form edit pengguna dengan update role
        - Gunakan shadcn-vue components (Table, Input, Select, Button, Dialog)
        - _Requirements: 3.1, 3.2, 3.4_

- [x]   6. Checkpoint — Pastikan auth, RBAC, dan user management berfungsi
    - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x]   7. Implementasi Content Management — Artikel (Backend + Frontend)
    - [x] 7.1 Buat ArticleController dengan CRUD operations
        - Buat `ArticleController.php` — index (pagination, search, filter by status/category), create, store, edit, update, destroy (soft delete)
        - Definisikan routes dengan middleware `role:super-admin|admin` untuk write, `role:super-admin|admin|user` untuk read
        - Integrasikan `SlugService` untuk auto-generate slug dari judul
        - Implementasi publish/draft toggle dan `published_at` timestamp
        - _Requirements: 4.1, 4.2, 4.3, 4.5, 4.6, 4.7_
    - [x] 7.2 Buat Vue pages untuk Article Management
        - Buat `Pages/Cms/Articles/Index.vue` — daftar artikel dengan pagination, search, filter status/kategori
        - Buat `Pages/Cms/Articles/Create.vue` — form artikel dengan Tiptap rich text editor, auto-slug, image upload, category select, status toggle, datetime picker
        - Buat `Pages/Cms/Articles/Edit.vue` — form edit artikel
        - _Requirements: 4.1, 4.4, 4.5, 4.6_
    - [x] 7.3 Buat RichTextEditor component (Tiptap wrapper)
        - Install `@tiptap/vue-3` dan extensions yang dibutuhkan
        - Buat `Components/Cms/RichTextEditor.vue` — wrapper Tiptap dengan toolbar: bold, italic, heading, list, link, image embed, code block
        - Integrasikan dengan shadcn-vue styling (tiptap-shadcn-vue)
        - _Requirements: 4.4_

- [x]   8. Implementasi Content Management — Card Components (Backend + Frontend)
    - [x] 8.1 Buat CardController dengan CRUD operations
        - Buat `CardController.php` — index, create, store, edit, update, destroy (soft delete), reorder (PATCH)
        - Definisikan routes dengan middleware `role:super-admin|admin` untuk write
        - Implementasi reorder endpoint untuk update `sort_order`
        - Validasi: judul max 100, deskripsi max 500, gambar JPG/PNG/WebP max 2MB
        - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_
    - [x] 8.2 Buat Vue pages untuk Card Management
        - Buat `Pages/Cms/Cards/Index.vue` — daftar card dengan preview dan drag-to-reorder
        - Buat `Pages/Cms/Cards/Create.vue` — form card (judul, deskripsi, gambar upload, link, urutan, is_active toggle)
        - Buat `Pages/Cms/Cards/Edit.vue` — form edit card
        - _Requirements: 5.1, 5.2, 5.3_

- [x]   9. Implementasi CMS Layout dan Dashboard
    - [x] 9.1 Buat CMS Layout components
        - Buat `Layouts/CmsLayout.vue` — layout utama dengan sidebar navigasi, top navbar, dan content area
        - Buat `Components/Cms/Sidebar.vue` — sidebar dengan menu items berdasarkan role/permission
        - Buat `Components/Cms/DataTable.vue` — reusable data table dengan search, filter, dan pagination
        - _Requirements: 10.1, 2.2, 2.3, 2.4_
    - [x] 9.2 Buat Dashboard dan halaman login
        - Buat `Pages/Cms/Dashboard.vue` — dashboard CMS dengan ringkasan konten
        - Buat `Pages/Auth/Login.vue` — halaman login dengan form email/password dan error handling
        - Buat `Layouts/GuestLayout.vue` — layout untuk halaman login (tanpa sidebar)
        - _Requirements: 1.2, 1.3_
    - [x] 9.3 Buat Composables untuk auth dan permission
        - Buat `Composables/useAuth.js` — auth state, current user, logout helper
        - Buat `Composables/usePermission.js` — permission checking di frontend (hasRole, can)
        - Buat `Composables/useForm.js` — form handling wrapper dengan Inertia
        - _Requirements: 2.2, 2.3, 2.4_

- [x]   10. Checkpoint — Pastikan seluruh CMS berfungsi end-to-end
    - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x]   11. Implementasi Utility Functions
    - [x] 11.1 Buat validation utilities
        - Buat `resources/js/Utils/validation.js` — fungsi: `isRequired`, `isEmail`, `isURL`, `isPhoneIndonesia`, `minLength`, `maxLength`, `isNumeric`
        - Sertakan JSDoc comments dengan parameter, return value, dan contoh penggunaan
        - _Requirements: 12.1, 8.7, 10.5_
    - [x] 11.2 Buat date utilities
        - Buat `resources/js/Utils/date.js` — fungsi: `formatIndonesia` (contoh: "1 Januari 2025"), `formatISO`, `parseDate`, `parseIndonesia`, `relativeTime` (contoh: "2 jam yang lalu")
        - Sertakan JSDoc comments
        - _Requirements: 12.2, 8.8, 10.5_
    - [x] 11.3 Buat format dan string utilities
        - Buat `resources/js/Utils/format.js` — fungsi: `formatCurrency` (Rupiah), `formatNumber` (separator), `truncateText` (ellipsis)
        - Buat `resources/js/Utils/string.js` — fungsi: `slugify`, `capitalize`, `sanitizeHTML`
        - Sertakan JSDoc comments
        - _Requirements: 12.3, 12.4, 10.5_
    - [ ]\* 11.4 Write property test — Slugify output format invariant
        - **Property 1: Slugify output format invariant**
        - Menggunakan Vitest + fast-check, minimum 100 iterasi
        - Generate random strings → `slugify` → assert output matches `/^[a-z0-9]+(-[a-z0-9]+)*$/` atau empty string
        - Assert output tidak diawali atau diakhiri dengan tanda hubung
        - Tag komentar: `// Feature: laravel-vuejs-cms-landing, Property 1: Slugify output format invariant`
        - **Validates: Requirements 4.5, 12.5**
    - [ ]\* 11.5 Write property test — Date format/parse round-trip
        - **Property 3: Date format/parse round-trip**
        - Menggunakan Vitest + fast-check, minimum 100 iterasi
        - Generate random valid dates → `formatIndonesia` → `parseIndonesia` → assert date equality (tahun, bulan, hari)
        - Tag komentar: `// Feature: laravel-vuejs-cms-landing, Property 3: Date format/parse round-trip`
        - **Validates: Requirements 12.6**
    - [ ]\* 11.6 Write unit tests untuk utility functions
        - Test validation.js: isEmail valid/invalid, isURL, isPhoneIndonesia, edge cases
        - Test date.js: formatIndonesia, formatISO, relativeTime
        - Test format.js: formatCurrency, formatNumber, truncateText
        - Test string.js: slugify edge cases, capitalize, sanitizeHTML
        - _Requirements: 12.1, 12.2, 12.3, 12.4_

- [x]   12. Implementasi Landing Page publik
    - [x] 12.1 Buat LandingPageController (Backend)
        - Buat `LandingPageController.php` — `index()` untuk serve landing page data (published articles, active cards sorted by sort_order)
        - Buat `showArticle($slug)` untuk detail artikel publik
        - Definisikan public routes: GET `/`, GET `/articles/{slug}` (tanpa auth middleware)
        - _Requirements: 6.1, 6.5, 6.6, 6.8_
    - [x] 12.2 Buat Landing Page layout dan halaman utama
        - Buat `Layouts/LandingLayout.vue` — layout landing page (navbar, content, footer)
        - Buat `Pages/Landing/Index.vue` — landing page utama dengan sections: Hero, Features, Artikel terbaru, Footer
        - Buat `Pages/Landing/ArticleDetail.vue` — halaman detail artikel
        - _Requirements: 6.1, 6.6, 6.8_
    - [x] 12.3 Buat Landing Page section components
        - Buat `Components/Landing/HeroSection.vue` — hero section dengan animasi
        - Buat `Components/Landing/FeaturesSection.vue` — section card components dari CMS
        - Buat `Components/Landing/ArticlesSection.vue` — section artikel terbaru (published)
        - Buat `Components/Landing/FooterSection.vue` — footer
        - Buat `Components/Landing/AnimatedCard.vue` — card dengan hover animation (shadow + scale)
        - _Requirements: 6.1, 6.5, 6.6_
    - [x] 12.4 Implementasi animasi (AOS + GSAP)
        - Install AOS dan GSAP
        - Buat `Composables/useAnimation.js` — inisialisasi AOS, GSAP smooth scrolling, dan animation helpers
        - Buat `resources/js/Utils/animation.js` — konfigurasi animasi (durasi, easing, offset)
        - Terapkan scroll-triggered animations (fade-in, slide-up, scale) pada setiap section
        - Terapkan hover animations pada tombol (scale + color transition) dan card (shadow + scale, 300ms)
        - Implementasi `prefers-reduced-motion` media query — nonaktifkan animasi jika aktif
        - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_
    - [x] 12.5 Implementasi responsive layout
        - Pastikan landing page responsif: mobile (320px), tablet (768px), desktop (1024px+)
        - Gunakan Tailwind CSS responsive utilities
        - _Requirements: 6.7_

- [ ]   13. Implementasi Article round-trip property test (Backend)
    - [ ]\* 13.1 Write property test — Article data round-trip persistence
        - **Property 2: Article data round-trip persistence**
        - Menggunakan PHPUnit/Pest dengan property-based testing approach
        - Generate random valid article data (judul non-empty, konten non-empty, kategori valid, status valid) → save ke database → load kembali → assert equality pada semua field (judul, slug, konten, featured_image, kategori, status, published_at)
        - Tag komentar: `// Feature: laravel-vuejs-cms-landing, Property 2: Article data round-trip persistence`
        - **Validates: Requirements 4.8**

- [x]   14. Buat dokumentasi README
    - [x] 14.1 Buat README.md lengkap
        - Tulis section: Deskripsi Project, Tech Stack, Prerequisites, Instalasi (step-by-step)
        - Tulis section: Konfigurasi Database (buat database, konfigurasi .env, jalankan migration, jalankan seeder)
        - Tulis section: Menjalankan dengan Docker (`docker-compose up`)
        - Tulis section: Struktur Folder, Panduan Penggunaan CMS
        - Dokumentasikan kredensial Super Admin default dari seeder
        - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [x]   15. Final checkpoint — Pastikan semua tests pass dan integrasi lengkap
    - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

## Notes

- Task yang ditandai `*` bersifat opsional dan bisa di-skip untuk MVP lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Checkpoints memastikan validasi inkremental di setiap tahap
- Property tests memvalidasi correctness properties universal (slugify, date round-trip, article round-trip)
- Unit tests memvalidasi contoh spesifik dan edge cases
- Build/deployment di-skip sesuai permintaan user (note #6)
- Tech stack: PHP (Laravel 11.x) untuk backend, JavaScript/Vue.js 3.x untuk frontend
- Property-based testing menggunakan fast-check (frontend) dan PHPUnit/Pest (backend)
