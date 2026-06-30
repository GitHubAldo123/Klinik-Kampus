-- =========================================================
-- DATABASE: klinik_kampus
-- Klinik Sistem Informasi Berbasis Web
-- Pengelolaan Data Pasien, Jadwal Dokter, dan Riwayat Pemeriksaan
-- =========================================================

CREATE DATABASE IF NOT EXISTS klinik_kampus
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE klinik_kampus;

-- =========================================================
-- 1. TABEL USERS
-- Tabel terpusat untuk login. Menyatukan akun Admin & Dokter
-- supaya 1 pintu login bisa redirect sesuai role.
-- =========================================================
CREATE TABLE users (
    id_user        INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('admin','dokter') NOT NULL,
    id_ref         INT NULL,
    email          VARCHAR(100) NULL,
    status_aktif   ENUM('aktif','nonaktif') DEFAULT 'aktif',
    last_login     DATETIME NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 2. TABEL ADMIN (Petugas Klinik / Staf)
-- =========================================================
CREATE TABLE admin (
    id_admin       INT AUTO_INCREMENT PRIMARY KEY,
    nama_admin     VARCHAR(100) NOT NULL,
    no_telepon     VARCHAR(20)  NULL,
    email          VARCHAR(100) NULL,
    jabatan        VARCHAR(50)  DEFAULT 'Staf Klinik',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 3. TABEL DOKTER
-- =========================================================
CREATE TABLE dokter (
    id_dokter      INT AUTO_INCREMENT PRIMARY KEY,
    nip_dokter     VARCHAR(30)  NULL UNIQUE,
    nama_dokter    VARCHAR(100) NOT NULL,
    spesialisasi   VARCHAR(100) DEFAULT 'Dokter Umum',
    no_telepon     VARCHAR(20)  NULL,
    email          VARCHAR(100) NULL,
    foto           VARCHAR(255) NULL,
    status_aktif   ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 4. TABEL PASIEN
-- =========================================================
CREATE TABLE pasien (
    id_pasien        INT AUTO_INCREMENT PRIMARY KEY,
    nim_nik          VARCHAR(30)  NOT NULL UNIQUE,
    nama_lengkap     VARCHAR(100) NOT NULL,
    jenis_kelamin    ENUM('Laki-laki','Perempuan') NOT NULL,
    tanggal_lahir    DATE NULL,
    alamat           TEXT NULL,
    no_telepon       VARCHAR(20)  NULL,
    email            VARCHAR(100) NULL,
    fakultas         VARCHAR(100) NULL,
    status_akademik  ENUM('Mahasiswa','Dosen','Tenaga Kependidikan') DEFAULT 'Mahasiswa',
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 5. TABEL JADWAL_DOKTER
-- Jadwal praktik mingguan tiap dokter
-- =========================================================
CREATE TABLE jadwal_dokter (
    id_jadwal      INT AUTO_INCREMENT PRIMARY KEY,
    id_dokter      INT NOT NULL,
    hari           ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai      TIME NOT NULL,
    jam_selesai    TIME NOT NULL,
    kuota_pasien   INT DEFAULT 20,
    status         ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_jadwal_dokter
        FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 6. TABEL PENDAFTARAN
-- Pasien mendaftar untuk periksa pada jadwal dokter tertentu.
-- Ini adalah titik temu (jembatan) antara Admin (input pendaftaran)
-- dan Dokter (melihat antrian pasiennya).
-- =========================================================
CREATE TABLE pendaftaran (
    id_pendaftaran   INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien        INT NOT NULL,
    id_jadwal        INT NOT NULL,
    id_dokter        INT NOT NULL,
    waktu_daftar     DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_kunjungan DATE NOT NULL,
    no_antrian       INT NOT NULL,
    keluhan_utama    TEXT NULL,
    status_kunjungan ENUM('menunggu','diperiksa','selesai','batal') DEFAULT 'menunggu',
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pendaftaran_pasien
        FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pendaftaran_jadwal
        FOREIGN KEY (id_jadwal) REFERENCES jadwal_dokter(id_jadwal)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pendaftaran_dokter
        FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 7. TABEL REKAM_MEDIS
-- Diisi oleh DOKTER setelah memeriksa pasien dari antrian pendaftaran.
-- =========================================================
CREATE TABLE rekam_medis (
    id_rekam_medis     INT AUTO_INCREMENT PRIMARY KEY,
    id_pendaftaran     INT NOT NULL,
    id_dokter          INT NOT NULL,
    waktu_periksa      DATETIME DEFAULT CURRENT_TIMESTAMP,
    anamnesis          TEXT NULL,
    pemeriksaan_fisik  TEXT NULL,
    diagnosis          TEXT NULL,
    kode_icd10         VARCHAR(20) NULL,
    tindakan           TEXT NULL,
    catatan_dokter     TEXT NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_rekam_pendaftaran
        FOREIGN KEY (id_pendaftaran) REFERENCES pendaftaran(id_pendaftaran)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rekam_dokter
        FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 8. TABEL OBAT
-- Dikelola oleh ADMIN (stok), dipakai oleh DOKTER (saat membuat resep)
-- =========================================================
CREATE TABLE obat (
    id_obat        INT AUTO_INCREMENT PRIMARY KEY,
    nama_obat      VARCHAR(100) NOT NULL,
    jenis_obat     VARCHAR(50)  NULL,
    satuan         VARCHAR(20)  DEFAULT 'pcs',
    stok           INT NOT NULL DEFAULT 0,
    stok_minimum   INT DEFAULT 10,
    kadaluarsa     DATE NULL,
    harga          DECIMAL(10,2) DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 9. TABEL RESEP
-- Header resep, dibuat oleh dokter berdasarkan 1 rekam medis
-- =========================================================
CREATE TABLE resep (
    id_resep        INT AUTO_INCREMENT PRIMARY KEY,
    id_rekam_medis  INT NOT NULL,
    tanggal_resep   DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_resep    ENUM('belum_diambil','sudah_diambil') DEFAULT 'belum_diambil',
    catatan_resep   TEXT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_resep_rekam_medis
        FOREIGN KEY (id_rekam_medis) REFERENCES rekam_medis(id_rekam_medis)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 10. TABEL DETAIL_RESEP
-- Rincian obat apa saja & berapa jumlahnya dalam 1 resep
-- =========================================================
CREATE TABLE detail_resep (
    id_detail       INT AUTO_INCREMENT PRIMARY KEY,
    id_resep        INT NOT NULL,
    id_obat         INT NOT NULL,
    jumlah          INT NOT NULL DEFAULT 1,
    aturan_pakai    VARCHAR(150) NULL,
    catatan_pakai   VARCHAR(150) NULL,

    CONSTRAINT fk_detail_resep
        FOREIGN KEY (id_resep) REFERENCES resep(id_resep)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_obat
        FOREIGN KEY (id_obat) REFERENCES obat(id_obat)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 11. TABEL LOG_AKTIVITAS
-- Mencatat siapa melakukan apa, untuk audit trail sederhana
-- =========================================================
CREATE TABLE log_aktivitas (
    id_log         INT AUTO_INCREMENT PRIMARY KEY,
    id_user        INT NOT NULL,
    aktivitas      VARCHAR(255) NOT NULL,
    waktu          DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_user
        FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- INDEX TAMBAHAN UNTUK PERFORMA QUERY
-- =========================================================
CREATE INDEX idx_pendaftaran_status ON pendaftaran(status_kunjungan);
CREATE INDEX idx_pendaftaran_tanggal ON pendaftaran(waktu_daftar);
CREATE INDEX idx_rekam_medis_dokter ON rekam_medis(id_dokter);
CREATE INDEX idx_jadwal_hari ON jadwal_dokter(hari);
CREATE INDEX idx_pasien_nama ON pasien(nama_lengkap);

-- =========================================================
-- DATA AWAL (SEED DATA) UNTUK TESTING
-- =========================================================

-- Admin / Staf Klinik
INSERT INTO admin (nama_admin, no_telepon, email, jabatan) VALUES
('Wahidin', '081234567890', 'wahidin@stikompoltek.ac.id', 'Kepala Staf Klinik'),
('Tri Wulandari', '081234567891', 'tri.wulandari@stikompoltek.ac.id', 'Staf Administrasi');

-- Dokter
INSERT INTO dokter (nip_dokter, nama_dokter, spesialisasi, no_telepon, email, status_aktif) VALUES
('D001', 'dr. Andi Pratama', 'Dokter Umum', '081298765432', 'andi.pratama@klinik.ac.id', 'aktif'),
('D002', 'dr. Siti Nurhaliza', 'Dokter Umum', '081298765433', 'siti.nurhaliza@klinik.ac.id', 'aktif'),
('D003', 'dr. Budi Santoso', 'Dokter Gigi', '081298765434', 'budi.santoso@klinik.ac.id', 'aktif');

-- Users
-- PENTING: password di bawah masih placeholder, BELUM bisa untuk login.
-- Akan kita generate hash aslinya pakai PHP password_hash() di langkah berikutnya.
-- Rencana: admin1/admin123, admin2/admin123, dokter1/dokter123, dokter2/dokter123, dokter3/dokter123
INSERT INTO users (username, password, role, id_ref, email, status_aktif) VALUES
('admin1', 'BELUM_DI_HASH', 'admin', 1, 'wahidin@stikompoltek.ac.id', 'aktif'),
('admin2', 'BELUM_DI_HASH', 'admin', 2, 'tri.wulandari@stikompoltek.ac.id', 'aktif'),
('dokter1', 'BELUM_DI_HASH', 'dokter', 1, 'andi.pratama@klinik.ac.id', 'aktif'),
('dokter2', 'BELUM_DI_HASH', 'dokter', 2, 'siti.nurhaliza@klinik.ac.id', 'aktif'),
('dokter3', 'BELUM_DI_HASH', 'dokter', 3, 'budi.santoso@klinik.ac.id', 'aktif');

-- Pasien contoh
INSERT INTO pasien (nim_nik, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, no_telepon, email, fakultas, status_akademik) VALUES
('14623001', 'Budi Santoso', 'Laki-laki', '2003-05-10', 'Jl. Sriwijaya No. 5, Cirebon', '081311111111', 'budi.s@mhs.stikompoltek.ac.id', 'Sistem Informasi', 'Mahasiswa'),
('14623002', 'Siti Aisyah', 'Perempuan', '2002-08-22', 'Jl. Tuparev No. 12, Cirebon', '081322222222', 'siti.a@mhs.stikompoltek.ac.id', 'Teknik Informatika', 'Mahasiswa'),
('14623003', 'Ahmad Rizki', 'Laki-laki', '2003-01-15', 'Jl. Cipto No. 8, Cirebon', '081333333333', 'ahmad.r@mhs.stikompoltek.ac.id', 'Sistem Informasi', 'Mahasiswa'),
('14623004', 'Dewi Lestari', 'Perempuan', '2002-11-30', 'Jl. Kartini No. 3, Cirebon', '081344444444', 'dewi.l@mhs.stikompoltek.ac.id', 'Manajemen Informatika', 'Mahasiswa'),
('NIK3209876', 'Rizal Maulana', 'Laki-laki', '1985-03-12', 'Jl. Wahidin No. 20, Cirebon', '081355555555', 'rizal.m@stikompoltek.ac.id', 'Dosen Sistem Informasi', 'Dosen');

-- Jadwal dokter
INSERT INTO jadwal_dokter (id_dokter, hari, jam_mulai, jam_selesai, kuota_pasien, status) VALUES
(1, 'Senin', '08:00:00', '12:00:00', 20, 'aktif'),
(1, 'Rabu', '08:00:00', '12:00:00', 20, 'aktif'),
(1, 'Jumat', '08:00:00', '11:00:00', 15, 'aktif'),
(2, 'Selasa', '13:00:00', '17:00:00', 20, 'aktif'),
(2, 'Kamis', '13:00:00', '17:00:00', 20, 'aktif'),
(3, 'Senin', '09:00:00', '14:00:00', 15, 'aktif'),
(3, 'Kamis', '09:00:00', '14:00:00', 15, 'aktif');

-- Obat
INSERT INTO obat (nama_obat, jenis_obat, satuan, stok, stok_minimum, kadaluarsa, harga) VALUES
('Paracetamol 500mg', 'Tablet', 'tablet', 500, 50, '2027-06-30', 500.00),
('Amoxicillin 500mg', 'Tablet', 'tablet', 200, 30, '2027-03-15', 1500.00),
('OBH Combi', 'Sirup', 'botol', 50, 10, '2026-12-31', 15000.00),
('Antasida DOEN', 'Tablet', 'tablet', 300, 30, '2027-01-20', 700.00),
('Betadine Salep', 'Salep', 'tube', 40, 10, '2027-08-10', 12000.00),
('CTM (Chlorpheniramine)', 'Tablet', 'tablet', 250, 25, '2027-05-05', 400.00),
('Vitamin C 500mg', 'Tablet', 'tablet', 400, 40, '2027-09-30', 600.00);

-- Pendaftaran contoh (antrian hari ini)
INSERT INTO pendaftaran (id_pasien, id_jadwal, id_dokter, tanggal_kunjungan, no_antrian, keluhan_utama, status_kunjungan) VALUES
(1, 1, 1, CURDATE(), 1, 'Demam dan batuk sejak 2 hari', 'selesai'),
(2, 1, 1, CURDATE(), 2, 'Sakit kepala dan pusing', 'menunggu'),
(3, 1, 1, CURDATE(), 3, 'Nyeri lambung setelah makan', 'menunggu'),
(4, 1, 1, CURDATE(), 4, 'Pusing dan mual', 'menunggu'),
(5, 1, 1, CURDATE(), 5, 'Kontrol rutin tekanan darah', 'menunggu');

-- Rekam medis contoh (untuk pendaftaran yang sudah selesai)
INSERT INTO rekam_medis (id_pendaftaran, id_dokter, anamnesis, pemeriksaan_fisik, diagnosis, kode_icd10, tindakan, catatan_dokter) VALUES
(1, 1, 'Pasien mengeluh demam dan batuk sejak 2 hari yang lalu, disertai pilek.',
 'Suhu tubuh 38.2C, Tekanan darah 110/70 mmHg, Nadi 88x/menit, Tenggorokan sedikit merah.',
 'ISPA (Infeksi Saluran Pernapasan Akut)', 'J06.9',
 'Istirahat cukup, banyak minum air hangat, kontrol ulang jika demam tidak turun dalam 3 hari.',
 'Pasien disarankan tidak masuk kuliah selama 2 hari untuk mencegah penularan.');

-- Resep contoh
INSERT INTO resep (id_rekam_medis, status_resep, catatan_resep) VALUES
(1, 'sudah_diambil', 'Obat diberikan langsung di klinik');

-- Detail resep contoh
INSERT INTO detail_resep (id_resep, id_obat, jumlah, aturan_pakai, catatan_pakai) VALUES
(1, 1, 10, '3x1 sehari', 'Diminum setelah makan jika demam'),
(1, 3, 1, '3x1 sendok takar sehari', 'Diminum jika batuk berlanjut');
