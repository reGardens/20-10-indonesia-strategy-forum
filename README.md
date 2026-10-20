# CMS Landing Page — Laravel + Vue.js

Template fullstack Content Management System (CMS) dan Landing Page publik menggunakan Laravel 11.x, Vue.js 3.x, dan Inertia.js. Sistem ini menyediakan antarmuka admin untuk mengelola konten landing page (sections, navbar, footer, pengguna) dengan Role-Based Access Control, serta landing page publik yang menampilkan konten dinamis dengan animasi modern.

## Tech Stack

| Layer       | Teknologi                      |
| ----------- | ------------------------------ |
| Backend     | Laravel 11.x, PHP 8.2+        |
| Frontend    | Vue.js 3.x (Composition API)  |
| SPA Bridge  | Inertia.js                     |
| Styling     | Tailwind CSS 3.x, shadcn-vue  |
| Rich Editor | Tiptap (@tiptap/vue-3)         |
| Animasi     | AOS (Animate On Scroll), GSAP  |
| Database    | SQLite (dev) / MySQL (prod)    |
| Build Tool  | Vite                           |
| RBAC        | spatie/laravel-permission v6   |

## Prerequisites

- PHP 8.2 atau lebih baru
- Composer 2.x
- Node.js 20+ dan npm
- SQLite (default, sudah built-in di PHP) atau MySQL 8.0

## Instalasi

### 1. Clone Repository

```bash
git clone <repository-url> cms-landing
cd cms-landing
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Default menggunakan SQLite — tidak perlu konfigurasi tambahan. File database akan otomatis dibuat saat migration.

> **Menggunakan MySQL?** Edit `.env` dan ubah:
>
> ```dotenv
> DB_CONNECTION=mysql
> DB_HOST=127.0.0.1
> DB_PORT=3306
> DB_DATABASE=cms_landing
> DB_USERNAME=root
> DB_PASSWORD=your_password
> ```

### 4. Setup Database

```bash
php artisan migrate
php artisan db:seed
```

### 5. Storage Link

```bash
php artisan storage:link
```

### 6. Jalankan Aplikasi

Buka dua terminal terpisah:

```bash
# Terminal 1 — Vite dev server
npm run dev

# Terminal 2 — Laravel server
php artisan serve
```

Aplikasi dapat diakses di: **http://localhost:8000**

## Kredensial Default

Setelah menjalankan seeder, akun Super Admin tersedia:

| Field    | Nilai          |
| -------- | -------------- |
| Email    | admin@cms.test |
| Password | password123    |
| Role     | super-admin    |

> **Penting:** Segera ubah password default ini di environment production.

## Arsitektur Proyek

Proyek ini menggunakan arsitektur **monolith** di mana Landing Page dan CMS berjalan dalam satu aplikasi Laravel. Landing page dirender sebagai halaman Inertia.js dengan layout terpisah (`LandingLayout`), sementara CMS menggunakan layout sendiri (`CmsLayout`).

```
Alur Request:
Browser → Laravel Router → Inertia Controller → Vue Page Component
                                                      ↓
                                            Landing Page (/) 
                                            CMS Admin (/cms/*)
                                            API (/api/*)
```

### API Endpoints (Public)

| Endpoint              | Deskripsi                                  |
| --------------------- | ------------------------------------------ |
| `GET /api/sections`   | Semua section Homepage yang aktif           |
| `GET /api/sections/{slug}` | Detail section berdasarkan slug        |
| `GET /api/navbar`     | Konfigurasi navbar (logo, menu, search)    |
| `GET /api/footer`     | Konfigurasi footer (logo, links, contacts) |

## Struktur Folder

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    # Controller autentikasi
│   │   │   ├── SectionController.php    # CRUD section & section items
│   │   │   ├── NavbarController.php     # Pengaturan navbar
│   │   │   ├── FooterController.php     # Pengaturan footer
│   │   │   ├── AclController.php        # Manajemen role & permission
│   │   │   └── LandingPageController.php # Landing page publik
│   │   └── Middleware/
│   │       ├── CheckRole.php            # Middleware role checking
│   │       └── HandleInertiaRequests.php # Share data ke frontend (termasuk sections)
│   ├── Models/
│   │   ├── Section.php                  # Model section (scopes: active, forPage, ordered)
│   │   ├── SectionItem.php              # Model item per section
│   │   ├── NavbarSetting.php            # Model pengaturan navbar
│   │   ├── FooterSetting.php            # Model pengaturan footer
│   │   └── User.php                     # Model pengguna
│   └── Services/
│       ├── MediaService.php             # Upload dan validasi gambar
│       └── SlugService.php              # Generate unique slug
├── database/
│   ├── migrations/                      # File migration database
│   └── seeders/
│       └── SectionSeeder.php            # Seeder 7 section homepage
├── resources/js/
│   ├── Components/
│   │   ├── Cms/                         # Komponen CMS (Sidebar, DataTable, RichTextEditor)
│   │   └── Landing/                     # Komponen Landing Page
│   │       ├── HeaderCarousel.vue       # Carousel header (dari CMS)
│   │       ├── NavbarSection.vue        # Navbar dinamis (dari API)
│   │       ├── FooterSection.vue        # Footer dinamis (dari API)
│   │       └── ui/                      # shadcn-vue UI components (Button, Input)
│   ├── Composables/                     # Vue composables (useAlert, useDarkMode, useI18n)
│   ├── Layouts/
│   │   ├── CmsLayout.vue               # Layout admin CMS
│   │   └── LandingLayout.vue           # Layout landing page (Navbar + Footer)
│   ├── Pages/
│   │   ├── Auth/                        # Halaman login
│   │   ├── Cms/                         # Halaman CMS (Dashboard, Sections, Users, dll)
│   │   └── Landing/                     # Halaman landing page
│   │       └── Homepage/
│   │           ├── index.vue            # Halaman utama dengan semua section
│   │           ├── WhySection.vue       # "Kenapa topi-care?"
│   │           ├── SkinBarrierSection.vue # Carousel skin barrier
│   │           ├── SkinProblemsSection.vue # Grid masalah kulit + hover effect
│   │           ├── ProductCarouselSection.vue # Carousel produk + flip card
│   │           ├── ArticlesSection.vue  # Grid artikel + hover color change
│   │           └── MarketplaceSection.vue # Grid logo marketplace
│   └── Utils/                           # Utility functions
├── public/assets/                       # Asset statis landing page
│   ├── images/                          # Background, ilustrasi skin barrier
│   ├── icons/                           # Ikon fitur dan masalah kulit
│   ├── products/                        # Gambar produk topi-care
│   ├── article/                         # Gambar artikel
│   └── marketplace/                     # Logo marketplace
├── routes/
│   ├── web.php                          # Route CMS + Landing Page (Inertia)
│   └── api.php                          # Route API publik (sections, navbar, footer)
└── .env.example                         # Template environment variables
```

## Panduan Penggunaan CMS

### Login

1. Buka `http://localhost:8000/login`
2. Masukkan email dan password
3. Setelah berhasil login, Anda akan diarahkan ke Dashboard CMS

### Dashboard

Dashboard menampilkan ringkasan konten: jumlah section, pengguna, dan statistik.

### Homepage — Manajemen Section

Di sidebar CMS, menu **Homepage** menampilkan daftar section yang tersedia secara dinamis dari database:

| Section             | Slug               | Deskripsi                                       |
| ------------------- | ------------------ | ----------------------------------------------- |
| Header Carousel     | `header`           | Slide carousel di bagian paling atas             |
| Kenapa topi-care?   | `why`              | Fitur unggulan dengan ikon                       |
| Skin Barrier        | `skin_barrier`     | Carousel damaged/healthy skin + konten           |
| Skin Problems       | `skin_problems`    | Grid masalah kulit dengan hover card effect       |
| Product Carousel    | `product_carousel` | Carousel produk dengan 3D flip card              |
| Articles            | `articles`         | Grid artikel dengan hover color change            |
| Marketplace         | `marketplace`      | Grid logo marketplace (Shopee, Tokopedia, dll)   |

Setiap section memiliki **Section Items** yang bisa di-CRUD (tambah, edit, hapus) melalui CMS.

### Navbar & Footer

- **Navbar**: Menu **Global > Navbar** — atur logo, menu navigasi, dan tampilan search
- **Footer**: Menu **Global > Footer** — atur logo, company name, halaman, kontak, dan sosial media

### Manajemen Pengguna (Super Admin)

- Navigasi ke menu **Pengguna** di sidebar
- Buat pengguna baru dengan mengisi nama, email, password, dan pilih role
- Edit atau hapus pengguna yang sudah ada
- Role yang tersedia: `super-admin`, `admin`, `user`

### Landing Page

- Akses di `http://localhost:8000/` (tanpa login)
- Menampilkan 7 section: Header, Why, Skin Barrier, Skin Problems, Products, Articles, Marketplace
- Animasi dan interaksi:
  - **Skin Barrier**: Carousel auto-slide (damaged vs healthy skin)
  - **Skin Problems**: Hover card effect dengan floating icon dan blue accent reveal
  - **Products**: 3D flip card hover (front: gambar, back: deskripsi)
  - **Articles**: Hover background color change (putih → biru)
- Konten diambil secara dinamis melalui API (`/api/sections/header`)
- Navbar dan Footer juga dinamis melalui API (`/api/navbar`, `/api/footer`)

## Role & Permission

| Fitur                        | Super Admin | Admin | User |
| ---------------------------- | :---------: | :---: | :--: |
| Manajemen Pengguna (CRUD)    |      ✅     |   ❌  |  ❌  |
| Pengaturan Role & Permission |      ✅     |   ❌  |  ❌  |
| Kelola Section & Items       |      ✅     |   ✅  |  ❌  |
| Kelola Navbar & Footer       |      ✅     |   ✅  |  ❌  |
| Lihat Konten di CMS          |      ✅     |   ✅  |  ✅  |
| Akses Dashboard              |      ✅     |   ✅  |  ✅  |
| Akses Landing Page (publik)  |      ✅     |   ✅  |  ✅  |

## Menambah Section Baru

1. Tambahkan entry baru di `SectionSeeder.php` dengan `page => 'homepage'`
2. Jalankan `php artisan db:seed --class=SectionSeeder`
3. Section otomatis muncul di sidebar CMS (karena dibaca dinamis dari database)
4. Buat komponen Vue baru di `resources/js/Pages/Landing/Homepage/`
5. Import dan gunakan di `Homepage/index.vue`

## Lisensi

MIT
