# 🗺️ DOKUMENTASI VISUAL: FLOWCHART & DIAGRAM SISTEM STOKKU ERP
## Studi Kasus: Warung Sayur Adijaya (96 Komoditas Segar & Sembako)

> **💡 Kunci Utama Agar Tidak Bingung Memahami Proyek Ini:**
> Bayangkan aplikasi **STOKKU ERP** ini seperti **Buku Catatan Gudang Pintar + Asisten Belanja Pasar**.
> * Aplikasi ini **BUKAN mesin kasir Indomaret** (tidak dipakai saat ibu-ibu sedang antre bayar sayur di jam sibuk pagi 07:00–11:00 WIB).
> * **Karyawan** hanya bertugas mencatat **fisik barang** (barang yang dikemas ulang, barang yang keluar/laku, barang yang habis di rak, dan sayur yang busuk), lalu jam 23:00 malam klik tombol **Kirim WA ke Owner**.
> * **Owner** hanya bertugas memasukkan **nota belanja pasar pagi** dan memantau **Untung Bersih, Urutan Belanja SAW, serta Klasifikasi Barang BCG**.

---

## 1. PETA KONSEP "1 HALAMAN" (BIG PICTURE STOKKU ERP)

Diagram blok paling sederhana ini menjawab pertanyaan: **"Dari mana data masuk, diproses di mana, dan menghasilkan apa?"**

```mermaid
flowchart LR
    subgraph INPUT["📥 1. DARI MANA DATA MASUK? (Input Harian)"]
        direction TB
        I1["👑 OWNER (Pagi 06:00)\nFoto Struk / Ketik Nota Pasar\ndi Menu Kulakan AI (/purchases)"]
        I2["👷 KARYAWAN (Pagi 06:30)\nCatat Bungkus Eceran Baru\ndi Menu Kemas Ulang (/inventory/production)"]
        I3["👷 KARYAWAN (Siang 11:00 & Sore 17:30)\nCatat Jumlah Barang Keluar\ndi Menu Barang Keluar (/pos)"]
        I4["👷 KARYAWAN (Malam 22:00)\nKlik 1-Tap (Aman/Menipis/Habis)\n& Lapor Sayur Busuk (/inventory/materials)"]
    end

    subgraph PROSES["⚙️ 2. DIPROSES DI MANA? (Otak Sistem Otomatis)"]
        direction TB
        P1["🔄 Hitung Ulang Modal Rata-Rata\n(HPP Moving Average)\nStok Lama + Belanja Baru"]
        P2["📦 Potong Stok Gudang & Etalase\n+ Cek Peringatan Sayur Sisa Kemarin\n(Kontrol FIFO)"]
        P3["🧮 Hitung Rumus SPK SAW (4 Kriteria)\nC1 Stok (35%) + C2 Cepat Busuk (30%)\n+ C3 Laris (20%) + C4 Margin (15%)"]
        P4["📊 Kelompokkan 4 Kuadran BCG\n& Prediksi Regresi 3 Hari ke Depan"]
    end

    subgraph OUTPUT["📤 3. MENGHASILKAN APA? (Output Keputusan)"]
        direction TB
        O1["📲 Pesan WA Jam 23:00 ke Owner\nBerisi Daftar Barang Habis &\nTop Prioritas Belanja Besok Subuh"]
        O2["🛒 Contekan Belanja Pasar (/spk)\nUrutan Ranking #1, #2, #3 dst.\n+ Simulasi Uang Belanja"]
        O3["💰 Dashboard GoPay & Laporan PDF\nTotal Omzet, Untung Bersih,\nKerugian Busuk, & Peta Barang BCG"]
    end

    I1 --> P1
    I2 --> P2
    I3 --> P2
    I3 --> P3
    I3 --> P4
    I4 --> P3
    P1 --> P3
    P1 --> P4
    P2 --> O1
    P3 --> O1
    P3 --> O2
    P4 --> O3
    P1 --> O3
```

### Penjelasan Bahasa Sehari-hari:
1. **Bagian Kiri (Input):** Hanya ada **4 kegiatan input** di warung: Owner masukkan nota pasar pagi, Karyawan catat bungkus eceran, Karyawan catat barang yang laku keluar pas warung senggang, dan Karyawan pencet tombol `Aman/Menipis/Habis` pas malam.
2. **Bagian Tengah (Proses):** Begitu 4 data tadi masuk, komputer otomatis menghitung rata-rata harga modal baru (*Moving Average*), memotong sisa stok, dan menjalankan rumus **SAW** serta **BCG**.
3. **Bagian Kanan (Output):** Hasilnya langsung jadi **Pesan WA jam 11 malam**, **Daftar Prioritas Belanja Pasar Subuh**, dan **Hitungan Untung Bersih Owner**.

---

## 2. FLOWCHART ALUR HARIAN WARUNG DARI SUBUH SAMPAI TUTUP (05:00 – 23:00 WIB)

Flowchart ini menggambarkan urutan waktu dari subuh saat Owner ke pasar induk hingga warung tutup pukul 23:00 WIB, memisahkan tugas **Owner** dan **Karyawan**:

```mermaid
flowchart TD
    START(["🌅 MULAI: Pukul 04:30 - 05:30 WIB\nOwner Berangkat ke Pasar Induk"]) --> SUBUH

    SUBUH["👑 OWNER membuka WA Laporan Semalam\natau halaman SPK SAW (/spk)\nMelihat Urutan Barang Prioritas #1, #2, #3"] --> PAGI_OWNER

    PAGI_OWNER["👑 Pukul 06:00 WIB - Barang Datang di Warung\nOwner buka menu Kulakan AI (/purchases)\nFoto Struk Pasar atau Ketik Kalimat Cepat\nKlik tombol: SIMPAN NOTA KULAKAN"] --> EFEK_KULAKAN

    EFEK_KULAKAN["⚙️ Efek Sistem Otomatis:\n1. Stok Gudang (ingredients) Bertambah\n2. Status Stok jadi AMAN (Hijau)\n3. HPP Moving Average Dihitung Ulang"] --> PAGI_KARYAWAN

    PAGI_KARYAWAN["👷 Pukul 06:30 WIB - Persiapan Meja Depan\nKaryawan buka menu Kemas Ulang (/inventory/production)\nMembungkus cabai/bawang/sayur eceran\nKlik tombol: SIMPAN KEMAS ULANG"] --> JAM_SIBUK_PAGI

    JAM_SIBUK_PAGI["🔥 Pukul 07:00 - 11:00 WIB (JAM SIBUK PAGI)\nIbu-ibu ramai belanja sayur.\nKaryawan fokus layani pembeli secara tunai\n& menjual stok bungkus sisa kemarin (FIFO) duluan\n(Aplikasi TIDAK dipakai ketik kasir agar tidak antre)"] --> SIANG_REKAP

    SIANG_REKAP["👷 Pukul 11:00 - 14:00 WIB (Warung Senggang)\nKaryawan buka menu Barang Keluar (/pos)\nPilih barang yang keluar pagi tadi & isi jumlahnya\nKlik tombol: SIMPAN REKAP BARANG KELUAR"] --> EFEK_POS_SIANG

    EFEK_POS_SIANG["⚙️ Efek Sistem Otomatis:\n1. Stok Etalase & Gudang Berkurang\n2. Data Kelarisan Barang (C3 SAW) Bertambah\n3. Estimasi Omzet & Untung Owner Terhitung"] --> JAM_SIBUK_SORE

    JAM_SIBUK_SORE["🔥 Pukul 14:00 - 17:30 WIB (JAM SIBUK SORE)\nPelayanan pembeli sore hari"] --> SORE_REKAP

    SORE_REKAP["👷 Pukul 17:30 - 21:00 WIB\nKaryawan rekap lagi Barang Keluar sore di (/pos)"] --> MALAM_CEK

    MALAM_CEK["👷 Pukul 21:30 - 22:30 WIB (Cek Rak Malam)\nKaryawan buka menu Cek Stok 1-Tap (/inventory/materials)\n• Klik tombol [AMAN] / [MENIPIS] / [HABIS]\n• Jika ada sayur layu, klik [Koreksi Fisik / Lapor Busuk]"] --> TUTUP_WARUNG

    TUTUP_WARUNG["👷 Pukul 23:00 WIB (Tutup Warung)\nKaryawan buka menu SPK SAW (/spk)\nKlik tombol hijau: KIRIM LAPORAN KE WA OWNER"] --> WA_OWNER

    WA_OWNER(["📲 SELESAI: HP Owner Menerima WA Otomatis\nBerisi Ringkasan Hari Ini, Stok Habis,\n& Rekomendasi Belanja Pasar Besok Subuh"])
```

---

## 3. ACTIVITY DIAGRAM SISTEM USULAN (FORMAT SWIMLANE: AKTOR VS SISTEM)

Bagian ini dibagi menjadi **3 Activity Diagram standar skripsi** yang memisahkan kolom tindakan **Aktor (Karyawan/Owner)** dan kolom respon **Sistem (`STOKKU ERP`)**.

### 3A. Activity Diagram Operasional Harian Karyawan Gudang
*(Alur: Cek FIFO Pagi -> Catat Barang Keluar -> Cek Stok 1-Tap & Lapor Busuk -> Kirim WA 23:00)*

```mermaid
flowchart TD
    subgraph KARYAWAN["👷 AKTOR: KARYAWAN GUDANG"]
        K1(["Mulai Shift Pagi"])
        K2["Login dengan Akun Karyawan\n(karyawan@stokku.com)"]
        K3["Buka menu Barang Keluar (/pos)\n& lihat Banner Kuning Prioritas FIFO"]
        K4["Taruh sayur/bungkus sisa kemarin\ndi barisan paling depan meja warung"]
        K5["Pasca jam sibuk (11:00 & 17:30),\npilih barang yang keluar & input jumlah"]
        K6["Klik tombol Simpan Rekap Barang Keluar"]
        K7["Malam hari (22:00), buka menu\nCek Stok 1-Tap (/inventory/materials)"]
        K8["Klik tombol 1-Tap: [AMAN] / [MENIPIS] / [HABIS]\natau klik [Koreksi Fisik / Lapor Busuk]"]
        K9["Pukul 23:00 WIB, klik tombol\n[Kirim Laporan ke WA Owner]"]
        K10(["Selesai / Tutup Warung"])
    end

    subgraph SISTEM_A["🖥️ SISTEM: STOKKU ERP"]
        S1["Validasi role = kasir\n& sembunyikan seluruh angka rupiah"]
        S2["Urutkan komoditas berstatus is_fifo = true\ndi urutan paling atas halaman /pos"]
        S3["Simpan ke tabel daily_sales & daily_sale_items,\npotong stok menu_items & ingredients"]
        S4["Perbarui stock_status di tabel ingredients\n& catat sayur busuk ke tabel expenses"]
        S5["Jalankan perhitungan SPK SAW terbaru\n& susun teks pesan WhatsApp otomatis"]
        S6["Simpan log ke wa_report_logs &\nbuka tautan wa.me ke nomor HP Owner"]
    end

    K1 --> K2
    K2 --> S1
    S1 --> K3
    K3 --> S2
    S2 --> K4
    K4 --> K5
    K5 --> K6
    K6 --> S3
    S3 --> K7
    K7 --> K8
    K8 --> S4
    S4 --> K9
    K9 --> S5
    S5 --> S6
    S6 --> K10
```

---

### 3B. Activity Diagram Kulakan Pasar & Perhitungan HPP Moving Average oleh Owner
*(Alur: Scan Foto Struk / Ketik Nota AI -> Ekstraksi Tabel -> Simpan -> Hitung Ulang HPP Rata-Rata)*

```mermaid
flowchart TD
    subgraph OWNER_B["👑 AKTOR: OWNER WARUNG"]
        O1(["Sepulang dari Pasar Induk (06:00 WIB)"])
        O2["Buka menu Kulakan AI (/purchases)"]
        O3{"Punya Foto Struk\natau Ketik Teks?"}
        O4["Upload Foto Nota Pasar\n[Upload Foto Struk]"]
        O5["Ketik kalimat bebas di kotak AI:\n'cabe keriting 8kg 360rb, jagung 12kg 144rb'"]
        O6["Klik tombol [Ekstrak ke Tabel Kulakan]"]
        O7["Periksa hasil tabel barang, qty, & harga\nlalu klik [Simpan Nota Kulakan]"]
        O8(["Selesai: Stok & HPP Terupdate"])
    end

    subgraph SISTEM_B["🖥️ SISTEM: PURCHASE CONTROLLER & AI ENGINE"]
        SB1["Kirim gambar ke Gemini AI Vision OCR\n(atau gunakan Fallback OCR Lokal)"]
        SB2["Proses teks dengan Gemini NLP /\nMesin Regex Lokal (parseTextWithLocalEngine)"]
        SB3["Cocokkan nama barang dengan 96 komoditas\ndi tabel ingredients & tampilkan preview"]
        SB4["Simpan nota ke tabel purchases,\npurchase_items, & ingredient_prices"]
        SB5["Hitung HPP Moving Average:\nHPP Baru = ((Stok Lama x HPP Lama) + Belanja Baru) / Total Stok Baru"]
        SB6["Tambah current_stock, ubah stock_status = AMAN,\n& sinkronkan HPP ke tabel menu_items"]
    end

    O1 --> O2
    O2 --> O3
    O3 -- "Foto Struk" --> O4
    O3 -- "Ketik Cepat" --> O5
    O4 --> SB1
    O5 --> O6
    O6 --> SB2
    SB1 --> SB3
    SB2 --> SB3
    SB3 --> O7
    O7 --> SB4
    SB4 --> SB5
    SB5 --> SB6
    SB6 --> O8
```

---

### 3C. Activity Diagram Perhitungan SPK Metode SAW (Rekomendasi Belanja Pasar)
*(Alur: Tarik Data 4 Kriteria C1–C4 -> Matriks Keputusan X -> Normalisasi R -> Skor Preferensi V -> Ranking)*

```mermaid
flowchart TD
    subgraph USER_C["👑/👷 AKTOR: OWNER / KARYAWAN"]
        UC1(["Membuka Halaman SPK SAW (/spk)\natau Klik Kirim WA 23:00"])
        UC2["Melihat Daftar Ranking Prioritas Belanja\n(#1 Prioritas Utama s/d #96)"]
        UC3["Owner mencentang Simulasi Keranjang Pasar\natau mengunduh PDF SPK SAW"]
        UC4(["Selesai"])
    end

    subgraph SISTEM_C["🖥️ SISTEM: SPK CONTROLLER (METODE SAW)"]
        SC1["Tarik seluruh komoditas aktif dari tabel ingredients\nbeserta histori barang keluar dari daily_sale_items"]
        SC2["Tetapkan Nilai Mentah 4 Kriteria (Matriks X):\n• C1 (Stok 35%): Habis=100, Menipis+FIFO=82, Menipis=75, Aman=15\n• C2 (Cepat Busuk 30%): Skor Perishability 1 s/d 3\n• C3 (Kelarisan 20%): Skor Popularitas + Volume Terjual\n• C4 (Margin 15%): Persentase Margin (Harga Jual - HPP)"]
        SC3["Cari Nilai Maksimum tiap kolom kriteria:\nMax(C1), Max(C2), Max(C3), Max(C4)"]
        SC4["Hitung Matriks Normalisasi Benefit (R_ij):\nR1 = C1 / Max(C1)\nR2 = C2 / Max(C2)\nR3 = C3 / Max(C3)\nR4 = C4 / Max(C4)"]
        SC5["Kalikan dengan Bobot & Jumlahkan Skor Akhir (V_i):\nV_i = (0.35 x R1) + (0.30 x R2) + (0.20 x R3) + (0.15 x R4)"]
        SC6["Urutkan komoditas dari Skor V_i Tertinggi ke Terendah\n& beri label: PRIORITAS UTAMA / TINGGI / TERKENDALI"]
    end

    UC1 --> SC1
    SC1 --> SC2
    SC2 --> SC3
    SC3 --> SC4
    SC4 --> SC5
    SC5 --> SC6
    SC6 --> UC2
    UC2 --> UC3
    UC3 --> UC4
```

---

## 4. USE CASE DIAGRAM & TABEL PEMBAGIAN HAK AKSES (OWNER VS KARYAWAN)

Diagram ini memperlihatkan batas fungsi antara menu operasional harian yang diakses oleh **Karyawan Gudang / Staf** dan menu analisis finansial yang dipantau oleh **Owner**:

```mermaid
flowchart LR
    KARYAWAN(["👷 AKTOR:\nKaryawan Gudang\n(role: gudang/kasir)"])
    OWNER(["👑 AKTOR:\nOwner Warung\n(role: admin)"])

    subgraph BERSAMA["🤝 FITUR BERSAMA (Terintegrasi Penuh)"]
        UC_LOGIN("🔐 Login & Logout Sistem (/login)")
        UC_DASH("🏠 Lihat Home Dashboard (/dashboard)\n• Owner: Lengkap Omzet Kotor & Bersih\n• Karyawan: Status Stok Gudang & Action Shortcut")
        UC_POS("📦 Catat Barang Keluar & Kontrol FIFO (/pos)\n• Pengurangan Stok Otomatis & Rekap Saluran")
        UC_STOK("👆 Cek Stok 1-Tap & Koreksi Fisik (/inventory/materials)\n• Klik [Aman] / [Menipis] / [Habis]\n• Koreksi Angka & Lapor Sayur Busuk/Susut\n• + Tambah Komoditas Baru 96 Barang")
        UC_BELANJA("🛒 Belanja Pasar & Scan Nota AI (/purchases & /inventory/shopping)\n• Upload Struk/Nota Pasar (Preview & Simpan)\n• Konversi Grosir (Karung/Dus) ke Eceran\n• Rekomendasi Harga Jual Baru jika Modal Naik")
        UC_LAPORAN("📄 Pusat Laporan Keuangan & Omset (/reports)\n• Omset Kotor, Belanja Pasar & Omset Bersih\n• Input Biaya Operasional & Kerugian Susut")
        UC_SPK("🏆 Lihat Urutan Belanja SAW & Kirim WA 23:00 (/spk)\n• Ranking #1 s/d #96 Berbasis Data Real-Time\n• Rekap Tutup Warung Otomatis ke WhatsApp")
    end

    subgraph EKSKLUSIF_OWNER["🔒 FITUR ADVANCED ANALYTICS OWNER"]
        UC_BCG("📊 Analisis Portofolio 4 Kuadran BCG\n(/analysis/klasifikasi)")
        UC_PREDIKSI("📈 Prediksi Permintaan Regresi Linier 3 Hari\n(/analysis/prediksi)")
        UC_PDF("🖨️ Cetak Dokumen PDF Resmi Laporan & SPK\n(/reports/finance, /reports/inventory, /spk/export-pdf)")
    end

    KARYAWAN --> UC_LOGIN
    KARYAWAN --> UC_DASH
    KARYAWAN --> UC_POS
    KARYAWAN --> UC_STOK
    KARYAWAN --> UC_BELANJA
    KARYAWAN --> UC_LAPORAN
    KARYAWAN --> UC_SPK

    OWNER --> UC_LOGIN
    OWNER --> UC_DASH
    OWNER --> UC_POS
    OWNER --> UC_STOK
    OWNER --> UC_BELANJA
    OWNER --> UC_LAPORAN
    OWNER --> UC_SPK

    OWNER --> UC_BCG
    OWNER --> UC_PREDIKSI
    OWNER --> UC_PDF
```

### Tabel Ringkas Perbedaan Tampilan Owner vs Karyawan:

| Nama Halaman & URL | Apa yang Dilihat KARYAWAN (`gudang`/`kasir`)? | Apa yang Dilihat OWNER (`admin`)? |
| :--- | :--- | :--- |
| **Home (`/dashboard`)** | Status ringkasan stok rak, shortcut menu cepat (Stok, Belanja, Laporan, Prioritas), dan daftar stok darurat. | Kartu Utama Putih **Total Omzet Warung**, **Untung/Omset Bersih**, **Kulakan Pasar**, **Margin %**, dan **Filter Periode (Hari Ini/Kemarin/Minggu/Bulan)**. |
| **Cek Stok Warung (`/inventory/materials`)** | Tombol status 1-Tap `[Aman] [Menipis] [Habis]`, tombol `[Koreksi Angka / Lapor Busuk]`, foto riil 96 komoditas, dan tombol `[+ Tambah Barang Baru]`. | Seluruh fitur operasional stok ditambah visibilitas harga modal rata-rata (`cost_per_unit`) dan pengaturan master barang. |
| **Belanja Pasar & Struk (`/purchases`)** | Input konversi grosir ke eceran, upload foto struk/nota pasar, scan catatan belanja WA, dan modal `[+ Barang Baru]`. | Seluruh fitur belanja pasar ditambah peringatan kenaikan modal pasar & tombol 1-klik terapkan saran harga jual baru. |
| **Barang Keluar (`/pos`)** | Input kuantitas keluar fisik per saluran (Eceran, Reseller, Agen) dengan banner kuning prioritas FIFO sisa kemarin. | Input barang keluar lengkap dengan kalkulasi nilai rupiah transaksi dan kalkulasi otomatis ke Omset Kotor. |
| **Pusat Laporan (`/reports`)** | Tampilan ringkasan Omset Kotor, Belanja Pasar, dan Omset Bersih hari ini/bulan ini, serta input beban operasional. | Ringkasan finansial menyeluruh, rincian biaya operasional & susut, grafik tren pendapatan, dan tombol ekspor PDF. |
| **SPK SAW & WhatsApp (`/spk`)** | Urutan rekomendasi belanja pasar besok (#1 s/d #96) dan tombol Kirim Laporan Tutup Warung ke WA Owner (23:00 WIB). | Seluruh urutan SAW, simulasi keranjang belanja, simulasi bobot kriteria kustom (w1–w4), dan tombol unduh PDF SPK. |
| **Analisis BCG & Prediksi (`/analysis/*`)** | Mode panduan operasional. | Matriks portofolio 4 kuadran (*Star, Plowhorse, Puzzle, Dog*) dan prediksi regresi linier 3 hari ke depan. |

---

## 5. DIAGRAM ALIRAN DATA (BAGAIMANA FITUR SALING NYAMBUNG TANPA MESIN KASIR)

Diagram ini adalah jawaban paling jelas jika Anda (atau dosen penguji) bertanya:
**"Ketika satu tombol diklik di halaman A, data itu lari ke tabel database mana dan mengubah angka apa di halaman B, C, dan D?"**

```mermaid
flowchart TD
    %% SUMBER AKSI PENGGUNA
    subgraph AKSI["👆 3 AKSI INPUT UTAMA DI WARUNG"]
        A_POS["1️⃣ KARYAWAN Input Barang Keluar\ndi Halaman (/pos)\nPosController@store"]
        A_STOK["2️⃣ KARYAWAN Klik 1-Tap [Aman/Menipis/Habis]\natau [Lapor Sayur Busuk]\ndi Halaman (/inventory/materials)\nInventoryController@updateQuickStatus & storeOpname"]
        A_BELI["3️⃣ OWNER Input Nota Belanja Pasar\ndi Halaman Kulakan AI (/purchases)\nPurchaseController@store"]
    end

    %% TABEL DATABASE
    subgraph DB["🗄️ TABEL DATABASE YANG BERUBAH OTOMATIS"]
        T_SALES[("Tabel daily_sales &\ndaily_sale_items\n• qty_sold (Jumlah Keluar)\n• total_revenue (Omzet)\n• total_hpp (Modal Barang)")]
        T_ING[("Tabel ingredients &\nmenu_items\n• current_stock (Sisa Stok)\n• stock_status (aman/menipis/habis)\n• cost_per_unit (HPP Moving Avg)\n• is_fifo (Prioritas FIFO)")]
        T_EXP[("Tabel expenses\n• Kategori: Barang Busuk/Susut\n• Kategori: Operasional Warung\n• amount (Nilai Kerugian Rp)")]
        T_PUR[("Tabel purchases,\npurchase_items &\ningredient_prices\n• total_amount (Total Kulakan)\n• price_per_unit (Harga Beli Baru)")]
    end

    %% EFEK KE HALAMAN LAIN
    subgraph EFEK["✨ ANGKA YANG IKUT BERUBAH DI HALAMAN OWNER & SPK"]
        E_DASH["🏠 Kartu Putih Home (/dashboard)\n• Total Omzet = SUM(total_revenue)\n• Kulakan Pasar = SUM(purchases + expenses)\n• Untung Bersih = Omzet - Kulakan & Beban"]
        E_SAW["🏆 Ranking Belanja SPK SAW (/spk)\n• Kriteria C1 (35%) ◄── dari stock_status & current_stock\n• Kriteria C2 (30%) ◄── dari perishability_score\n• Kriteria C3 (20%) ◄── dari SUM(qty_sold) /pos\n• Kriteria C4 (15%) ◄── dari (Harga Jual - cost_per_unit)"]
        E_BCG["📊 Matriks BCG & Regresi (/analysis/*)\n• Sumbu Volume BCG & Tren Regresi ◄── dari qty_sold\n• Sumbu Margin BCG ◄── dari (subtotal_revenue - subtotal_hpp)"]
        E_PDF["📄 Laporan Laba Rugi & Waste Analysis (/reports)\n• Menampilkan Laba Bersih Final\n• Menampilkan Grafik Kerugian Sayur Busuk"]
    end

    %% HUBUNGAN PANAH AKSI 1 (/pos)
    A_POS -- "Catat jumlah laku &\nhitung Omzet x Harga Standar" --> T_SALES
    A_POS -- "Potong current_stock &\nsinkronkan stock_status" --> T_ING

    %% HUBUNGAN PANAH AKSI 2 (/inventory/materials)
    A_STOK -- "Ubah stock_status manual\n(Aman / Menipis / Habis)" --> T_ING
    A_STOK -- "Jika ada sayur busuk:\nSelisih x HPP dicatat rugi" --> T_EXP

    %% HUBUNGAN PANAH AKSI 3 (/purchases)
    A_BELI -- "Simpan nilai belanja pasar" --> T_PUR
    A_BELI -- "Tambah current_stock,\nset status = AMAN,\n& update HPP Moving Average" --> T_ING

    %% HUBUNGAN DATABASE KE EFEK LAYAR
    T_SALES --> E_DASH
    T_SALES --> E_SAW
    T_SALES --> E_BCG
    T_SALES --> E_PDF

    T_ING --> E_SAW
    T_ING --> E_BCG

    T_EXP --> E_DASH
    T_EXP --> E_PDF

    T_PUR --> E_DASH
    T_PUR --> E_PDF
```

---

### Ringkasan Penjelasan Aliran Data (Tanpa Mesin Kasir):

#### 1. Apa yang terjadi kalau Karyawan input di `/pos` (Barang Keluar)?
* **Yang dilakukan Karyawan:** Hanya memilih barang (misal: *Cabai Rawit 3 kg*, *Minyak Kita 5 pouch*) lalu klik **Simpan**.
* **Yang dilakukan Sistem di Balik Layar (`PosController@store`):**
  1. Memotong stok fisik di tabel `menu_items` dan `ingredients`. Jika sisa stok menyentuh batas minimum, kolom `stock_status` otomatis berubah dari `aman` menjadi `menipis` atau `habis`.
  2. Mengalikan jumlah keluar tadi dengan harga jual standar warung dan menyimpannya ke tabel `daily_sales` & `daily_sale_items`.
* **Angka yang langsung berubah di layar Owner:**
  * Angka **Total Omzet Warung** dan **Untung Bersih** di kartu putih `/dashboard` langsung bertambah.
  * Nilai **Kriteria C3 (Tingkat Kelarisan)** dan **Kriteria C1 (Kekritisan Stok)** di halaman `/spk` langsung naik.
  * Posisi kuadran barang di **Matriks BCG** (`/analysis/klasifikasi`) dan grafik **Prediksi 3 Hari** (`/analysis/prediksi`) langsung ter-update.

#### 2. Apa yang terjadi kalau Karyawan klik `[Aman / Menipis / Habis]` atau `[Koreksi Busuk]` di `/inventory/materials`?
* **Kalau klik tombol 1-Tap `[MENIPIS]` atau `[HABIS]` (`InventoryController@updateQuickStatus`):**
  * Kolom `stock_status` di tabel `ingredients` langsung berubah.
  * Di halaman **SPK SAW (`/spk`)**, skor **Kriteria C1 (Bobot 35%)** untuk barang tersebut langsung melonjak menjadi `75` (Menipis) atau `100` (Habis), sehingga barang itu langsung naik ke peringkat atas daftar belanja pasar besok pagi dan masuk ke daftar **Laporan WA pukul 23:00 WIB**.
* **Kalau klik tombol `[Koreksi Fisik / Lapor Busuk]` (`InventoryController@storeOpname`):**
  * Misalnya stok Tomat di aplikasi `10 kg`, tapi setelah ditimbang tinggal `8.5 kg` karena `1.5 kg` busuk.
  * Sistem mengubah `current_stock` Tomat menjadi `8.5 kg`, lalu mengalikan `1.5 kg x HPP Tomat` dan menyimpannya secara otomatis ke tabel `expenses` dengan kategori **"Barang Busuk/Susut"**.
  * Akibatnya, **Untung Bersih Owner** di `/dashboard` dan `/reports` otomatis dikurangi nilai kerugian tomat busuk tersebut, dan grafik **Analisis Kerugian Busuk (`/reports/waste-analysis`)** langsung mencatatnya.

#### 3. Apa yang terjadi kalau Owner input nota di `/purchases` (Kulakan AI)?
* **Yang dilakukan Owner (`PurchaseController@store`):** Upload foto nota pasar atau ketik kalimat belanja, lalu klik **Simpan**.
* **Efek berantai ke seluruh aplikasi:**
  1. **Tabel `purchases` & `purchase_items`:** Mencatat pengeluaran kas belanja pasar hari ini (menambah angka *Kulakan Pasar* di kartu putih `/dashboard`).
  2. **Tabel `ingredients`:** Menambahkan jumlah barang yang baru dibeli ke `current_stock` dan mengubah `stock_status` kembali menjadi **`🟢 AMAN`** (sehingga barang tersebut turun dari daftar darurat SPK SAW).
  3. **Rumus HPP Moving Average:** Menghitung rata-rata harga modal baru (`cost_per_unit`) antara sisa stok lama dengan stok yang baru dibeli, lalu memperbarui HPP di tabel `menu_items`. Perubahan HPP ini otomatis memperbarui perhitungan **Kriteria C4 (Margin Keuntungan)** di SPK SAW dan **Matriks BCG**.

