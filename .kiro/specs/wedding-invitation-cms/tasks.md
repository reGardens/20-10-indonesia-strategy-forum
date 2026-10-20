# Implementation Plan: Wedding Invitation CMS

## Overview

Implementasi Wedding Invitation CMS sebagai Vue 3 SPA dengan dua aplikasi utama: CMS Panel (/cms/\*) untuk admin dan Aplikasi Undangan (/wedding/:slug) untuk tamu. Data disimpan secara lokal menggunakan JSON files + localStorage dengan arsitektur service layer yang siap migrasi ke backend database. Menggunakan Vuex + TanStack Vue Query untuk state management, Vue Router untuk navigasi, dan Vitest + fast-check untuk testing.

## Tasks

- [x] 1. Setup project structure, routing, and shared infrastructure
  - [x] 1.1 Create folder structure and base configuration files
    - Create directory structure sesuai design: `src/api/data/`, `src/api/services/`, `src/api/schemas/`, `src/cms/`, `src/invitation/`, `src/composables/`, `src/store/`, `src/router/`, `src/i18n/`, `src/utils/`
    - Create all JSON data files with default/empty data: `couple.json`, `events.json`, `guests.json`, `rsvp.json`, `wishes.json`, `media.json`, `payments.json`, `settings.json`
    - Configure Vitest with jsdom environment, fast-check dependency, and test setup file as specified in design
    - Setup vue-i18n with `id.json` locale file (Bahasa Indonesia as default)
    - _Requirements: 18.1, 20.4_

  - [x] 1.2 Configure Vue Router with CMS and Invitation route groups
    - Create `src/router/index.js` with nested routes for CMS (`/cms/*`) and Invitation (`/wedding/:slug`)
    - Implement root `/` redirect to `/cms/dashboard`
    - Define CMS child routes: dashboard, couple, events, template, guests, checkin, media, payment, rsvp, settings
    - Define Invitation route with `:slug` parameter
    - _Requirements: 20.1, 20.2_

  - [x] 1.3 Create Vuex store with all modules
    - Create `src/store/index.js` with module registration
    - Create store modules: `couple.js`, `events.js`, `guests.js`, `rsvp.js`, `wishes.js`, `media.js`, `payments.js`, `settings.js`, `template.js`
    - Each module implements state, mutations, actions (calling service layer), and getters
    - _Requirements: 18.2_

  - [x] 1.4 Create `App.vue` and `main.js` entry point
    - Wire up Vue Router, Vuex, vue-i18n, TanStack Vue Query, and AOS in `main.js`
    - Create `App.vue` with `<router-view>` and global error boundary
    - _Requirements: 20.2, 20.4_

- [x] 2. Implement data layer: schemas, serializer, validators, and service layer
  - [x] 2.1 Create JSON schemas and validators
    - Create `src/api/schemas/` with schema definitions for all entities: `coupleSchema.js`, `eventSchema.js`, `guestSchema.js`, `rsvpSchema.js`, `wishSchema.js`, `mediaSchema.js`, `paymentSchema.js`, `settingsSchema.js`
    - Each schema defines required fields, types, patterns, and constraints as specified in design
    - Create `src/utils/validators.js` with validation functions: `validateCoordinate(value, type)`, `validateMusicFile(file)`, `validatePastDate(date)`, `validateSchema(entity, schema)`
    - _Requirements: 3.4, 3.5, 4.5, 18.4_

  - [x] 2.2 Create serializer and utility modules
    - Create `src/utils/serializer.js` with `serialize()`, `deserialize()`, and `roundTrip()` functions
    - Create `src/utils/formatters.js` with date/time/currency formatting helpers (Bahasa Indonesia locale)
    - Create `src/utils/urlEncoder.js` with URL encoding/decoding for guest names with Unicode support
    - _Requirements: 2.5, 7.5, 18.5_

  - [x] 2.3 Implement all CRUD services in `api/services/`
    - Create `coupleService.js` — get/save pattern using localStorage with JSON file fallback
    - Create `eventService.js` — full CRUD (getAll, getById, create, update, delete)
    - Create `guestService.js` — full CRUD + importFromExcel + exportToExcel + search/filter
    - Create `rsvpService.js` — full CRUD + getByGuestId + getSummary (counts by status)
    - Create `wishService.js` — full CRUD + getByStatus filter + updateStatus
    - Create `mediaService.js` — full CRUD + reorder (sortOrder) + bulkDelete
    - Create `paymentService.js` — bank account CRUD + gift log CRUD + getTotal
    - Create `settingsService.js` — get/save pattern for global settings
    - All services use localStorage with JSON file as initial data, implement `ServiceError` class for error handling
    - _Requirements: 6.2, 18.1, 18.2, 18.3, 18.6_

  - [ ]\* 2.4 Write property tests for serialization round-trip
    - **Property 1: Serialization Round-Trip** — For any valid entity, serialize then deserialize produces equivalent object
    - **Validates: Requirements 2.5, 3.3, 18.5**
    - Create `tests/properties/serialization.property.test.js` with arbitrary generators for all entity types

  - [ ]\* 2.5 Write property tests for CRUD consistency and save idempotence
    - **Property 9: Save Idempotence** — Saving same data twice produces identical state to saving once
    - **Property 10: Service Layer CRUD Consistency** — Create→Read returns same data; Update→Read reflects update; Delete→Read returns null
    - **Property 27: RSVP Persistence Round-Trip** — Save RSVP then read back produces equivalent data
    - **Validates: Requirements 6.2, 18.2, 18.6, 14.2**
    - Create `tests/properties/crud.property.test.js`

  - [ ]\* 2.6 Write property tests for validation functions
    - **Property 15: Coordinate Validation** — Latitude accepts [-90,90], longitude accepts [-180,180]
    - **Property 16: Past Date Detection** — Dates before today flagged as past
    - **Property 17: File Type Validation** — Only MP3 files accepted
    - **Property 25: Schema Validation Correctness** — Valid entities accepted, invalid rejected
    - **Validates: Requirements 3.4, 3.5, 4.5, 18.4**
    - Create `tests/properties/validation.property.test.js`

  - [ ]\* 2.7 Write unit tests for services and validators
    - Create `tests/unit/services/` with test files for each service (specific examples, edge cases, error conditions)
    - Create `tests/unit/utils/validators.test.js`, `serializer.test.js`, `formatters.test.js`
    - Create `tests/unit/schemas/schemaValidation.test.js`
    - _Requirements: 2.4, 3.4, 3.5, 4.5, 6.5, 10.5, 14.6, 18.3_

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement shared composables
  - [x] 4.1 Create `useImageOptimizer` composable
    - Implement `optimize(file, options)` and `optimizeMultiple(files)` using browser-image-compression
    - Output WebP format, max 500KB, max 1920px width/height
    - Return `OptimizedImage` with file, originalSize, compressedSize, and object URL
    - _Requirements: 2.2, 9.2_

  - [x] 4.2 Create `useWhatsAppLink` composable
    - Implement `generateInvitationUrl(guestName)` with format `{domain}/wedding/{slug}?to={encoded-name}`
    - Implement `generateWhatsAppUrl(phoneNumber, guestName)` with format `https://wa.me/{phone}?text={encoded-message}`
    - Implement `generateBulkLinks(guests)` for mass link generation
    - _Requirements: 7.1, 7.3, 7.4, 7.5_

  - [x] 4.3 Create `useQrCode` composable
    - Implement `encode(data)` to generate QR code string from guest identification (guestId, weddingSlug, checksum)
    - Implement `decode(qrString)` to parse QR code string back to guest data
    - Ensure unique QR codes for distinct guests
    - _Requirements: 8.1, 17.4_

  - [x] 4.4 Create `useCalendarExport` composable
    - Implement `.ics` file generation with event name, date, start/end time, venue, and address in valid iCalendar format
    - Implement Google Calendar link generation as alternative
    - _Requirements: 13.4_

  - [x] 4.5 Create `useClipboard` composable
    - Implement copy-to-clipboard functionality for bank account numbers and invitation URLs
    - _Requirements: 16.2_

  - [ ]\* 4.6 Write property tests for composables
    - **Property 3: URL Encoding Round-Trip** — Encoding then decoding guest names preserves original (including Unicode)
    - **Property 4: Invitation URL Format Correctness** — Generated URLs match expected format
    - **Property 5: WhatsApp URL Format Correctness** — Generated WhatsApp URLs match expected format
    - **Property 7: QR Code Encode/Decode Round-Trip and Uniqueness** — Encode then decode preserves data; distinct guests get different QR codes
    - **Property 19: Calendar Export Content** — Generated .ics contains all event fields in valid format
    - **Property 23: Image Compression Output** — Output is WebP with size ≤ 500KB
    - **Validates: Requirements 7.1, 7.3, 7.4, 7.5, 8.1, 9.2, 13.4, 17.4**
    - Create `tests/properties/urlEncoding.property.test.js`, `qrCode.property.test.js`, `calendarExport.property.test.js`, `imageOptimizer.property.test.js`

  - [ ]\* 4.7 Write unit tests for composables
    - Create `tests/unit/composables/` with test files for each composable
    - Test specific examples, edge cases (empty names, special characters, missing phone numbers)
    - _Requirements: 7.5, 8.1, 13.4, 16.2_

- [x] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Implement CMS layout and shared components
  - [x] 6.1 Create CMS layout with sidebar navigation
    - Create `src/cms/layouts/CmsLayout.vue` with sidebar + main content area
    - Create `src/cms/components/shared/SidebarNav.vue` with menu items: Dashboard, Data Pernikahan, Template & Preview, Daftar Tamu, Media, Pembayaran & Hadiah, RSVP & Ucapan, Pengaturan
    - Highlight active menu item based on current route
    - _Requirements: 20.1, 20.2_

  - [x] 6.2 Create shared CMS UI components
    - Create `LoadingIndicator.vue` — spinner/skeleton for async operations
    - Create `ErrorMessage.vue` — descriptive error display with retry button (Bahasa Indonesia)
    - Create `ConfirmDialog.vue` — confirmation modal for destructive actions
    - Create `ColorPicker.vue` — theme color selection component
    - Create `AudioPlayer.vue` — music preview player
    - Create `FileUploader.vue` — drag-and-drop file upload with validation
    - _Requirements: 20.3, 20.5_

- [x] 7. Implement CMS Dashboard view
  - [x] 7.1 Create Dashboard view and sub-components
    - Create `src/cms/views/DashboardView.vue` as main dashboard page
    - Create `StatCard.vue` — reusable stat display card
    - Create `VisitorChart.vue` — visitor count chart with daily/weekly/monthly toggle
    - Create `RsvpSummary.vue` — RSVP counts (Hadir/Tidak Hadir/Mungkin) with diagram
    - Create `ActivityLog.vue` — recent wishes/activities in reverse chronological order
    - Wire to Vuex store for reactive data updates without page refresh
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [ ]\* 7.2 Write property tests for dashboard aggregation logic
    - **Property 11: Visitor Count Aggregation** — Sum of daily counts equals total visits
    - **Property 12: RSVP Status Counting** — Status counts sum to total RSVP entries
    - **Property 26: Activity Log Ordering** — Activities displayed newest first
    - **Validates: Requirements 1.1, 1.2, 1.3**
    - Create `tests/properties/aggregation.property.test.js`

- [x] 8. Implement CMS Couple and Events views
  - [x] 8.1 Create Couple view with form and photo upload
    - Create `src/cms/views/CoupleView.vue` with groom and bride data sections
    - Create `CoupleForm.vue` — form inputs for fullName, nickname, photo, fatherName, motherName, instagramUrl, childOrder (for both groom and bride)
    - Create `PhotoUploader.vue` — image upload with automatic WebP compression via `useImageOptimizer`
    - Implement required field validation (nama lengkap wajib diisi)
    - Wire save action to coupleService via Vuex store
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 8.2 Create Events view with form and map preview
    - Create `src/cms/views/EventsView.vue` with support for multiple events
    - Create `EventForm.vue` — form inputs for name, date, startTime, endTime, venueName, address, latitude, longitude
    - Create `MapPreview.vue` — Google Maps embed preview based on coordinates
    - Implement coordinate validation (lat: -90 to 90, lng: -180 to 180) and past date warning
    - Wire save action to eventService via Vuex store
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 9. Implement CMS Template and Music/Theme views
  - [x] 9.1 Create Template view with gallery and live preview
    - Create `src/cms/views/TemplateView.vue` as template selection and customization page
    - Create `TemplateGallery.vue` — grid of available templates with thumbnails
    - Create `TemplateCard.vue` — individual template card with name, description, preview
    - Create `ThemeCustomizer.vue` — color pickers for primary/secondary/accent colors, font family selector
    - Create `LivePreview.vue` — iframe or embedded preview of invitation with current template and theme
    - Implement music upload (MP3 only, max 10MB) with `AudioPlayer.vue` preview
    - Wire template selection and theme changes to settingsService via Vuex store
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3_

  - [ ]\* 9.2 Write property test for template switch idempotence
    - **Property 6: Template Switch Preserves Data** — Switching A→B→A produces identical rendering; underlying data unchanged
    - **Validates: Requirements 5.2, 5.5**
    - Create `tests/properties/templateSwitch.property.test.js`

- [x] 10. Implement CMS Guest Management views
  - [x] 10.1 Create Guests view with table and CRUD operations
    - Create `src/cms/views/GuestsView.vue` as guest management page
    - Create `GuestTable.vue` — data table with columns: nama, telepon, status RSVP, jumlah tamu, status check-in
    - Create `GuestForm.vue` — add/edit guest form with validation
    - Create `ImportExport.vue` — Excel import (.xlsx) with column validation and export functionality
    - Wire to guestService via Vuex store
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 10.2 Create WhatsApp link generator and QR code components
    - Create `WhatsAppLinkGenerator.vue` — per-guest link generation with copy and open-in-WhatsApp buttons, bulk generation support
    - Create `QrCodeGenerator.vue` — per-guest QR code generation using qrcode.vue, with download option
    - Wire to `useWhatsAppLink` and `useQrCode` composables
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 8.1_

  - [x] 10.3 Create Check-In view with QR scanner
    - Create `src/cms/views/CheckInView.vue` with camera-based QR scanner using vue-qrcode-reader
    - Implement check-in logic: decode QR → lookup guest → update status with timestamp
    - Handle edge cases: unknown QR code ("Tamu tidak ditemukan"), already checked-in (show previous timestamp, no data change)
    - Display check-in result with guest name and status
    - _Requirements: 8.2, 8.3, 8.4, 8.5_

  - [ ]\* 10.4 Write property tests for guest management
    - **Property 2: Guest Excel Export/Import Round-Trip** — Export then import produces equivalent guest list
    - **Property 8: Check-In Idempotence** — Re-scanning checked-in guest doesn't modify existing record
    - **Validates: Requirements 6.6, 8.5**
    - Create `tests/properties/excelRoundTrip.property.test.js`, `tests/properties/checkIn.property.test.js`

- [x] 11. Implement CMS Media, Payment, and RSVP views
  - [x] 11.1 Create Media view with upload and gallery management
    - Create `src/cms/views/MediaView.vue` as media library page
    - Create `MediaUploader.vue` — drag-and-drop multi-file upload with automatic WebP compression
    - Create `MediaGrid.vue` — thumbnail grid with file info (name, original size, compressed size), drag-to-reorder, individual and bulk delete
    - Create `GalleryLayoutPicker.vue` — selector for masonry/slider/grid layout with live preview
    - Wire to mediaService via Vuex store
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

  - [x] 11.2 Create Payment view with bank accounts and gift log
    - Create `src/cms/views/PaymentView.vue` as payment management page
    - Create `BankAccountForm.vue` — add/edit bank account (bankName, accountNumber, accountHolder) with validation
    - Create `QrisUploader.vue` — QRIS image upload
    - Create `GiftLog.vue` — table of received gifts with sender, amount, method, message, timestamp, and total accumulation display
    - Wire to paymentService via Vuex store
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [x] 11.3 Create RSVP and Wishes moderation view
    - Create `src/cms/views/RsvpView.vue` as RSVP and wishes management page
    - Create `WishList.vue` — list of all wishes with hide/delete actions
    - Create `WishModerator.vue` — filter wishes by status (all/approved/hidden/pending)
    - Create `RsvpExporter.vue` — export RSVP data to Excel (.xlsx) or PDF with guest list, status, pax count, and wishes
    - Wire to wishService and rsvpService via Vuex store
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [ ]\* 11.4 Write property tests for filtering and aggregation
    - **Property 13: Wish Visibility Filtering** — Guest-facing list shows only approved wishes in chronological order
    - **Property 14: Wish Status Filter** — Filter returns only matching status wishes
    - **Property 21: Wish Default Status Based on Moderation** — New wish gets "pending" if moderation on, "approved" if off
    - **Property 22: Gift Total Accumulation** — Displayed total equals sum of all gift amounts
    - **Validates: Requirements 11.2, 11.4, 14.3, 14.4, 10.4**
    - Create `tests/properties/filtering.property.test.js`

- [x] 12. Implement CMS Settings view
  - [x] 12.1 Create Settings view with all configuration options
    - Create `src/cms/views/SettingsView.vue` with sections for:
      - Custom domain input (optional)
      - Whitelabel toggle (show/hide "Powered by" watermark)
      - Password protection toggle with password input
      - Moderation toggle (enable/disable wish moderation)
      - Health protocol text area
      - Live streaming URL input
      - Shipping address for physical gifts
      - SEO meta fields (title, description, image)
    - Wire to settingsService via Vuex store
    - _Requirements: 12.1, 12.2, 12.3, 17.1, 19.4_

- [x] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Implement Invitation app layout and template engine
  - [x] 14.1 Create Invitation layout and main view
    - Create `src/invitation/layouts/InvitationLayout.vue` — full-page wrapper with AOS initialization and scroll-triggered animations
    - Create `src/invitation/views/InvitationView.vue` — loads wedding data by slug, resolves template, renders sections
    - Implement dynamic template loading via `<component :is>` pattern from template registry
    - _Requirements: 5.4, 19.1, 19.3_

  - [x] 14.2 Create template registry and 3 Nusantara templates
    - Create `src/invitation/templates/index.js` — template registry with lazy-loaded component definitions
    - Create `batik-elegance/BatikEleganceTemplate.vue` + `styles.css` — Batik Jawa themed template
    - Create `wayang-romance/WayangRomanceTemplate.vue` + `styles.css` — Wayang themed template
    - Create `songket-royal/SongketRoyalTemplate.vue` + `styles.css` — Songket themed template
    - Each template renders all invitation sections with unique styling and Nusantara cultural elements
    - _Requirements: 5.1, 5.4_

  - [x] 14.3 Create invitation composables
    - Create `src/invitation/composables/useCountdown.js` — reactive countdown (days, hours, minutes, seconds, isExpired) with 1-second interval
    - Create `src/invitation/composables/useMusicPlayer.js` — autoplay after first interaction, toggle play/pause, persistent floating button
    - Create `src/invitation/composables/useRsvpForm.js` — form state management, validation, submission to rsvpService and wishService
    - Create `src/invitation/composables/useGuestName.js` — extract and decode guest name from `to` URL parameter
    - _Requirements: 13.1, 13.2, 14.1, 15.2_

  - [ ]\* 14.4 Write property tests for invitation composables
    - **Property 18: Countdown Calculation** — Countdown values added to current time equal target date (within 1s tolerance)
    - **Property 20: Guest Name Display from URL Parameter** — Name from `to` parameter displays correctly after URL decoding
    - **Validates: Requirements 13.1, 13.2**
    - Create `tests/properties/countdown.property.test.js`, `tests/properties/guestName.property.test.js`

- [x] 15. Implement Invitation sections (guest-facing components)
  - [x] 15.1 Create Cover and Couple sections
    - Create `src/invitation/sections/CoverSection.vue` — opening page with personalized guest name ("Kepada Yth: {nama}"), fallback to generic text if no `to` param
    - Create `src/invitation/sections/CoupleSection.vue` — groom and bride profiles with photos, names, parents, Instagram links
    - Create `src/invitation/sections/PasswordGate.vue` — password input page shown before content when password protection is enabled
    - _Requirements: 13.1, 13.5, 17.1, 17.2, 17.3_

  - [x] 15.2 Create Event and Countdown sections
    - Create `src/invitation/sections/EventSection.vue` — event details with name, date, time, venue, address, and embedded Google Maps
    - Create `src/invitation/sections/CountdownSection.vue` — real-time countdown timer using `useCountdown` composable
    - Implement "Add to Calendar" button using `useCalendarExport` composable (.ics download + Google Calendar link)
    - _Requirements: 13.2, 13.3, 13.4_

  - [x] 15.3 Create Gallery and Music sections
    - Create `src/invitation/sections/GallerySection.vue` — photo/video gallery with masonry/slider/grid layout based on settings
    - Create `src/invitation/sections/MusicPlayer.vue` — floating music toggle button, autoplay after first user interaction
    - Support YouTube embed and direct video in gallery
    - Apply AOS scroll-triggered animations to all sections
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

  - [x] 15.4 Create RSVP, Wishes, and Gift sections
    - Create `src/invitation/sections/RsvpSection.vue` — RSVP form (name, status Hadir/Tidak Hadir/Mungkin, jumlah tamu) with validation using `useRsvpForm`
    - Create `src/invitation/sections/WishesSection.vue` — display approved wishes chronologically, wish submission form
    - Create `src/invitation/sections/GiftSection.vue` — bank account info with copy-to-clipboard, QRIS image display, gift confirmation form, shipping address (if enabled), live streaming link (if enabled)
    - Implement health protocol display section (if enabled)
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 15.1, 16.1, 16.2, 16.3, 16.4, 17.5_

  - [ ]\* 15.5 Write property test for SEO meta tag generation
    - **Property 24: SEO Meta Tag Generation** — Generated HTML contains og:title, og:description, og:image matching wedding data
    - **Validates: Requirements 19.4**
    - Create `tests/properties/seoMeta.property.test.js`

- [x] 16. Implement SEO, error handling, and performance optimization
  - [x] 16.1 Implement SEO meta tags and error handling
    - Add Open Graph meta tags (og:title, og:description, og:image) to InvitationView based on wedding data
    - Implement Vue error boundary (`onErrorCaptured`) in CMS views wrapping with `ErrorMessage.vue`
    - Implement graceful error handling in Invitation app: placeholder for failed images, hide music player on load failure, silent error handling for guests
    - _Requirements: 19.2, 19.4, 20.5_

  - [x] 16.2 Wire whitelabel watermark toggle and final integration
    - Implement "Powered by" watermark in InvitationLayout, controlled by settings toggle
    - Ensure all CMS views are connected to their respective Vuex store modules and services
    - Ensure all invitation sections receive data from service layer via InvitationView
    - Verify SPA navigation works without full page reloads across all CMS routes
    - _Requirements: 12.2, 12.3, 20.2_

- [x] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation after major implementation phases
- Property tests validate the 27 universal correctness properties defined in the design document
- Unit tests validate specific examples, edge cases, and error conditions
- All data services use localStorage with JSON file fallback, designed for easy migration to REST API/database
- Bahasa Indonesia is the default UI language throughout the application
