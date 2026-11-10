# 📋 PRODUCT REQUIREMENTS DOCUMENT (PRD) — VERSI FINAL
## STOKKU ERP: Sistem Informasi Manajemen Inventori Gudang, Pengendalian FIFO & SPK Rekomendasi Belanja Komoditas Warung Sayur Adijaya

| Informasi Dokumen | Detail Spesifikasi |
| :--- | :--- |
| **Nama Aplikasi** | **STOKKU ERP** (*Smart Inventory & Decision Support System*) |
| **Objek Studi Kasus** | **Warung Sayur Adijaya** (UMKM Ritel Bahan Pangan Segar & Sembako Harian) |
| **Kategori Sistem** | *Internal Warehouse Inventory Management, FIFO Control, Accounting & Decision Support System (Non-Cashier Payment System)* |
| **Framework & Teknologi** | Laravel 12 (PHP 8.2+), MySQL/SQLite, Tailwind CSS, Alpine.js, Chart.js, DomPDF, Gemini AI Vision/NLP (dengan Regex Fallback), WhatsApp Direct API (`wa.me`) |
| **Konsep UI/UX Utama** | *Mobile-First GoPay-Inspired Layout* (`max-w-[430px]` dengan Sticky Top Emerald Header di Home, Curved White Sheet `rounded-t-[32px]`, dan Clean Sub-Page Headers) |
| **Status Dokumen** | **100% SINKRON DENGAN SOURCE CODE FINAL** |

---

## 1. LATAR BELAKANG & POSISI SISTEM (SYSTEM POSITIONING)

### 1.1 Masalah Utama di Warung Sayur Adijaya
1. **Tingginya Risiko Kerugian Sayur Busuk (*Perishable Waste*):** Komoditas sayur daun, cabai, dan bumbu basah memiliki umur simpan pendek (1–3 hari). Tanpa pencatatan stok kemas ulang kemarin (*First-In First-Out* / FIFO), stok lama sering tertumpuk stok baru hingga layu dan terbuang.
2. **Keputusan Belanja Pasar Subuh Masih Mengandalkan Kira-Kira (*Intuisi*):** Owner berangkat ke Pasar Induk pukul 04:30 WIB tanpa daftar prioritas berbasis data. Akibatnya, barang yang sedang laris dan bermargin tinggi sering lupa dibeli (*stockout*), sementara barang yang lambat laku justru menumpuk (*overstock*).
3. **Harga Modal Pasar Fluktuatif:** Harga cabai, bawang, dan telur di pasar induk berubah setiap hari. Pencatatan manual tidak mampu menghitung Harga Pokok Penjualan (HPP) rata-rata tertimbang secara akurat.
4. **Hambatan Operasional Jam Sibuk:** Warung mengalami puncak antrean pembeli pada pagi (**07:00–11:00 WIB**) dan sore (**14:00–17:30 WIB**). Penggunaan mesin kasir pembayaran pelanggan (*Point of Sale Payment*) pada jam tersebut justru memperlambat transaksi tunai.

### 1.2 Solusi & Batasan Posisi Sistem
**STOKKU ERP BUKAN sistem pemesanan pelanggan atau mesin kasir pembayaran (*non-payment gateway*).**
Sistem ini dirancang khusus sebagai **Aplikasi Internal Kontrol Gudang, Rekap Barang Keluar Pasca-Jam Sibuk, Perhitungan HPP Moving Average, SPK Belanja Pasar Metode SAW, Analisis Portofolio Matriks BCG, dan Pelaporan Otomatis Tutup Warung pukul 23:00 WIB ke WhatsApp Owner.**

---

## 2. PEMBAGIAN HAK AKSES PENGGUNA (ROLE-BASED ACCESS CONTROL)

Sistem menerapkan pembagian hak akses kolaboratif antara **Owner (`admin`)** dan **Karyawan Gudang (`gudang`/`kasir`)** untuk memastikan kelancaran operasional warung:

| Modul / Fitur | Owner (`owner@stokku.com`) | Karyawan (`karyawan@stokku.com`) | Keterangan Hak Akses |
| :--- | :---: | :---: | :--- |
| **Home Dashboard (`/dashboard`)** | ✅ Mode Finansial + Gudang | ✅ Mode Operasional Gudang | Karyawan melihat ringkasan status stok rak, quick action, dan daftar barang darurat. |
| **Cek Stok 1-Tap & Koreksi (`/inventory/materials`)** | ✅ Penuh (+ Visibilitas Modal) | ✅ Cek 1-Tap (`Aman`/`Menipis`/`Habis`), Koreksi Angka & Tambah Barang | Karyawan & Owner dapat cek stok fisik, lapor susut/busuk, dan tambah komoditas baru. |
| **Belanja Pasar & Nota AI (`/purchases` & `/inventory/shopping`)** | ✅ Penuh (+ Saran Harga Jual) | ✅ Input Belanja, Upload Struk & Tambah Barang Baru | Karyawan & Owner dapat mencatat hasil belanja pasar subuh, upload foto struk, dan restock otomatis. |
| **Barang Keluar & FIFO (`/pos`)** | ✅ Kuantitas + Nilai Finansial | ✅ Input Kuantitas Fisik Keluar | Mengurangi stok etalase & gudang, serta otomatis menghitung omzet penjualan harian. |
| **Pusat Laporan & Omset (`/reports`)** | ✅ Penuh (+ Cetak PDF & BI Hub) | ✅ Lihat Omset Kotor/Bersih & Input Beban Operasional | Transparansi kinerja omzet dan beban harian warung. |
| **Rekomendasi SPK SAW & WA 23:00 (`/spk`)** | ✅ Penuh (+ Simulasi Budget & Bobot) | ✅ Lihat Prioritas & Kirim WA 23:00 | Karyawan menekan tombol Kirim WA Tutup Warung pukul 23:00 WIB ke nomor Owner. |
| **Analisis Matriks BCG (`/analysis/klasifikasi`)** | ✅ **Eksklusif Owner** | 🔒 Fitur Khusus Owner | Evaluasi kuadran *Star, Plowhorse, Puzzle, Dog*. |
| **Prediksi Regresi Linier (`/analysis/prediksi`)** | ✅ **Eksklusif Owner** | 🔒 Fitur Khusus Owner | Peramalan kebutuhan stok 3 hari ke depan ($Y = a + bX$). |

---

## 3. KLASIFIKASI 96 KOMODITAS ASLI WARUNG SAYUR ADIJAYA & SATUAN STANDAR

Seluruh database ([`DatabaseSeeder.php`](file:///c:/laragon/www/STOKKUNAGA/database/seeders/DatabaseSeeder.php)) dan antarmuka aplikasi telah distandarisasi 100% menggunakan **96 komoditas riil Warung Sayur Adijaya** (tanpa istilah rumah makan seperti *"Porsi"*, *"Stok Matang"*, atau *"Resep"*):

1. **Kategori A — Sayur & Buah Segar (`perishability_score = 3` / Cepat Busuk):**
   * *Sayuran Buah/Umbi (`kg` / `buah`):* Terong (`kg`), Jagung Manis (`kg`), Buncis (`kg`), Labu Siam (`buah`), Labu Kuning (`kg`), Kol (`kg`), Sawi Putih (`kg`), Tomat (`kg`), Mentimun (`kg`).
   * *Sayuran Daun (`ikat` / `ons`):* Sawi Hijau (`ikat`), Selada (`ikat`), Daun Kemangi (`ikat`), Seledri (`ikat`), Daun Bawang (`ikat`), Tauge / Toge (`ons`).
   * *Buah-buahan (`kg` / `sisir` / `ons` / `butir`):* Alpukat (`kg`), Pisang (`sisir`), Mangga (`kg`), Jeruk Makan (`kg`), Jeruk Nipis (`ons`), Kelapa (`butir`).
2. **Kategori B — Bumbu Dapur & Rempah (`perishability_score = 1 s/d 3`):**
   * *Bawang & Cabai (`kg` - Skor 2 & 3):* Bawang Merah (`kg`), Bawang Putih (`kg`), Bawang Bombay (`kg`), Cabai Kecil / Rawit (`kg`), Cabai Keriting (`kg`), Cabai Besar (`kg`).
   * *Rimpang & Daun Rempah (`ons` / `ikat` / `bungkus` - Skor 2 & 3):* Jahe (`ons`), Lengkuas (`ons`), Kunyit (`ons`), Kencur (`ons`), Serai (`ikat`), Daun Salam (`ikat`), Daun Jeruk (`ons`), Asam Jawa (`bungkus`), Kemiri (`ons`), Ketumbar (`bungkus`), Merica / Lada (`bungkus`).
   * *Bumbu Kemasan Pabrik (`renceng` / `bungkus` / `pouch` - Skor 1):* Royco (`renceng`), Masako (`renceng`), Sajiku Tepung Bumbu (`bungkus`), Kobe Tepung Bumbu (`bungkus`), Sasa MSG (`bungkus`), Santan Kara (`bungkus`), Desaku Bumbu Instan (`renceng`), Racik Indofood (`bungkus`), Saori Saus Tiram (`pouch`).
3. **Kategori C — Minyak Goreng, Kecap & Saus (`perishability_score = 1`):**
   * Minyak Kita (`pouch`), Minyak Sunco (`pouch`), Minyak Bimoli (`pouch`), Kecap Bango (`pouch`), Kecap ABC (`pouch`), Kecap Sedap (`pouch`), Saus ABC (`botol`), Saus Indofood (`botol`), Sambal Indofood (`botol`), Mayonaise Maestro (`pouch`), Blueband Margarin (`bungkus`).
4. **Kategori D — Beras, Gula, Telur & Bahan Pokok (`perishability_score = 1 & 2`):**
   * Beras Medium/Premium (`kg`), Gula Pasir (`kg`), Gula Merah / Jawa (`kg`), Telur Ayam Negeri (`kg`), Telur Puyuh (`butir`), Tepung Terigu Segitiga Biru (`kg`), Tepung Beras Rose Brand (`bungkus`), Tepung Tapioka / Kanji (`bungkus`), Garam Dapur (`bungkus`), Bihun Jagung (`bungkus`), Soun (`bungkus`), Makaroni (`ons`), Kerupuk Mentah (`ons`).
5. **Kategori E — Mie Instan (`perishability_score = 1`):**
   * Indomie Goreng (`bungkus`), Indomie Soto/Ayam Bawang (`bungkus`), Mie Sedaap Goreng/Kuah (`bungkus`), Sarimi Isi 2 (`bungkus`), Pop Mie Cup (`buah`), Mie Eko / Mie Telur (`bungkus`).
6. **Kategori F — Minuman, Kopi, Teh & Susu (`perishability_score = 1`):**
   * Kopi Kapal Api (`renceng`), Kopi ABC Susu (`renceng`), Good Day (`renceng`), Luwak White Koffie (`renceng`), Teh Sariwangi (`kotak`), Teh Poci (`bungkus`), Teh Pucuk Harum (`botol`), Susu Frisian Flag Kental Manis (`kaleng` & `renceng`), Susu Indomilk (`kaleng`), Energen Sereal (`renceng`), Nutrisari (`renceng`).
7. **Kategori G — Gas, Air Galon & Bahan Bakar (`perishability_score = 1`):**
   * Gas LPG 3 Kg (`tabung`), Air Galon Aqua (`galon`), Air Galon Le Minerale (`galon`), Air Mineral Gelas/Botol (`dus`).
8. **Kategori H — Sabun & Kebutuhan Rumah Tangga (`perishability_score = 1`):**
   * Sunlight Cuci Piring (`pouch`), Mama Lemon (`pouch`), Rinso Deterjen (`renceng`), So Klin Liquid (`renceng`), Molto Pewangi (`renceng`), Sabun Mandi Lifebuoy/Nuvo (`buah`), Shampo Pantene/Clear (`renceng`), Pepsodent Pasta Gigi (`buah`), Baygon / Obat Nyamuk (`kaleng`), Tisu Wajah (`bungkus`).
9. **Kategori I — Kemasan & Operasional Warung (`perishability_score = 1`):**
   * Kantong Plastik Kresek Hitam/Putih (`pack`), Plastik Es / Gula (`pack`), Karet Gelang (`ons`), Kertas Bungkus Nasi (`pack`).

---

## 4. SPESIFIKASI DESAIN UI/UX (GOPAY-INSPIRED MOBILE LAYOUT)

1. **Header Hijau Sticky/Fixed Khusus Halaman Utama (`/dashboard`):**
   * Menggunakan kelas `fixed top-0 left-0 right-0 z-50 w-full max-w-[430px] mx-auto md:max-w-none md:sticky md:top-16 bg-[#0D9488]` sehingga saat pengguna menggeser layar ke bawah, header hijau tetap menempel di atas.
   * Menampilkan identitas `STOKKU ERP [OWNER/KARYAWAN]`, filter waktu minimalis (`Hari Ini`, `Kemarin`, `Minggu Ini`, `Bulan Ini`), ikon notifikasi, tombol keluar, dan badge ringkas `Jam Sibuk Warung: 07:00–11:00 & 14:00–17:30 WIB`.
2. **Container Putih Melengkung (`Curved White Sheet`):**
   * Kontainer utama di bawah header menggunakan `relative z-20 -mt-10 sm:-mt-12 bg-white rounded-t-[32px] shadow-lg min-h-screen` yang melengkung halus ke dalam dan bergerak mulus menutupi header saat di-scroll.
   * Memuat **Wallet Card Header**, **Grid Menu 8 Ikon (4x2)**, **Horizontal Circular Shortcuts (Supplier & Langganan)**, **Kartu Spesial Untuk Owner (Top #1 SAW & Status Gudang)**, serta **Eksplorasi Stok (Search Bar + Filter Tab Kategori A–I)**.
3. **Halaman Sub-Menu Bersih Tanpa Header Hijau Duplikat:**
   * Seluruh halaman tab lain (`/pos`, `/inventory/materials`, `/inventory/production`, `/purchases`, `/spk`, `/analysis/klasifikasi`, `/analysis/prediksi`, `/reports`) dimulai langsung dengan latar bersih (`bg-[#F8FAFC]`) dan header kartu putih minimalis di bawah status bar ponsel.

---

## 5. SPESIFIKASI ALGORITMA & RUMUS MATEMATIS INTI

### 5.1 Perhitungan HPP Rata-Rata Tertimbang (*Moving Average Cost*)
Setiap kali nota kulakan baru disimpan di [`PurchaseController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/PurchaseController.php):
$$\text{HPP}_{\text{baru}} = \frac{(\text{Stok}_{\text{lama}} \times \text{HPP}_{\text{lama}}) + (\text{Qty}_{\text{beli}} \times \text{Harga}_{\text{beli}})}{\text{Stok}_{\text{lama}} + \text{Qty}_{\text{beli}}}$$

### 5.2 Sistem Pendukung Keputusan (SPK) Metode SAW (*Simple Additive Weighting*)
Dihitung secara *real-time* di [`SpkController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/SpkController.php) menggunakan 4 kriteria bertipe **Benefit** (semakin tinggi nilai kriteria, semakin mendesak barang tersebut untuk dibeli di pasar pagi):

| Kode | Nama Kriteria | Bobot ($W_j$) | Tipe | Sumber Data Otomatis di Aplikasi |
| :---: | :--- | :---: | :---: | :--- |
| **C1** | **Tingkat Kekritisan Stok** | **35% (0.35)** | *Benefit* | Rasio defisit sisa stok (`stock`) terhadap batas minimum (`min_stock`) + status 1-Tap (`Habis`/`Menipis`/`Aman`). |
| **C2** | **Tingkat Cepat Busuk (*Perishability*)** | **30% (0.30)** | *Benefit* | Skor kerawanan busuk komoditas (`3` = Sayur/Cabai Segar, `2` = Bumbu/Telur, `1` = Sembako/Kemasan). |
| **C3** | **Tingkat Kelarisan Harian** | **20% (0.20)** | *Benefit* | Total kuantitas fisik barang keluar dari modul `/pos` dalam periode berjalan. |
| **C4** | **Margin Keuntungan** | **15% (0.15)** | *Benefit* | Selisih Harga Jual Standar Warung dikurangi HPP Moving Average ($\text{Harga Jual} - \text{HPP}$). |

* **Rumus Normalisasi Matriks ($R_{ij}$ untuk Kriteria Benefit):**
  $$R_{ij} = \frac{X_{ij}}{\max_i(X_{ij})}$$
* **Rumus Nilai Preferensi Akhir ($V_i$):**
  $$V_i = \sum_{j=1}^{4} W_j \cdot R_{ij} = (0.35 \cdot R_{i1}) + (0.30 \cdot R_{i2}) + (0.20 \cdot R_{i3}) + (0.15 \cdot R_{i4})$$

### 5.3 Klasifikasi Portofolio Komoditas Matriks BCG (*Boston Consulting Group*)
Dihitung di [`AnalysisController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/AnalysisController.php) dengan membandingkan setiap komoditas terhadap nilai rata-rata/median populasi warung pada 2 sumbu:
* **Sumbu X:** Margin Keuntungan per Unit ($\text{Harga Jual} - \text{HPP Moving Average}$).
* **Sumbu Y:** Volume Penjualan Fisik (Kuantitas Barang Keluar).
* **4 Kuadran Klasifikasi:**
  1. **STAR (Bintang Warung):** Volume Tinggi & Margin Tinggi -> *Prioritas stok utama, pantang kosong.*
  2. **PLOWHORSE (Penarik Pembeli):** Volume Tinggi & Margin Rendah -> *Barang pancingan trafik pembeli harian, jaga ketersediaan.*
  3. **PUZZLE (Potensial):** Volume Rendah & Margin Tinggi -> *Beli secukupnya agar modal tidak mandek, letakkan di etalase depan.*
  4. **DOG (Beban Gudang):** Volume Rendah & Margin Rendah -> *Kurangi kuota kulakan atau hentikan stok.*

### 5.4 Prediksi Permintaan Metode Regresi Linier Sederhana
Dihitung di [`AnalysisController.php`](file:///c:/laragon/www/STOKKUNAGA/app/Http/Controllers/AnalysisController.php) berdasarkan deret waktu (*time-series*) kuantitas barang keluar 7 hari terakhir ($X = 1, 2, \dots, 7$):
$$Y = a + bX$$
$$b = \frac{n\sum(XY) - \sum X \sum Y}{n\sum(X^2) - (\sum X)^2}, \quad a = \frac{\sum Y - b\sum X}{n}$$
Digunakan untuk memproyeksikan kebutuhan stok pada hari ke-8, ke-9, dan ke-10 (3 hari ke depan).

---

## 6. ALUR OPERASIONAL HARIAN WARUNG SAYUR ADIJAYA (05:00 – 23:00 WIB)

1. **Pukul 04:30 – 06:00 WIB (Belanja Pasar Induk):** Owner membuka pesan WA laporan malam atau halaman **SPK SAW (`/spk`)** di HP untuk melihat daftar prioritas barang yang wajib dibeli beserta simulasi modal.
2. **Pukul 06:00 – 07:00 WIB (Barang Datang & Kemas Ulang):**
   * Owner memfoto nota pasar atau mengetik cepat di **Kulakan AI (`/purchases`)** -> Stok gudang bertambah & HPP Moving Average terupdate otomatis.
   * Karyawan membungkusi eceran/mengikat sayur untuk meja depan dan mencatatnya di **Kemas Ulang (`/inventory/production`)**.
3. **Pukul 07:00 – 11:00 WIB (Jam Sibuk Pagi):** Pelayanan pembeli dilakukan secara tunai cepat tanpa gangguan ketik kasir. Karyawan memprioritaskan penjualan bungkus eceran sisa kemarin yang ditandai peringatan **FIFO**.
4. **Pukul 11:00 – 14:00 WIB (Senggang Siang):** Karyawan merekap jumlah barang yang keluar pagi tadi pada halaman **Barang Keluar (`/pos`)**.
5. **Pukul 14:00 – 17:30 WIB (Jam Sibuk Sore):** Pelayanan pembeli sore hari.
6. **Pukul 17:30 – 22:30 WIB (Rekap Sore & Cek Rak 1-Tap):** Karyawan merekap barang keluar sore di **`/pos`**, lalu mengecek rak sayur menggunakan tombol **1-Tap (`Aman` / `Menipis` / `Habis`)** dan mencatat sayur busuk/susut di **`/inventory/materials`**.
7. **Pukul 23:00 WIB (Tutup Warung):** Karyawan membuka halaman **`/spk`** dan menekan tombol **`[📲 Kirim Laporan ke WA Owner]`**. Ringkasan stok habis, peringatan FIFO besok pagi, dan Top 10 rekomendasi belanja SAW langsung terkirim ke WhatsApp Owner.

