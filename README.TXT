========================================================================
APLIKASI ANALISIS DAN PREDIKSI RISIKO ANGSURAN ANGGOTA KOPERASI MITRA DHUAFA
========================================================================

Aplikasi ini dikembangkan untuk kebutuhan Capstone Project (Tugas Akhir Semester 8)
yang menggabungkan model Machine Learning (CART Decision Tree) dan aplikasi web 
interaktif untuk menganalisis kelayakan angsuran anggota Koperasi Mitra Dhuafa.

------------------------------------------------------------------------
STRUKTUR DIREKTORI PROYEK
------------------------------------------------------------------------
/
├── data/
│   └── dataset_komida.csv       - Dataset latih (gabungan data real & seimbang)
├── ml/
│   ├── train.py                 - Script Python scikit-learn untuk training model
│   ├── requirements.txt         - Library Python yang dibutuhkan (pandas, sklearn)
│   └── model.json               - Model yang sudah dilatih (hasil ekspor format JSON)
├── web/
│   ├── assets/                  - File CSS premium dan JavaScript
│   ├── includes/                - File header, footer, & logika DecisionTree.php
│   ├── config.php               - Koneksi database & auto-import bootstrap data
│   ├── database.sql             - Skema database MySQL
│   ├── index.php                - Dashboard utama (Statistik & Visualisasi Grafik)
│   ├── predict.php              - Kalkulator Prediksi & Explainable AI (XAI)
│   ├── members.php              - Kelola & cari database data anggota
│   └── model_analysis.php       - Laporan metrik akurasi & aturan logika pohon
└── DATA_ANGSURAN.xlsx           - Sumber data delinquency real awal dari Koperasi

------------------------------------------------------------------------
PERSYARATAN SISTEM
------------------------------------------------------------------------
1. XAMPP (Apache & MySQL aktif)
2. PHP versi 8.1 ke atas (aplikasi diuji pada PHP 8.2)

------------------------------------------------------------------------
CARA MENJALANKAN APLIKASI WEB
------------------------------------------------------------------------
Sistem ini menggunakan fitur "Self-Healing Auto Setup Database". Anda tidak perlu
mengimpor database manual ke phpMyAdmin. Sistem akan melakukannya otomatis!

Langkah 1:
Buka XAMPP Control Panel, lalu klik tombol "Start" pada Apache dan MySQL.

Langkah 2:
Buka Terminal PowerShell Anda dan jalankan perintah berikut untuk menjalankan server:

C:\xampp\php\php.exe -S localhost:8000 -t "H:/mata_kuliah/SEMESTER 8/7.CAPSTONE PROJECT/ANALISIS_DAN_PREDIKSI_ANGSURAN_ANGGOTA_KOPERASI_MITRA_DHUAFA/web"

Langkah 3:
Buka browser (Google Chrome, Microsoft Edge, dll) dan kunjungi URL berikut:
http://localhost:8000

*Catatan: Pada akses halaman pertama, sistem akan otomatis mendeteksi database, 
membuat database baru bernama 'db_komida_risk', mengimpor tabel SQL, dan memuat 
2.135 data anggota latih dari file CSV secara otomatis!

------------------------------------------------------------------------
METODE MACHINE LEARNING & EVALUASI
------------------------------------------------------------------------
- Algoritma: CART Decision Tree Classifier
- Akurasi Model: 90.21%
- Variabel Utama: Simpanan Wajib, Simpanan Sukarela, Simpanan Hari Raya, Tenor, 
  Jumlah Pinjaman, dan Rasio Simpanan terhadap Pinjaman.

Selamat mempresentasikan tugas Capstone Project Anda!
========================================================================
