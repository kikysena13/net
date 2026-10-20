Buatkan sebuah proyek web **Portal Kampus untuk praktikum keamanan aplikasi** dengan tiga halaman utama: Login, Dashboard Admin, dan Dashboard Mahasiswa.

**Teknologi dan hosting:**
- Frontend menggunakan HTML, CSS, dan JavaScript.
- Backend menggunakan PHP.
- Database menggunakan MySQL, dapat dikelola melalui phpMyAdmin.
- Frontend harus dapat di-deploy ke GitHub Pages.
- Backend dijalankan secara lokal menggunakan XAMPP.
- Berikan panduan untuk menjalankan proyek di Windows 10/11 dan VS Code.

**Fitur halaman:**
1. **Login:** form username dan password, validasi input, sesi login, dan pengalihan berdasarkan role.
2. **Dashboard Admin:** statistik mahasiswa, daftar mahasiswa, NIM, program studi, semester, serta fitur CRUD data mahasiswa.
3. **Dashboard Mahasiswa:** profil mahasiswa, NIM, program studi, semester, jadwal kuliah, nilai dummy, dan informasi akademik.

**Desain UI/UX:**
Gunakan desain modern yang terinspirasi dari portal universitas dan Discord, dengan kombinasi warna navy, ungu, dan aksen biru. Buat sidebar, kartu statistik, tabel responsif, navigasi yang konsisten, dan tampilan yang nyaman digunakan di desktop maupun perangkat mobile.

**Database:**
Buat query SQL lengkap untuk:
- Membuat database `portal_kampus`.
- Membuat tabel `users`, `mahasiswa`, `mata_kuliah`, `jadwal_kuliah`, dan `nilai`.
- Menentukan primary key, foreign key, unique constraint, dan relasi antartabel.
- Memasukkan akun admin, akun mahasiswa, dan data akademik dummy.
- Menyediakan akun demo beserta username dan password untuk pengujian lokal.

**Praktikum keamanan aplikasi:**
Tujuan praktikum adalah memahami SQL Injection dan risiko penyimpanan password plaintext. Sediakan lingkungan lab lokal yang terisolasi, menggunakan data dummy saja. Jelaskan pola query SQL yang rentan, alasan kerentanannya, cara menguji dampak secara terkontrol, serta perbandingannya dengan prepared statements. Tunjukkan secara konseptual risiko password plaintext dan cara memperbaikinya dengan `password_hash()` dan `password_verify()`. Jangan mengaktifkan endpoint login yang sengaja dapat dibypass pada hosting publik. Pisahkan demonstrasi kerentanan dari implementasi login yang aman.

**Struktur proyek:**
Pisahkan folder `frontend`, `backend`, dan `database`. Sertakan seluruh kode yang diperlukan, termasuk file HTML, CSS, JavaScript, PHP API, konfigurasi database, autentikasi sesi, otorisasi berdasarkan role, logout, serta file SQL.

**Ketentuan implementasi:**
- Semua halaman harus benar-benar berfungsi dan terhubung dengan API.
- Mahasiswa hanya boleh melihat data miliknya sendiri.
- Admin dapat mengelola data mahasiswa.
- Validasi input harus diterapkan di frontend dan backend.
- Gunakan prepared statements untuk implementasi login yang aman.
- Jangan menyimpan kredensial database di frontend.
- Jangan menggunakan `localStorage` sebagai pengganti autentikasi server.
- Jelaskan bahwa GitHub Pages hanya menjalankan frontend statis sehingga PHP dan MySQL harus berjalan di server terpisah.
- Untuk pengujian lokal, jelaskan konfigurasi API dan cara menjalankan XAMPP.
- Untuk deployment, berikan langkah membuat repository GitHub, mengunggah frontend, mengaktifkan GitHub Pages, serta mengonfigurasi URL API.
- Sertakan README yang berisi instalasi, konfigurasi, akun demo, pengujian fitur, dan panduan troubleshooting.

**Format jawaban:**
Berikan proyek secara lengkap dan sistematis, dimulai dari struktur folder, query SQL, kode setiap file, cara menjalankan secara lokal, skenario praktikum SQL Injection, perbaikan keamanan, hingga deployment GitHub Pages. Pastikan nama file, endpoint API, struktur database, dan kode frontend konsisten satu sama lain. Hindari kode pseudocode atau bagian yang hanya berupa placeholder tanpa penjelasan.