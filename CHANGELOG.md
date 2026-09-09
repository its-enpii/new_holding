# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam berkas ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Unreleased]

### Added

- Fondasi Laravel 13 dengan PHP 8.3, Inertia Vue 3, Ziggy, Sanctum, DomPDF, Tinker, Tailwind 4, dan Vite 7.
- Token UI Material Design 3 tema classic navy-green untuk mode light dan dark, termasuk font Inter dan Material Symbols.
- Komponen antarmuka umum `App*`, `SmartDataTable`, `SmartSelect`, layout admin/authenticated, serta composables `useCan`, `useConfirm`, `useMoney`, `useTheme`, dan `useToast`.
- Halaman login session-based dengan rate limit, logout, dashboard per role, dan pembagian state `auth.user`.
- Migrasi dan model `tenants`, `users` berperan, `applications`, serta `activity_logs`.
- CRUD tenant dan aplikasi khusus superadmin dengan validasi FormRequest, toggle status, pagination, pencarian, dan logging aktivitas.
- Seeder superadmin lokal `admin@holding.local` dengan password `password` beserta data demo tenant, aplikasi, dan owner tenant.
- Konfigurasi pengujian SQLite in-memory, pengujian auth, role guard, CRUD, dan seeder.
- Pedoman protokol UI dan token pada `AGENT.md` serta dokumentasi perubahan pada `CHANGELOG.md`.

### Changed

- `composer test` dan skrip `package.json` disesuaikan untuk pipeline standar holding.
- Konfigurasi `.env.example` memakai sesi array, antrean sync, dan cache array untuk konsistensi lokal/development.
