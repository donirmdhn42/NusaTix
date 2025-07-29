<?php
// backend/helpers/functions.php

/**
 * Berisi kumpulan fungsi pembantu umum yang bisa digunakan di seluruh aplikasi.
 */

if (!function_exists('getInitials')) {
    /**
     * Mengambil 1 atau 2 huruf inisial dari nama lengkap.
     *
     * @param string $name Nama lengkap pengguna.
     * @return string Inisial nama.
     */
    function getInitials($name)
    {
        $words = explode(" ", trim($name));
        $initials = "";
        if (count($words) >= 2) {
            $initials .= strtoupper(substr($words[0], 0, 1));
            $initials .= strtoupper(substr($words[count($words) - 1], 0, 1));
        } elseif (count($words) == 1 && strlen($words[0]) > 0) {
            $initials .= strtoupper(substr($words[0], 0, 1));
        }
        return $initials ?: 'U'; // 'U' untuk User jika nama kosong
    }
}