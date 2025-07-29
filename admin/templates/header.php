<?php
// admin/templates/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Memastikan hanya admin yang bisa mengakses halaman ini
require_once __DIR__ . '/../../backend/auth.php';
require_once __DIR__ . '/../../backend/helpers/functions.php';
requireAdmin();

$current_page = basename($_SERVER['PHP_SELF']);
$user_initials = isset($_SESSION['user_name']) ? getInitials($_SESSION['user_name']) : 'A';

// Array untuk judul halaman dinamis
$page_titles = [
    'dashboard.php' => 'Dashboard',
    'manage_films.php' => 'Manajemen Film',
    'manage_studios.php' => 'Manajemen Studio',
    'manage_schedules.php' => 'Manajemen Jadwal',
    'manage_promos.php' => 'Manajemen Promo',
    'manage_payments.php' => 'Verifikasi Pembayaran',
    'manage_testimonials.php' => 'Manajemen Testimoni'
];
$page_title = $page_titles[$current_page] ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - NusaTix Admin</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg viewBox='0 0 48 48' fill='%23ff0000' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z' fill='currentColor'%3E%3C/path%3E%3C/svg%3E" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#e92932', // Brand color
                    }
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="" />
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'" href="https://fonts.googleapis.com/css2?display=swap&family=Be+Vietnam+Pro%3Awght%4400%3B500%3B600%3B700&family=Noto+Sans%3Awght%4400%3B500%3B700" />
    <style>
        body {
            font-family: "Be Vietnam Pro", "Noto Sans", sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .sidebar-link.active {
            background-color: #e92932;
            /* Brand Color */
            color: white;
        }

        /* FIX: Menjaga warna background saat link aktif di-hover */
        .sidebar-link.active:hover {
            background-color: #e92932;
        }

        .sidebar-link.active i,
        .sidebar-link.active span {
            color: white !important;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-100">
    <div x-data="{ sidebarOpen: false }" class="flex min-h-screen">

        <aside :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" class="w-64 flex-shrink-0 bg-white flex flex-col fixed inset-y-0 left-0 z-30 transform lg:relative lg:translate-x-0 transition-transform duration-300 ease-in-out border-r border-slate-200">
            <div class="h-20 flex items-center justify-center gap-3 px-4 border-b border-slate-200">
                <div class="size-6 text-primary">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                    </svg>
                </div>
                <a href="../index.php" class="text-slate-900 text-xl font-bold">NusaTix Admin</a>
            </div>
            <nav class="flex-grow p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt fa-fw w-5 text-center text-slate-400"></i><span>Dashboard</span>
                </a>
                <a href="manage_payments.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'manage_payments.php' ? 'active' : '' ?>">
                    <i class="fas fa-credit-card fa-fw w-5 text-center text-slate-400"></i><span>Verifikasi Bayar</span>
                </a>
                <a href="manage_films.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'manage_films.php' ? 'active' : '' ?>">
                    <i class="fas fa-film fa-fw w-5 text-center text-slate-400"></i><span>Manajemen Film</span>
                </a>
                <a href="manage_studios.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'manage_studios.php' ? 'active' : '' ?>">
                    <i class="fas fa-person-booth fa-fw w-5 text-center text-slate-400"></i><span>Manajemen Studio</span>
                </a>
                <a href="manage_schedules.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'manage_schedules.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt fa-fw w-5 text-center text-slate-400"></i><span>Manajemen Jadwal</span>
                </a>
                <a href="manage_promos.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'manage_promos.php' ? 'active' : '' ?>">
                    <i class="fas fa-tags fa-fw w-5 text-center text-slate-400"></i><span>Manajemen Promo</span>
                </a>
                <a href="manage_testimonials.php" class="sidebar-link flex items-center gap-4 px-4 py-2.5 rounded-lg font-semibold text-sm text-slate-600 hover:bg-slate-100 transition-colors <?= $current_page == 'manage_testimonials.php' ? 'active' : '' ?>">
                    <i class="fas fa-comment-dots fa-fw w-5 text-center text-slate-400"></i><span>Manajemen Testimoni</span>
                </a>
            </nav>

            <div class="p-4 mt-auto border-t border-slate-200">
                <a href="../views/auth_user.php?action=logout" class="sidebar-link flex items-center gap-4 w-full px-4 py-2.5 rounded-lg font-semibold text-sm text-primary hover:bg-primary/10 transition-colors">
                    <i class="fas fa-sign-out-alt fa-fw w-5 text-center"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-20 lg:hidden" x-cloak></div>

        <div class="flex-1 flex flex-col h-screen overflow-y-auto">
            <header class="h-20 bg-white/80 backdrop-blur-sm border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-slate-500 hover:text-slate-900">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
                <div x-data="{ profileOpen: false }" class="relative">
                    <button @click="profileOpen = !profileOpen" class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold">
                        <?= htmlspecialchars($user_initials) ?>
                    </button>
                    <div x-show="profileOpen" @click.away="profileOpen = false" x-transition class="absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-md shadow-lg z-20" x-cloak>
                        <div class="px-4 py-3 border-b border-slate-200">
                            <p class="text-sm text-slate-800 font-semibold"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                            <p class="text-xs text-slate-500 truncate">Administrator</p>
                        </div>
                        <a href="../views/auth_user.php?action=logout" class="block w-full text-left px-4 py-3 text-sm text-primary hover:bg-primary/10">
                            <i class="fas fa-sign-out-alt fa-fw mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </header>
            <main class="flex-grow">