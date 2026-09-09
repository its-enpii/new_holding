# Changelog

Semua perubahan penting pada proyek **new_holding** dicatat dalam berkas ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

## [Unreleased]

### Added
- **Phase 1 Foundation Bootstrap**:
  - Laravel 13 + Inertia 2 + Vue 3 (Composition API) + Tailwind CSS 4 diselaraskan dengan standar workspace `new_sidbm`.
  - Database schema & migrations:
    - `users`: penambahan `tenant_id` (FK nullable), `role` enum (`superadmin`, `tenant_owner`, `tenant_staff`), `is_active`, `last_login_at`.
    - `tenants`: data client BUMDesma induk (`name`, `slug` unique, `email`, `phone`, `address`, `logo_path`, `is_active`).
    - `applications`: master registry aplikasi vendor (`name`, `slug` unique, `description`, `icon_path`, `base_url`, `has_financial_report`, `is_active`).
    - `activity_logs`: pencatatan aktivitas login/logout & CRUD.
  - Autentikasi session-based via `AuthController` dengan rate-limiting bawaan, share context `auth.user` via Inertia middleware, dan role guard `EnsureUserHasRole`.
  - Modul Superadmin:
    - CRUD & toggle aktif **Tenants** (`/admin/tenants`).
    - CRUD & toggle aktif **Applications** (`/admin/applications`).
    - Validasi FormRequest ketat (slug unik, format URL, tipe data).
  - Dashboard per role: statistik ringkas untuk superadmin, placeholder tenant apps untuk tenant role.
  - Kit Komponen Atomik & Domain MD3 (`resources/js/Components/`): `AppButton`, `AppIconButton`, `AppCard`, `AppInput`, `AppTextarea`, `AppSelect`, `AppCheckbox`, `AppSwitch`, `AppBadge`, `AppModal`, `AppConfirmDialog`, `AppEmptyState`, `AppToast`, `AppTooltip`, `AppIcon`, `SmartDataTable`.
  - Komposabel: `useMoney`, `useConfirm`, `useTheme`, `useToast`, `useCan`, `usePeriodOptions`.
  - Testing suite: 14 test PHPUnit SQLite in-memory (auth flow, role middleware, tenant CRUD + validasi slug unik, application CRUD + toggle, seeder).

### Changed
- **Distinct Visual Identity — "Indigo Ledger" (Pembeda dari new_sidbm)**:
  - **Palet Baru (Indigo + Teal + Amber)**: menggantikan palet navy+green `new_sidbm`. Primary deep indigo (`#4338ca`/`#2f2a7a`), secondary teal (`#0f766e`), tertiary amber (`#92400e`). Dark mode indigo-charcoal (`#131120`/`#191728`).
  - **Tipografi**: standardisasi ke font modern korporat **Plus Jakarta Sans** (`wght@400;500;600`), menggantikan Inter. Heading maksimal `font-semibold` (600), body `font-normal` (400), label/button `font-medium` (500).
  - **Branded Deep-Indigo Sidebar**: sidebar dengan linear-gradient indigo (`from-primary-deep via-primary to-primary-container`) baik pada mode terang maupun gelap, teks item putih/indigo-terang dengan active indicator amber 3px (`bg-tertiary-fixed`).
  - **Split-Screen Auth/Login**: layout login desktop dua kolom dengan panel brand indigo di kiri (tagline holding + mark icon) dan panel form di kanan.
  - **Stat Tiles Ledger**: tile dashboard dengan accent bar kiri 3px (`border-l-[3px]`) selang-seling warna triad (primary/secondary/tertiary).
  - **Dual-mode Dark & Light**: `color-scheme: light` pada root default dan `color-scheme: dark` pada `[data-theme='dark']` dengan token latar `rgb(19, 17, 32)` dan teks `rgb(231, 230, 243)`.
  - **Radius Kontainer**: standardisasi radius tipis 6px–8px pada seluruh card, panel, modal, dan button.
