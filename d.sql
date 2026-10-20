CREATE DATABASE IF NOT EXISTS portal_kampus
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE portal_kampus;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS nilai;
DROP TABLE IF EXISTS jadwal_kuliah;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS mata_kuliah;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mahasiswa') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE mahasiswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    nim VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    program_studi VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT chk_mahasiswa_semester CHECK (semester BETWEEN 1 AND 14),
    CONSTRAINT fk_mahasiswa_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mata_kuliah (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_mk VARCHAR(20) NOT NULL UNIQUE,
    nama_mk VARCHAR(100) NOT NULL,
    sks TINYINT UNSIGNED NOT NULL DEFAULT 2,
    CONSTRAINT chk_mata_kuliah_sks CHECK (sks BETWEEN 1 AND 6)
) ENGINE=InnoDB;

CREATE TABLE jadwal_kuliah (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT UNSIGNED NOT NULL,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruangan VARCHAR(30) NOT NULL,
    CONSTRAINT chk_jadwal_waktu CHECK (jam_selesai > jam_mulai),
    CONSTRAINT uq_jadwal_mahasiswa_mk UNIQUE (mahasiswa_id, mata_kuliah_id),
    CONSTRAINT fk_jadwal_mahasiswa FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa(id) ON DELETE CASCADE,
    CONSTRAINT fk_jadwal_mata_kuliah FOREIGN KEY (mata_kuliah_id)
        REFERENCES mata_kuliah(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE nilai (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT UNSIGNED NOT NULL,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    nilai_angka DECIMAL(5, 2) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    CONSTRAINT chk_nilai_angka CHECK (nilai_angka BETWEEN 0 AND 100),
    CONSTRAINT chk_nilai_semester CHECK (semester BETWEEN 1 AND 14),
    CONSTRAINT uq_nilai_mahasiswa_mk UNIQUE (mahasiswa_id, mata_kuliah_id),
    CONSTRAINT fk_nilai_mahasiswa FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa(id) ON DELETE CASCADE,
    CONSTRAINT fk_nilai_mata_kuliah FOREIGN KEY (mata_kuliah_id)
        REFERENCES mata_kuliah(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (id, username, password, role) VALUES
    (1, 'admin', 'DemoAdmin123!', 'admin'),
    (2, '231001', 'DemoMhs123!', 'mahasiswa');

INSERT INTO mahasiswa (id, user_id, nim, nama, program_studi, semester) VALUES
    (1, 2, '231001', 'Mahasiswa Demo', 'Manajemen Informatika', 3);

INSERT INTO mata_kuliah (id, kode_mk, nama_mk, sks) VALUES
    (1, 'IF301', 'Basis Data', 3),
    (2, 'IF302', 'Pemrograman Web', 3),
    (3, 'IF303', 'Keamanan Informasi', 2),
    (4, 'IF304', 'Rekayasa Perangkat Lunak', 3);

INSERT INTO jadwal_kuliah
    (mahasiswa_id, mata_kuliah_id, hari, jam_mulai, jam_selesai, ruangan) VALUES
    (1, 1, 'Senin', '08:00:00', '10:30:00', 'Lab 2'),
    (1, 2, 'Rabu', '10:00:00', '12:30:00', 'Ruang 301'),
    (1, 3, 'Kamis', '13:00:00', '15:30:00', 'Lab Keamanan'),
    (1, 4, 'Jumat', '08:00:00', '10:30:00', 'Ruang 204');

INSERT INTO nilai (mahasiswa_id, mata_kuliah_id, nilai_angka, semester) VALUES
    (1, 1, 88.50, 2),
    (1, 2, 91.00, 3),
    (1, 3, 86.00, 3);