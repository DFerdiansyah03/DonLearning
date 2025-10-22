# DonLearning - Learning Management System

Sistem manajemen pembelajaran (LMS) berbasis web untuk guru dan siswa.

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx) atau PHP built-in server

## Instalasi

1. **Clone repository:**
   ```bash
   git clone https://github.com/DFerdiansyah03/DonLearning.git
   cd DonLearning
   ```

2. **Setup database:**
   - Buat database MySQL baru dengan nama `smartlms`
   - Import file `setup_database_new.sql` ke database tersebut

3. **Konfigurasi database (opsional):**
   - Jika menggunakan kredensial database yang berbeda, edit file `config/db.php`
   - Atau buat file `.env` berdasarkan `.env.example` (jika ada)

4. **Jalankan aplikasi:**
   ```bash
   php -S localhost:8000
   ```

5. **Akses aplikasi:**
   Buka browser dan kunjungi `http://localhost:8000`

## Struktur Database

Database menggunakan nama `smartlms` dengan tabel-tabel berikut:
- `users` - Data pengguna (guru, siswa, admin)
- `classes` - Data kelas
- `class_members` - Anggota kelas
- `materials` - Materi pembelajaran
- `quizzes` - Data kuis
- `questions` - Pertanyaan kuis
- `choices` - Pilihan jawaban
- `attempts` - Percobaan kuis siswa
- `answers` - Jawaban siswa
- `forum_posts` - Postingan forum
- `forum_comments` - Komentar forum

## Fitur

### Untuk Guru:
- Membuat dan mengelola kelas
- Upload materi pembelajaran
- Membuat dan mengelola kuis
- Melihat hasil kuis siswa
- Mengelola anggota kelas

### Untuk Siswa:
- Bergabung ke kelas menggunakan token
- Mengakses materi pembelajaran
- Mengerjakan kuis
- Melihat nilai kuis

## Troubleshooting

### Masalah koneksi database:
1. Pastikan MySQL server sedang berjalan
2. Periksa kredensial database di `config/db.php`
3. Pastikan database `smartlms` sudah dibuat dan tabel-tabel sudah di-import

### Error saat menjalankan:
1. Pastikan PHP terinstall dengan ekstensi mysqli
2. Periksa permission file dan folder
3. Pastikan port 8000 tidak digunakan aplikasi lain

## Kontribusi

Silakan buat issue atau pull request untuk kontribusi.

## Lisensi

MIT License
