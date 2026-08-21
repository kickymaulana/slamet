# Tech Spec — SLAMET

## 1. Tech Stack & Arsitektur
| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.5 |
| Frontend | Inertia 3 + Vue 3 Composition API + TypeScript |
| UI | Varlet UI (full import, MD3 light, orange #fb8c00) |
| Routing JS | Ziggy (route()) |
| Auth | SSO sekali_login (OAuth2) + Spatie permission |
| DB | MariaDB `slamet` |
| QR | npm `qrcode` (client-side) |
| Build | Vite |

Struktur mengikuti template SI KARTO: `app/Http/Controllers`, `app/Models`, `database/migrations`, `routes/web.php`, `resources/js/Pages/**/*.vue`, `resources/js/Layouts/AppLayout.vue`, `composables/cart.ts`.

## 2. Database Design
| Entity | Key Fields | Relasi |
|---|---|---|
| categories | id, name (unique), sort_order, is_active, softDeletes | → items 1:N |
| items | id, category_id, name, description, price (int), photo, stock, stock_date, is_active, softDeletes | ← categories; → order_items 1:N |
| orders | id, nota_code (unique), user_id, total_amount, status [pending|paid|cancelled], notes, paid_at, paid_by | ← users; → order_items 1:N |
| order_items | id, order_id, item_id, item_name (snapshot), price, qty, subtotal | ← orders, items |

Index: items.category_id, items.stock_date, orders.status, orders.user_id, order_items.order_id, orders.nota_code (unique).

Data flow: Karyawan lihat katalog (item stok hari ini) → keranjang → checkout → order + order_items dibuat, stok decrement (lock) → kasir cari nota → konfirmasi lunas.

## 3. Interface
| Method | Path | Deskripsi | Permission |
|---|---|---|---|
| GET | /login, /auth/sso, /callback, /pending-role | Auth SSO | guest |
| POST | /pending-role | Pilih role | guest |
| GET | /dashboard | Statistik + link | auth |
| GET | /menu | Katalog hari ini | auth |
| GET | /checkout | Halaman keranjang | order.create |
| POST | /orders | Buat pesanan | order.create |
| GET | /orders | Riwayat (own, atau semua jika payment.manage) | order.read |
| GET | /orders/{order} | Detail + QR | owner/order.read |
| GET | /kasir?q= | Cari nota | payment.manage |
| POST | /kasir/{order}/pay | Konfirmasi lunas | payment.manage |
| GET/POST/PUT/DELETE | /items | CRUD menu + foto | item.* |
| GET/POST | /stock | Set stok hari ini | stock.manage |
| GET/POST/PUT/DELETE | /masters/{entity} | CRUD kategori | category.* |
| GET/POST | /users/... | Approval user | user.manage |
| GET | /reports | Laporan periode | report.read |

## 4. Alur & Business Rules
**Checkout:** validasi items[] → DB::transaction → tiap item `lockForUpdate` → cek `availableToday()` (is_active + stock_date=today + stock>0) & stock cukup → decrement → buat order + order_items (snapshot nama/harga) → redirect detail.

**Kasir:** cari by nota_code (like q) atau daftar hari ini (pending dulu) → pay: status harus pending → paid + paid_at + paid_by.

**Nota:** `SLM-` . YYYYMMDD . `-` . 4-digit urutan hari itu. Unique constraint.

**Aturan:** nasi gratis = item harga 0 (kategori Nasi). Stok per `stock_date`. Order immutable setelah dibuat (v1 tanpa cancel).

## 5. Keamanan, Performa, Deployment
- Permission Spatie tiap endpoint; validasi server-side; role: admin (semua), kasir (order.read, payment.manage), karyawan (order.create, order.read).
- Eager load relasi (user, items, category); paginate list; index hot column.
- Dev: `composer install && npm install && php artisan migrate:fresh --seed && npm run dev` + `php artisan serve`.
- Test: `php artisan test` (OrderTest, UserApprovalTest), `npm run typecheck`, `vendor/bin/pint`.
