## Deskripsi aplikasi
StayPoint adalah aplikasi bergerak berbasis Android/iOS yang berfungsi untuk melakukan pencarian dan reservasi kamar hotel secara real-time. Proyek ini dibangun untuk memenuhi komponen penilaian UAS mata kuliah Pemrograman Berbasis Platform (PBP) 2026, Program Studi Informatika, Universitas Atma Jaya Yogyakarta.

## Identitas Pengembang
* **Nama:** Raymond Wijaya Gautama - 230712363
* **Program Studi:** Informatika
* **Institusi:** Universitas Atma Jaya Yogyakarta (UAJY)

##  Komponen Penilaian UAS PBP (Ceklis Fitur)

Berdasarkan lembar komponen penilaian UAS PBP 2026, berikut adalah fungsionalitas yang telah diimplementasikan dalam proyek StayPoint:

### 1. Hosting & Deployment (15%)
* **Back-end System & Database:** REST API Laravel dan database MySQL telah berhasil dihosting secara online dan dapat diakses publik.
* **Device Deployment:** Aplikasi Flutter berhasil dideploy dan dijalankan langsung pada perangkat HP fisik / emulator.

### 2. Autentifikasi (5%)
* **Registrasi & Login:** Form dilengkapi dengan validasi input (tidak boleh kosong/format email harus valid).
* **Konfirmasi Dialog:** Fitur alert dialog konfirmasi muncul sebelum proses registrasi dieksekusi.
* **Toast / SnackBar:** Aplikasi menampilkan floating SnackBar sukses berwarna hijau ketika user berhasil login maupun register.
* **Integrasi API:** Sistem autentikasi sepenuhnya terhubung dengan endpoint backend Laravel.

### 3. Read & Update Profil (10%)
* **Menampilkan & Mengubah Data:** Fitur membaca data profil user dan memperbaruinya secara dinamis.
* **Manajemen Gambar Profil:** Menampilkan dan mengunggah foto profil baru dari perangkat ke server melalui API.

### 4. CRUD Transaksi (15%) & Full API (20%)
* **Dua Fitur CRUD Transaksi:** Implementasi penuh manajemen reservasi hotel (Booking Kamar) dan riwayat transaksi yang mencakup operasi Create, Read, Update, dan Delete.
* **Koneksi Rest API:** Seluruh fungsionalitas aplikasi menggunakan integrasi REST API secara end-to-end.

### 5. Penggunaan Library UI (12%) & Estetika (5%)
* Menggunakan minimal 3 library eksternal untuk mempercantik UI dan fungsionalitas data:
  1. `image_picker` (Akses Media & Kamera)
  2. `google_nav_bar` (Menu Navigasi Bawah Modern)
  3. `http` / `dio` (Komunikasi REST API)
* Tampilan antarmuka rapi, responsif, dan konsisten dengan rancangan desain UTS.

### 6. Integrasi Hardware (13%)
* **Kamera:** Mengimplementasikan fitur kamera bawaan perangkat menggunakan perantara library `image_picker` untuk mengubah foto profil secara langsung.
* **Hardware Tambahan:** (Isi dengan hardware kedua Anda, contoh: Geolocation/Biometric Login).



## Cara Menjalankan Proyek secara Lokal

### Prerequisites
* Flutter SDK (Versi terbaru)
* PHP >= 8.x & Composer
* MySQL / XAMPP

### 1. Pengaturan Backend (Laravel)
1. Masuk ke folder backend: `cd staypoint-backend`
2. Install dependensi: `composer install`
3. Salin file konfigurasi: `cp .env.example .env` (Sesuaikan pengaturan database Anda di `.env`)
4. Jalankan migrasi & seeder database: `php artisan migrate --seed`
5. Jalankan server lokal: `php artisan serve --host=0.0.0.0`

### 2. Pengaturan Frontend (Flutter)
1. Masuk ke folder frontend: `cd staypoint-frontend`
2. Ambil paket library: `flutter pub get`
3. Sesuaikan `baseUrl` pada file `lib/api_service.dart` dengan IP lokal laptop Anda atau URL hasil hosting.
4. Jalankan aplikasi ke emulator/HP: `flutter run`