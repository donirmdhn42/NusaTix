<?php
session_start();

// Jika pengguna belum login, arahkan ke halaman login
if (!isset($_SESSION['user_id'])) {
    header('Location: auth_user.php?mode=login');
    exit();
}

// Memanggil file functions terpusat
require_once __DIR__ . '/../backend/helpers/functions.php';

// Ambil data pengguna dari sesi
$user_name = $_SESSION['user_name'] ?? 'Pengguna';
$user_email = $_SESSION['user_email'] ?? 'Email tidak ditemukan'; // Sekarang akan terisi

$user_initials = getInitials($user_name);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - NusaTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-[#181111] font-sans text-gray-300">
    <div class="flex flex-col min-h-screen">
        <?php include __DIR__ . '/templates/header.php'; ?>

        <main class="flex-grow px-4 md:px-10 lg:px-20 py-8">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col sm:flex-row items-center gap-6 mb-10">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-red-600 flex-shrink-0 flex items-center justify-center text-white font-bold text-4xl border-4 border-gray-800">
                        <?= htmlspecialchars($user_initials) ?>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white text-center sm:text-left"><?= htmlspecialchars($user_name) ?></h1>
                        <p class="text-gray-400 mt-1 text-center sm:text-left"><?= htmlspecialchars($user_email) ?></p>
                    </div>
                </div>

                <div class="space-y-8">
                    <div>
                        <h2 class="text-xs font-bold uppercase text-gray-500 mb-3 px-1">Akun & Keamanan</h2>
                        <div class="bg-[#211717] border border-gray-700 rounded-lg shadow-lg">
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-user-edit menu-icon"></i><span>Pengaturan Profil</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-shield-alt menu-icon"></i><span>Keamanan Akun</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-credit-card menu-icon"></i><span>Metode Pembayaran</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item border-none"><i class="fas fa-history menu-icon"></i><span>Aktivitas</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xs font-bold uppercase text-gray-500 mb-3 px-1">Lainnya</h2>
                        <div class="bg-[#211717] border border-gray-700 rounded-lg shadow-lg">
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-tags menu-icon"></i><span>Promo & Voucher</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-language menu-icon"></i><span>Bahasa</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-universal-access menu-icon"></i><span>Aksesibilitas</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-question-circle menu-icon"></i><span>Pusat Bantuan</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item"><i class="fas fa-user-secret menu-icon"></i><span>Kebijakan Privasi</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                            <a href="#" onclick="showWIPAlert(); return false;" class="menu-item border-none"><i class="fas fa-user-plus menu-icon"></i><span>Undang Teman</span><i class="fas fa-chevron-right menu-arrow"></i></a>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-700 mt-6">
                    <button id="logoutButton" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded-md text-white text-base font-medium transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
                </div>
            </div>
        </main>
    </div>
    <style>
        .menu-item {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid #374151;
            transition: background-color 0.2s;
        }

        .menu-item:hover {
            background-color: rgba(31, 41, 55, 0.5);
        }

        .menu-item.border-none {
            border-bottom: none;
        }

        .menu-icon {
            width: 1.25rem;
            text-align: center;
            margin-right: 1rem;
            color: #9CA3AF;
        }

        .menu-item span {
            flex-grow: 1;
            color: white;
        }

        .menu-arrow {
            color: #6B7280;
        }
    </style>
   <script>
    document.addEventListener('DOMContentLoaded', function () {
        const logoutButton = document.getElementById('logoutButton');

        if (logoutButton) {
            logoutButton.addEventListener('click', function (e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Konfirmasi Logout',
                    text: 'Apakah Anda yakin ingin keluar dari akun?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Logout',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e92932',
                    cancelButtonColor: '#4B5563',
                    background: '#211717',
                    color: '#E5E7EB',
                    customClass: {
                        popup: 'border border-gray-700 rounded-lg'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'auth_user.php?action=logout';
                    }
                });
            });
        }

        // Fungsi untuk alert fitur belum tersedia
        function showWIPAlert() {
            Swal.fire({
                icon: 'info',
                title: 'Segera Hadir',
                text: 'Fitur ini sedang dalam tahap pengembangan dan akan segera tersedia.',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#e92932',
                background: '#211717',
                color: '#E5E7EB',
                customClass: {
                    popup: 'border border-gray-700 rounded-lg'
                }
            });
        }

        window.showWIPAlert = showWIPAlert;
    });
</script>


</body>

</html>