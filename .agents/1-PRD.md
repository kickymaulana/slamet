# PRD — SLAMET
**Sistem Laporan & Antrean Makan Enak Teratur**

Versi: 1.0 | Status: Draft | Tech Stack: Laravel 13, Inertia 3, Vue 3, TypeScript, Varlet UI

## 1. Visi & Tujuan
Aplikasi pemesanan makanan kantin perusahaan berbasis HP. Karyawan pesan dari katalog menu harian tanpa antre, bayar di kasir. Nasi gratis sebagai pilihan porsi (Rp 0). Stok menu harian dikelola admin, otomatis berkurang saat pesanan dibuat.

Tujuan:
1. Hilangkan antrean pemesanan di kantin — indikator: waktu order < 1 menit di HP.
2. Stok harian real-time tanpa kehabisan — indikator: stok otomatis turun tiap checkout.
3. Pembayaran manual tertib — indikator: tiap nota lunas tercatat dengan kasir + waktu.
4. Laporan penjualan harian untuk evaluasi menu — indikator: rekap per periode.

## 2. User Persona
### Persona 1: Karyawan (Pemesan)
- Karyawan pabrik, HP Android. Ingin pesan makan cepat, tahu menu & stok, bayar di kasir setelahnya.

### Persona 2: Kasir Kantin
- Petugas pembayaran di antrean kasir. Cari nota (ketik kode / scan QR), konfirmasi lunas cepat.

### Persona 3: Admin Kantin
- Kelola kategori, menu + foto, stok harian, setujui user, lihat laporan.

## 3. User Stories
1. Sebagai karyawan, saya ingin login SSO, agar bisa memesan.
2. Sebagai karyawan, saya ingin lihat menu hari ini, agar tahu apa yang tersedia.
3. Sebagai karyawan, saya ingin pilih porsi nasi gratis (Tanpa/1/1.5/2 centong), agar bisa makan gratis sesuai jatah.
4. Sebagai karyawan, saya ingin tambah item ke keranjang, agar bisa checkout sekaligus.
5. Sebagai karyawan, saya ingin checkout dengan catatan, agar pesanan sesuai selera.
6. Sebagai karyawan, saya ingin lihat kode nota + QR setelah pesan, agar ditunjukkan ke kasir.
7. Sebagai karyawan, saya ingin lihat riwayat pesanan saya, agar bisa cek status bayar.
8. Sebagai kasir, saya ingin cari nota via kode / QR, agar pembayaran cepat.
9. Sebagai kasir, saya ingin konfirmasi lunas, agar pesanan tercatat dibayar.
10. Sebagai admin, saya ingin kelola kategori, agar menu terorganisir.
11. Sebagai admin, saya ingin kelola menu + foto + harga, agar katalog akurat.
12. Sebagai admin, saya ingin set stok harian, agar menu habis tidak bisa dipesan.
13. Sebagai admin, saya ingin setujui user baru, agar hanya karyawan valid yang akses.
14. Sebagai admin, saya ingin lihat laporan periode, agar tahu menu terlaris & pendapatan.

## 4. Functional Requirements (ringkas)
- **FR-01 Login SSO**: OAuth2 authorization code; user baru auto-create is_approved=false → pilih role → approval admin. FR-02 Pesanan: keranjang → POST /orders → stok terdeduct (transaction + lock), nota `SLM-YYYYMMDD-NNNN` unik, total otomatis, status pending. FR-03 Kasir: cari nota (q param / search), konfirmasi lunas → status paid, paid_at, paid_by. FR-04 Katalog: item aktif + stok tanggal hari ini > 0, dikelompokkan kategori, foto opsional. FR-05 Stok: bulk set stok per item + stock_date=today. FR-06 Laporan: filter from/to, ringkasan total/lunas/pending.

## 5. Non-Functional
- Mobile-first (Varlet), Bahasa Indonesia, responsif desktop.
- Validasi server-side semua form; permission Spatie tiap endpoint.
- Index: orders.status, orders.user_id, items.stock_date, items.category_id, order_items.order_id.
- Login SSO wajib; user is_approved=false tak bisa login.

## 6. Out of Scope & Dependensi
- v2: pembayaran online/e-wallet, cancel order, notifikasi, export Excel/PDF, antrean real-time push.
- Dependensi: sekali_login (SSO), MariaDB, Varlet UI, spatie/laravel-permission, npm qrcode.
