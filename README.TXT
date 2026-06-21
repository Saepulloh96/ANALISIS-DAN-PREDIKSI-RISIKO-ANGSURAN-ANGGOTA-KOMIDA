========================================================================
  ANALISIS & PREDIKSI RISIKO ANGSURAN ANGGOTA KOPERASI MITRA DHUAFA
========================================================================

Proyek ini bertujuan untuk membantu Koperasi Mitra Dhuafa (KOMIDA) memahami siapa saja anggota yang
berisiko mengalami kesulitan membayar angsuran — sebelum masalah itu benar-
benar terjadi.

Caranya? Dengan menggabungkan kecerdasan buatan (Machine Learning menggunakan
algoritma CART Decision Tree) dan sebuah aplikasi web yang mudah digunakan.
Hasilnya bukan cuma angka prediksi, tapi juga penjelasan mengapa sistem
mengambil keputusan tersebut — yang sering disebut Explainable AI (XAI).

Aplikasi ini juga dilengkapi sistem login dengan Role-Based Access Control
(RBAC), sehingga setiap pengguna hanya bisa mengakses fitur yang sesuai
dengan peran/jabatannya masing-masing.


Berikut gambaran singkat isi proyek ini:

  /
  ├── data/
  │   └── dataset_komida.csv       → Dataset untuk melatih model. Berisi
  │                                   gabungan data real + data yang sudah
  │                                   diseimbangkan agar model lebih adil.
  │
  ├── ml/
  │   ├── train.py                 → Script Python untuk melatih model ML.
  │   │                               Jalankan ini jika ingin melatih ulang.
  │   ├── requirements.txt         → Daftar library Python yang diperlukan
  │   │                               (pandas, scikit-learn, dll).
  │   └── model.json               → Hasil model yang sudah dilatih,
  │                                   disimpan dalam format JSON agar bisa
  │                                   dibaca oleh aplikasi PHP.
  │
  ├── web/
  │   ├── assets/                  → File CSS dan JavaScript tampilan web
  │   ├── includes/                → Komponen reusable: header, footer,
  │   │                               auth helper (RBAC), dan logika
  │   │                               DecisionTree di PHP
  │   ├── config.php               → Koneksi ke database + setup otomatis
  │   │                               (termasuk buat tabel users & seeding)
  │   ├── database.sql             → Skema tabel database MySQL
  │   ├── login.php                → Halaman login sistem (RBAC)
  │   ├── logout.php               → Hancurkan sesi & kembali ke login
  │   ├── index.php                → Dashboard utama: statistik & grafik
  │   ├── predict.php              → Halaman prediksi + penjelasan AI-nya
  │   ├── members.php              → Kelola data anggota (akses per role)
  │   ├── model_analysis.php       → Laporan akurasi & aturan pohon keputusan
  │   │                               (hanya Administrator & Manager)
  │   └── admin_users.php          → Manajemen akun pengguna
  │                                   (hanya Administrator)
  │
  └── DATA_ANGSURAN.xlsx           → File Excel sumber data asli dari KOMIDA


------------------------------------------------------------------------
  SEBELUM MULAI — PASTIKAN INI SUDAH TERPASANG
------------------------------------------------------------------------

Cukup dua hal yang dibutuhkan:

  1. XAMPP — pastikan Apache dan MySQL-nya sudah aktif.
  2. PHP versi 8.1 ke atas (proyek ini diuji di PHP 8.2).

Itu saja, tidak perlu install apapun yang rumit.


------------------------------------------------------------------------
  CARA MENJALANKAN APLIKASINYA
------------------------------------------------------------------------

Kabar baiknya: kamu TIDAK perlu mengimpor database secara manual. Sistem
ini punya fitur "Self-Healing Auto Setup" yang akan mengurus semuanya
sendiri saat pertama kali diakses. Tinggal ikuti 3 langkah ini:

  Langkah 1 — Aktifkan XAMPP
  ───────────────────────────
  Buka XAMPP Control Panel, lalu klik "Start" untuk Apache dan MySQL.
  Pastikan keduanya berwarna hijau (berjalan).

  Langkah 2 — Jalankan Server PHP
  ─────────────────────────────────
  Buka PowerShell, lalu copy-paste perintah di bawah ini dan tekan Enter:

    C:\xampp\php\php.exe -S localhost:8080 -t "C:\xampp\htdocs\ANALISIS_DAN_PREDIKSI_ANGSURAN_ANGGOTA_KOPERASI_MITRA_DHUAFA\web"

  Sesuaikan path-nya jika kamu menyimpan folder ini di lokasi yang berbeda.

  Langkah 3 — Buka di Browser
  ─────────────────────────────
  Buka browser favoritmu (Chrome, Edge, Firefox), lalu kunjungi:

    http://localhost:8080
  Selesai!

  ⚠ Catatan penting untuk akses pertama kali:
  Saat halaman pertama kali terbuka, sistem akan otomatis:
    • Mendeteksi apakah database sudah ada atau belum
    • Membuat database baru bernama 'db_komida_risk'
    • Mengimpor semua tabel yang dibutuhkan (termasuk tabel users)
    • Memuat 2.135 data anggota dari file CSV ke database
    • Membuat 3 akun default untuk login (lihat bagian di bawah)

  Proses ini hanya terjadi sekali. Selanjutnya aplikasi langsung jalan normal.


------------------------------------------------------------------------
  SISTEM LOGIN & HAK AKSES (RBAC)
------------------------------------------------------------------------

Aplikasi ini menggunakan sistem login dengan 3 tingkat akses:

  ┌─────────────────┬──────────────────────────────────────────────────┐
  │ Role            │ Yang Bisa Diakses                                │
  ├─────────────────┼──────────────────────────────────────────────────┤
  │ Administrator   │ Semua fitur + Kelola akun pengguna               │
  │ Manager         │ Dashboard, Prediksi, Data Anggota, Analisis ML   │
  │ Staff           │ Dashboard & Prediksi Risiko saja (read-only)     │
  └─────────────────┴──────────────────────────────────────────────────┘

  Akun default yang sudah disiapkan otomatis:

    Username : admin       | Password : Admin2026    | Role: Administrator
    Username : manager     | Password : Manager2026  | Role: Manager
    Username : staff       | Password : Staff2026    | Role: Staff

  Catatan: Password disimpan terenkripsi (bcrypt) di database.
           Akun ini bisa diubah atau dihapus kapan saja melalui
           halaman "Kelola Pengguna" (hanya bisa diakses oleh Administrator).


------------------------------------------------------------------------
  TENTANG MODEL MACHINE LEARNING-NYA
------------------------------------------------------------------------

Model yang digunakan adalah CART Decision Tree Classifier — salah satu
algoritma ML yang cukup populer karena hasilnya mudah dijelaskan ke orang
awam sekalipun (tidak seperti "kotak hitam" pada model AI lainnya).

  • Akurasi model  : 90,21%
  • Fitur/variabel : Simpanan Wajib, Simpanan Sukarela, Simpanan Hari Raya,
                     Tenor Pinjaman, Jumlah Pinjaman, dan Rasio Simpanan
                     terhadap Pinjaman.

Tingginya akurasi ini menunjukkan bahwa pola pembayaran angsuran anggota
KOMIDA bisa diprediksi cukup baik hanya dari data simpanan dan pinjaman
yang sudah ada.

