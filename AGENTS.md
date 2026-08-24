# AGENTS.md

SLAMET — Sistem Laporan & Antrean Makan Enak Teratur. Pemesanan makan kantin mobile-first. PRD: `.agents/1-PRD.md`, Tech Spec: `.agents/2-TECH-SPEC.md`.

## Stack
- Laravel 13 + Inertia **3** + Vue 3 + TypeScript + Varlet UI (full import) + Ziggy + Spatie Permission
- Setup bersih tanpa starter kit, tanpa Tailwind. Template = SI KARTO (`D:\Apache24\htdocs\sikarto`).

## Commands
- Dev: `npm run dev` + `php artisan serve`
- Build: `npm run build` (setelah frontend berubah)
- Typecheck: `npm run typecheck` — jalankan sebelum build
- Test: `php artisan test`
- Code style: `vendor/bin/pint`

## Struktur frontend
- Halaman: `resources/js/Pages/**/*.vue` (auto-resolve). Shared props: `resources/js/types/index.d.ts` + `HandleInertiaRequests::share()`.
- Route di JS wajib Ziggy `route()`. App di subfolder `/slamet/public`.
- Cart state global: `resources/js/composables/cart.ts` (katalog → checkout). Search state: `composables/search.ts`.

## Desain
- Android-layout mobile-first + Varlet MD3 light. Primary orange `#fb8c00`, peach fill `#fdf0ea`, bg `#f8fafc`. `StyleProvider` override `'color-primary': '#fb8c00'` di `app.ts` (wajib).
- AppBar gradient `#fb8c00→#f57c00`, sticky (`html, body { margin:0 }` di `app.css`). Bottom nav di halaman utama (Beranda/Menu/Pesanan/Kasir/Laporan), FAB tambah di list (items/masters).
- Varlet icon font KUSTOM — cek `node_modules/@varlet/ui/es/icon/icon.css`. Yang dipakai: `home-outline`, `fire`, `notebook`, `qrcode-scan`, `file-document-outline`, `magnify`, `power`, `arrow-left`, `plus`, `minus`, `delete`, `image-outline`, `chevron-right`, `close-circle-outline`.
- Konfirmasi wajib `Dialog` Varlet (bukan `confirm()`), tombol Indonesia. `var-form` pakai `:onsubmit` (bukan `@submit.prevent`).

## Otorisasi
- Spatie. Role: `admin` (semua), `kasir` (order.read, payment.manage), `karyawan` (order.create, order.read). Endpoint cek `permission:` middleware. Validasi server-side.

## SSO Login (Manual Provisioning)
- Login wajib SSO (OAuth2). Server: sekali_login. Client `slamet` terdaftar di DB sekalilogin (client_id `80a21e8a-a645-4b9e-b15f-2a3f297dbdba`, secret `slamet-sso-secret-2026`), redirect `http://localhost/slamet/public/callback`. Config di `.env` (SSO_BASE_URL/CLIENT_ID/CLIENT_SECRET).
- User baru match by `nik` → auto-create `is_approved=false` → `/pending-role` (pilih admin/kasir/karyawan) → admin approve di `/users` (`user.manage`).

## DB
- MariaDB `slamet` (root/kicky123). Index: `orders.status`, `orders.user_id`, `items.stock_date`, `items.category_id`, `order_items.order_id`; `orders.nota_code` unique.
- `outlets` (Kantin 1/2). `items.outlet_id` = menu per kantin. `orders.outlet_id` = kantin tujuan. Filter kantin via query param `?outlet=` di catalog/kasir/stock/report.

## Foto Menu (MinIO)
- Storage `minio` disk (S3-compatible, `league/flysystem-aws-s3-v3`). Config `.env` `AWS_*` → endpoint `http://127.0.0.1:9000`, bucket `slamet`, creds `minioadmin`. Dependensi default: `D:\minio\minio.exe server D:\minio --console-address :9001` + `mc.exe alias set local ... && mc.exe mb local/slamet`.
- Kompres foto CLIENT-SIDE di `Items/Form.vue` (`compressImage`, canvas max 1200px, jpeg quality 0.75) → upload `Storage::disk('minio')` path `items/{ts}-{rand}.jpg`.
- Tampil via route `items.foto` (stream dari MinIO, `ItemController::foto`), bukan asset URL publik.

## Business rules
- Stok harian per `items.stock_date`; katalog = is_active + stock_date=today + stock>0 + outlet aktif. `Item::availableToday()` (cek `stock_date` Carbon → bandingkan format Y-m-d, jangan `===` string).
- Checkout = transaction + `lockForUpdate` per item, stok auto-deduct, order_items snapshot nama/harga. `items.*.outlet_id` harus = `outlet_id` order. Nota `SLM-YYYYMMDD-NNNN`. Nasi gratis = item harga 0 di kategori Nasi.
- **Saldo per kantin**: `user_balances` (user_id+outlet_id unique, balance). Bukan kolom di users. Checkout cek saldo di kantin order; kasir pay deduksi + catat `balance_transactions` (topup/deduction). Kasir terikat kantin bisa isi saldo hanya kantinnya (`/kasir/topup`, `UserBalance::credit`, kasir_id tercatat).
- Kasir: pay hanya status pending → paid + paid_at + paid_by. Redirect balik eksplisit `kasir.index` + q + outlet (jangan `back()`).

## Gotchas
- Paginator Inertia top-level keys (`current_page`, `last_page`, ...), TIDAK ada `meta`.
- Varlet `InputType` HANYA `text|password|number|tel|email`; textarea = `:textarea="true"`; tanggal = native `<input type="date">`.
- Tidak ada `var-stepper` di Varlet 3.20.6 — qty pakai tombol custom (lihat `Menu/Catalog.vue`).
- QR pakai npm `qrcode` (client-side, `QRCode.toDataURL`). `@types/qrcode` sudah terpasang.
- Test: phpunit.xml override `APP_URL=http://localhost` — jangan dihapus.
- Role list SLAMET: `admin`, `kasir`, `karyawan`. Bukan inspector/admin_master.
