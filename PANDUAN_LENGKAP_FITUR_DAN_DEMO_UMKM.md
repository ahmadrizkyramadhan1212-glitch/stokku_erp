# 📘 PANDUAN LENGKAP FITUR, ALUR DEMO UMKM & CONTEKAN SIDANG SKRIPSI
## Sistem Informasi Manajemen Inventori Gudang & SPK Kulakan Warung Sayur Adijaya (**STOKKU ERP**)

> **Posisi Sistem (Penting untuk Dipahami & Dijelaskan):**
> **STOKKU ERP BUKAN Mesin Kasir Pembayaran Pelanggan (Non-POS Payment Gateway).**
> Di Warung Sayur Adijaya, transaksi jual-beli fisik tetap dilakukan secara tunai/biasa agar antrean ibu-ibu belanja di jam sibuk pagi (**07:00–11:00 WIB**) dan sore (**14:00–17:30 WIB**) tidak macet. Aplikasi ini adalah **Sistem Kontrol Gudang Internal, Pencatatan Barang Keluar Harian, Perhitungan Modal Rata-Rata (Moving Average), Rekomendasi Belanja Pasar Pagi (SPK SAW), Klasifikasi Barang (Matriks BCG), dan Pelaporan Otomatis ke WhatsApp Owner pukul 23:00 WIB.**

---

# BAGIAN 1: PETA SELURUH HALAMAN & TOMBOL (SCREEN-BY-SCREEN GUIDE)

Berikut adalah penjelasan lengkap satu per satu halaman yang ada di aplikasi **STOKKU ERP** saat ini, mulai dari halaman Login hingga Pusat Laporan Keuangan.

---

## 1. Halaman Login (`/login`)
* **File View:** [`resources/views/auth/login.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/auth/login.blade.php)

### a) Halaman ini untuk siapa?
Untuk **Owner (Pemilik Warung)** maupun **Karyawan (Penjaga Warung/Gudang)**. Sistem otomatis membedakan tampilan berdasarkan akun yang masuk:
* **Akun Owner (`admin`):** `owner@stokku.com` (atau `admin@stokku.com`) | Password: `password` -> Membuka seluruh fitur keuangan, modal (HPP), laba bersih, kulakan, SPK SAW, BCG, dan laporan PDF.
* **Akun Karyawan (`kasir`):** `karyawan@stokku.com` (atau `kasir@stokku.com`) | Password: `password` -> Membuka mode khusus operasional gudang. **Seluruh angka uang (Omzet, Laba, Harga Modal, Margin) disembunyikan secara otomatis.**

### b) Kapan halaman ini dipakai di warung?
Saat pertama kali membuka HP/Tablet di warung pada pagi hari, atau saat berganti shift antara Owner dan Karyawan.

### c) Fungsi setiap Kartu, Angka, dan Tombol:
* **Kolom Email & Kata Sandi:** Tempat memasukkan akun pengguna.
* **Checkbox "Ingat perangkat saya":** Agar di HP warung tidak perlu ketik ulang password setiap pagi.
* **Tombol `[MASUK KE SISTEM STOKKU]`:** Masuk ke halaman utama sesuai hak akses masing-masing.
* **Tombol Pintasan Demo `[👑 Login sebagai Owner]` & `[👷 Login sebagai Karyawan]`:** Tombol sekali klik (1-Tap Auto-Fill) untuk langsung mengisi email & password otomatis saat mendemokan aplikasi di depan pemilik UMKM atau dosen penguji tanpa repot mengetik manual.

---

## 2. Halaman Utama / Home Dashboard Ala GoPay (`/dashboard`)
* **File View:** [`resources/views/dashboard.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/dashboard.blade.php)
* **Controller:** [`DashboardController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/DashboardController.php)

### a) Halaman ini untuk siapa?
Untuk **Owner** dan **Karyawan**, namun isi kartunya otomatis menyesuaikan siapa yang login:
* Jika **Owner** yang membuka: Kartu finansial atas menampilkan **Estimasi Laba Bersih, Omzet Bruto, Pengeluaran, dan Margin (%)**, serta tombol **Catat Kulakan** dan **Laporan PDF**.
* Jika **Karyawan** yang membuka: Kartu atas otomatis berubah menjadi **Ringkasan Operasional Gudang Harian** (Jumlah transaksi barang keluar hari ini, jumlah item menipis/habis, jumlah kemas ulang FIFO), tanpa ada nominal rupiah sedikit pun.

### b) Kapan halaman ini dipakai di warung?
Setiap saat sebagai layar beranda utama untuk memantau kondisi warung dalam 5 detik dan berpindah ke menu lain.

### c) Fungsi setiap Kartu, Angka, Filter, dan Tombol:

#### 1) Header Hijau Atas yang Menempel (`Sticky/Fixed Top Header`)
* **Logo `STOKKU ERP` & Badge `[OWNER]` / `[KARYAWAN]`:** Penanda identitas pengguna yang sedang aktif. Header ini menempel tetap di atas saat layar di-scroll ke bawah.
* **Dropdown Filter Waktu (`Hari Ini`, `Kemarin`, `Minggu Ini`, `Bulan Ini`):** Mengubah seluruh angka ringkasan di dashboard secara instan sesuai periode yang dipilih.
* **Ikon Lonceng Notifikasi (dengan titik merah):** Membuka daftar peringatan otomatis jika ada sayur/bumbu yang stoknya habis atau hampir busuk (FIFO).
* **Tombol `Keluar` (Logout):** Keluar dari akun dengan aman.
* **Badge `Jam Sibuk Warung: 07:00–11:00 & 14:00–17:30 WIB`:** Pengingat operasional bahwa pencatatan aplikasi dilakukan saat warung mulai senggang (pasca jam sibuk) agar pelayanan pembeli tetap cepat.

#### 2) Kartu Finansial Atas (`Wallet Card Header` di dalam Container Putih Melengkung `rounded-t-[32px]`)
* **Angka Utama `Estimasi Laba Bersih` (Khusus Owner):** Uang untung bersih warung setelah dikurangi harga modal barang (HPP Moving Average) dan biaya operasional (plastik, listrik, bensin).
* **Badge `Omzet`, `Keluar`, dan `Margin`:** Rincian pemasukan kotor, pengeluaran kas, dan persentase keuntungan bersih.
* **Tombol `[+ Catat Kulakan]` (Khusus Owner):** Jalan pintas menuju halaman input nota belanja pasar (`/purchases`).
* **Tombol `[Laporan]` (Khusus Owner):** Jalan pintas menuju pusat laporan keuangan & unduh PDF (`/reports`).
* *(Catatan: Bagi Karyawan, kedua tombol di atas otomatis diganti menjadi `[+ Barang Keluar]` dan `[Cek Stok 1-Tap]`).*

#### 3) Grid Menu Akses Cepat 8 Ikon (`Quick Action Grid` 4x2)
1. **Barang Keluar (`/pos`):** Mencatat jumlah barang yang laku/keluar hari ini dan memotong stok gudang otomatis.
2. **Cek Stok 1-Tap (`/inventory/materials`):** Mengubah status stok (`Aman` / `Menipis` / `Habis`) cukup 1 kali sentuh dan mencatat sayur busuk/susut.
3. **Kulakan AI (`/purchases` - *Khusus Owner*) / Stok Etalase (`/inventory/production` - *Bagi Karyawan*):** Bagi Owner untuk scan foto struk/ketik nota belanja pasar; bagi Karyawan untuk kontrol stok kemas ulang.
4. **SPK SAW (`/spk`):** Melihat daftar urutan prioritas barang yang wajib dibeli di pasar besok subuh beserta kirim laporan WA 23:00 WIB.
5. **Analisis BCG (`/analysis/klasifikasi` - *Khusus Owner*):** Mengelompokkan barang ke 4 kuadran (Star, Plowhorse, Puzzle, Dog) agar modal tidak mandek.
6. **Laporan PDF (`/reports` - *Khusus Owner*):** Mencetak laporan keuangan resmi dan mencatat pengeluaran operasional warung.
7. **Kemas Ulang (`/inventory/production`):** Mencatat pembungkusan stok gudang besar menjadi paket eceran siap pajang di meja depan (FIFO).
8. **Lihat Semua (Bottom Sheet Drawer):** Membuka laci pop-up dari bawah yang menampilkan seluruh fitur aplikasi secara lengkap (termasuk Prediksi Regresi Linier `/analysis/prediksi`).

#### 4) Shortcut Lingkaran Horizontal (`Supplier & Langganan Warung`)
* Deretan ikon bulat bergaya kontak cepat GoPay: **+ Nota Pasar, Pasar Induk, Agen Sembako, Agen Gas/Air, Langganan Warung, Eceran Warga, Kirim WA 23:00**.
* **Fungsinya:** Sekali sentuh langsung membawa pengguna ke halaman terkait (misal klik *Pasar Induk* langsung membuka input nota kulakan pasar; klik *Langganan Warung* langsung membuka halaman barang keluar).

#### 5) Kartu "Spesial Untuk Owner: Pantauan Cerdas Warung" (2 Kartu Bersebelahan)
* **Kartu Kiri (Hijau Tua - Prioritas Belanja #1 SAW):** Menampilkan 1 nama barang yang paling mendesak dibeli besok pagi menurut perhitungan rumus SAW (misal: *Cabai Rawit — Skor 0.925*), lengkap dengan tombol menuju SPK SAW.
* **Kartu Kanan (Amber/Oranye - Status Gudang & WA 23:00):** Menampilkan jumlah barang yang sedang kritis/habis dan jumlah paket FIFO sisa kemarin yang wajib dijual duluan hari ini, lengkap dengan tombol kirim laporan WA.

#### 6) Bagian "Eksplorasi Stok Warung" (Pencarian & Filter Kategori A–I)
* **Kotak Pencarian (`Search Bar`):** Ketik nama barang apa saja (misal *"Bawang"*, *"Minyak"*, *"Telur"*), maka daftar di bawahnya langsung menyaring detik itu juga tanpa loading ulang halaman.
* **Tombol Filter Tab Kategori Horizontal:**
  * `[Semua (96)]`
  * `[A. Sayur & Buah]` (Terong, Tomat, Sawi, Cabai, Kelapa, Pisang, dll.)
  * `[B. Bumbu Dapur]` (Bawang Merah/Putih, Jahe, Kunyit, Royco, Santan Kara, dll.)
  * `[C. Minyak & Kecap]` (Minyak Kita, Sunco, Bimoli, Kecap Bango, Saus ABC, dll.)
  * `[D-E. Sembako & Mie]` (Beras, Gula, Telur Ayam, Tepung, Indomie, Mie Sedaap, dll.)
  * `[F-I. Lainnya]` (Kopi, Teh, Susu, Gas LPG 3Kg, Galon Aqua, Sabun, Plastik Kresek)
* **Accordion / Daftar Komoditas:** Menampilkan sisa stok, satuan asli warung (`kg`, `ikat`, `ons`, `bungkus`, `renceng`, `tabung`), serta indikator warna status (`Aman` = Hijau, `Menipis` = Kuning, `Habis` = Merah).

---

## 3. Halaman Pencatatan Barang Keluar & FIFO (`/pos`)
* **File View:** [`resources/views/pos/index.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/pos/index.blade.php)
* **Controller:** [`PosController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/PosController.php)

### a) Halaman ini untuk siapa?
Untuk **Karyawan** (sebagai tugas utama mencatat rekap barang yang keluar/terjual) dan **Owner** (jika ikut menjaga warung).
* **Tampilan Karyawan:** Hanya melihat nama barang, sisa stok fisik, peringatan FIFO, dan tombol angka `+` / `-` jumlah fisik yang keluar. **Tidak ada harga jual dan tidak ada total uang.**
* **Tampilan Owner:** Melihat tambahan informasi estimasi harga jual dan pilihan tingkatan harga pembeli.

### b) Kapan halaman ini dipakai di warung?
Dipakai **setelah jam sibuk pagi (sekitar pukul 11:00 WIB)**, **setelah jam sibuk sore (sekitar pukul 17:30 WIB)**, atau saat melayani pesanan borongan pelanggan warung makan/katering.

### c) Fungsi setiap Kartu, Angka, Filter, dan Tombol:
* **Banner Peringatan Kuning (`Prioritas Jual Hari Ini / FIFO Sisa Kemarin`):** Muncul paling atas jika ada barang kemas ulang/sayur kupas sisa kemarin yang belum habis, agar Karyawan menaruh barang tersebut di barisan paling depan etalase dan menjualnya lebih dulu sebelum busuk.
* **Pilihan Tingkatan Harga (`Eceran Warga`, `Langganan Warung/Reseller`, `Grosir/Agen` - *Khusus Owner*):** Menentukan dasar perhitungan estimasi omzet karena di Warung Sayur Adijaya harga beli tetangga biasa berbeda dengan harga beli tukang nasi goreng langganan.
* **Search Bar & Filter Tab Kategori:** Memudahkan mencari barang dari 96 komoditas warung.
* **Kartu Barang (Grid Item):** Sekali klik langsung memasukkan barang ke daftar rekap keluar di sebelah kanan/bawah. Dilengkapi badge sisa stok dan label merah/kuning jika ada stok FIFO.
* **Tombol `+` / `-` dan Input Angka Kuantitas:** Mengatur jumlah barang yang keluar (bisa desimal/pecahan maupun bulat sesuai satuan warung).
* **Tombol Hijau `[SIMPAN REKAP BARANG KELUAR]`:** Menyimpan data. Begitu ditekan, sistem otomatis:
  1. Memotong stok fisik di gudang/etalase (mendahulukan pemotongan stok FIFO sisa kemarin).
  2. Mencatat riwayat kelarisan barang (yang nantinya dipakai oleh rumus **Kriteria C3 SAW**, **Matriks BCG**, dan **Regresi Linier**).
  3. Menghitung estimasi Omzet & HPP untuk laporan Laba Rugi Owner.

---

## 4. Halaman Cek Stok Warung 1-Tap & Koreksi Busuk (`/inventory/materials`)
* **File View:** [`resources/views/inventory/materials.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/inventory/materials.blade.php)
* **Controller:** [`InventoryController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/InventoryController.php)

### a) Halaman ini untuk siapa?
Untuk **Karyawan** (tugas wajib sore/malam hari untuk mengecek kondisi rak sayur) dan **Owner** (untuk menambah jenis barang dagangan baru atau mengaudit stok).

### b) Kapan halaman ini dipakai di warung?
Dipakai **sore hari (17:30 WIB)** atau **malam hari menjelang tutup warung (22:00–23:00 WIB)** saat merapikan rak sayur dan membuang sayur yang layu/busuk.

### c) Fungsi setiap Kartu, Angka, Filter, dan Tombol:
* **Kartu Input Cepat Suara / Ketik Teks AI (`Update Status Stok Instan`):**
  * Karyawan cukup mengetik atau mendikte lewat mikrofon HP dengan bahasa sehari-hari, misalnya: *"Cabe rawit habis, bawang merah menipis, minyak kita aman"*, lalu klik **`[Proses Otomatis]`**. Sistem langsung mengubah status ketiga barang tersebut sekaligus tanpa perlu mencari satu per satu!
* **4 Kartu Angka Ringkasan Atas:** Menampilkan total komoditas terdaftar, berapa yang statusnya **Aman (Hijau)**, **Menipis (Kuning)**, dan **Habis (Merah)**.
* **Tombol `[+ Tambah Barang Baru]` (*Khusus Owner*):** Membuka form untuk mendaftarkan barang dagangan baru ke warung (mengisi nama barang, kategori A–I, satuan warung, tingkat kerawanan busuk 1–3, batas stok minimum, serta harga jual eceran/grosir).
* **Filter Tab Kategori & Filter Status (`Semua`, `Perlu Beli / Habis & Menipis`, `Aman`):** Memudahkan melihat barang mana saja yang sedang kosong di rak.
* **Tombol Status 1-Tap pada Setiap Barang (`[🟢 AMAN]` | `[🟡 MENIPIS]` | `[🔴 HABIS]`):**
  * Inilah fitur paling praktis bagi UMKM! Karyawan tidak wajib menimbang ulang seluruh 96 barang setiap malam. Cukup lihat rak: kalau tinggal sedikit klik **`[🟡 MENIPIS]`**, kalau kosong klik **`[🔴 HABIS]`**.
  * Saat tombol ini diklik, sistem otomatis mengirimkan sinyal darurat ke rumus **Kriteria C1 SAW** agar barang tersebut naik ke peringkat atas daftar belanja pasar besok pagi.
* **Tombol `[⚖️ Koreksi Fisik / Lapor Busuk]` (Modal Opname & Waste):**
  * Digunakan jika ada selisih timbangan nyata atau ada sayur/buah yang **busuk/layu/rusak dimakan tikus**.
  * Karyawan memasukkan jumlah yang rusak dan memilih alasannya (*Sayur Layu/Busuk*, *Susut Timbangan*, atau *Rusak Kemasan*). Sistem otomatis memotong stok fisik dan mencatat kerugian susut (*waste*) ke laporan Owner.

---

## 5. Halaman Kemas Ulang Eceran & Stok Etalase FIFO (`/inventory/production`)
* **File View:** [`resources/views/inventory/production.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/inventory/production.blade.php)
* **Controller:** [`InventoryController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/InventoryController.php)

### a) Halaman ini untuk siapa?
Untuk **Karyawan** dan **Owner**.

### b) Kapan halaman ini dipakai di warung?
Dipakai **pagi hari setelah barang pasar datang** (saat karyawan membungkusi cabai/bawang/tauge karungan menjadi bungkusan plastik kecil eceran atau mengikat ulang sayur untuk dipajang di meja depan) dan **malam hari** untuk mengecek sisa bungkusan yang belum terjual.

### c) Fungsi setiap Kartu, Angka, dan Tombol:
* **Kartu Form `Catat Kemas Ulang / Pajang Etalase Baru`:**
  * Memilih paket kemas ulang (misal: *Paket Sayur Sop Siap Masak*, *Cabai Rawit Plastik Eceran 1 Ons*, *Bawang Kupas Bungkus*) dan memasukkan jumlah bungkus yang dibuat.
  * Saat tombol **`[Simpan Kemas Ulang]`** ditekan, stok bahan curah di gudang otomatis berkurang, dan stok bungkusan siap jual di meja depan bertambah.
* **Daftar `Stok Etalase & Pemantauan FIFO Sisa Kemarin`:**
  * Menampilkan berapa bungkus stok yang dibuat **Hari Ini (Segar)** dan berapa bungkus **Sisa Kemarin (Wajib Jual Duluan / FIFO)**.
  * Jika ada sisa kemarin, kartu otomatis menyala kuning/merah sebagai peringatan agar dipajang paling depan.
* **Tombol `[Lapor Sisa / Rusak Malam Hari]`:** Jika sampai warung tutup pukul 23:00 WIB paket sayur potong tersebut layu dan tidak bisa dijual lagi besok, karyawan mencatatnya di sini agar stok direset bersih.

---

## 6. Halaman Scan Struk Pasar & Kulakan AI (`/purchases`)
* **File View:** [`resources/views/purchases/index.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/purchases/index.blade.php)
* **Controller:** [`PurchaseController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/PurchaseController.php)

### a) Halaman ini untuk siapa?
**KHUSUS OWNER.** Karyawan tidak memiliki akses ke halaman ini karena menyangkut rahasia harga modal kulakan dari tengkulak/pasar induk.

### b) Kapan halaman ini dipakai di warung?
Dipakai **pagi hari sepulang Owner belanja dari Pasar Induk (sekitar pukul 06:00–07:00 WIB)** atau saat sales sembako/agen gas datang mengantar barang ke warung.

### c) Fungsi setiap Kartu, Angka, dan Tombol:
* **Kartu Hijau `Estimasi Untung Bersih Hari Ini`:** Menampilkan perbandingan langsung antara pemasukan barang keluar hari ini dengan modal kulakan yang dikeluarkan.
* **Kotak `Scan Foto Nota Pasar / Ketik Cepat Kulakan AI`:**
  * **Tombol `[📷 Upload Foto Nota/Struk]`:** Owner cukup memfoto nota tulis tangan/struk cetakan dari pasar induk. AI Vision (Gemini) akan membaca tulisan di nota dan langsung mengisi tabel barang, jumlah, dan harganya secara otomatis!
  * **Kolom Ketik Cepat (NLP/Regex):** Jika di pasar tidak dapat nota (beli ke petani/pedagang sayur lesehan), Owner cukup mengetik kalimat singkat:
    `cabe rawit 5 kg 220000, bawang merah 10 kg 320000, telor 2 peti 840000`
    Lalu klik **`[✨ Ekstrak ke Tabel Kulakan]`**. Sistem otomatis memecahnya menjadi baris-baris tabel yang rapi beserta harga modal per satuannya!
* **Tabel Rincian Barang Kulakan & Perhitungan Otomatis `HPP Moving Average`:**
  * Menampilkan daftar barang yang dibeli, kuantitas masuk, dan harga beli total.
  * Saat tombol **`[SIMPAN NOTA KULAKAN & UPDATE STOK]`** ditekan, sistem melakukan **3 pekerjaan otomatis sekaligus**:
    1. Menambah jumlah stok fisik di gudang dan mengubah status barang menjadi **`🟢 AMAN`**.
    2. Menghitung ulang **Harga Pokok Penjualan Rata-Rata Tertimbang (*Moving Average Cost*)** — mencampurkan nilai sisa stok lama dengan harga kulakan baru hari ini agar perhitungan laba tetap akurat meskipun harga cabai/bawang di pasar naik-turun setiap hari.
    3. Menyimpan riwayat pengeluaran belanja pasar ke laporan keuangan.

---

## 7. Halaman Rekomendasi Belanja SPK Metode SAW & Laporan WA 23:00 (`/spk`)
* **File View:** [`resources/views/spk/index.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/spk/index.blade.php)
* **Controller:** [`SpkController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/SpkController.php) & [`WaReportController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/WaReportController.php)

### a) Halaman ini untuk siapa?
Untuk **Owner** (melihat nilai lengkap, margin, simulasi budget belanja pasar, dan matriks perhitungan SAW) serta **Karyawan** (melihat urutan barang prioritas yang perlu disiapkan dan menekan tombol **Kirim Laporan WA Tutup Warung pukul 23:00 WIB**).

### b) Kapan halaman ini dipakai di warung?
1. **Malam hari pukul 23:00 WIB saat tutup warung:** Karyawan membuka halaman ini untuk mengirim laporan ringkas stok habis & rekomendasi belanja ke WhatsApp Owner.
2. **Subuh hari pukul 04:30–05:30 WIB saat Owner di Pasar Induk:** Owner membuka halaman ini sebagai daftar contekan belanja agar tidak ada barang penting yang kelupaan dibeli.

### c) Fungsi setiap Kartu, Angka, Tab, dan Tombol:
* **Banner Penjelasan Bobot 4 Kriteria SAW:**
  * **C1 — Kekritisan Stok (Bobot 35% - Benefit):** Semakin tipis/habis stok di rak, semakin tinggi nilainya.
  * **C2 — Tingkat Cepat Busuk / *Perishability* (Bobot 30% - Benefit):** Sayur daun/cabai segar (skor 3) yang harus dibeli harian mendapat prioritas perputaran lebih tinggi dibanding barang awet (skor 1).
  * **C3 — Tingkat Kelarisan Harian (Bobot 20% - Benefit):** Diambil otomatis dari rata-rata jumlah barang keluar di menu `/pos` selama 7 hari terakhir.
  * **C4 — Margin Keuntungan (Bobot 15% - Benefit):** Diambil otomatis dari selisih harga jual dengan HPP Moving Average dari menu `/purchases`.
* **Kartu `Laporan Tutup Warung 23:00 WIB ke WhatsApp Owner`:**
  * **Tombol `[👁️ Preview Pesan WA]`:** Melihat isi teks laporan otomatis yang sudah disusun rapi oleh sistem (berisi daftar barang habis, barang menipis, peringatan FIFO besok pagi, dan Top 10 rekomendasi belanja SAW).
  * **Tombol Hijau `[📲 Kirim Laporan ke WA Owner Sekarang]`:** Membuka aplikasi WhatsApp (`wa.me`) secara langsung menuju nomor HP Owner dengan teks laporan yang sudah terketik otomatis—tinggal tekan *Send*!
* **Tab Navigasi (`[🏆 Perankingan Belanja SAW]`, `[📊 Peta Kuadran BCG]`, `[🧮 Detail Rumus & Normalisasi]`):**
  * **Tab 1 (Perankingan Belanja SAW):** Menampilkan urutan barang dari Peringkat #1, #2, #3 dst., lengkap dengan lencana **Prioritas Tinggi (Merah)**, **Sedang (Kuning)**, dan **Rendah (Hijau)**, saran jumlah kulakan, serta estimasi modal yang dibutuhkan.
  * **Simulasi Keranjang Belanja Pasar (*Khusus Owner*):** Owner bisa mencentang barang yang mau dibeli besok pagi, dan sistem langsung menjumlahkan **Total Estimasi Uang Tunai** yang harus dibawa ke pasar.
  * **Tombol `[+ Restock Cepat]`:** Memasukkan stok barang yang baru dibeli langsung dari daftar SPK.
  * **Tab 3 (Detail Rumus & Normalisasi):** Menampilkan tabel transparansi akademis (Matriks Keputusan $X_{ij}$, Matriks Normalisasi $R_{ij}$, hingga Nilai Preferensi Akhir $V_i$) untuk keperluan pembuktian saat sidang skripsi.

---

## 8. Halaman Klasifikasi Portofolio Barang Matriks BCG (`/analysis/klasifikasi`)
* **File View:** [`resources/views/analysis/klasifikasi.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/analysis/klasifikasi.blade.php)
* **Controller:** [`AnalysisController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/AnalysisController.php)

### a) Halaman ini untuk siapa?
**KHUSUS OWNER.**

### b) Kapan halaman ini dipakai di warung?
Dipakai **setiap akhir minggu atau akhir bulan** ketika Owner ingin mengevaluasi barang dagangan mana yang paling menghasilkan uang dan barang mana yang cuma memenuhi rak tapi tidak laku.

### c) Fungsi setiap Kartu, Angka, dan Tombol:
* **4 Kartu Kuadran Utama Matriks BCG:**
  1. **🌟 STAR / Bintang Warung (Laris Tinggi & Untung Tinggi):** Komoditas primadona warung (misal: Cabai Rawit, Bawang Merah, Telur). *Saran Sistem:* Stok tidak boleh sampai kosong sama sekali!
  2. **🐎 PLOWHORSE / Penarik Pembeli (Laris Tinggi & Untung Tipis):** Barang yang dicari semua orang setiap hari tapi untungnya tipis (misal: Beras, Minyak Kita, Gas LPG 3Kg, Gula). *Saran Sistem:* Tetap wajib sedia sebagai penarik pembeli datang ke warung, lalu tawarkan barang *Star* di sebelahnya.
  3. **🧩 PUZZLE / Potensial (Jarang Laku tapi Untung Besar):** Barang yang sekali laku untungnya tebal tapi pembelinya jarang (misal: Bumbu Rempah Khusus, Saus Botol Besar, Susu Kaleng). *Saran Sistem:* Jangan kulakan terlalu banyak agar modal tidak mengendap, pajang di tempat yang lebih terlihat.
  4. **🐕 DOG / Beban Gudang (Tidak Laris & Untung Kecil):** Barang yang lambat laku dan untungnya tipis. *Saran Sistem:* Kurangi jumlah kulakannya atau habiskan sisa stok lalu ganti dengan barang lain.
* **Tabel Rincian Komoditas + `Confidence Score (%)`:** Menampilkan posisi setiap barang beserta persentase tingkat keyakinan data dan saran tindakan bisnis otomatis dalam bahasa yang mudah dipahami.

---

## 9. Halaman Prediksi Permintaan 3 Hari ke Depan (`/analysis/prediksi`)
* **File View:** [`resources/views/analysis/prediksi.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/analysis/prediksi.blade.php)
* **Controller:** [`AnalysisController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/AnalysisController.php)

### a) Halaman ini untuk siapa?
**KHUSUS OWNER.**

### b) Kapan halaman ini dipakai di warung?
Dipakai saat merencanakan jumlah stok kulakan untuk beberapa hari ke depan (terutama menjelang akhir pekan atau hari ramai).

### c) Fungsi setiap Kartu, Angka, dan Tombol:
* **Perhitungan Regresi Linier Sederhana ($Y = a + bX$):** Menganalisis tren penjualan 7 hari terakhir untuk menebak apakah permintaan suatu barang sedang **Tren Naik (📈)**, **Stabil (➡️)**, atau **Tren Turun (📉)**.
* **Angka Prediksi Kebutuhan H+1, H+2, dan Total 3 Hari:** Memberitahu Owner berapa `kg`/`ikat`/`bungkus` yang kira-kira akan habis dalam 3 hari ke depan, dibandingkan dengan sisa stok di gudang saat ini.
* **Indikator `Rekomendasi Tambah Stok`:** Jika prediksi kebutuhan 3 hari lebih besar daripada sisa stok di gudang, sistem otomatis menghitungkan berapa kekurangannya yang harus dibeli di pasar.

---

## 10. Halaman Pusat Laporan Keuangan, Biaya Operasional & Export PDF (`/reports`)
* **File View:** [`resources/views/reports/index.blade.php`](file:///c:/laragon/www/STOKKUNAGA/resources/views/reports/index.blade.php)
* **Controller:** [`ReportController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/ReportController.php) & [`BiHubController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/BiHubController.php)

### a) Halaman ini untuk siapa?
**KHUSUS OWNER.**

### b) Kapan halaman ini dipakai di warung?
Dipakai setiap ada pengeluaran uang kas warung di luar belanja barang dagangan (misal beli plastik kresek, bayar listrik, uang bensin ke pasar) dan saat akhir minggu/bulan untuk mengunduh laporan PDF resmi.

### c) Fungsi setiap Kartu, Angka, dan Tombol:
* **Filter Tanggal (`Dari Tanggal` s/d `Sampai Tanggal`):** Memilih rentang waktu laporan keuangan yang ingin dilihat atau dicetak.
* **4 Kartu Ringkasan Laba Rugi (`Omzet Bruto`, `HPP Barang Terjual`, `Biaya Operasional & Susut`, `Laba Bersih Final`):** Rincian pembukuan warung secara utuh dan transparan.
* **Form `Catat Pengeluaran Operasional Warung`:** Tempat mencatat biaya plastik kresek, karet gelang, listrik/air, bensin motor ke pasar, atau gaji karyawan agar perhitungan Laba Bersih benar-benar riil.
* **3 Tombol Cetak Dokumen Resmi (`Export PDF`):**
  1. **`[📄 Unduh PDF Rekomendasi SPK SAW & BCG]`:** Mencetak daftar belanja pasar pagi dan peta kuadran barang dalam format PDF siap cetak.
  2. **`[📄 Unduh PDF Laporan Laba Rugi Keuangan]`:** Mencetak laporan pembukuan keuangan resmi Warung Sayur Adijaya (bisa dipakai juga sebagai lampiran pengajuan KUR Bank UMKM).
  3. **`[📄 Unduh PDF Laporan Nilai Aset & Stok Gudang]`:** Mencetak daftar seluruh 96 komoditas beserta sisa stok fisik dan nilai uang yang tersimpan di dalam barang dagangan.
* **Menu `BI Hub Analitik` (`Tren Penjualan`, `Ranking Komoditas`, `Analisis Kerugian Busuk/Waste`):** Grafik visual mendalam untuk melihat jam/hari paling ramai dan memantau berapa rupiah yang berhasil diselamatkan dari pengurangan sayur busuk.

---
---

# BAGIAN 2: CONTEKAN KALIMAT DEMO KE UMKM (OWNER & KARYAWAN WARUNG SAYUR ADIJAYA)

Gunakan naskah obrolan santai di bawah ini saat mendemokan aplikasi langsung di depan **Ibu/Bapak Pemilik Warung Sayur Adijaya** dan **Karyawannya**. *(Hindari kata-kata rumit seperti "Normalisasi Matriks", "Algoritma Regresi", atau "Moving Average"—ganti dengan bahasa dagang sehari-hari).*

---

### 🎬 TAHAP 1: Kalimat Pembuka (Menenangkan Hati Pemilik Warung)
> *"Bu/Pak, saya paham betul kalau di Warung Sayur Adijaya ini jam 7 sampai jam 11 pagi itu ibu-ibu komplek lagi ramai-ramainya ngerubung beli sayur. Kalau disuruh pakai mesin kasir ketik satu-satu kayak di Indomaret pas lagi antre, malah bikin pembeli kabur karena kelamaan.*
>
> *Makanya aplikasi **STOKKU** yang saya buatkan ini **BUKAN mesin kasir pembayaran**. Ibu dan karyawan tetap layani pembeli terima uang tunai seperti biasa. Aplikasi ini baru dipakai pas warung sudah agak senggang buat **ngontrol stok gudang, nyatat sayur yang hampir layu biar kejual duluan, ngitung untung bersih otomatis, sama ngasih contekan daftar belanja ke pasar induk** biar nggak ada barang penting yang kelupaan."*

---

### 🎬 TAHAP 2: Demo Pemisahan Akun (Menunjukkan Keamanan Rahasia Dapur Owner)
*(Buka halaman `/login`, klik tombol **`[👷 Login sebagai Karyawan]`** dulu, lalu tunjukkan ke Owner)*
> *"Pertama, Bu, di sini saya buatkan 2 pintu masuk yang beda: satu buat **Ibu sebagai Bos/Owner**, satu lagi buat **Karyawan jaga warung**.*
> *Coba Ibu lihat kalau yang login ini Karyawan: tampilannya bersih, **nggak ada angka uang sama sekali**. Karyawan nggak bakal tahu harga modal kulakan Ibu di pasar induk berapa, dan nggak tahu untung bersih Ibu sehari berapa. Tugas karyawan di HP ini murni cuma urusan fisik barang di rak."*

*(Lalu Logout, klik **`[👑 Login sebagai Owner]`**, tunjukkan Dashboard ala GoPay)*
> *"Nah, sekarang kita masuk pakai akun Ibu sendiri. Tampilannya kayak aplikasi GoPay yang biasa kita pakai sehari-hari, Bu. Begitu buka HP, di kartu paling atas Ibu langsung tahu hari ini **Untung Bersihnya berapa rupiah**, barang yang lagi habis ada berapa, dan barang nomor 1 yang paling wajib dibeli besok subuh itu apa."*

---

### 🎬 TAHAP 3: Demo Rutinitas Pagi Sepulang dari Pasar Induk (Fitur Kulakan AI `/purchases`)
*(Klik ikon **`Kulakan AI`** di Dashboard)*
> *"Misalnya jam 6 pagi Ibu baru pulang kulakan dari Pasar Induk. Biasanya kan capek, Bu, malas ngitung-ngitung nota. Di sini Ibu nggak perlu pusing:*
> *Kalau ada struk/nota tulis tangan, tinggal difoto pakai tombol kamera ini.*
> *Atau kalau beli cabe sama petani di pasar nggak pakai nota, Ibu tinggal ketik biasa aja di kotak ini kayak kirim chat WA: **`cabe rawit 5 kg 220000, bawang merah 10 kg 320000`**, terus klik tombol **Ekstrak**. Lihat Bu, aplikasinya langsung pintar mecah sendiri ke tabel! Harga modal kemarin yang murah sama harga modal hari ini yang lagi naik langsung dirata-rata otomatis sama sistem, jadi hitungan untung Ibu nggak bakal meleset."*

---

### 🎬 TAHAP 4: Demo Kemas Ulang & Peringatan Sayur Sisa Kemarin (Fitur FIFO `/inventory/production` & `/pos`)
*(Klik menu **`Kemas Ulang`**, lalu pindah ke **`Barang Keluar`**)*
> *"Sering kan Bu, sayur atau cabe eceran yang dibungkus kemarin ketumpuk sama barang baru hari ini, tahu-tahu pas dicek di belakang rak sudah busuk? Nah, setiap karyawan bungkusin eceran pagi-pagi, dicatat di menu **Kemas Ulang** ini.*
> *Nanti kalau sampai besok masih ada sisa yang belum laku, di layar **Barang Keluar** bakal muncul **kotak peringatan warna kuning menyala** paling atas: 'Awas, masih ada sisa kemarin, taruh paling depan meja dan jual duluan!'. Jadi nggak ada lagi cerita rugi gara-gara sayur kelupaan di pojokan rak."*

---

### 🎬 TAHAP 5: Demo Siang/Sore Pasca Jam Sibuk (Barang Keluar `/pos` & Cek Stok 1-Tap `/inventory/materials`)
*(Tunjukkan halaman **`Barang Keluar`** lalu pindah ke **`Cek Stok 1-Tap`**)*
> *"Terus gimana cara nyatat barang yang laku tanpa bikin antrean macet? Gampang Bu. Pas jam 11 siang ibu-ibu sudah pada pulang masak, baru karyawan buka menu **Barang Keluar** ini, tinggal klik barang apa saja yang tadi keluar dan simpan.*
>
> *Atau yang paling praktis pas sore mau malam: karyawan nggak perlu nimbang ulang seluruh 96 barang satu-satu. Cukup buka menu **Cek Stok 1-Tap**, keliling lihat rak sayur sambil bawa HP: lihat tempat cabe tinggal dikit, tinggal pencet tombol kuning **`[MENIPIS]`**. Lihat tempat sawi kosong, pencet tombol merah **`[HABIS]`**. Bahkan bisa ngomong pakai suara lewat mikrofon HP! Kalau ada tomat yang penyok/busuk 2 ons, tinggal klik tombol **Lapor Busuk** biar stok di aplikasi sama stok asli di warung selalu cocok 100%."*

---

### 🎬 TAHAP 6: Demo Tutup Warung Jam 23:00 WIB & Belanja Subuh (SPK SAW `/spk` & WA Otomatis)
*(Buka menu **`SPK SAW`** lalu klik **`[👁️ Preview Pesan WA]`**)*
> *"Nah, ini bagian favoritnya Bu. Jam 11 malam pas warung mau tutup, karyawan tinggal pencet satu tombol hijau **`[Kirim Laporan ke WA Owner]`** ini.*
> *HP Ibu di kamar langsung bunyi terima pesan WA rapi yang isinya: daftar barang yang habis malam ini, barang yang menipis, sama **Urutan Prioritas Belanja Pasar Besok Subuh** yang sudah dihitungkan sama aplikasi.*
> *Jadi pas jam setengah 5 subuh Ibu berangkat ke Pasar Induk dan uang tunai di dompet lagi pas-pasan, Ibu nggak bingung lagi mau dahulukan beli yang mana—tinggal ikuti urutan nomor 1, 2, 3 dari aplikasi karena itu sudah dihitung dari barang yang paling habis, paling cepat laku, dan paling gede untungnya buat warung Ibu!"*

---
---

# BAGIAN 3: CONTEKAN JAWABAN SIDANG SKRIPSI (ALUR KETERKAITAN DATA END-TO-END)

Bagian ini dirancang khusus sebagai amunisi jawaban lugas, terstruktur, dan ilmiah saat Dosen Penguji bertanya:
> **❓ Pertanyaan Penguji:** *"Aplikasi kamu ini kan tidak pakai mesin kasir pembayaran pelanggan (non-POS payment). Lalu dari mana sistem kamu bisa mendapatkan angka untuk menghitung Kriteria SAW (terutama tingkat kelarisan C3 dan margin C4), mengelompokkan Matriks BCG, sampai menghasilkan Laporan Laba Bersih untuk Owner?"*

---

### 💡 Jawaban Pamungkas Sidang (Jelaskan dengan Urutan Siklus Data 5 Titik Berikut):

> *"Terima kasih Bapak/Ibu Penguji. Meskipun sistem **STOKKU ERP** pada Warung Sayur Adijaya tidak menggunakan mesin kasir pembayaran pelanggan di depan (untuk menghindari bottleneck antrean pada jam sibuk 07:00–11:00 WIB), seluruh variabel perhitungan **SPK SAW**, **Matriks BCG**, **Regresi Linier**, dan **Laba Rugi** tetap terhubung secara *end-to-end* dari **4 sumber pencatatan internal gudang** yang bermuara pada **1 mesin kalkulasi terpusat**:"*

```
[1. NOTA KULAKAN (/purchases)] ──► Menghasilkan: Stok Awal + HPP Moving Average
         │
         ▼
[2. BARANG KELUAR (/pos)]      ──► Menghasilkan: Volume Kelarisan Harian + Estimasi Omzet
         │
         ▼
[3. CEK STOK 1-TAP & OPNAME]   ──► Menghasilkan: Sisa Stok Aktual + Kerugian Susut (Waste)
         │
         ▼
╔══════════════════════════════════════════════════════════════════════════╗
║               4. MESIN KALKULASI SPK SAW, BCG & REGRESI                  ║
║ • C1 (Kekritisan Stok 35%)  ◄── Dari Sisa Stok vs Min Stock & Status 1-Tap║
║ • C2 (Perishability 30%)    ◄── Dari Skor Kerawanan Busuk Komoditas (1-3) ║
║ • C3 (Kelarisan 20%)        ◄── Dari Akumulasi Kuantitas Keluar (/pos)    ║
║ • C4 (Margin Untung 15%)    ◄── Dari (Harga Jual Master - HPP Moving Avg) ║
╚══════════════════════════════════════════════════════════════════════════╝
         │
         ▼
[5. OUTPUT KEPUTUSAN: Ranking Belanja SAW + WA 23:00 + Matriks BCG + Laba Bersih PDF]
```

#### Rincian Keterkaitan Data per Variabel:

1. **Asal Data HPP & Harga Modal (`Kriteria C4 SAW` & `HPP Laba Rugi`):**
   * Saat Owner menginput nota belanja pasar pagi di modul **Kulakan (`/purchases`)**, sistem menghitung **HPP Rata-Rata Tertimbang (*Moving Average Cost*)** dengan rumus:
     $$\text{HPP Baru} = \frac{(\text{Stok Lama} \times \text{HPP Lama}) + (\text{Jumlah Beli Baru} \times \text{Harga Beli Baru})}{\text{Stok Lama} + \text{Jumlah Beli Baru}}$$
   * Dalam tabel master komoditas (`ingredients`), setiap barang sudah memiliki **Harga Jual Standar Warung** (Eceran, Langganan, Grosir).
   * Maka **Kriteria C4 (Margin Keuntungan - Bobot 15%)** dan **Sumbu Margin pada Matriks BCG** didapat secara otomatis dari selisih:
     $$\text{Margin per Satuan} = \text{Harga Jual Standar} - \text{HPP Moving Average}$$

2. **Asal Data Tingkat Kelarisan & Tren Penjualan (`Kriteria C3 SAW`, `Sumbu Volume BCG`, & `Regresi Linier`):**
   * Pasca jam sibuk (pukul 11:00 dan 17:30 WIB), karyawan merekap jumlah fisik barang yang keluar/terjual pada modul **Pencatatan Barang Keluar (`/pos`)**.
   * Akumulasi kuantitas fisik barang yang keluar per hari ini disimpan ke dalam tabel transaksi (`transaction_items` dan `stock_movements`).
   * Data kuantitas keluar selama 7–30 hari terakhir inilah yang langsung ditarik oleh sistem menjadi:
     * Nilai **Kriteria C3 SAW (Tingkat Kelarisan - Bobot 20%)**.
     * Nilai **Sumbu Vertikal (Volume Penjualan)** pada **Matriks BCG**.
     * Variabel deret berkala ($Y$) pada rumus **Regresi Linier ($Y = a + bX$)** untuk memprediksi permintaan 3 hari ke depan.

3. **Asal Data Kekritisan Stok & Kerawanan Busuk (`Kriteria C1 & C2 SAW`):**
   * Pemotongan stok dari `/pos`, ditambah klik tombol **Cek Stok 1-Tap (`Aman`/`Menipis`/`Habis`)** dan **Koreksi Busuk (`/inventory/materials`)** pada malam hari menghasilkan **Sisa Stok Aktual (`stock`)**.
   * Sistem membandingkan Sisa Stok Aktual terhadap Batas Stok Minimum (`min_stock`) serta status 1-Tap untuk menghasilkan skor **Kriteria C1 SAW (Kekritisan Stok - Bobot 35%)**—semakin habis stoknya, semakin mendekati nilai maksimal.
   * Sedangkan **Kriteria C2 SAW (Tingkat Cepat Busuk / *Perishability* - Bobot 30%)** diambil dari atribut karakteristik alami komoditas pada database (`perishability_score`: Skor `3` untuk sayur daun/cabai segar, Skor `2` untuk umbi/bumbu rimpang, Skor `1` untuk sembako/kemasan).

4. **Asal Data Estimasi Omzet & Laba Bersih Owner (`/dashboard` & `/reports`):**
   * Meskipun tidak mencetak struk ke pembeli, setiap kali jumlah barang keluar dicatat di `/pos`, sistem mengalikan **Kuantitas Fisik Keluar $\times$ Harga Jual Standar Warung** sebagai **Estimasi Omzet Bruto**, dan mengalikan **Kuantitas Fisik Keluar $\times$ HPP Moving Average** sebagai **Beban Pokok Penjualan (COGS)**.
   * Setelah dikurangi **Nilai Kerugian Barang Busuk (*Waste*)** dari `/inventory/materials` dan **Biaya Operasional Harian** (plastik, listrik, bensin) dari `/reports`, sistem menyajikan **Estimasi Laba Bersih Riil** yang akurat bagi Owner.

