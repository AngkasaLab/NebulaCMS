<?php

return [
    // Layout — Step labels
    'step_requirements' => 'Persyaratan',
    'step_database'     => 'Database',
    'step_site'         => 'Situs',
    'step_admin'        => 'Admin',
    'step_install'      => 'Instal',
    'step_done'         => 'Selesai',

    // Welcome (Requirements)
    'requirements_title'       => 'Persyaratan Sistem',
    'requirements_lead'        => 'Pastikan server Anda memenuhi semua persyaratan berikut sebelum melanjutkan instalasi.',
    'php_min'                  => 'minimal :version',
    'status_ok'                => 'OK',
    'status_not_met'           => 'Tidak memenuhi',
    'extensions_title'         => 'Ekstensi PHP',
    'ext_available'            => 'Tersedia',
    'ext_not_found'            => 'Tidak ditemukan',
    'directories_title'        => 'Izin Direktori',
    'dir_writable'             => 'Dapat ditulis',
    'dir_not_writable'         => 'Tidak dapat ditulis',
    'requirements_not_met'     => 'Persyaratan belum terpenuhi.',
    'requirements_not_met_sub' => 'Perbaiki item yang gagal di atas sebelum melanjutkan.',
    'btn_continue'             => 'Lanjutkan',
    'btn_back'                 => 'Kembali',

    // Database
    'database_title'      => 'Konfigurasi Database',
    'database_lead'       => 'Masukkan detail koneksi MySQL / MariaDB Anda.',
    'connection_failed'   => 'Koneksi gagal:',
    'label_host'          => 'Host',
    'label_port'          => 'Port',
    'label_database_name' => 'Nama Database',
    'label_username'      => 'Username',
    'label_password'      => 'Password',
    'password_optional'   => '(opsional)',
    'btn_test_connection' => 'Uji Koneksi',
    'testing'             => 'Menguji…',
    'connection_success'  => 'Berhasil terhubung',
    'server_error'        => 'Gagal menghubungi server',

    // Site
    'site_title'          => 'Pengaturan Situs',
    'site_lead'           => 'Konfigurasi dasar situs Anda. Ini dapat diubah nanti melalui panel admin.',
    'label_site_name'     => 'Nama Situs',
    'label_site_url'      => 'URL Situs',
    'label_environment'   => 'Lingkungan',
    'env_production'      => 'Production',
    'env_production_desc' => 'Debug nonaktif. Untuk situs live.',
    'env_development'     => 'Development',
    'env_development_desc'=> 'Debug aktif. Untuk pengembangan.',
    'label_default_lang'  => 'Bahasa Default',

    // Account
    'account_title'       => 'Akun Administrator',
    'account_lead'        => 'Buat akun super admin untuk mengelola situs Anda.',
    'label_name'          => 'Nama',
    'label_email'         => 'Email',
    'label_confirm_pass'  => 'Konfirmasi Password',
    'placeholder_min_chars'=> 'Minimal 8 karakter',
    'placeholder_repeat'  => 'Ulangi password',
    'account_info'        => 'Akun ini akan memiliki akses penuh sebagai <strong>Super Administrator</strong>.',
    'btn_start_install'   => 'Mulai Instalasi',

    // Installing
    'installing_title'       => 'Menginstal NebulaCMS',
    'installing_lead'        => 'Harap tunggu, jangan tutup halaman ini.',
    'install_step_1'         => 'Menulis file konfigurasi',
    'install_step_2'         => 'Membuat application key',
    'install_step_3'         => 'Migrasi database & peran akses',
    'install_step_4'         => 'Membuat akun administrator',
    'install_step_5'         => 'Mengisi konten contoh',
    'install_step_6'         => 'Menyelesaikan instalasi',
    'install_failed'         => 'Instalasi gagal.',
    'install_back_retry'     => '← Kembali dan coba lagi',
    'unexpected_error'       => 'Terjadi kesalahan tak terduga.',
    'cannot_reach_server'    => 'Tidak dapat menghubungi server: ',

    // Installing — controller messages
    'install_step_1_label'   => 'Menulis file konfigurasi (.env)',
    'install_step_2_label'   => 'Membuat application key',
    'install_step_3_label'   => 'Migrasi database & peran akses',
    'install_step_4_label'   => 'Membuat akun administrator',
    'install_step_5_label'   => 'Mengisi konten contoh',
    'install_step_6_label'   => 'Menyelesaikan instalasi',
    'invalid_install_request'=> 'Permintaan instalasi tidak valid. Muat ulang halaman atau mulai lagi dari langkah admin.',
    'session_expired'        => 'Sesi instalasi berakhir atau tidak dikenal. Silakan ulangi dari langkah admin.',
    'invalid_install'        => 'Instalasi tidak valid. Mulai ulang dari langkah admin.',
    'db_connection_success'  => 'Koneksi berhasil!',

    // Done
    'done_title'             => 'Instalasi Berhasil',
    'done_lead'              => 'NebulaCMS siap digunakan.',
    'done_config_written'    => 'File konfigurasi ditulis',
    'done_migration'         => 'Migrasi database selesai',
    'done_seeded'            => 'Data awal berhasil diisi',
    'done_admin_created'     => 'Akun administrator dibuat',
    'done_locked'            => 'Installer kini terkunci secara otomatis. Simpan kredensial admin Anda di tempat yang aman.',
    'btn_go_admin'           => 'Masuk ke Admin',
    'btn_view_site'          => 'Lihat Situs',

    // Language switcher
    'lang_en' => 'English',
    'lang_id' => 'Bahasa Indonesia',
];
