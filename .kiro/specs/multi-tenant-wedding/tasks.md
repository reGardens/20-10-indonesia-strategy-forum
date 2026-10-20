# Implementation Plan: Multi-Tenant Wedding Support

## Overview

This plan converts the Wedding Invitation CMS from single-tenant to multi-tenant by introducing slug-namespaced localStorage keys, a wedding registry, and slug-aware routing. Implementation proceeds bottom-up: utilities → services → store → router → UI components → migration, ensuring each layer builds on tested foundations. All existing 530 tests will be updated to pass slug parameters where needed.

## Tasks

- [x] 1. Create slug validation utility
  - [x] 1.1 Create `src/utils/slugValidator.js` with `validateSlug` and `normalizeSlug` functions
    - `validateSlug(slug)` returns `{ valid: boolean, error: string | null }`
    - Accept only lowercase a-z, digits 0-9, hyphens; length 3-64; no leading/trailing/consecutive hyphens
    - `normalizeSlug(input)` converts to lowercase and trims whitespace
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ]\* 1.2 Write property test for slug validation
    - Create `tests/utils/slugValidator.property.test.js`
    - **Property 1: Slug Validation Correctness**
    - For any string input, validateSlug accepts iff it matches the format rules; normalizeSlug always produces lowercase output
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**

  - [ ]\* 1.3 Write unit tests for slug validation
    - Create `tests/unit/utils/slugValidator.test.js`
    - Test specific valid slugs: `"budi-ani"`, `"abc"`, `"a1b2c3"`
    - Test specific invalid slugs: `"-start"`, `"end-"`, `"a--b"`, `"AB"`, `"ab"` (too short), empty string
    - Test normalizeSlug with uppercase input, whitespace trimming
    - Test error messages in Bahasa Indonesia
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 2. Create wedding registry service
  - [x] 2.1 Create `src/api/services/weddingRegistryService.js`
    - Implement `getAll()`, `getBySlug(slug)`, `create({ slug, label })`, `delete(slug)`, `exists(slug)`
    - Store registry as JSON array in `wedding_registry` localStorage key
    - Each entry: `{ id, slug, label, createdAt }` — use `uuid` package for id generation
    - Validate slug via `slugValidator` on create; enforce uniqueness
    - On delete: remove registry entry AND all `wedding_{slug}_*` localStorage keys
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [ ]\* 2.2 Write property tests for wedding registry
    - Create `tests/api/services/weddingRegistry.property.test.js`
    - **Property 2: Registry Serialization Round-Trip** — serialize/deserialize produces equal data
    - **Property 3: Registry Create Produces Valid Unique Entries** — created entries have id, slug, label, createdAt; no duplicate slugs
    - **Property 6: Slug Existence Validation** — exists() returns true iff slug is in registry
    - **Property 7: Wedding Deletion Removes All Namespaced Data** — delete removes entry and all `wedding_{slug}_*` keys
    - **Validates: Requirements 1.2, 1.3, 1.4, 1.5, 1.6, 4.3, 4.4, 6.1, 6.2**

  - [ ]\* 2.3 Write unit tests for wedding registry service
    - Create `tests/unit/services/weddingRegistryService.test.js`
    - Test CRUD operations: create, getAll, getBySlug, delete
    - Test duplicate slug rejection with error message "Slug sudah digunakan oleh pernikahan lain"
    - Test invalid slug rejection
    - Test delete cleans up all namespaced keys
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 3. Refactor service layer to accept slug parameter
  - [x] 3.1 Refactor `coupleService.js` and `settingsService.js` to accept slug
    - Add `getStorageKey(slug)` helper returning `wedding_{slug}_{entity}`
    - Throw `ServiceError('Slug pernikahan wajib disediakan', 'SLUG_REQUIRED')` if slug is missing
    - Change `get()` → `get(slug)`, `save(data)` → `save(slug, data)`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 3.2 Refactor `eventService.js`, `guestService.js`, `rsvpService.js`, `wishService.js`, `mediaService.js`, `paymentService.js` to accept slug
    - Same pattern: add slug as first parameter to all CRUD methods (getAll, getById, create, update, delete, and service-specific methods)
    - Each service uses `wedding_{slug}_{entity}` storage key
    - Throw `ServiceError` if slug is missing
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [ ]\* 3.3 Write property tests for data isolation
    - Create `tests/api/services/multiTenantIsolation.property.test.js`
    - **Property 4: Data Isolation Across Slugs** — writing to slug A does not affect slug B for all entity types
    - **Property 5: Missing Slug Rejection** — calling any service method without slug throws error, no data written
    - **Validates: Requirements 3.2, 3.3, 3.5, 3.6, 6.3**

  - [x] 3.4 Update all existing service tests to pass slug parameter
    - Update `tests/unit/services/coupleService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/eventService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/guestService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/rsvpService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/wishService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/mediaService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/paymentService.test.js` — pass slug to all method calls
    - Update `tests/unit/services/settingsService.test.js` — pass slug to all method calls
    - _Requirements: 3.4_

- [x] 4. Checkpoint — Ensure all service layer tests pass
  - Run `npm test` and ensure all existing tests (updated with slug) plus new tests pass. Ask the user if questions arise.

- [x] 5. Create Vuex wedding module and refactor data modules
  - [x] 5.1 Create `src/store/modules/wedding.js` Vuex module
    - State: `activeSlug`, `registry`, `loading`, `error`
    - Mutations: `SET_ACTIVE_SLUG`, `SET_REGISTRY`, `SET_LOADING`, `SET_ERROR`
    - Actions: `fetchRegistry`, `setActiveWedding(slug)` (validate + set + reset all modules), `createWedding({ slug, label })`, `deleteWedding(slug)`
    - `resetAllModules` action dispatches RESET mutation on all 8 data modules
    - Getters: `activeSlug`, `registry`, `activeWedding`
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [x] 5.2 Register wedding module in `src/store/index.js`
    - Import and add `wedding` module to the store
    - _Requirements: 8.1_

  - [x] 5.3 Refactor all 8 data store modules to use slug from rootState
    - Add `RESET` mutation to each module (couple, events, guests, rsvp, wishes, media, payments, settings) that resets state to initial values
    - In every action, read `rootState.wedding.activeSlug` and pass to service calls
    - If `activeSlug` is null, commit error "Tidak ada pernikahan aktif" and return early
    - Modules to update: `couple.js`, `events.js`, `guests.js`, `rsvp.js`, `wishes.js`, `media.js`, `payments.js`, `settings.js`
    - _Requirements: 8.2, 8.3, 8.4_

  - [ ]\* 5.4 Write property test for store state reset on slug change
    - Create `tests/store/wedding.property.test.js`
    - **Property 9: Store State Reset on Slug Change** — switching from slug A to slug B resets all data modules and loads slug B's data
    - **Validates: Requirements 8.2**

  - [ ]\* 5.5 Write unit tests for wedding Vuex module
    - Create `tests/unit/store/wedding.test.js`
    - Test `setActiveWedding` sets slug and resets modules
    - Test `createWedding` adds to registry and navigates
    - Test `deleteWedding` removes from registry and cleans data
    - Test null slug rejection in data module actions
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [x] 6. Refactor router with slug context and navigation guard
  - [x] 6.1 Restructure CMS routes in `src/router/index.js`
    - Add `/cms` route pointing to `WeddingSelectorView` (name: `cms-selector`)
    - Change CMS layout route from `/cms` to `/cms/:slug` with all child routes unchanged
    - Update root redirect from `/cms/dashboard` to `/cms`
    - Keep `/wedding/:slug` invitation route unchanged
    - _Requirements: 4.1, 4.2_

  - [x] 6.2 Add `beforeEach` navigation guard for CMS slug validation
    - For routes matching `/cms/:slug/*`, validate slug exists in registry via `weddingRegistryService.exists()`
    - If slug not found, redirect to `cms-selector` with `query: { error: 'not-found' }`
    - If slug changed from current `activeSlug`, dispatch `wedding/setActiveWedding`
    - _Requirements: 4.3, 4.4, 4.5_

  - [ ]\* 6.3 Write unit tests for router slug guard
    - Create `tests/unit/router/slugGuard.test.js`
    - Test valid slug allows navigation
    - Test invalid slug redirects to selector with error query
    - Test slug change triggers store dispatch
    - _Requirements: 4.3, 4.4, 4.5_

- [x] 7. Checkpoint — Ensure all store and router tests pass
  - Run `npm test` and ensure all tests pass. Ask the user if questions arise.

- [x] 8. Create WeddingSelectorView and update CMS components
  - [x] 8.1 Create `src/cms/views/WeddingSelectorView.vue`
    - Display list of registered weddings as cards (label, slug, createdAt)
    - Form to create new wedding: label input + slug input with real-time validation via `slugValidator`
    - Select wedding navigates to `/cms/{slug}/dashboard`
    - Delete wedding with `ConfirmDialog` — explain permanent data deletion
    - Show error message if redirected with `?error=not-found` query param ("Pernikahan tidak ditemukan")
    - Detect legacy data via `hasLegacyData()` and show migration prompt
    - All UI text in Bahasa Indonesia
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 7.1_

  - [x] 8.2 Update `SidebarNav.vue` to show active wedding context
    - Display active wedding name and slug in sidebar header section
    - Add "Ganti Pernikahan" link/button navigating to `/cms` (WeddingSelectorView)
    - Update all `router-link` `:to` bindings to include `params: { slug: activeSlug }`
    - Read `activeWedding` and `activeSlug` from Vuex getters
    - _Requirements: 5.6, 5.7_

  - [x] 8.3 Update `CmsLayout.vue` to inject slug context
    - Ensure slug from route params is available to child components
    - Mobile header can optionally show active wedding name
    - _Requirements: 4.5_

  - [ ]\* 8.4 Write unit tests for WeddingSelectorView
    - Create `tests/unit/cms/WeddingSelectorView.test.js`
    - Test rendering wedding list
    - Test create wedding form submission and validation
    - Test select wedding navigation
    - Test delete wedding with confirmation
    - Test error message display on redirect
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ]\* 8.5 Update existing SidebarNav tests
    - Update `tests/unit/cms/SidebarNav.test.js` to account for active wedding display and slug params in links
    - _Requirements: 5.6, 5.7_

- [x] 9. Update InvitationView with slug validation
  - [x] 9.1 Modify `InvitationView.vue` to validate slug against registry
    - Import `weddingRegistryService` and check `exists(slug)` before loading data
    - If slug not registered, show "Undangan tidak ditemukan" error without loading any data
    - Pass slug to all service calls: `coupleService.get(slug)`, `eventService.getAll(slug)`, `settingsService.get(slug)`, `mediaService.getAll(slug)`
    - If slug valid but data incomplete, render available sections gracefully
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]\* 9.2 Update InvitationView tests
    - Update `tests/unit/invitation/InvitationView.test.js` to test slug validation against registry
    - Test invalid slug shows error page
    - Test valid slug loads namespaced data
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 10. Create legacy data migration utility
  - [x] 10.1 Create `src/utils/legacyMigration.js`
    - `hasLegacyData()` — detect if any legacy keys (`wedding_couple`, `wedding_events`, etc.) exist without slug prefix
    - `migrateLegacyData(slug)` — validate slug, copy each entity from old key to `wedding_{slug}_{entity}`, add entry to registry, remove old keys on success
    - Implement rollback: if any copy fails, remove all new keys and keep old keys intact
    - Return `{ success, migratedEntities, error? }`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ]\* 10.2 Write property test for legacy migration
    - Create `tests/utils/legacyMigration.property.test.js`
    - **Property 8: Legacy Migration Data Preservation** — migrated data at new keys equals original legacy data; old keys removed after success
    - **Validates: Requirements 7.2, 7.3, 7.5**

  - [ ]\* 10.3 Write unit tests for legacy migration
    - Create `tests/unit/utils/legacyMigration.test.js`
    - Test `hasLegacyData()` detection
    - Test successful migration copies all entities and removes old keys
    - Test rollback on failure preserves old keys
    - Test migration with invalid slug is rejected
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [x] 11. Wire migration into WeddingSelectorView
  - [x] 11.1 Integrate legacy migration flow in `WeddingSelectorView.vue`
    - On mount, call `hasLegacyData()` and show migration banner if true
    - Migration banner: prompt admin to enter a slug for the legacy data
    - On migrate: call `migrateLegacyData(slug)`, refresh registry, navigate to new wedding
    - Show success/error feedback
    - _Requirements: 7.1, 7.2_

- [x] 12. Final checkpoint — Ensure all tests pass
  - Run `npm test` and ensure all 530+ existing tests (updated) and all new tests pass. Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The design uses JavaScript — all code examples and implementations use JavaScript (ES modules)
- All UI text must be in Bahasa Indonesia to match the existing application
- The `uuid` package is already available in project dependencies
