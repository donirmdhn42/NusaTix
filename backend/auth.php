<?php
/**
 * backend/auth.php
 * Berisi fungsi-fungsi bantuan terkait otentikasi,
 * seperti memeriksa hak akses admin.
 */

// Memulai sesi jika belum ada.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memeriksa apakah pengguna yang sedang login adalah admin.
 * Jika bukan, skrip akan berhenti dan pengguna akan diarahkan
 * ke halaman login utama. Ini adalah garda terdepan untuk
 * keamanan panel admin.
 */
function requireAdmin() {
    // Periksa apakah sesi role tidak ada atau nilainya bukan 'admin'
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // Arahkan paksa ke halaman login user jika akses ditolak
        header("Location: ../views/auth_user.php?mode=login"); 
        exit(); // Hentikan eksekusi skrip segera.
    }
}