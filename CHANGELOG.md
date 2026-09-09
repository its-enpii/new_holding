# Changelog

Semua perubahan penting pada proyek **new_holding** dicatat dalam berkas ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

## [Unreleased]

### Added
- **Phase 1 Foundation Bootstrap**:
  - Laravel 13 + Inertia 2 + Vue 3 (Composition API) + Tailwind CSS 4 foundation diselaraskan dengan standar workspace `new_sidbm`.
  - Database schema & migrations:
    - `users`: penambahan `tenant_id` (FK nullable), `role` enum (`superadmin`, `tenant_owner`, `tenant_staff`), `is_active`, `last_login_at`.
    - `tenants`: `name`, `slug` (unique), `email`, `phone`, `address`, `logo_path`, `is_active`.
    - `applications`: `name`, `slug` (unique), `description`, `icon_path`, `base_url`, `has_financial_report`, `is_active`.
    - `activity_logs`: logging aktivitas autentikasi dan CRUD per tenant/user.
  - Autentikasi session-based via `AuthController` dengan rate-limiting bawaan, share context `auth.user` via Inertia middleware, dan role guard `EnsureUserHasRole`.
  - Modul Superadmin:
    - CRUD Tenants (`Admin\TenantController` + `TenantFormRequest` + Inertia Pages `Admin/Tenants/{Index,Create,Edit,Show}`).
    - CRUD Applications (`Admin\ApplicationController` + `ApplicationFormRequest` + Inertia Pages `Admin/Applications/{Index,Create,Edit,Show}`).
    - Toggle status aktif via dedicated patch routes.
  - Dashboard:
    - `DashboardController` render statistik per role (ringkasan tenant aktif, total app, total user untuk superadmin; placeholder tenant apps untuk tenant role).
  - Kit Komponen Atomik & Domain MD3 (`resources/js/Components/`):
    - `AppButton`, `AppIconButton`, `AppCard`, `AppInput`, `AppTextarea`, `AppSelect`, `AppCheckbox`, `AppSwitch`, `AppBadge`, `AppModal`, `AppConfirmDialog`, `AppEmptyState`, `AppToast`, `AppTooltip`, `AppIcon`, `SmartDataTable`.
  - Komposabel: `useMoney`, `useConfirm`, `useTheme`, `useToast`, `useCan`, `usePeriodOptions`.
  - Testing suite: 14 test PHPUnit SQLite in-memory (auth flow, role middleware, tenant CRUD + validasi slug unik, application CRUD + toggle, seeder).

### Changed
- **Visual Design Conformance (Owner Guidelines)**:
  - **Material Design 3 Token System**: palette classic navy + green dengan semantic token (`surface-container-*`, `primary`, `on-surface-variant`, dsb.).
  - **Sudut rounded tipis**: standardisasi radius kontainer ke `rounded-md`/`rounded-lg` (6px–8px di button, 8px di card/modal/tile), pill/full hanya untuk badge, chip, switch, dan avatar.
  - **Warna gradient soft**: linear-gradient halus di sidebar (`from-surface-container-lowest via-surface to-surface-container-low`), topbar dengan backdrop-blur, dashboard stat tiles, tombol primary (`from-primary via-primary-deep to-primary-container`), dan background auth login.
  - **Tipografi terkendali (anti over-bold)**: pembatasan Google Fonts Inter ke `wght@400;500;600`, heading dibatasi maksimal `font-semibold` (600), label/button `font-medium` (500), body `font-normal` (400), eliminasi seluruh `font-extrabold`/`font-black`.
  - **Dual-mode Dark & Light**: `color-scheme: light` pada root default dan `color-scheme: dark` pada `[data-theme='dark']` dengan token latar `rgb(11, 18, 32)` dan teks `rgb(228, 235, 245)`, dilengkapi toggle tema di topbar dan halaman login.
