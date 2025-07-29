<?php
// views/my_tickets.php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/booking.php';
require_once __DIR__ . '/../backend/auth.php'; 
require_once __DIR__ . '/../backend/helpers/functions.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth_user.php?mode=login');
    exit();
}

$user_id = $_SESSION['user_id'];
$active_tickets = Booking::getActiveTicketsByUserId($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Saya - NusaTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] { display: none !important; }
        .star-rating i:hover {
            transform: scale(1.2);
            transition: color 0.2s, transform 0.2s;
        }
        /* Transisi untuk area yang diperluas */
        .transition-max-h {
            transition: max-height 0.5s ease-in-out;
        }
    </style>
</head>

<body class="bg-[#181111] font-sans text-gray-300">
    <div class="flex flex-col min-h-screen">

        <?php include __DIR__ . '/templates/header.php'; ?>

        <main class="flex-grow px-4 md:px-10 lg:px-20 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl md:text-4xl font-bold text-white">Tiket Saya</h1>
                <a href="riwayat_transaksi.php" class="text-red-400 hover:text-red-300 transition-colors">
                    Riwayat Transaksi <i class="fas fa-history ml-1"></i>
                </a>
            </div>

            <div class="space-y-6">
                <?php if (empty($active_tickets)): ?>
                    <div class="text-center py-16 bg-[#211717] rounded-lg border border-gray-700">
                        <i class="fas fa-ticket-alt fa-4x text-gray-600"></i>
                        <p class="mt-4 text-gray-400">Anda belum memiliki tiket yang aktif.</p>
                        <a href="index.php" class="mt-4 inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg">Cari Film</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_tickets as $ticket): ?>
                        <?php $has_reviewed = !empty($ticket['id_testimonial']); ?>

                        <div x-data="{ showTicket: false, showReview: false }" class="bg-[#211717] border border-gray-700 rounded-lg shadow-lg overflow-hidden transition-all duration-300">
                            <div class="flex flex-col md:flex-row">
                                <img src="../uploads/posters/<?= htmlspecialchars($ticket['film_poster'] ?? 'default.jpg') ?>" alt="Poster Film" class="w-full md:w-36 h-48 md:h-auto object-cover bg-gray-800">

                                <div class="p-5 flex-grow flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($ticket['film_title'] ?? 'N/A') ?></h2>
                                            <span class="text-xs font-bold py-1 px-3 rounded-full bg-green-500 text-green-900">Berhasil</span>
                                        </div>
                                        <p class="text-sm text-gray-400 mt-1">
                                            <i class="fas fa-calendar-alt fa-fw"></i> <?= date('d M Y', strtotime($ticket['show_date'])) ?> &nbsp;
                                            <i class="fas fa-clock fa-fw"></i> <?= date('H:i', strtotime($ticket['show_time'])) ?> &nbsp;
                                            <i class="fas fa-map-marker-alt fa-fw"></i> <?= htmlspecialchars($ticket['studio_name'] ?? 'N/A') ?>
                                        </p>
                                        <p class="text-sm text-gray-300 font-semibold mt-2">
                                            <i class="fas fa-couch fa-fw"></i> Kursi: <?= htmlspecialchars($ticket['seat_codes'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                    <div class="border-t border-gray-700 mt-4 pt-4 flex justify-between items-center gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500">No. Booking: #<?= $ticket['id_booking'] ?></p>
                                            <p class="font-bold text-red-500">Rp <?= number_format($ticket['total_amount'], 0, ',', '.') ?></p>
                                        </div>
                                        <div class="flex gap-2">
                                            <?php if (!$has_reviewed): ?>
                                                <button @click="showReview = !showReview; showTicket = false" class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-transform transform hover:scale-105">
                                                    <i class="fas fa-star mr-1"></i> Beri Ulasan
                                                </button>
                                            <?php else: ?>
                                                <button class="bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg text-sm cursor-not-allowed" disabled>
                                                    <i class="fas fa-check mr-1"></i> Diulas
                                                </button>
                                            <?php endif; ?>
                                            <button @click="showTicket = !showTicket; showReview = false" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-transform transform hover:scale-105">
                                                Lihat E-Tiket
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div x-show="showTicket" x-transition x-cloak class="bg-gray-800 p-6 text-center border-t border-gray-600">
                                <p class="text-sm text-gray-400 mb-2">Scan QR Code ini di pintu masuk studio</p>
                                <div class="flex justify-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=NusaTix-BOOKING-<?= $ticket['id_booking'] ?>&bgcolor=374151&color=FFFFFF&qzone=1" alt="QR Code" class="rounded-lg border border-gray-700">
                                </div>
                                <p class="text-xs text-gray-500 mt-3">Booking ID: #<?= $ticket['id_booking'] ?></p>
                            </div>

                            <div x-show="showReview" x-transition x-cloak class="bg-gray-800 p-6 border-t border-gray-600">
                                <form @submit.prevent="submitTestimonial($event)">
                                    <input type="hidden" name="action" value="create"> <input type="hidden" name="id_booking" value="<?= $ticket['id_booking'] ?>">
                                    <input type="hidden" name="id_film" value="<?= $ticket['id_film'] ?>">

                                    <div x-data="{ rating: 0, hoverRating: 0 }">
                                        <p class="text-gray-400 mb-2">Rating Anda</p>
                                        <div class="flex items-center star-rating space-x-2">
                                            <template x-for="star in 5" :key="star">
                                                <i class="fas fa-star text-3xl cursor-pointer transition-colors"
                                                   :class="(hoverRating || rating) >= star ? 'text-yellow-400' : 'text-gray-600'"
                                                   @mouseenter="hoverRating = star"
                                                   @mouseleave="hoverRating = 0"
                                                   @click="rating = star"></i>
                                            </template>
                                        </div>
                                        <input type="hidden" name="rating" x-model="rating">
                                    </div>
                                    <div class="mt-4">
                                        <label for="message-<?= $ticket['id_booking'] ?>" class="text-gray-400 mb-2 block">Ulasan Anda</label>
                                        <textarea name="message" id="message-<?= $ticket['id_booking'] ?>" rows="4" class="w-full bg-[#181111] border border-gray-600 rounded-lg p-3 text-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Bagaimana pendapat Anda tentang film ini?" required></textarea>
                                    </div>
                                    <button type="submit" class="w-full mt-4 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-5 rounded-lg">Kirim Ulasan</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <?php include __DIR__ . '/templates/footer.php'; ?>
    </div>

    <script>
    function submitTestimonial(event) {
        const form = event.target;
        const formData = new FormData(form);

        if (!formData.get('rating') || formData.get('rating') == 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops...',
                text: 'Jangan lupa berikan rating bintang!',
                background: '#211717',
                color: '#E0E0E0'
            });
            return;
        }

        fetch('../backend/api/testimonial_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    background: '#211717',
                    color: '#E0E0E0',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Muat ulang halaman untuk melihat perubahan
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: data.message,
                    background: '#211717',
                    color: '#E0E0E0'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan saat mengirim data.',
                background: '#211717',
                color: '#E0E0E0'
            });
        });
    }
    </script>
</body>
</html>