<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/models/booking.php';

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) {
    die("Booking tidak valid.");
}

$booking_details = Booking::getDetailsForUser($conn, $booking_id, $user_id);
if (!$booking_details) {
    die("Booking tidak ditemukan atau bukan milik Anda.");
}

if ($booking_details['status'] !== 'booked') {
    header("Location: riwayat_transaksi.php?info=" . $booking_details['status']);
    exit();
}

// ==> PERBAIKAN: Countdown diubah menjadi 5 menit (300 detik)
$countdown_duration = 300;

// Jika session expiry belum ada, buat baru. Jika sudah ada, gunakan yang lama.
if (!isset($_SESSION['last_booking_details']['expires_at'])) {
    $_SESSION['last_booking_details']['expires_at'] = time() + $countdown_duration;
}
$expires_at = $_SESSION['last_booking_details']['expires_at'];

$is_expired = false;
if (time() > $expires_at) {
    $is_expired = true;
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id_booking = ? AND status = 'booked'");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
}

if (!$is_expired && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $payment_method = $_POST['payment_method'] ?? null;
    $proof_file = $_FILES['proof_of_payment'] ?? null;
    if (!$payment_method || !$proof_file || $proof_file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Metode pembayaran dan bukti pembayaran wajib diisi.";
    } else {
        $upload_dir = __DIR__ . '/../uploads/proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_ext = strtolower(pathinfo($proof_file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = 'proof_' . $booking_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($proof_file['tmp_name'], $upload_path)) {
                $db_path = 'proofs/' . $new_filename;
                $submission_success = Booking::processPaymentSubmission($conn, $booking_id, $user_id, $payment_method, $booking_details['total_amount'], $db_path);
                if ($submission_success) {
                    unset($_SESSION['last_booking_details']);
                    header("Location: riwayat_transaksi.php?info=pending");
                    exit();
                } else {
                    $error_message = "Gagal mengajukan pembayaran.";
                    unlink($upload_path);
                }
            } else {
                $error_message = "Gagal memindahkan file.";
            }
        } else {
            $error_message = "Format file tidak diizinkan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pembayaran Booking #<?= $booking_id ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg viewBox='0 0 48 48' fill='%23ff0000' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z' fill='currentColor'%3E%3C/path%3E%3C/svg%3E" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#181111] font-sans text-gray-300">
    <div class="flex min-h-screen flex-col">
        <?php include __DIR__ . '/templates/header.php'; ?>
        <main class="flex-1 px-4 py-8 justify-center flex">
            <div class="w-full max-w-2xl">
                <h1 class="text-3xl font-bold text-white mb-2">Konfirmasi Pembayaran</h1>

                <?php if ($is_expired): ?>
                    <div class="text-center bg-red-900/50 border border-red-700 p-8 rounded-lg">
                        <i class="fas fa-clock fa-4x text-red-500 mb-4"></i>
                        <h2 class="text-3xl font-bold text-white">Waktu Habis!</h2>
                        <p class="text-gray-400 mt-2 mb-6">Waktu pembayaran telah berakhir. Kursi yang Anda pesan telah dilepaskan.</p>
                        <a href="index.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg">Pesan Ulang Tiket</a>
                    </div>
                <?php else: ?>
                    <div x-data="{ paymentMethod: '' }">
                        <div class="flex justify-between items-center bg-yellow-900/50 text-yellow-300 border border-yellow-700 rounded-lg p-4 mb-6">
                            <p>Selesaikan pembayaran dalam:</p>
                            <div id="countdown-timer" class="text-2xl font-bold font-mono">05:00</div>
                        </div>

                        <?php if (isset($error_message)): ?>
                            <div class="p-4 mb-4 text-sm rounded-lg bg-red-900/50 text-red-300 border border-red-500"><?= $error_message ?></div>
                        <?php endif; ?>

                        <form id="paymentForm" method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="pay">

                            <div class="bg-[#211717] border border-gray-700 p-6 rounded-lg mb-6">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Total Pembayaran</span>
                                    <span class="text-xl font-bold text-red-500">Rp <?= number_format($booking_details['total_amount'], 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <div class="bg-[#211717] border border-gray-700 p-6 rounded-lg mb-6">
                                <h3 class="font-bold text-lg text-white mb-4">1. Pilih Metode Pembayaran</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <label @click="paymentMethod = 'qris'" class="p-4 bg-[#382929] border-2 rounded-lg text-center cursor-pointer transition-colors" :class="paymentMethod === 'qris' ? 'border-red-500' : 'border-gray-700'">
                                        <input type="radio" name="payment_method" value="qris" class="hidden" required>
                                        <i class="fas fa-qrcode text-3xl mb-2"></i>
                                        <span class="block text-sm">QRIS</span>
                                    </label>
                                    <label @click="paymentMethod = 'transfer'" class="p-4 bg-[#382929] border-2 rounded-lg text-center cursor-pointer transition-colors" :class="paymentMethod === 'transfer' ? 'border-red-500' : 'border-gray-700'">
                                        <input type="radio" name="payment_method" value="transfer" class="hidden">
                                        <i class="fas fa-university text-3xl mb-2"></i>
                                        <span class="block text-sm">Transfer Bank</span>
                                    </label>
                                </div>
                            </div>

                            <div x-show="paymentMethod" x-transition x-cloak class="bg-[#211717] border border-gray-700 p-6 rounded-lg mb-6">
                                <h3 class="font-bold text-lg text-white mb-4">2. Lakukan Pembayaran</h3>
                                <div x-show="paymentMethod === 'qris'" class="text-center">
                                    <p class="text-gray-400 mb-4">Scan QR Code di bawah ini menggunakan aplikasi pembayaran Anda.</p>
                                    <img src="https://image.shutterstock.com/image-vector/sample-qr-code-260nw-1712468050.jpg" alt="QRIS Code" class="mx-auto rounded-lg border-4 border-white w-64">
                                </div>
                                <div x-show="paymentMethod === 'transfer'" class="space-y-4">
                                    <p class="text-gray-400">Silakan transfer ke salah satu rekening bank di bawah ini:</p>
                                    <div class="bg-gray-800 p-4 rounded-lg">
                                        <p class="text-sm font-semibold">Bank BCA</p>
                                        <p class="text-xl font-mono text-white tracking-widest">123 456 7890</p>
                                        <p class="text-xs text-gray-400 mt-1">a.n. PT NusaTix Indonesia</p>
                                    </div>
                                    <div class="bg-gray-800 p-4 rounded-lg">
                                        <p class="text-sm font-semibold">Bank Mandiri</p>
                                        <p class="text-xl font-mono text-white tracking-widest">098 765 4321</p>
                                        <p class="text-xs text-gray-400 mt-1">a.n. PT NusaTix Indonesia</p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="paymentMethod" x-transition x-cloak class="bg-[#211717] border border-gray-700 p-6 rounded-lg mb-6">
                                <h3 class="font-bold text-lg text-white mb-2">3. Unggah Bukti Pembayaran</h3>
                                <p class="text-sm text-gray-400 mb-4">Setelah membayar, unggah screenshot atau foto bukti transfer Anda di sini.</p>
                                <input type="file" name="proof_of_payment" id="proof_of_payment" required class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-red-600 file:text-white hover:file:bg-red-700">
                            </div>

                            <button id="submitBtn" type="submit" class="w-full py-3 bg-green-600 text-white text-lg font-bold rounded-lg hover:bg-green-700 transition-all disabled:bg-gray-600 disabled:cursor-not-allowed" :disabled="!paymentMethod">
                                Kirim Bukti Pembayaran
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <?php include __DIR__ . '/templates/footer.php'; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timerDisplay = document.getElementById('countdown-timer');
            if (timerDisplay) {
                const expiryTimestamp = <?= $expires_at * 1000 ?>;
                const interval = setInterval(() => {
                    const now = new Date().getTime();
                    const distance = expiryTimestamp - now;
                    if (distance < 0) {
                        clearInterval(interval);
                        window.location.reload();
                        return;
                    }
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timerDisplay.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }, 1000);
            }
        });
    </script>
</body>

</html>