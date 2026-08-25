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
- Seed + reset: `php artisan migrate:fresh --seed` (MinIO harus jalan dulu biar dummy foto ke-upload)

## Struktur frontend
- Halaman: `resources/js/Pages/**/*.vue` (auto-resolve). Shared props: `resources/js/types/index.d.ts` + `HandleInertiaRequests::share()`.
- Route di JS wajib Ziggy `route()`. App di subfolder `/slamet/public`.
- Cart state global: `resources/js/composables/cart.ts` (katalog → checkout, `outletId` ikut kantin). Search state: `composables/search.ts`.

## Desain
- Android-layout mobile-first + Varlet MD3 light. Primary orange `#fb8c00`, peach fill `#fdf0ea`, bg `#f8fafc`. `StyleProvider` override `'color-primary': '#fb8c00'` di `app.ts` (wajib).
- AppBar gradient `#fb8c00→#f57c00`, sticky (`html, body { margin:0 }` di `app.css`). Bottom nav di halaman utama (Beranda/Menu/Pesanan/Kasir/Laporan), FAB tambah di list (items/masters).
- **Cart di AppBar**: pill `🛒 N • Total Coin` di halaman katalog → popup mini-cart (`var-popup` bottom) dengan foto item + qty +/− + total + tombol Checkout. Tidak ada cart-bar di bawah.
- **Foto klik → zoom**: overlay fullscreen hitam (gambar besar + nama, klik luar tutup) dipakai di katalog, checkout, detail pesanan, stok, form item.
- **Label field statis** `.form-label` + placeholder (jangan andalkan floating label Varlet — sering tak kelihatan). Pola: `<label class="form-label">X</label>` + `var-input :placeholder`.
- Varlet icon font KUSTOM — cek `node_modules/@varlet/ui/es/icon/icon.css`. Yang dipakai: `home-outline`, `fire`, `notebook`, `qrcode-scan`, `file-document-outline`, `magnify`, `power`, `arrow-left`, `plus`, `minus`, `delete`, `image-outline`, `chevron-right`, `close-circle-outline`, `cart`.
- Konfirmasi wajib `Dialog` Varlet (bukan `confirm()`), tombol Indonesia. `var-form` pakai `:onsubmit` (bukan `@submit.prevent`).

## Otorisasi
- Spatie. Role: `admin` (semua), `Petugas Kantin`, `User`. Endpoint cek `permission:` middleware. Validasi server-side.
- `Petugas Kantin`: order.read, payment.manage, item.read/create/update/delete, stock.manage, category.read/create/update/delete. Terikat `users.outlet_id` → filter paksa di kasir/stock/report, item CRUD scoped kantinnya, pay/topup lintas kantin ditolak (403).
- `User`: order.create, order.read.
- **`/orders` & dashboard "Pesanan Terbaru" = HANYA pesanan sendiri, semua role.** Petugas Kantin lihat semua via `/kasir` (bukan `/orders`).

## SSO Login (Manual Provisioning)
- Login wajib SSO (OAuth2). Server: sekali_login. Client `slamet` terdaftar di DB sekalilogin (client_id `80a21e8a-a645-4b9e-b15f-2a3f297dbdba`, secret `slamet-sso-secret-2026`), redirect `http://localhost/slamet/public/callback`. Config di `.env` (SSO_BASE_URL/CLIENT_ID/CLIENT_SECRET).
- User baru match by `nik` → auto-create `is_approved=false` → `/pending-role` (pilih `admin`/`Petugas Kantin`/`User`) → admin approve di `/users` (`user.manage`, kasir wajib pilih kantin → `required_if:role,Petugas Kantin`).

## DB
- MariaDB `slamet` (root/kicky123). Index: `orders.status`, `orders.user_id`, `items.stock_date`, `items.category_id`, `order_items.order_id`; `orders.nota_code` unique.
- `outlets` (Kantin 1/2). `items.outlet_id` = menu per kantin. `orders.outlet_id` = kantin tujuan. Filter kantin via query param `?outlet=` di catalog/kasir/stock/report.
- `user_balances` (user_id+outlet_id unique, balance) — saldo PER KANTIN, bukan kolom di users. `users.outlet_id` = kantin Petugas Kantin.
- `balance_transactions` (user_id, outlet_id, type topup|deduction, amount, kasir_id, note) — riwayat saldo.

## Routes penting
- Menu: `menu.catalog` (katalog + tab/dropdown kantin), `checkout`, `orders.store` / `orders.index` (own) / `orders.show` (+QR)
- Kasir: `kasir.index` (filter `?status=` Semua/Belum Bayar/Lunas, `?q=` kode nota), `kasir.saldo` (isi saldo), `kasir.user` (cek NIK→JSON), `kasir.topup`, `kasir.pay`
- Saldo: `saldo.riwayat` (transaksi sendiri), `saldo.transfer` + `saldo.transfer.submit`
- Admin: `items.*`, `stock.today`/`stock.save`, `masters.*` (kategori), `reports.index`, `users.*`

## Foto Menu (MinIO)
- Storage `minio` disk (S3-compatible, `league/flysystem-aws-s3-v3`). Config `.env` `AWS_*` → endpoint `http://127.0.0.1:9000`, bucket `slamet`, creds `minioadmin`. Dependensi: `D:\minio\minio.exe server D:\minio --console-address :9001` + `mc.exe alias set local http://127.0.0.1:9000 minioadmin minioadmin && mc.exe mb local/slamet`.
- Kompres foto CLIENT-SIDE di `Items/Form.vue` (`compressImage`, canvas max 1200px, jpeg quality 0.75) → upload `Storage::disk('minio')->putFileAs('items', $file, "{ts}-{rand}.jpg")`.
- Tampil via route `items.foto` (stream dari MinIO, `ItemController::foto`), bukan asset URL publik. Thumbnail di: list items, stok, checkout, detail pesanan, katalog.
- Seeder `seedPhotos()`: generate placeholder JPEG via GD (peach bg + nama orange) → upload MinIO → set `item.photo`.

## Business rules
- Stok harian per `items.stock_date`; katalog = is_active + stock_date=today + stock>0 + outlet aktif. `Item::availableToday()` (cek `stock_date` Carbon → bandingkan format Y-m-d, jangan `===` string).
- Checkout = transaction + `lockForUpdate` per item, stok auto-deduct, order_items snapshot nama/harga. `items.*.outlet_id` harus = `outlet_id` order. Nota `SLM-YYYYMMDD-NNNN`. Nasi gratis = item harga 0 di kategori Nasi.
- Form menu TIDAK punya stok — stok diatur khusus via `/stock` (per kantin, bulk, `stock_date`=hari ini).
- **Saldo per kantin**: checkout cek `UserBalance::balanceOf(user, outlet_order)` ≥ total; `pay` = transaction (lock order, re-check saldo, `UserBalance::debit`, catat `balance_transactions` type deduction, paid+paid_at+paid_by). Topup via `/kasir/saldo` → `UserBalance::credit` + transaksi topup (kasir_id). Transfer per kantin sama → debit sender + credit receiver + 2 transaksi; self-transfer ditolak.
- Kasir pay: hanya status pending → paid. Redirect balik eksplisit `kasir.index` + q + outlet + status (jangan `back()`).

## Gotchas
- Paginator Inertia top-level keys (`current_page`, `last_page`, ...), TIDAK ada `meta`.
- Varlet `InputType` HANYA `text|password|number|tel|email`; textarea = `:textarea="true"`; tanggal = native `<input type="date">`.
- Tidak ada `var-stepper` di Varlet 3.20.6 — qty pakai tombol custom (lihat `Menu/Catalog.vue`, popup mini-cart).
- QR pakai npm `qrcode` (client-side, `QRCode.toDataURL`). `@types/qrcode` sudah terpasang.
- `Storage::put()` return **bool**, bukan path — konstruksi path manual dulu, lalu `put($path, $content)`.
- `route().current()` TIDAK reaktif — `AppLayout` pakai `currentRoute = computed(() => { void page.url; return route().current() ?? ''; })` biar judul/highlight bottom-nav update.
- Test pakai SQLite — `FIELD()` MariaDB-only bikin 500; pakai `orderByRaw("CASE status WHEN ...")`.
- Stale `bootstrap/cache/config.php` bikin semua test 404 → `php artisan config:clear`.
- Test: phpunit.xml override `APP_URL=http://localhost` — jangan dihapus. Test user helper set `nik` + `UserBalance`.
- Role list SLAMET: `admin`, `Petugas Kantin`, `User`. Bukan inspector/admin_master/kasir/karyawan.
