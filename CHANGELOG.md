# Changelog

Semua perubahan penting pada proyek **new_holding** dicatat dalam berkas ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

## [Unreleased]

### Added
- **PWA (Progressive Web App) & Offline Handling (`new_holding`)**:
  - `public/manifest.webmanifest` & ikon standar (`icon-192.png`, `icon-512.png`) dengan tema Indigo Ledger.
  - `public/sw.js`: Service worker yang meng-cache shell dasar dan fallback navigasi ke `offline.html` saat jaringan terputus.
  - `public/offline.html`: Halaman offline mandiri bertema Indigo Ledger dengan tombol coba lagi.
  - `AppOfflineBanner.vue`: Warning banner mengambang saat koneksi offline terpasang di `AdminLayout.vue` dengan tombol cek koneksi ke endpoint `/up`.
  - Registrasi Service Worker di `resources/js/app.js` pada mode produksi dan meta tags PWA di `resources/views/app.blade.php`.
- **Branded Error Pages Bebas Default Laravel**:
  - Halaman error Inertia: `401.vue` dan `503.vue` baru, serta pembaruan visual kaya pada `ErrorPage.vue` dengan token tema Indigo Ledger.
  - Server-side Blade error views di `resources/views/errors/` (`layout.blade.php`, `401`, `403`, `404`, `419`, `429`, `500`, `503.blade.php`) untuk fallback non-Inertia dan kegagalan fatal rendering.
  - Exception handler di `bootstrap/app.php` menangani seluruh kode status 401, 403, 404, 419, 429, 500, 503 dengan fallback bertingkat Inertia -> Blade.
  - 6 test feature baru (`tests/Feature/PwaAndErrorPagesTest.php`) mencakup manifest, service worker, offline shell, error Inertia, Blade fallback, dan kegagalan render.

### Changed
- **Refactor Raw HTML ke Komponen Desain Sistem (`new_holding`)**:
  - Ganti raw `<button>` Quick Access di `Tenant/Dashboard.vue` dan pembungkus kartu manual dengan `<AppButton>` dan `<AppCard>`.
  - Ganti raw `<button>` Detail metadata di `Admin/ActivityLogs/Index.vue` dengan `<AppButton variant="outline" size="compact">`.
  - Ganti raw `<input type="file">` di `Components/CsvImportExport.vue` dengan `<AppFileUpload>`.
  - Sinkronisasi selektor kartu pada suite Playwright E2E (`e2e/deep-functional.spec.js`) agar mengenali pembungkus komponen `AppCard`.

### Added
- **Fitur Download Bundle Laporan Level Holding (`new_holding`)**:
  - `App\Services\ReportBundleService`: merakit ZIP berisi 5 laporan holding (Neraca, Laba Rugi, Arus Kas, Perubahan Ekuitas, CALK) + `README_Manifest.txt`, dengan penomoran `01_Neraca_{mode}_{periode}.pdf` dst dan pembersihan berkas sementara setelah respons dikirim.
  - Dua mode penyajian: `gabungan`/`comparative` (kolom berdampingan per unit usaha + kolom **Total Gabungan**, tanpa eliminasi) dan `konsolidasi`/`consolidated` (satu kolom entitas ekonomi tunggal).
  - Eliminasi konsolidasi atas saldo & transaksi resiprokal antar unit: piutang-utang internal, penyertaan versus modal anak usaha, dan jual-beli internal (beban-pendapatan), termasuk penyesuaian akun induk dan total agregatnya.
  - Template PDF `resources/views/reports/comparative.blade.php` (kolom Total Gabungan, status unit, footer total) dan baru `reports/consolidated.blade.php` (tabel Kode, Nama Akun, Saldo Konsolidasi, jurnal eliminasi, tema Indigo Ledger).
  - Route `GET /tenant/reports/bundle` (`tenant.reports.bundle`) dan `GET /admin/reports/bundle` (`admin.reports.bundle`) dengan validasi `apps`, `year`, `month`, `mode` (`in:gabungan,konsolidasi`), `force`.
  - Tombol "Bundle Gabungan (ZIP)" dan "Bundle Konsolidasi (ZIP)" pada halaman Laporan tenant dan preview admin (`AppButton` compact di action bar).
  - Activity log `export_bundle_report` (metadata mode, periode, daftar aplikasi).
  - 12 test feature baru (`tests/Feature/ReportBundleTest.php`) untuk unduhan ZIP, isi arsip 5 PDF + manifest, agregasi & eliminasi konsolidasi, unit offline, isolasi tenant, validasi mode, dan akses admin.
- **Standardisasi Form Control (`new_holding`)**:
  - Komponen `AppDatePicker` berbasis native date input dengan API konsisten `AppInput`, state error/hint/disabled, batasan `min`/`max`, dan ikon tanggal yang tidak menghalangi picker native.
  - Ganti date input langsung pada Create/Edit lisensi aplikasi menjadi `AppDatePicker`.
- **Phase 3 — Unified Report (`new_holding`)**:
  - `report_caches` untuk payload laporan subsidiary dengan kunci unik `tenant_application_id + report_type + period`, TTL 30 menit, dan `fetched_at`.
  - `App\Services\SubsidiaryReportService` untuk fetch kontrak API `new_sidbm`, cache valid/force, matching composite key `code||name`, dan pemetaan state `ok|cache|offline|auth_error`.
  - Custom exception `SubsidiaryAuthException` dan `SubsidiaryUnavailableException`.
  - Halaman laporan tenant dan preview superadmin dengan filter jenis/tahun/periode, pemilihan aplikasi, tabel hierarki, badge state, muat ulang paksa, serta export CSV (`;`, BOM UTF-8, format angka id) dan PDF A4 landscape.
  - Activity log `view_report` dan `export_report` dengan metadata laporan.
  - Proteksi isolasi tenant serta penyaringan aplikasi nonaktif, kedaluwarsa, dan non-finansial.
  - Komponen domain `ReportComparativeTable` dengan header sticky, indentasi level, sub-kolom Prior/Ini/YTD untuk income statement, dan nominal via `useMoney().format()`.
  - Navigasi sidebar "Laporan" untuk tenant dan superadmin.
  - 12 test feature baru (total 41 tests) untuk cache, matching, offline/auth state, otorisasi, filter, export, dan akses admin.
- **Phase 2 — App Registry & Access (`new_holding`)**:
  - Database schema & migrations:
    - `tenant_applications`: pivot relasi tenant-application (`tenant_id`, `application_id`, `label`, `instance_url`, `api_secret` [40 char unique], `is_active`, `activated_at`, `expired_at`, `notes`, unique key [`tenant_id`, `application_id`]).
    - `tenants.domain`: kolom domain / subdomain opsional untuk identifikasi instance subsidiary.
  - Model & Factory `TenantApplication`:
    - Relasi `tenant()`, `application()`, scope `active()`, helper `isExpired(): bool`.
    - Otomatisasi generate `api_secret` 40 karakter via `Str::random(40)`.
  - Modul Vendor Admin (Superadmin) — Manajemen Lisensi Aplikasi per Tenant:
    - Resource routes `admin/tenants/{tenant}/applications` (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`).
    - Endpoint & action `regenerate-secret` (POST) dengan konfirmasi keamanan dan flash token baru.
    - Halaman `Show` lisensi dengan tampilan masked API secret, toggle reveal via switch, tombol copy-to-clipboard, dan kredensial header subsidiary (`X-Holding-Token`, `X-Holding-Tenant`).
    - Activity logs: `assign_app`, `update_app_license`, `revoke_app`, `regenerate_api_secret`.
  - Modul Tenant Side (Role `tenant_owner` & `tenant_staff`):
    - Halaman **"Aplikasi Saya"** di `Tenant/Dashboard.vue` dengan grid card dinamis, icon aplikasi, status badge (Aktif / Nonaktif / Kadaluarsa), dan tombol Quick Access bergradien `from-primary via-primary-deep to-primary-container`.
    - Endpoint Quick Access: POST `/app/{tenantApplication}/access` (name `app.access`), validasi kepemilikan tenant, status aktif, dan masa berlaku lisensi, pencatatan log `access_app`, serta redirect aman ke `instance_url`.
  - Modul Manajemen Staff Tenant (Khusus `tenant_owner`):
    - Resource routes `tenant/staff` (`index`, `create`, `store`, `edit`, `update`, `destroy`, `toggle`, `reset-password`).
    - Proteksi keamanan: proteksi demosi diri sendiri, proteksi deaktivasi & penghapusan akun diri sendiri, isolasi data antar-tenant, dan pencegahan akses untuk role `tenant_staff` (403).
    - Modal reset password oleh owner tenant.
    - Activity logs: `staff_created`, `staff_updated`, `staff_deactivated`, `staff_password_reset`.
  - Modul Log Aktivitas (Superadmin):
    - Halaman `Admin/ActivityLogs/Index.vue` dengan SmartDataTable audit trail, badge tone aksi, filter dropdown aksi, search pengguna/subjek, dan dialog detail metadata JSON.
  - Suite Pengujian Komprehensif (28 tests passing):
    - `TenantApplicationLicenseTest`: pengujian CRUD lisensi tenant, penolakan aplikasi duplikat pada tenant yang sama, regenerasi API secret, dan guard akses superadmin.
    - `AppAccessTest`: pengujian redirect quick access, pencatatan activity log `access_app`, penolakan tenant yang salah (403), dan pencegahan akses aplikasi nonaktif/kadaluarsa.
    - `TenantStaffManagementTest`: pengujian manajemen staff oleh owner, reset password, proteksi self-demote/self-deactivate, isolasi tenant, dan larangan akses role `tenant_staff`.
    - `ActivityLogViewerTest`: pengujian viewer audit trail superadmin dan proteksi role non-superadmin.
- **Phase 1 Foundation Bootstrap**:
  - Laravel 13 + Inertia 2 + Vue 3 (Composition API) + Tailwind CSS 4 diselaraskan dengan standar workspace `new_sidbm`.
  - Database schema & migrations: `users`, `tenants`, `applications`, `activity_logs`.
  - Autentikasi session-based via `AuthController`, middleware `EnsureUserHasRole`.
  - Modul Superadmin CRUD Tenants & Applications.
  - Kit Komponen Atomik & Domain MD3 (`resources/js/Components/`).

### Changed
- **Phase 4 Polish (`new_holding`)**:
  - Added `licenses:check-expiry` console command with daily 07:00 scheduler, database notifications for superadmin, and once-per-expiry / once-per-day dedupe.
  - Added license alert section to superadmin dashboard and expiring-soon badge on tenant application cards.
  - Added `web-app` and `app.access` rate limiters, branded 429 page, standard notifications table, and focused feature coverage.
- **Form Control UI (`new_holding`)**:
  - Tinggi default `AppButton` diseragamkan menjadi `min-h-14` mengikuti field form; varian `compact` tetap untuk konteks inline.
  - Konvensi tinggi form control dan inventory `AppDatePicker` didokumentasikan.
- **Navigation & Sidebar (`AdminLayout.vue`)**:
  - Penyesuaian menu navigasi otomatis sesuai peran: Superadmin (Dashboard, Tenants, Master Aplikasi, Log Aktivitas), Tenant Owner (Aplikasi Saya, Manajemen Staff), Tenant Staff (Aplikasi Saya).
  - Penyesuaian header panel dinamis (`Panel Superadmin`, `Panel Owner — {tenant}`, `Portal Staff — {tenant}`).
- **Tenants Management UI**:
  - Penambahan kolom & input domain pada form tenant.
  - Tombol aksi cepat "Kelola Lisensi Aplikasi" pada daftar tenant dan halaman detail tenant.
