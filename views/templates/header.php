<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../backend/helpers/functions.php';
require_once __DIR__ . '/../../backend/auth.php';

$user_initials = isset($_SESSION['user_name']) ? getInitials($_SESSION['user_name']) : '';
$locations = ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Medan', 'Makassar', 'Denpasar'];
?>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .mobile-nav-link.mobile-nav-active .nav-icon-wrapper {
        background-color: rgba(233, 41, 50, 0.15);
    }

    .mobile-nav-link.mobile-nav-active .nav-icon,
    .mobile-nav-link.mobile-nav-active .nav-text {
        color: #e92932;
    }

    .mobile-nav-link.mobile-nav-active .nav-text {
        font-weight: 600;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<header class="hidden md:flex items-center justify-between whitespace-nowrap border-b border-solid border-b-[#382929] px-10 py-3 text-white sticky top-0 bg-[#181111] z-20">
    <div class="flex-1">
        <a href="index.php" class="flex items-center gap-4">
            <div class="size-5 text-red-500">
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path></svg>
            </div>
            <h2 class="text-lg font-bold">NusaTix</h2>
        </a>
    </div>

    <nav class="flex items-center gap-9">
        <a class="text-sm font-medium hover:text-red-400" href="index.php">Home</a>
        <a class="text-sm font-medium hover:text-red-400" href="my_tickets.php">My Ticket</a>
        <a class="text-sm font-medium hover:text-red-400" href="riwayat_transaksi.php">Riwayat</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a class="text-sm font-medium hover:text-red-400" href="../admin/dashboard.php">Dashboard Admin</a>
        <?php endif; ?>
    </nav>

    <div class="flex-1 flex items-center justify-end gap-8">
        <div x-data="{ open: false, selectedLocation: 'Pilih Lokasi' }" x-init="selectedLocation = localStorage.getItem('selectedLocation') || 'Pilih Lokasi'" class="relative" x-cloak>
            <button @click="open = !open" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white">
                <i class="fas fa-map-marker-alt"></i>
                <span x-text="selectedLocation"></span>
                <i class="fas fa-chevron-down fa-xs transition-transform" :class="{'rotate-180': open}"></i>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-[#211717] border border-gray-700 rounded-md shadow-lg z-20">
                <?php foreach ($locations as $location): ?>
                    <a href="#" @click.prevent="selectedLocation = '<?= $location ?>'; localStorage.setItem('selectedLocation', '<?= $location ?>'); open = false" class="block px-4 py-2 text-sm text-gray-300 hover:bg-red-800/50"><?= $location ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" title="Profil Saya" class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                <?= $user_initials ?>
            </a>
        <?php else: ?>
            <div class="flex items-center gap-4">
                <a href="auth_user.php?mode=login" class="flex min-w-[84px] items-center justify-center rounded-full h-10 px-4 bg-[#e92932] hover:bg-red-700 text-white text-sm font-bold transition-colors">Masuk</a>
                <a href="auth_user.php?mode=register" class="flex min-w-[84px] items-center justify-center rounded-full h-10 px-4 border border-gray-600 hover:bg-gray-800 text-white text-sm font-bold transition-colors">Daftar</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<div x-data="{ locationModalOpen: false }" class="md:hidden">
    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-b-[#382929] px-4 py-3 text-white sticky top-0 bg-[#181111] z-20">
        <a href="index.php" class="flex items-center gap-3">
            <div class="size-5 text-red-500">
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path></svg>
            </div>
            <h2 class="text-lg font-bold">NusaTix</h2>
        </a>
        <button @click="locationModalOpen = true" class="flex items-center gap-2 text-sm">
            <i class="fas fa-map-marker-alt"></i>
            <span id="mobile-location-display">Pilih Lokasi</span>
        </button>
    </header>

    <div x-show="locationModalOpen" x-transition class="fixed inset-0 bg-black/80 z-40 flex items-center justify-center p-4" x-cloak>
        <div @click.away="locationModalOpen = false" class="bg-[#211717] w-full max-w-sm rounded-lg border border-gray-700 p-5">
            <h3 class="text-lg font-bold mb-4 text-white">Pilih Lokasi Bioskop</h3>
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($locations as $location): ?>
                    <a href="#" data-location="<?= $location ?>" class="location-link-mobile block p-3 text-center text-sm text-gray-300 bg-gray-800 rounded-md hover:bg-red-800/50"><?= $location ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="h-16"></div> 
    
    <nav class="fixed bottom-0 left-0 right-0 h-16 bg-[#211717] border-t border-solid border-t-[#382929] flex justify-around items-center z-20">
        <a href="index.php" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
            <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-home fa-lg nav-icon"></i></span>
            <span class="text-xs mt-1 nav-text">Home</span>
        </a>
        <a href="my_tickets.php" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
            <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-ticket-alt fa-lg nav-icon"></i></span>
            <span class="text-xs mt-1 nav-text">My Ticket</span>
        </a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="riwayat_transaksi.php" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
                <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-history fa-lg nav-icon"></i></span>
                <span class="text-xs mt-1 nav-text">Riwayat</span>
            </a>
            <a href="profile.php" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
                <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-user-circle fa-lg nav-icon"></i></span>
                <span class="text-xs mt-1 nav-text">Profil</span>
            </a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="../admin/dashboard.php" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
                    <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-tachometer-alt fa-lg nav-icon"></i></span>
                    <span class="text-xs mt-1 nav-text">Admin</span>
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="auth_user.php?mode=login" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
                <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-sign-in-alt fa-lg nav-icon"></i></span>
                <span class="text-xs mt-1 nav-text">Masuk</span>
            </a>
            <a href="auth_user.php?mode=register" class="mobile-nav-link flex flex-col items-center justify-center text-gray-400 w-full pt-1">
                <span class="nav-icon-wrapper w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-user-plus fa-lg nav-icon"></i></span>
                <span class="text-xs mt-1 nav-text">Daftar</span>
            </a>
        <?php endif; ?>
    </nav>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileLocationDisplay = document.getElementById('mobile-location-display');

        function updateLocationDisplay() {
            const savedLocation = localStorage.getItem('selectedLocation') || 'Pilih Lokasi';
            if (mobileLocationDisplay) {
                mobileLocationDisplay.textContent = savedLocation;
            }
        }
        updateLocationDisplay();
        document.querySelectorAll('.location-link-mobile').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const newLocation = this.getAttribute('data-location');
                localStorage.setItem('selectedLocation', newLocation);
                updateLocationDisplay();
                document.dispatchEvent(new CustomEvent('close-location-modal'));
            });
        });

        document.addEventListener('close-location-modal', () => {
            const modalComponent = document.querySelector('[x-data="{ locationModalOpen: false }"]');
            if (modalComponent && modalComponent.__x) {
                modalComponent.__x.data.locationModalOpen = false;
            }
        });

        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const authMode = new URLSearchParams(window.location.search).get('mode');
        let effectivePage = currentPage;
        if (currentPage === 'auth_user.php') {
             effectivePage = authMode ? `auth_user.php?mode=${authMode}` : 'auth_user.php?mode=login';
        }
        
        const navLinks = document.querySelectorAll('.mobile-nav-link');

        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            if (effectivePage === linkHref) {
                link.classList.add('mobile-nav-active');
            }
        });
    });
</script>