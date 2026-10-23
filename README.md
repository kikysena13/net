# NIH LINK PORALNYA YAA
# Nusa Campus — Portal Kampus

Portal praktikum lokal dengan frontend HTML/CSS/JavaScript statis dan backend PHP + MySQL. **Versi ini sengaja menyimpan password plaintext untuk demonstrasi risiko dan API hanya menerima koneksi dari komputer lokal. Jangan deploy backend ini ke internet.** Query tetap menggunakan prepared statements, sesi server, otorisasi berdasarkan role, dan perlindungan CSRF untuk operasi yang mengubah data.

## Struktur proyek

```text
frontend/
  index.html
  login.html
  admin.html
  mahasiswa.html
  config.js
  assets/
    app.js
    styles.css
backend/
  api.php
  config.php
database/
  portal_kampus.sql
README.md
```

## Persyaratan

- Windows 10/11, XAMPP (Apache, PHP, MySQL/MariaDB), dan VS Code.
- PHP 8.0+ dengan ekstensi `pdo_mysql` dan `mbstring`.
- Browser modern. Koneksi internet hanya dibutuhkan untuk font Google; font cadangan tetap tersedia.

## Menjalankan lokal dengan XAMPP

1. Salin folder proyek ke `C:\xampp\htdocs\portal-kampus`.
2. Buka XAMPP Control Panel dan jalankan **Apache** serta **MySQL**.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, lalu impor `database/portal_kampus.sql`. Skrip ini membuat ulang tabel dan data demo di database `portal_kampus`; jangan impor ke database yang berisi data penting.
4. Buka `frontend/config.js`. URL bawaan `http://localhost/portal-kampus/backend/api.php` sesuai dengan lokasi folder di langkah pertama. Jika folder berbeda, sesuaikan URL.
5. Buka `http://localhost/portal-kampus/frontend/login.html`.
6. Masuk dengan salah satu akun:

   | Peran | Username | Password |
   |---|---|---|
   | Admin | `admin` | `DemoAdmin123!` |
   | Mahasiswa | `231001` | `DemoMhs123!` |

Konfigurasi koneksi bawaan untuk XAMPP adalah host `127.0.0.1`, database `portal_kampus`, user `root`, password kosong. Untuk nilai lain, set environment variables PHP berikut sebelum menjalankan Apache: `PORTAL_DB_HOST`, `PORTAL_DB_NAME`, `PORTAL_DB_USER`, `PORTAL_DB_PASSWORD`. Jangan menaruh kredensial database di file frontend atau repository publik.

### Membuka di VS Code

Pilih **File → Open Folder...** dan buka `C:\xampp\htdocs\portal-kampus`. Jalankan situs melalui Apache, bukan dengan membuka HTML sebagai `file://` atau mengandalkan Live Server: API PHP dan cookie sesi membutuhkan server.

## Fitur

- **Login:** validasi browser dan server, autentikasi sesi lokal, perbandingan password plaintext untuk kebutuhan lab, pesan kesalahan generik, dan pengalihan sesuai role.
- **Dashboard Admin:** statistik, daftar mahasiswa, tambah/edit/hapus profil dan username login. Password baru wajib minimal 10 karakter; saat edit kosongkan password jika tidak ingin menggantinya.
- **Dashboard Mahasiswa:** profil, jadwal, ruangan, SKS, dan nilai; backend mencari profil berdasarkan ID dari sesi, bukan ID yang dikirim pengguna.
- **Keamanan API:** semua query memakai PDO prepared statements. Operasi admin dibatasi role dan token CSRF; sesi memakai cookie HttpOnly dan konfigurasi SameSite.

## API

Semua endpoint berada di `backend/api.php` dan menerima/merespons JSON:

| Aksi | HTTP | Hak akses | Fungsi |
|---|---|---|---|
| `?action=login` | POST | Publik | Masuk; mengembalikan user dan token CSRF |
| `?action=logout` | POST | Sesi aktif + CSRF | Keluar |
| `?action=dashboard` | GET | Sesi aktif | Data dashboard yang sesuai role |
| `?action=student_save` | POST | Admin + CSRF | Tambah atau perbarui mahasiswa |
| `?action=student_delete` | POST | Admin + CSRF | Hapus mahasiswa |

Frontend menggunakan `credentials: "include"`; browser menyimpan sesi dalam cookie, bukan `localStorage`.

## Praktikum: SQL Injection dan password

Lakukan pembahasan ini hanya pada lingkungan lokal terisolasi dengan data dummy. **Aplikasi ini tidak menyediakan endpoint login rentan atau bypass.** Jangan menyalin pola rentan ke server publik, menguji sistem pihak lain, atau mengisi database ini dengan data nyata.

Pola login yang rentan adalah menyisipkan nilai input langsung ke string SQL:

```php
$sql = "SELECT id FROM users WHERE username = '$username' AND password = '$password'";
$result = $pdo->query($sql);
```

Input diperlakukan sebagai bagian dari sintaks SQL, bukan sebagai data terpisah. Ini dapat mengubah makna kondisi, membuka data, atau melewati pemeriksaan autentikasi. Untuk pengujian terkontrol, gunakan code review atau salinan latihan tanpa jaringan: bandingkan bagaimana query gabungan berubah ketika karakter khusus dimasukkan pada kolom input, amati hasil dan log lokal, lalu pulihkan snapshot data. Jangan menguji bypass pada portal aktif. Sebagai perbandingan, backend portal memisahkan query dan nilai:

```php
$statement = $pdo->prepare(
    'SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1'
);
$statement->execute(['username' => $username]);
```

Prepared statement memastikan input menjadi nilai parameter, bukan sintaks query.

Password plaintext dapat dibaca siapa pun yang memperoleh akses ke dump database dan memperbesar dampak kebocoran (termasuk penggunaan ulang password di layanan lain). **Database dan CRUD proyek ini memang menyimpan password plaintext sebagai demonstrasi lokal.** API menolak koneksi yang bukan dari loopback (`127.0.0.1`/`::1`), tetapi pembatasan ini bukan pengganti hashing.

```php
$hash = password_hash($password, PASSWORD_DEFAULT);
```

Pulihkan keamanan sebelum aplikasi dipakai sungguhan: ubah kolom `password` menjadi `password_hash`, simpan hasil `password_hash()` saat membuat atau memperbarui akun, dan verifikasi login dengan `password_verify($password, $hash)`. Jangan membandingkan password secara langsung atau menyimpan password asli. Reset seluruh password dan hapus mode loopback-only setelah migrasi selesai.

## Deployment frontend ke GitHub Pages

GitHub Pages hanya menyajikan berkas statis. Ia **tidak** menjalankan PHP atau MySQL. Backend harus tetap di server PHP/MySQL terpisah dengan HTTPS.

1. Buat repository GitHub baru dan unggah isi folder `frontend` ke root repository (atau set folder publikasi Pages sesuai struktur pilihan).
2. Di GitHub, buka **Settings → Pages**, pilih branch publikasi dan folder root, lalu simpan. Tunggu URL Pages muncul.
3. **Jangan deploy backend versi ini:** backend sengaja menyimpan plaintext dan hanya mengizinkan koneksi loopback. Sebelum menyiapkan API PHP/MySQL publik, pulihkan hashing dengan `password_hash()`/`password_verify()`, migrasikan kolom database, reset seluruh password, lalu lakukan review keamanan. Jangan impor akun demo plaintext ke layanan publik.
4. Atur `PORTAL_ALLOWED_ORIGINS` di server API menjadi origin Pages yang tepat, misalnya `https://nama-user.github.io` (tanpa path). Jangan menggunakan wildcard karena API memakai cookie kredensial.
5. Atur `PORTAL_SESSION_SAMESITE=None` dan HTTPS pada API untuk frontend/API beda situs. Cookie lintas situs dapat diblokir oleh pengaturan privasi browser; untuk penggunaan nyata, host frontend dan API pada situs yang sama (misalnya subdomain) atau gunakan arsitektur autentikasi yang disediakan hosting.
6. Ubah `frontend/config.js` menjadi URL HTTPS API, commit, dan push. Jangan mengisi konfigurasi tersebut dengan rahasia database.
7. Verifikasi login untuk kedua role, CRUD admin, logout, serta penolakan akses role yang salah. Jangan deploy versi rentan sebagai endpoint publik.

Perubahan konfigurasi server yang diperlukan:

```text
PORTAL_ALLOWED_ORIGINS=https://nama-user.github.io
PORTAL_SESSION_SAMESITE=None
PORTAL_DB_HOST=...
PORTAL_DB_NAME=portal_kampus
PORTAL_DB_USER=...
PORTAL_DB_PASSWORD=...
```

## Troubleshooting

- **API tidak dapat dijangkau:** pastikan Apache berjalan, buka frontend melalui `http://localhost`, dan cek `apiUrl` di `frontend/config.js`.
- **Koneksi MySQL gagal:** pastikan MySQL aktif, database sudah diimpor, dan environment variables / kredensial cocok.
- **Login berhasil tetapi dashboard kembali ke login:** izinkan cookie situs di browser; pada deployment lintas situs perlu HTTPS, `SameSite=None`, origin CORS yang tepat, dan cookie pihak ketiga tidak boleh diblokir.
- **Origin tidak diizinkan:** cocokkan nilai `Origin` browser secara persis dengan `PORTAL_ALLOWED_ORIGINS`, termasuk skema (`http`/`https`) dan port bila ada.
- **Endpoint menyatakan profil tidak ditemukan:** gunakan akun demo hasil impor atau minta admin membuat profil yang terkait ke akun mahasiswa.
- **Periksa error PHP:** lihat log Apache/PHP XAMPP. API tidak mengirim detail internal database ke browser.
