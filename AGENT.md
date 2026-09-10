# AGENT.md — Pedoman Wajib untuk Coding UI

Project ini adalah **Laravel + Inertia.js + Vue 3 (Composition API) + Tailwind 4** dengan token **Material Design 3**. Sebelum membuat atau mengubah UI, jalankan protokol berikut secara berurutan.

## Protokol 3-Langkah Sebelum Coding UI

### Langkah 1 — Inventarisasi komponen

Baca daftar berikut sebelum menulis markup:

```
resources/js/Components/*.vue
resources/js/Layouts/*.vue
resources/js/Composables/*.js
resources/js/Pages/**/*.vue
```

**Inventory aktif:**

- Atomik `App*`: `AppBadge`, `AppButton`, `AppCard`, `AppCheckbox`, `AppConfirmDialog`, `AppEmptyState`, `AppIcon`, `AppIconButton`, `AppInput`, `AppModal`, `AppSelect`, `AppSwitch`, `AppTextarea`, `AppToast`, `AppTooltip`.
- Atomik `App*`: `AppDatePicker` untuk pemilih tanggal (`YYYY-MM-DD` atau string kosong); jangan pakai raw `<input type="date">`.
- Data & select: `SmartDataTable`, `SmartSelect`, `ReportComparativeTable`.
- Layout: `AdminLayout` (sidebar + topbar + dialog/toast), `AuthenticatedLayout`.
- Composables: `useCan`, `useConfirm`, `useMoney`, `useTheme`, `useToast`.
- Halaman Phase 1: `Auth/Login`, `Dashboard`, `Tenant/Dashboard`, `Admin/Tenants/*`, `Admin/Applications/*`.
- Halaman Phase 2: `Admin/TenantApplications/*` (Index, Create, Edit, Show), `Admin/ActivityLogs/*` (Index), `Tenant/Staff/*` (Index, Create, Edit).
- Halaman Phase 3: `Tenant/Reports/Index`, `Admin/Reports/Index`.

### Langkah 2 — Pakai ulang komponen

Gunakan komponen dan composable yang sudah ada. Jangan membuat abstraksi baru bila kebutuhan sudah tercakup.

### Langkah 3 — Buat baru hanya bila perlu

Komponen baru boleh dibuat jika tidak ada padanan, dipakai minimal dua kali, atau kompleksitasnya nyata. Letakkan komponen umum di `resources/js/Components/`, lalu tambahkan ke inventory di atas.

## Aturan Token MD3

## Konvensi Form Control

- Semua form control (`AppInput`, `AppSelect`, `SmartSelect`, `AppDatePicker`, `AppSwitch` varian `field`, dan `AppButton` default) berdiri setinggi `h-14`/`min-h-14`.
- `AppButton` varian `compact` (`min-h-10`) hanya untuk konteks inline, topbar, dan table actions; jangan override ukuran tombol pada form utama.
- `AppTextarea` mengikuti tinggi konten karena multiline, tetapi wajib memakai token border, radius, dan focus yang sama.

- Identitas visual adalah **Indigo Ledger**: light/dark token indigo sebagai primary, teal sebagai secondary, dan amber sebagai tertiary di `resources/css/app.css`.
- Semua warna, permukaan, border, ikon status, dan shadow wajib memakai token dari `resources/css/app.css` (`surface`, `primary`, `secondary`, `error`, `tertiary`, `outline`, `on-*`).
- Dilarang memakai warna hard-coded (`bg-gray-500`, `text-red-600`, `bg-blue-500`, inline style hex/rgb).
- Ikon hanya lewat `AppIcon`/`AppIconButton` dengan Material Symbols.
- Interaksi destruktif wajib memakai `useConfirm()` + `AppConfirmDialog`, bukan `window.confirm`/`alert`.
- Nominal Rupiah hanya diformat lewat `useMoney().format()`, bukan `toLocaleString` inline.
- Tabel list CRUD wajib memakai `SmartDataTable`.

## Aturan Dark Mode

- Light dan dark memakai token terpisah pada `:root`/`@theme` dan `[data-theme='dark']`.
- `app.blade.php` menerapkan tema sinkron dengan `localStorage`/OS sebelum render.
- Setiap perubahan UI wajib diaudit dalam mode light dan dark.
- Jangan pakai override force-dark; `color-scheme` diatur per tema.

## Protokol CHANGELOG

Sebelum commit atau push, jalankan `git status` dan `git diff --stat`, lalu perbarui `CHANGELOG.md` dengan seluruh perubahan riil changeset. Gunakan format Keep a Changelog dan kategori `Added`, `Changed`, `Fixed`, atau `Removed` secara akurat.
