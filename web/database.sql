-- SQL Database Schema for Koperasi Mitra Dhuafa Risk Prediction Application
-- Database Name: db_komida_risk

CREATE DATABASE IF NOT EXISTS db_komida_risk;
USE db_komida_risk;

-- Table for Members (Anggota)
CREATE TABLE IF NOT EXISTS anggota (
    id_anggota VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tujuan_pinjaman VARCHAR(100) NOT NULL,
    jumlah_pinjaman DECIMAL(15,2) NOT NULL,
    tenor INT NOT NULL,
    cicilan DECIMAL(15,2) NOT NULL,
    simp_wajib DECIMAL(15,2) DEFAULT 0,
    simp_sukarela DECIMAL(15,2) DEFAULT 0,
    simp_pensiun DECIMAL(15,2) DEFAULT 0,
    simp_hari_raya DECIMAL(15,2) DEFAULT 0,
    total_simpanan DECIMAL(15,2) GENERATED ALWAYS AS (simp_wajib + simp_sukarela + simp_pensiun + simp_hari_raya) STORED,
    rasio_simpanan DECIMAL(7,6) GENERATED ALWAYS AS ((simp_wajib + simp_sukarela + simp_pensiun + simp_hari_raya) / jumlah_pinjaman) STORED,
    week_past_due INT DEFAULT 0,
    label_risiko VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Prediction History
CREATE TABLE IF NOT EXISTS prediksi_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tujuan_pinjaman VARCHAR(100) NOT NULL,
    jumlah_pinjaman DECIMAL(15,2) NOT NULL,
    tenor INT NOT NULL,
    cicilan DECIMAL(15,2) NOT NULL,
    total_simpanan DECIMAL(15,2) NOT NULL,
    rasio_simpanan DECIMAL(7,6) NOT NULL,
    predicted_label VARCHAR(20) NOT NULL,
    probability DECIMAL(5,4) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
