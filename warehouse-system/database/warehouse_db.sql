-- Buat database
CREATE DATABASE IF NOT EXISTS warehouse_barus_julu;
USE warehouse_barus_julu;

-- Tabel users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Tabel wortel (master data)
CREATE TABLE wortel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_wortel VARCHAR(20) UNIQUE NOT NULL,
    jenis_wortel VARCHAR(50) NOT NULL,
    berat DECIMAL(10,2) NOT NULL,
    kualitas ENUM('Premium', 'Standard', 'Kelas 2') DEFAULT 'Standard',
    lokasi_penyimpanan VARCHAR(100),
    tanggal_input DATE NOT NULL,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tabel stok masuk
CREATE TABLE stock_in (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal_masuk DATE NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    asal_panen VARCHAR(100) NOT NULL,
    kode_wortel VARCHAR(20),
    petugas_id INT NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (petugas_id) REFERENCES users(id),
    FOREIGN KEY (kode_wortel) REFERENCES wortel(kode_wortel)
);

-- Tabel stok keluar
CREATE TABLE stock_out (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal_keluar DATE NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    tujuan_distribusi VARCHAR(100) NOT NULL,
    kode_wortel VARCHAR(20),
    petugas_id INT NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (petugas_id) REFERENCES users(id),
    FOREIGN KEY (kode_wortel) REFERENCES wortel(kode_wortel)
);

-- Tabel aktivitas log
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Gudang', 'admin');

-- Insert default petugas user (password: petugas123)
INSERT INTO users (username, password, full_name, role) VALUES 
('petugas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Gudang', 'petugas');