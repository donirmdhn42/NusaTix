<?php
// views/riwayat_transaksi.php
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
// Memanggil fungsi yang mengambil SEMUA riwayat booking
$bookings = Booking::getBookingHistoryByUserId($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - NusaTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-[#181111] font-sans text-gray-300">
    <div class="flex flex-col min-h-screen">
        <?php include __DIR__ . '/templates/header.php'; ?>
        <main class="flex-grow px-4 md:px-10 lg:px-20 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl md:text-4xl font-bold text-white">Riwayat Transaksi</h1>
                <a href="my_tickets.php" class="text-red-400 hover:text-red-300 transition-colors">
                    Lihat Tiket Saya <i class="fas fa-ticket-alt ml-1"></i>
                </a>
            </div>

            <div class="space-y-6">
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-16 bg-[#211717] rounded-lg border border-gray-700">
                        <p class="mt-4 text-gray-400">Anda belum pernah melakukan transaksi.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $status = $booking['status'];
                        $is_expired = false;

                        if ($status == 'booked' && (time() - strtotime($booking['booking_time'])) > 60) {
                            $status = 'expired';
                        }

                        $status_config = [
                            'paid' => ['text' => 'Berhasil', 'color' => 'bg-green-500 text-green-900'],
                            'pending' => ['text' => 'Menunggu Konfirmasi', 'color' => 'bg-yellow-500 text-yellow-900'],
                            'booked' => ['text' => 'Belum Dibayar', 'color' => 'bg-blue-500 text-blue-900'],
                            'cancelled' => ['text' => 'Dibatalkan', 'color' => 'bg-red-500 text-red-900'],
                            'expired' => ['text' => 'Kedaluwarsa', 'color' => 'bg-gray-600 text-gray-900']
                        ];

                        $current_status = $status_config[$status];
                        ?>
                        <div class="bg-[#211717] border border-gray-700 rounded-lg p-5">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-lg font-bold text-white"><?= htmlspecialchars($booking['film_title']) ?></h2>
                                        <span class="text-xs font-bold py-1 px-3 rounded-full <?= $current_status['color'] ?>"><?= $current_status['text'] ?></span>
                                    </div>
                                    <p class="text-sm text-gray-400 mt-1">
                                        Tanggal Transaksi: <?= date('d M Y', strtotime($booking['booking_time'])) ?>
                                    </p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        Waktu Transaksi: <?= date('H:i', strtotime($booking['booking_time'])) ?> WIB
                                    </p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs text-gray-500">No. Booking #<?= $booking['id_booking'] ?></p>
                                    <p class="font-bold text-lg text-red-500">Rp <?= number_format($booking['total_amount'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                            <?php if ($status == 'booked'): ?>
                                <div class="mt-4 pt-4 border-t border-gray-700 text-right">
                                    <a href="payment.php?booking_id=<?= $booking['id_booking'] ?>" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">Lanjutkan Pembayaran</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        <?php include __DIR__ . '/templates/footer.php'; ?>
    </div>
</body>

</html>